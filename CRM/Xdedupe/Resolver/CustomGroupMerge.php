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
    return E::ts("Merge '%1' values", [1 => $group_name[0]['title']]);
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
        // todo:
        // 1) get all the record sets from main and other contacts
        // 2) fill main contact with those values, no overwrite!
        // 3) delete all entries for non-main contacts

        // compare CRM_Xdedupe_Resolver_CustomGroupIntegral

        return TRUE;
    }


    /**
     * Add a resolver spec for each Multi-Select field to the list
     * @param array<string> $list list of resolver specs
     * @return void
     */
    public static function addAllResolvers(&$list) {
        // currently disabled, implementation halted:
//        $contact_custom_groups = civicrm_api4(
//                'CustomGroup',
//                'get',
//                ['select' => ['id', 'title'],
//                        'where' => [
//                                ['extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization']],
//                                ['is_active', '=', TRUE],
//                                ['is_multiple', '=', FALSE], // multiple entry groups can always be "merged" without any issue, so no resolver needed
//                        ],
//                ]
//        );
//
//        foreach ($contact_custom_groups as $custom_group) {
//            $list[] = "CRM_Xdedupe_Resolver_CustomGroupMerge:{$custom_group['id']}";
//        }
    }
}
