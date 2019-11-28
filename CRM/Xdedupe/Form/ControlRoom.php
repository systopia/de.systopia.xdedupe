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
class CRM_Xdedupe_Form_ControlRoom extends CRM_Core_Form
{

  const TUPLES_PER_PAGE = 50;
  const PICKER_COUNT = 10;
  private static $null = NULL;

  /**
   * @var CRM_Xdedupe_DedupeRun the current dedupe session
   */
  protected $dedupe_run = NULL;
  protected $cr_command = NULL;
  protected $offset = 0;

  public function buildQuickForm()
  {
    CRM_Utils_System::setTitle(E::ts("Extendend Dedupe - Control Room"));

    // find/create run
    $dedupe_run       = CRM_Utils_Request::retrieve('dedupe_run', 'String');
    $this->offset     = CRM_Utils_Request::retrieve('paging_offset', 'Integer', self::$null, FALSE, 0);
    $this->dedupe_run = new CRM_Xdedupe_DedupeRun($dedupe_run);
    $this->dedupe_run->cleanupDB();

    // if this is a new form, set the most recently used configuration
    if (!$dedupe_run) {
      $last_configuration = self::getUserSettings()->get('xdedup_last_configuration');
      if ($last_configuration) {
        foreach (['qfKey', 'entryURL', '_qf_default', '_qf_ControlRoom_find'] as $strip_attribute) {
          unset($last_configuration[$strip_attribute]);
        }
        // split the pickers
        $main_contact = CRM_Utils_Array::value('main_contact', $last_configuration, []);
        foreach (range(1, self::PICKER_COUNT) as $i) {
          $last_configuration["main_contact_{$i}"] = CRM_Utils_Array::value($i - 1, $main_contact, '');
        }
        $this->setDefaults($last_configuration);
      }
    }

    // add field for run ID
    $this->add('hidden', 'dedupe_run', $this->dedupe_run->getID());
    $this->add('hidden', 'paging_offset', $this->offset);

    // add finder criteria
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

    $this->add(
        'select',
        'finder_4',
        E::ts("Quaternary Criteria"),
        $finders,
        FALSE,
        ['class' => 'huge']
    );

    $this->add(
        'select',
        'finder_5',
        E::ts("Quinary Criteria"),
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
        FALSE,
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
        'contact_group_exclude',
        E::ts("Exclude Group"),
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

    $this->add(
        'select',
        'filters',
        E::ts("More Filters"),
        CRM_Xdedupe_Filter::getFilterList(),
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple']
    );

    // add merge options
    $this->add(
        'checkbox',
        'force_merge',
        E::ts("Force Merge")
    );

    $picker_list       = ['' => E::ts('- none -')] + CRM_Xdedupe_Picker::getPickerList();
    $picker_field_list = [];
    foreach (range(1, self::PICKER_COUNT) as $i) {
      $picker_field_list[] = "main_contact_{$i}";
      $this->add(
          'select',
          "main_contact_{$i}",
          E::ts("Main Contact"),
          $picker_list,
          FALSE
      );
    }
    $this->assign('picker_fields', $picker_field_list);

    $this->add(
        'select',
        'auto_resolve',
        E::ts("Auto Resolve"),
        CRM_Xdedupe_Resolver::getResolverList(),
        FALSE,
        ['class' => 'huge crm-select2', 'multiple' => 'multiple']
    );

    // build buttons
    $buttons = [
        [
            'type'      => 'find',
            'name'      => E::ts('Find'),
            'icon'      => 'fa-search',
            'isDefault' => TRUE,
        ],
        [
            'type'      => 'merge',
            'name'      => E::ts('Merge All'),
            'icon'      => 'fa-compress',
            'isDefault' => FALSE,
        ]
    ];
    $this->addButtons($buttons);

    // let's add some style...
    CRM_Core_Resources::singleton()->addStyleFile('de.systopia.xdedupe', 'css/xdedupe.css');

    // add the JS logic
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/controlroom.js');
    CRM_Core_Resources::singleton()->addVars('xdedupe_controlroom', [
        'xdedupe_data_url'  => CRM_Utils_System::url("civicrm/ajax/xdedupetuples", "dedupe_run={$dedupe_run}"),
        'exclude_tuple_url' => CRM_Utils_System::url("civicrm/ajax/rest", "className=CRM_Contact_Page_AJAX&fnName=processDupes"),
        'dedupe_run_id'     => $dedupe_run,
    ]);

    parent::buildQuickForm();
  }

  public function postProcess()
  {
    $values = $this->exportValues();

    // store clean values + store last configuration
    foreach (['qfKey', 'entryURL', '_qf_default', '_qf_ControlRoom_find'] as $strip_attribute) {
      unset($values[$strip_attribute]);
    }

    // join picker fields
    $values['main_contact'] = [];
    foreach (range(1, self::PICKER_COUNT) as $i) {
      $picker = CRM_Utils_Array::value("main_contact_{$i}", $values);
      if ($picker) {
        $values['main_contact'][] = $picker;
      }
      unset($values["main_contact_{$i}"]);
    }

    // store settings by user
    self::getUserSettings()->set('xdedup_last_configuration', $values);

    if ($this->cr_command == 'find') {
      // re-compile runner
      $this->dedupe_run->clear();

      // add finders
      foreach (range(1, 5) as $index) {
        if (!empty($values["finder_{$index}"])) {
          $this->dedupe_run->addFinder($values["finder_{$index}"], $values);
        }
      }

      // add filters
      if (!empty($values['contact_group'])) {
        $this->dedupe_run->addFilter('CRM_Xdedupe_Filter_Group', ['group_id' => $values['contact_group']]);
      }
      if (!empty($values['contact_group_exclude'])) {
        $this->dedupe_run->addFilter('CRM_Xdedupe_Filter_Group', ['group_id' => $values['contact_group_exclude'], 'exclude' => true]);
      }
      if (!empty($values['contact_tag'])) {
        $this->dedupe_run->addFilter('CRM_Xdedupe_Filter_Tag', ['tag_id' => $values['contact_tag']]);
      }
      foreach ($values['filters'] as $filter) {
        $this->dedupe_run->addFilter($filter, $values);
      }

      // finally: run again
      $this->dedupe_run->find($values);

    } elseif ($this->cr_command == 'merge') {
      // call merge runner
      CRM_Xdedupe_MergeJob::launchMergeRunner($this->dedupe_run->getID(), [
          'force_merge' => empty($values['force_merge']) ? '0' : '1',
          'resolvers'   => $values['auto_resolve'],
          'pickers'     => $values['main_contact'],
      ]);
    }

    $this->assign('result_count', $this->dedupe_run->getTupleCount());
    $this->assign('contact_count', $this->dedupe_run->getContactCount());

    parent::postProcess();
  }

  /**
   * Re-route our commands to submit
   */
  public function handle($command)
  {
    if (in_array($command, ['find', 'merge', 'nextpage', 'prevpage'])) {
      $this->cr_command = $command;
      $command          = 'submit';
    }
    return parent::handle($command);
  }

  /**
   * AJAX call to get the data for tuple data
   */
  public static function getTupleRowsAJAX()
  {
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams(['dedupe_run' => 'String', 'pickers' => 'String']);
    // CRM_Core_Error::debug_log_message("params : " . json_encode($params));

    $dedupe_run = new CRM_Xdedupe_DedupeRun($params['dedupe_run']);
    $pickers    = CRM_Xdedupe_Picker::getPickerInstances(explode(',', $params['pickers']));
    $tuples     = $dedupe_run->getTuples($params['rp'], $params['offset'], $pickers);

    // load all these contacts
    $records = [];
    if (!empty($tuples)) {
      $all_contact_ids = [];
      foreach ($tuples as $main_contact_id => $contact_ids) {
        $all_contact_ids[] = $main_contact_id;
        foreach ($contact_ids as $contact_id) {
          $all_contact_ids[] = $contact_id;
        }
      }
      $all_contacts = civicrm_api3('Contact', 'get', [
          'id'           => ['IN' => $all_contact_ids],
          'sequential'   => 0,
          'option.limit' => 0,
          'return'       => 'contact_type,contact_sub_type,display_name,id'
      ])['values'];

      // compile rows
      foreach ($tuples as $main_contact_id => $contact_ids) {
        $record = [];

        // render main contact
        $contact                = $all_contacts[$main_contact_id];
        $image                  = CRM_Contact_BAO_Contact_Utils::getImage(empty($contact['contact_sub_type']) ? $contact['contact_type'] : $contact['contact_sub_type'], FALSE, $contact['id']);
        $url                    = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $contact['id']);
        $record['main_contact'] = "{$image} <a target=\"_blank\" href=\"{$url}\">{$contact['display_name']}</a>";

        // render other contacts
        $lines = [];
        foreach ($contact_ids as $contact_id) {
          $contact = $all_contacts[$contact_id];
          $image   = CRM_Contact_BAO_Contact_Utils::getImage(empty($contact['contact_sub_type']) ? $contact['contact_type'] : $contact['contact_sub_type'], FALSE, $contact['id']);
          $url     = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $contact['id']);
          $lines[] = "{$image} <a target=\"_blank\" href=\"{$url}\">{$contact['display_name']}</a>";
        }
        $record['duplicates'] = implode('<br/>', $lines);

        // add links
        $links = [];

        // add compare link
//        $caption = E::ts("Compare");
//        $title   = E::ts("View Contact Comparison");
//        $link    = "TODO";
//        $links[] = "<a href=\"{$link}\" class=\"action-item crm-hover-button\" title=\"{$title}\"><strike>{$caption}</strike></a>";

        // add merge link
        $caption = E::ts("Merge");
        $title   = E::ts("Merge with X-Dedupe");
        $links[] = "<a href=\"\" class=\"action-item crm-hover-button xdedupe-merge-individual\" title=\"{$title}\">{$caption}</a>";

        // add manual merge link
        if (count($contact_ids) == 1) {
          $first_contact_ids = reset($contact_ids);
          $caption           = E::ts("Manual");
          $title             = E::ts("CiviCRM's manual merge");
          $link              = CRM_Utils_System::url("civicrm/contact/merge", "reset=1&cid={$main_contact_id}&oid={$first_contact_ids}");
          $links[]           = "<a href=\"{$link}\" class=\"action-item crm-hover-button\" title=\"{$title}\">{$caption}</a>";

          $caption = E::ts("Exclude");
          $title   = E::ts("Mark as 'not a duplicate'");
          $links[] = "<a href=\"\" class=\"action-item crm-hover-button xdedupe-mark-exception\" title=\"{$title}\">{$caption}</a>";
        }

        // add 'fake' link for IDs
        $links[] = "<span style=\"display: none;\" class=\"xdedupe-main-contact-id\">{$main_contact_id}</span>";
        $links[] = "<span style=\"display: none;\" class=\"xdedupe-other-contact-ids\">" . implode(',', $contact_ids) . "</span>";

        // compile links
        $record['links'] = "<ul>" . implode(' ', $links) . "</ul>";
        $records[]       = $record;
      }
    }

