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

declare(strict_types = 1);

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * This is the actual merge process
 */
class CRM_Xdedupe_Merge {

  protected $resolvers = [];

  protected $required_contact_attributes = NULL;

  protected $force_merge = FALSE;

  protected $stats = [];

  protected $merge_log = NULL;

  protected $merge_details = [];

  protected $merge_log_handle = NULL;

  protected $_contact_cache = [];

  public function __construct(array $params) {
    // initialise stats
    $this->stats = [
      'tuples_merged' => 0,
      'contacts_merged' => 0,
      'conflicts_resolved' => 0,
      'aborted' => 0,
      'errors' => [],
      'failed' => [],
    ];

    // get resolvers and the required attributes
    $this->resolvers = [];
    $required_contact_attributes = ['is_deleted', 'contact_type'];
    $resolver_classes = $params['resolvers'] ?? '';
    if (is_string($resolver_classes)) {
      $resolver_classes = explode(',', $resolver_classes);
    }
    foreach ($resolver_classes as $resolver_class) {
      $resolver_class = trim($resolver_class);
      if ($resolver_class === '') {
        continue;
      }
      $resolver = CRM_Xdedupe_Resolver::getResolverInstance($resolver_class, $this);
      if ($resolver) {
        $this->resolvers[] = $resolver;
        $required_contact_attributes = array_merge(
          $required_contact_attributes,
          $resolver->getContactAttributes()
        );
      }
      else {
        $this->logError("Resolver class '{$resolver_class}' not found!");
      }
    }
    $this->required_contact_attributes = implode(',', $required_contact_attributes);

    // set force merge
    $this->force_merge = (bool) ($params['force_merge'] ?? FALSE);

    // initialise merge_log
    if (($params['merge_log'] ?? '') !== '') {
      $this->merge_log = $params['merge_log'];
      $this->merge_log_handle = fopen($this->merge_log, 'a');
    }
    else {
      $this->merge_log = NULL;
      $this->merge_log_handle = NULL;
    }

    $this->log('Initialised merger: ' . json_encode($params));
  }

  public function __destruct() {
    if ($this->merge_log_handle) {
      fclose($this->merge_log_handle);
    }
  }

  /**
   * Get the stats from all the merges performed by this object
   *
   * @param bool $summary
   *   don't return 'failed' and 'errors' as list, but as aggregation
   *
   * @return array stats
   *   stats
   */
  public function getStats(bool $summary = FALSE): array {
    if ($summary) {
      // flatten error / failure lists
      $stats = $this->stats;
      $stats['failed'] = count($this->stats['failed']);

      $error_counts = [];
      foreach ($stats['errors'] as $error_message) {
        $error_counts[$error_message] = ($error_counts[$error_message] ?? 0) + 1;
      }
      $stats['errors'] = $error_counts;
      return $stats;
    }

    return $this->stats;
  }

  /**
   * Mark the merge process as aborted, pro
   *
   * @param string $reason
   *   abortion reason
   */
  public function setAborted($reason): void {
    $this->stats['aborted'] = $reason;
  }

  /**
   * Log a general merge message to the merge log
   *
   * @param string $message message
   */
  public function log(string $message): void {
    if ($this->merge_log_handle === NULL) {
      Civi::log()->error("XMERGE: $message");
    }
    else {
      $message = date('[Y-m-d H:i:s] ') . $message . "\n";
      fwrite($this->merge_log_handle, $message);
    }
  }

  /**
   * Log an error message to the merge log, and the internal error counter
   *
   * @param $message string message
   */
  public function logError(string $message): void {
    $this->stats['errors'][] = $message;
    $this->log('ERROR: ' . $message);
  }

  /**
   * @param int $main_contact_id int   main contact ID
   * @param list<int> $other_contact_ids other contact IDs
   */
  public function multiMerge(int $main_contact_id, array $other_contact_ids): void {
    $this->log("Merging into contact [$main_contact_id]: [" . implode(',', $other_contact_ids) . ']');

    // first check for poor judgement:
    if (in_array($main_contact_id, $other_contact_ids, FALSE)) {
      throw new Exception('Cannot merge contact(s) with itself!');
    }

    // do some more verification here
    $contact_ids = $other_contact_ids;
    $contact_ids[] = $main_contact_id;
    $this->loadContacts($contact_ids);
    $main_contact = $this->getContact($main_contact_id);
    if ((int) ($main_contact['is_deleted'] ?? 0) !== 0) {
      $this->logError("Main contact [{$main_contact_id}] is deleted. This is wrong!");
      return;
    }

    // TODO: run multi-resolvers? problem is, that it might resolve contacts that then don't get merged after all...
    //
    //    foreach ($this->resolvers as $resolver) {
    //      $changes = $resolver->resolve($main_contact_id, $other_contact_ids);
    //      if ($changes) {
    //        $this->stats['conflicts_resolved'] += 1;
    //        $this->unloadContact($main_contact_id);
    //      }
    //    }

    // now simply merge all contacts individually:
    $merge_succeeded = TRUE;
    foreach ($other_contact_ids as $other_contact_id) {
      $merge_succeeded &= $this->merge($main_contact_id, (int) $other_contact_id, TRUE);
    }
    if ($merge_succeeded) {
      $this->stats['tuples_merged'] += 1;
    }
  }

