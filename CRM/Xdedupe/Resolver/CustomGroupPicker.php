<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2025 SYSTOPIA                            |
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

/** @var int $custom_group_id the ID of group in question */
protected $custom_group_id = null;

/**
 * Implements a resolver to delete all entries of this custom group except one.
 * Preferably the head's record is used, otherwise any other one
 */
class CRM_Xdedupe_Resolver_CustomGroupPicker extends CRM_Xdedupe_Resolver
{
    public function __construct($merge, int $custom_group_id)
    {
        parent::__construct($merge);
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        $custom_group = $this->getCustomGroupData();
        return E::ts("Custom Group " . $custom_group['group_label']);
    }

    /**
     * Get the metadata for the group we're trying to resolve
     *
     * @return array group data
     */
    public function getCustomGroupData() : array
    {
        static $group_data = null;
        if (empty($group_data)) {
            $group_data = \Civi\Api4\CustomGroup::get(TRUE)
                    ->addWhere('id', '=', $this->getGroupId())
                    ->execute()->first();
        }
        return $group_data;
    }

    /**
     * Get the ID of the custom group processed here
     *
     * @return int id of the custom group
     */
    public function getGroupId()
    {
        return (int) $this->getCustomGroupData()['id'];
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("In case of conflicts of entries to this group, keep only one, preferring the main contact's one. ");
    }


    /**
     * Resolve the merge conflicts by editing the contact
     *
     * CAUTION: IT IS PARAMOUNT TO UNLOAD A CONTACT FROM THE CACHE IF CHANGED AS FOLLOWS:
     *  $this->merge->unloadContact($contact_id)
     *
     * @param $main_contact_id    int     the main contact ID
     * @param $other_contact_ids  array   other contact IDs
     * @return boolean TRUE, if there was a conflict to be resolved
     * @throws Exception if the conflict couldn't be resolved
     */
    public function resolve($main_contact_id, $other_contact_ids)
    {
        // check if the data set is present in multiple contacts,
        // and if so delete all but one - preferring to keep the main contact's

        $custom_data_table = $this->getCustomGroupData()['table'];
    }

    /**
     * Rate the given value, meant to be overwritten.
     *
     * Default implementation: pick the main contact's one
     *
     * @param $value            string value to be rated
     * @param $contact_ids      array list of contact_ids using it
     * @param $main_contact_id
     * @return int rating -> the higher, the better
     */
    protected function getValueRating($value, $contact_ids, $main_contact_id)
    {
        // we want to keep the main contact's one...
        if (in_array($main_contact_id, $contact_ids)) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Add a resolver spec for each Multi-Select field to the list
     * @param $list array list of resolver specs
     */
    public static function addAllResolvers(&$list)
    {
        $contact_custom_group_ids = [];

        $contact_custom_groups = \Civi\Api4\CustomGroup::get(TRUE)
                ->addSelect('id', 'table_name', 'name')
                ->addWhere('is_multiple', '=', FALSE)
                ->addWhere('extends', '=', 'Contact')
                ->addWhere('is_active', '=', TRUE)
                ->setLimit(25)
                ->execute();

        foreach ($contact_custom_groups['values'] as $contact_custom_group) {
            $contact_custom_group_ids[] = $contact_custom_group['id'];
        }
        if (empty($contact_custom_group_ids)) {
            return;
        }

        $all_multi_selects = civicrm_api3(
                'CustomField',
                'get',
                [
                        'option.limit'    => 0,
                        'html_type'       => 'Multi-Select',
                        'custom_group_id' => ['IN' => $contact_custom_group_ids],
                        'is_active'       => 1,
                        'return'          => 'id'
                ]
        );
        foreach ($all_multi_selects['values'] as $multi_select) {
            $list[] = "CRM_Xdedupe_Resolver_MultiSelect:{$multi_select['id']}";
        }
    }
}
