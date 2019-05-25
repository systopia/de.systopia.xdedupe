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
    $finders = ['' => E::ts('None')] + CRM_Xdedupe_Finder::getFinderList();

    // add finder criteria
    $this->add(
      'select',
      'finder_1',
      E::ts("Main Criteria"),
        $finders,
      TRUE,
      ['class' => 'huge']
    );

    $this->add(
        'select',
        'finder_2',
        E::ts("Secondary Criteria"),
        $finders,
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'finder_3',
        E::ts("Tertiary Criteria"),
        $finders,
        FALSE,
        ['class' => 'huge']
    );

    // add filter elements
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
        'contact_group',
        E::ts("Group"),
        $this->getGroups(),
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'contact_tag',
        E::ts("Tag"),
        $this->getTags(),
        FALSE,
        ['class' => 'huge']
    );

    $filters =  ['' => E::ts('None')] + CRM_Xdedupe_Filter::getFilterList();
    $this->add(
        'select',
        'filter_1',
        E::ts("Match Condition 1"),
        $filters,
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'filter_2',
        E::ts("Match Condition 2"),
        $filters,
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'filter_3',
        E::ts("Match Condition 3"),
        $filters,
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
        'main_contact',
        E::ts("Main Contact"),
        CRM_Xdedupe_Picker::getPickerList(),
        FALSE,
        ['class' => 'huge']
    );


    $this->add(
        'select',
        'auto_resolve',
        E::ts("Auto Resolve"),
        CRM_Xdedupe_Resolver::getResolverList(),
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
      // re-compile runner
      $this->dedupe_run->clear();

      // add finders
      foreach (range(1,3) as $index) {
        if (!empty($values["finder_{$index}"])) {
          $this->dedupe_run->addFinder($values["finder_{$index}"], $values);
        }
      }

      // add filters
      if (!empty($values['contact_group'])) {
        $this->dedupe_run->addFilter('CRM_Xdedupe_Filter_Group', ['group_id' => $values['contact_group']]);
      }
      if (!empty($values['contact_tag'])) {
        $this->dedupe_run->addFilter('CRM_Xdedupe_Filter_Tag', ['tag_id' => $values['contact_tag']]);
      }
      foreach (range(1,3) as $index) {
        if (!empty($values["filter_{$index}"])) {
          $this->dedupe_run->addFilter($values["filter_{$index}"], $values);
        }
      }

      // finally: run agains
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
  protected function getContactTypeOptions() {
    // todo: dynamic?
    return [
        ''             => E::ts("any"),
        'Individual'   => E::ts("Individual"),
        'Organization' => E::ts("Organization"),
        'Household'    => E::ts("Household")
    ];
  }


  /**
   * Get the list of all (active) groups
   */
  protected function getGroups() {
    $group_list = ['' => E::ts("None")];
    $groups = civicrm_api3('Group', 'get', [
        'is_active'    => 1,
        'option.limit' => 0,
        'return'       => 'id,name'
    ]);
    foreach ($groups['values'] as $group) {
      $group_list[$group['id']] = $group['name'];
    }
    return $group_list;
  }

  /**
   * Get the list of all active contact tags
   */
  protected function getTags() {
    $tag_list = ['' => E::ts("None")];
    $tags = civicrm_api3('Tag', 'get', [
        'entity_table' => 'civicrm_contact',
        'is_active'    => 1,
        'option.limit' => 0,
        'return'       => 'id,name'
    ]);
    foreach ($tags['values'] as $tag) {
      $tag_list[$tag['id']] = $tag['name'];
    }
    return $tag_list;
  }

}