  /**
   * Merge the other contact into the main contact, using
   *  CiviCRM's merge function. Before, though, the
   *  resovlers ar applied.
   *
   * @param int $main_contact_id int main contact ID
   * @param int $other_contact_id int other contact ID
   * @param bool $part_of_tuple boolean is this pair part of an x-tuple?
   *
   * @return bool merge succeeded?
   * @throws \CRM_Core_Exception
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh,Generic.Metrics.NestingLevel.TooHigh
  public function merge(int $main_contact_id, int $other_contact_id, bool $part_of_tuple = FALSE): bool {
    // phpcs:enable
    if ($main_contact_id === $other_contact_id) {
      // nothing to do here
      return FALSE;
    }

    // isolate merge in log tables
    $this->resetLogId();

    // prepare logs + co
    $this->resetMergeDetails();

    // first: verify that the contacts are "fit" for merging
    $this->loadContacts([$main_contact_id, $other_contact_id]);
    $main_contact = $this->getContact($main_contact_id);
    if ((int) ($main_contact['is_deleted'] ?? 0) !== 0) {
      $this->logError("Main contact [{$main_contact_id}] is deleted. This is wrong!");
      return FALSE;
    }
    $other_contact = $this->getContact($other_contact_id);
    if ((int) ($other_contact['is_deleted'] ?? 0) !== 0) {
      $this->logError("Other contact [{$other_contact_id}] is deleted. This is wrong!");
      return FALSE;
    }

    $merge_succeeded = FALSE;
    $transaction = new CRM_Core_Transaction();
    try {
      // then: run resolvers
      /** @var $resolver CRM_Xdedupe_Resolver */
      foreach ($this->resolvers as $resolver) {
        $changes = $resolver->resolve($main_contact_id, [$other_contact_id]);
        if ($changes) {
          $this->stats['conflicts_resolved'] += 1;
        }
      }

      // now: run the merge
      $result = civicrm_api3(
        'Contact',
        'merge',
        [
          'to_keep_id' => $main_contact_id,
          'to_remove_id' => $other_contact_id,
          'mode' => ($this->force_merge ? '' : 'safe'),
        ]
      );

