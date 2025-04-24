<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2025 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
|         S. Frank (frank@systopia.de)                   |
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
 * Implements a resolver for contact custom group conflicts.
 *
 * This version will treat the custom group data
 * in an integral way, i.e. the fields of the group should be treated
 * as one record set
 */
class CRM_Xdedupe_Resolver_CustomGroupIntegral extends CRM_Xdedupe_Resolver {

  /**
   * @var integer ID of the custom group
   */
  protected int $custom_group_id;

  protected ?array $custom_group_data = null;

  /**
   * @param CRM_Xdedupe_Merge $merge
   * @param mixed $custom_group_id
   */
  public function __construct($merge, $custom_group_id) {
    $this->custom_group_id = (int) $custom_group_id;
    parent::__construct($merge);
  }

  /**
   * Get the spec (i.e. class name) that refers to this resolver
   * @return string spec
   */
  public function getSpec() {
    return "CRM_Xdedupe_Resolver_CustomGroupIntegral:{$this->custom_group_id}";
  }

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array<string> list of contact attributes
   */
  public function getContactAttributes() {
    return ['id'];
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("\"%1\" conflicts (integral)", [
            1 => $this->getCustomGroupData()['title']]);
  }

    /**
     * Get information on the custom group we're working on
     *
     * @return array custom group data
     * @throws CRM_Core_Exception
     */
  public function getCustomGroupData() : array {
      if ($this->custom_group_data === null) {
          $this->custom_group_data = civicrm_api4(
                  'CustomGroup', 'get',
                  [
                          'select' => ['title', 'table_name'],
                          'where' => [['id', '=', $this->custom_group_id]],
                  ]
          )->first();
      }
      return $this->custom_group_data;
  }

    /**
     * Get the table name for the custom group referred to by this instance
     * @return void
     */
  public function getTableName() {
      return $this->getCustomGroupData()['table_name'];
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts(
        "This resolver will make sure that any conflicts in the custom group '%1' will be solved by treating the custom group data as an integral record, i.e. it will <i>not</i> merge attributes from different custom group records, but rather select one of the existing records to prevail - preferredly the main contact's.",
        [1 => $this->getCustomGroupData()['title']]
    );
  }

  /**
   * Resolve conflicts between two (integral) group records
   *   deleting all but one records of the group,
   *   preferring to keep the main contact's one
   *
   * @param $main_contact_id  integer the main contact ID
   * @param $other_contact_ids  integer[] other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   *
   * @note I'd prefer to do this via API, but I'm not sure it's possible
   */
  public function resolve($main_contact_id, $other_contact_ids) {
    $priority_list_of_contact_ids = array_unique(array_merge([$main_contact_id], $other_contact_ids));
    $priority_list_of_contact_ids_as_string = implode(',', $priority_list_of_contact_ids);
    $table_name = $this->getTableName();
    $group_title = $this->getCustomGroupData()['title'];
    $prevailing_record_id = 0;
    $existing_records = [];

    // fetch all existing records
    $existing_record_query = CRM_Core_DAO::executeQuery("
        SELECT id, entity_id FROM {$table_name}
        WHERE entity_id IN ({$priority_list_of_contact_ids_as_string})");
    while ($existing_record_query->fetch()) {
        $existing_records[$existing_record_query->id] = [
                'record_id'  => $existing_record_query->id,
                'contact_id' => $existing_record_query->entity_id
        ];
        // if the main contact has a record, keep that one
        if ($existing_record_query->entity_id == $main_contact_id) {
            $prevailing_record_id = (int) $existing_record_query->id;
            $this->addMergeDetail(E::ts("Deleting all but the head's record for custom group '{$group_title}'"));
        }
    }

    // if the prevailing_record_id isn't on the head, take the lowest ID
    if (empty($prevailing_record_id)) {
        $prevailing_record_id = (int) min(array_keys($existing_records));
        $this->addMergeDetail(E::ts("Deleting all but the oldest record for custom group '{$group_title}'"));
    }

    // so... let's delete all the others to allow for a merge to succeed with no conflicts
    foreach ($existing_records as $existing_record) {
        if ($existing_record['record_id'] != $prevailing_record_id) {
            CRM_Core_DAO::executeQuery("DELETE FROM {$table_name} WHERE id = {$existing_record['record_id']}");
        }
    }

    return TRUE;
  }

  /**
   * Add a resolver spec for each Multi-Select field to the list
   * @param array<string> $list list of resolver specs
   * @return void
   */
  public static function addAllResolvers(&$list) {
    $contact_custom_groups = civicrm_api4(
            'CustomGroup',
            'get',
            ['select' => ['id', 'title'],
              'where' => [
                    ['extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization']],
                    ['is_active', '=', TRUE],
                    ['is_multiple', '=', FALSE], // multiple entry groups can always be "merged" without any issue, so no resolver needed
              ],
            ]
    );

    foreach ($contact_custom_groups as $custom_group) {
      $list[] = "CRM_Xdedupe_Resolver_CustomGroupIntegral:{$custom_group['id']}";
    }
  }
}