    $total_count = $dedupe_run->getTupleCount();
    CRM_Utils_JSON::output([
        'data'            => $records,
        'recordsTotal'    => $total_count,
        'recordsFiltered' => $total_count
    ]);
  }


  /**
   * Generate the contact image with the overview popup
   * @param $contact array contact_data
   * @return string HTML code for image
   */
  protected function getContactImage($contact)
  {
    return CRM_Contact_BAO_Contact_Utils::getImage(empty($contact['contact_sub_type']) ? $contact['contact_type'] : $contact['contact_sub_type'], FALSE, $contact['id']);
  }

  /**
   * Get a list of filter options
   */
  protected function getContactTypeOptions()
  {
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
  protected function getGroups()
  {
    static $group_list = NULL;
    if ($group_list === NULL) {
      $group_list = ['' => E::ts("None")];
      $groups     = civicrm_api3('Group', 'get', [
          'is_active'    => 1,
          'option.limit' => 0,
          'return'       => 'id,name'
      ]);
      foreach ($groups['values'] as $group) {
        $group_list[$group['id']] = $group['name'];
      }
    }
    return $group_list;
  }

  /**
   * Get the list of all active contact tags
   */
  protected function getTags()
  {
    $tag_list = ['' => E::ts("None")];
    $tags     = civicrm_api3('Tag', 'get', [
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

  /**
   * Get the current user's settings
   * @return \Civi\Core\SettingsBag
   */
  public static function getUserSettings()
  {
    return Civi::service('settings_manager')->getBagByContact(CRM_Core_Config::domainID(), CRM_Core_Session::getLoggedInContactID());
  }
}