      if (count($result['values']['skipped'])) {
        // merge didn't work
        $transaction->rollback();
        $this->stats['failed'][] = [$main_contact_id, $other_contact_id];
        // get conflicts
        $conflicts = [];
        if (version_compare(CRM_Utils_System::version(), '5.18.0', '>=')) {
          $conflicts = civicrm_api3(
            'Contact',
            'get_merge_conflicts',
            [
              'to_keep_id' => $main_contact_id,
              'to_remove_id' => $other_contact_id,
            ]
          );
          foreach ($conflicts['values'] as $merge_mode => $conflict_data) {
            if (($conflict_data['conflicts'] ?? []) !== []) {
              foreach ($conflict_data['conflicts'] as $entity => $entity_conflicts) {
                foreach ($entity_conflicts as $field_name => $field_conflict) {
                  if ($entity == 'contact') {
                    $this->stats['errors'][] = $field_conflict['title'];
                  }
                  else {
                    $this->stats['errors'][] = "{$field_conflict['title']} ({$entity})";
                  }
                }
              }
            }
          }
        }
        else {
          $this->stats['errors'][] = E::ts('unknown');
        }
      }
      elseif (count($result['values']['merged'])) {
        // MERGE SUCCESSFUL!
        try {
          // finally: run postProcessors
          foreach ($this->resolvers as $resolver) {
            $resolver->postProcess($main_contact_id, [$other_contact_id]);
          }
        }
        catch (Exception $ex) {
          // @ignoreException
          // something's wrong
          $transaction->rollback();
          $this->stats['errors'][] = 'Postprocessing Error: ' . $ex->getMessage();
          $this->stats['failed'][] = [$main_contact_id, $other_contact_id];
          return FALSE;
        }

        // ALL IS GOOD NOW!
        $merge_succeeded = TRUE;
        // merge worked
        $transaction->commit();
        $this->stats['contacts_merged'] += 1;
        if (!$part_of_tuple) {
          $this->stats['tuples_merged'] += 1;
        }

        // store merge details
        $success = $this->updateMergeActivity($main_contact_id);
        if (!$success) {
          $this->createMergeDetailNote(
            $main_contact_id,
            E::ts('Merged contact [%1] into [%2]', [1 => $other_contact_id, 2 => $main_contact_id])
          );
        }
      }
      else {
        // this is weird
        $transaction->rollback();
        $this->stats['errors'][] = E::ts('Merge API Error');
        $this->stats['failed'][] = [$main_contact_id, $other_contact_id];
      }
    }
    catch (Exception $ex) {
      // @ignoreException
      // something's wrong
      $transaction->rollback();
      $this->stats['errors'][] = $ex->getMessage();
      $this->stats['failed'][] = [$main_contact_id, $other_contact_id];
    }

    // finally: update the stats
    $this->unloadContact($main_contact_id);
    $this->unloadContact($other_contact_id);

    return $merge_succeeded;
  }

  /**
   * Load the given contact IDs into the internal contact cache
   *
   * @param list<int> $contact_ids list of contact IDs
   *
   * @return list<int> list of contact IDs that have been loaded into cache, the other ones were already in there
   * @throws \CRM_Core_Exception
   */
  public function loadContacts(array $contact_ids) {
    // first: check which ones are already there
    $contact_ids_to_load = [];
    foreach ($contact_ids as $contact_id) {
      if (!isset($this->_contact_cache[$contact_id])) {
        $contact_ids_to_load[] = $contact_id;
      }
    }

    // load remaining contacts
    if ($contact_ids_to_load !== []) {
      $query = civicrm_api3(
        'Contact',
        'get',
        [
          'id' => ['IN' => $contact_ids_to_load],
          'option.limit' => 0,
          'return' => $this->required_contact_attributes,
          'sequential' => 0,
        ]
      );
      foreach ($query['values'] as $contact) {
        $this->_contact_cache[(int) $contact['id']] = $contact;
      }
    }

    return $contact_ids_to_load;
  }

  /**
   * Remove the given contact ID from cache, e.g. when we know it's changed
   *
   * @param int $contact_id
   *
   * @return void
   */
  public function unloadContact(int $contact_id): void {
    unset($this->_contact_cache[$contact_id]);
  }

  /**
   * Get the single contact. If it's not cached, load it first
   *
   * @param int $contact_id contact ID to load
   *
   * @return array contact data
   */
  public function getContact(int $contact_id): array {
    if (!isset($this->_contact_cache[$contact_id])) {
      $this->loadContacts([$contact_id]);
    }
    return $this->_contact_cache[$contact_id];
  }

  /**
   * Reset the merge detail stack
   */
  public function resetMergeDetails(): void {
    $this->merge_details = [];
  }

  /**
   * Add a merge detail (detailed merge changes)
   *
   * @param $information string info
   */
  public function addMergeDetail(string $information): void {
    $this->merge_details[] = $information;
  }

  /**
   * The last activity
   *
   * @param int $main_contact_id
   *
   * @return list<string> merge details
   */
  public function getMergeDetails(int $main_contact_id): array {
    return $this->merge_details;
  }

  /**
   * Get the ID of the last merge activity.
   *
   * @param int $contact_id contact ID
   *
   * @return int|NULL activity id
   */
  public function getLastMergeActivityID(int $contact_id) {
    $merge_activity_type_id = (int) CRM_Xdedupe_Config::getMergeActivityTypeID();
    if (!$merge_activity_type_id || !$contact_id) {
      return NULL;
    }

    // find activity
    return (int) CRM_Core_DAO::singleValueQuery(
      "
            SELECT activity.id AS activity_id
            FROM civicrm_activity activity
            LEFT JOIN civicrm_activity_contact ac ON ac.activity_id = activity.id
            WHERE ac.contact_id = {$contact_id}
              AND ac.record_type_id = 3
              AND activity.activity_type_id = {$merge_activity_type_id}
              -- AND activity.activity_date_time BETWEEN (NOW() - INTERVAL 10 SECOND) AND (NOW() + INTERVAL 10 SECOND)
            ORDER BY activity.id DESC
            LIMIT 1;"
    );
  }

  /**
   * Copy the merge note into the details of the merge activity
   *
   * @param int $main_contact_id
   *
   * @return bool if successfull
   */
  public function updateMergeActivity(int $main_contact_id): bool {
    $merge_details = $this->getMergeDetails($main_contact_id);
    if ($merge_details !== []) {
      $activity_id = $this->getLastMergeActivityID($main_contact_id);
      if (!$activity_id) {
        // not found
        return FALSE;
      }

      // update activity
      civicrm_api3(
        'Activity',
        'create',
        [
          'id' => $activity_id,
          'details' => implode('<br/>', $merge_details),
        ]
      );
    }
    return TRUE;
  }

  /**
   * Create a new not with the contact adding the merge details
   *
   * @param $contact_id int    contact ID the merge detail should be recorded
   * @param $subject    string the subject line
   */
  public function createMergeDetailNote($contact_id, $subject = 'Merge Details'): void {
    $merge_details = $this->getMergeDetails($contact_id);
    if ($merge_details !== []) {
      civicrm_api3(
        'Note',
        'create',
        [
          'entity_id' => $contact_id,
          'entity_table' => 'civicrm_contact',
          'note' => implode("\n", $merge_details),
          'subject' => $subject,
        ]
      );
    }
  }

  /**
   * Regenerate @uniqueID which is used for log_conn_id in log tables
   */
  private function resetLogId(): void {
    CRM_Core_DAO::executeQuery(
      'SET @uniqueID = %1',
      [
        1 => [
          uniqid() . CRM_Utils_String::createRandom(4, CRM_Utils_String::ALPHANUMERIC),
          'String',
        ],
      ]
    );
  }

}
