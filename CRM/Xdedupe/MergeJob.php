<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

define('XDEDUPE_BATCH_SIZE', 10);

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Queue Item for batch merges
 */
class CRM_Xdedupe_MergeJob {

  public $title             = NULL;
  protected $mode           = NULL;
  protected $params         = NULL;
  protected $dedupe_run_id  = NULL;
  protected $tuples         = NULL;


  /**
   * Use CRM_Queue_Runner to run a merge on all entries
   *
   * This doesn't return, but redirects to the runner
   *
   * @param $dedupe_run_id string dedupe run
   * @param $params        array  parameters for API Xdedupe.merge
   * @param $onEndUrl      string URL to return to after the runner is finished. Default is the control room
   */
  public static function launchMergeRunner($dedupe_run_id, $params, $onEndUrl = null) {
    // create a queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'type'  => 'Sql',
      'name'  => 'dedupe_merge',
      'reset' => TRUE,
    ));

    // fill queue
    unset($params['dedupe_run']);
    $pickers = CRM_Xdedupe_Picker::getPickerInstances($params['pickers']);
    $dedupe_run = new CRM_Xdedupe_DedupeRun($dedupe_run_id);
    $count = $dedupe_run->getTupleCount();
    $offset = 0;
    while ($offset < $count) {
      $tuples = $dedupe_run->getTuples(XDEDUPE_BATCH_SIZE, $offset, $pickers);
      $queue->createItem(new CRM_Xdedupe_MergeJob('merge', $dedupe_run_id, $params, $offset, $tuples));
      $offset += XDEDUPE_BATCH_SIZE;
    }

    // add summary task
    $queue->createItem(new CRM_Xdedupe_MergeJob('summary', $dedupe_run_id, $params));

    // create a runner and launch it
    $runner = new CRM_Queue_Runner([
      'title'     => E::ts("Merging %1 tuples.", [1 => $count]),
      'queue'     => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl'  => $onEndUrl ? $onEndUrl : CRM_Utils_System::url('civicrm/xdedupe/controlroom', 'reset=1'),
    ]);
    $runner->runAllViaWeb(); // does not return
  }


  /**
   * Merge job packed
   *
   * @param string $mode            either 'merge' or 'summary'
   * @param string $dedupe_run_id   either 'merge' or 'summary'
   * @param array $params           parameters for Xdedupe.merge API call
   * @param int $offset             offset within the dedupe run
   * @param array $tuples           tuples [main_contact_id => other contact IDs]
   */
  protected function __construct($mode, $dedupe_run_id, $params, $offset = NULL, $tuples = NULL) {
    $this->mode          = $mode;
    $this->params        = $params;
    $this->dedupe_run_id = $dedupe_run_id;
    $this->tuples        = $tuples;

    // set title
    switch ($this->mode) {
      case 'merge':
        $this->title = E::ts("Merged tuples %1 - %2", [
            1 => ($offset + 1),
            2 => ($offset + 1 + XDEDUPE_BATCH_SIZE)
        ]);
        break;

      case 'summary':
        $this->title = E::ts("Summarising");
        break;

      default:
        $this->title = "Unknown";
    }
  }

  /**
   * Run the taks
   *
   * @param $context    CRM_Queue_TaskContext
   * @return bool       success
   * @throws Exception  shouldn't...
   */
  public function run($context) {
    switch ($this->mode) {
      case 'merge':
        // this one needs a lock
        $dedupe_run = new CRM_Xdedupe_DedupeRun($this->dedupe_run_id);
        $merger = new CRM_Xdedupe_Merge($this->params);
        foreach ($this->tuples as $main_contact_id => $other_contact_ids) {
          // call the
          $merged_before = $merger->getStats()['contacts_merged'];
          $merger->multiMerge($main_contact_id, $other_contact_ids);
          $tuples_merged = $merger->getStats()['contacts_merged'] - $merged_before;
          $dedupe_run->setContactsMerged($main_contact_id, $tuples_merged);
        }
        break;

      case 'summary':
        $dedupe_run      = new CRM_Xdedupe_DedupeRun($this->dedupe_run_id);
        $table_name      = $dedupe_run->getTableName();
        $tuple_count     = $dedupe_run->getTupleCount();
        $tuples_merged   = CRM_Core_DAO::singleValueQuery("SELECT COUNT(merged_count) FROM `{$table_name}` WHERE merged_count > 0;");
        $contact_count   = $dedupe_run->getContactCount() - $tuple_count; // don't count the main contacts
        $contacts_merged = CRM_Core_DAO::singleValueQuery("SELECT SUM(merged_count) FROM `{$table_name}`;");
        CRM_Core_Session::setStatus(E::ts("Merged %1 of %2 tuples, %3 of %4 contacts.", [
            1 => $tuples_merged,
            2 => $tuple_count,
            3 => $contacts_merged,
            4 => $contact_count
        ]), E::ts("Merge Finished"), 'alert');

        // update stats
        if (!empty($this->params['config_id'])) {
          $configuration = CRM_Xdedupe_Configuration::get($this->params['config_id']);
          $stats = $configuration->getStats();
          $stats['tuples_merged'] = (int) $tuples_merged;
          $stats['contacts_merged'] = (int) $contacts_merged;
          $stats['aborted'] = 0;
          $stats['errors'] = []; // todo: get? from where?
          $stats['failed'] = []; // todo: get? from where?
          $stats['merger_runtime'] = strtotime('now') - strtotime($stats['last_run']);
          $configuration->setStats($stats, true);
        }

        // clear table
        $dedupe_run->clear();
        break;

      default:
        return FALSE;
    }

    return TRUE;
  }
}


