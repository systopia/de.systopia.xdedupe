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

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Main command&control form to trigger automatic merge processes.
 */
class CRM_Xdedupe_Form_ControlRoom extends CRM_Core_Form {

  /**
   * @var CRM_Xdedupe_DedupeRun the current dedupe session
   */
  protected $dedupe_run = NULL;
  protected $cr_command = NULL;

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts("Extendend Dedupe - Control Room"));

    // find/create run
    $dedupe_run = CRM_Utils_Request::retrieve('dedupe_run', 'String');
    $this->dedupe_run = new CRM_Xdedupe_DedupeRun($dedupe_run);

    // add finder criteria
    $this->add('hidden', 'dedupe_run', $this->dedupe_run->getID());

    // add finder criteria
    $this->add(
        'select',
        'contact_type',
        E::ts("Contact Type"),
        $this->getContactTypeOptions(),
        TRUE,
        ['class' => 'huge']
    );

    $this->add(
      'select',
      'finder_1',
      E::ts("Main Criteria"),
      $this->getFinderOptions(),
      TRUE,
      ['class' => 'huge']
    );

    $this->add(
        'select',
        'finder_2',
        E::ts("Secondary Criteria"),
        $this->getFinderOptions(),
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'finder_3',
        E::ts("Tertiary Criteria"),
        $this->getFinderOptions(),
        FALSE,
        ['class' => 'huge']
    );

    // add filter elements
    $this->add(
        'select',
        'filter_1',
        E::ts("Match Condition 1"),
        $this->getFilterOptions(),
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'filter_2',
        E::ts("Match Condition 2"),
        $this->getFilterOptions(),
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'filter_3',
        E::ts("Match Condition 3"),
        $this->getFilterOptions(),
        FALSE,
        ['class' => 'huge']
    );

    // add merge options
    $this->add(
        'checkbox',
        'force_merge',
        E::ts("Force Merge")
    );

    $this->add(
        'select',
        'auto_resolve',
        E::ts("Auto Resolve"),
        $this->getFinderOptions(),
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple']
    );



    $this->addButtons(array(
      array(
        'type' => 'find',
        'name' => E::ts('Find'),
        'isDefault' => TRUE,
      ),
      array(
          'type' => 'merge',
          'name' => E::ts('Merge'),
          'isDefault' => FALSE,
      ),
    ));

    // add some stats
    $this->addStats();

    // let's add some style...
    CRM_Core_Resources::singleton()->addStyleFile('de.systopia.xdedupe', 'css/xdedupe.css');

    // export form elements
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    if ($this->cr_command == 'find') {
      $this->dedupe_run->clear();
      $this->dedupe_run->addFinder('CRM_Xdedupe_Finder_Email', $values);
      $this->dedupe_run->find($values);
    }

    $this->addStats();
    parent::postProcess();
  }

  /**
   * Re-route our commands to submit
   */
  public function handle($command) {
    if ($command == 'find' || $command == 'merge') {
      $this->cr_command = $command;
      $command = 'submit';
    }
    return parent::handle($command);
  }

  /**
   * Pass the current statistics to the form
   */
  public function addStats() {
    $this->assign("result_count",  $this->dedupe_run->getTupleCount());
    $this->assign("contact_count", $this->dedupe_run->getContactCount());
  }

  /**
   * Get a list of filter options
   */
  protected function getFilterOptions() {
    // TODO: implement
    return [
        ''     => E::ts("None"),
        'TODO' => E::ts("Some Filter")
    ];
  }

  /**
   * Get a list of filter options
   */
  protected function getFinderOptions() {
    // TODO: dynamic implementation
    return [
        ''                         => E::ts("None"),
        'CRM_Xdedupe_Finder_Email' => E::ts("Identical EMail"),
    ];
  }

  /**
   * Get a list of filter options
   */
  protected function getContactTypeOptions() {
    return [
        ''             => E::ts("any"),
        'Individual'   => E::ts("Individual"),
        'Organization' => E::ts("Organization"),
        'Household'    => E::ts("Household")
    ];
  }

}
