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
 * Implements a resolver for contact custom fields
 */
class CRM_Xdedupe_Resolver_CustomGroupMerge extends CRM_Xdedupe_Resolver_CustomGroupIntegral {

  /**
   * Get the spec (i.e. class name) that refers to this resolver
   * @return string spec string
   */
  public function getSpec() {
    return "CRM_Xdedupe_Resolver_CustomGroupMerge:{$this->custom_group_id}";
  }

    /**
     * get an explanation what this resolver does
     * @return string name
     */
    public function getHelp() {
        return E::ts(
                "This resolver will make sure that any potential merge conflicts in the custom group '%1' will be solved by merging all of it's individual field values. The main contact's values will have preference, the other ones' will be added if not already specified by the previous contacts.",
                [1 => $this->getCustomGroupData()['title']]
        );
    }


  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    $group_name = civicrm_api4(
            'CustomGroup', 'get',
            [
              'select' => ['title'],
              'where' => [['id', '=', $this->custom_group_id]],
            ]
    );
    return E::ts("Merge values for custom group '%1'", [1 => $group_name[0]['title']]);
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

}
