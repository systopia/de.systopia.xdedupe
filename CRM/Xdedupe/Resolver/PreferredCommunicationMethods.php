<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2023 SYSTOPIA                            |
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

const PREFERRED_COMMUNICATION_METHOD_FIELD = 'preferred_communication_method';

/**
 * Implements a resolver for option value fields,
 *   selects the value with the lowest weight in the option group
 */
class CRM_Xdedupe_Resolver_PreferredCommunicationMethods extends CRM_Xdedupe_Resolver
{
    public function __construct($merge, $custom_field_id)
    {
        parent::__construct($merge);
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return [PREFERRED_COMMUNICATION_METHOD_FIELD];
    }

    /**
     * get the name of the resolver
     * @return string name
     */
    public function getName()
    {
        return E::ts("Preferred Communication Methods");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("This resolver will simply keep all of the preferred communication methods.");
    }

    /**
     * Get the preferred communication languages of the given contact
     *
     * @param int $contact_id
     *  the contact ID
     *
     * @return array list of communication languages
     */
    protected function getValues($contact_id)
    {
        $contact_data = $this->merge->getContact($contact_id);
        return $contact_data[PREFERRED_COMMUNICATION_METHOD_FIELD] ?? [];
    }

    /**
     * Resolve the conflicting preferred communication methods by just keeping all of them
     *
     * @param $main_contact_id    int     the main contact ID
     * @param $other_contact_ids  array   other contact IDs
     * @return boolean TRUE, if there was a conflict to be resolved
     * @throws Exception if the conflict couldn't be resolved
     */
    public function resolve($main_contact_id, $other_contact_ids)
    {
        $main_contact_values     = $this->getValues($main_contact_id);
        $new_main_contact_values = $main_contact_values;
        foreach ($other_contact_ids as $other_contact_id) {
            $other_contact_values      = $this->getValues($other_contact_id);
            $only_other_contact_values = array_diff($other_contact_values, $main_contact_values);
            if ($only_other_contact_values) {
                // there are values that are only set in the other contact
                $new_main_contact_values = array_merge($new_main_contact_values, $only_other_contact_values);
                $new_values              = implode(',', $only_other_contact_values);
                $this->addMergeDetail(E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id]));
            }
        }

        // now, perform the contact updates if necessary
        sort($new_main_contact_values);
        $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
        foreach ($all_contact_ids as $contact_id) {
            $current_values = $this->getValues($contact_id);
            if ($current_values != $new_main_contact_values) {
                civicrm_api3(
                    'Contact',
                    'create',
                    [
                        'id' => $contact_id,
                        PREFERRED_COMMUNICATION_METHOD_FIELD => $new_main_contact_values
                    ]
                );
                $this->getContext()->unloadContact($contact_id);
            }
        }

        return true;
    }


    /**
     * Add a resolver spec for each Multi-Select field to the list
     * @param $list array list of resolver specs
     */
    public static function addAllResolvers(&$list)
    {
        $list[] = "CRM_Xdedupe_Resolver_PreferredCommunicationMethods";
    }
}
