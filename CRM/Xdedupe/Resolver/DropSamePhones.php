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
 * Drop phone numbers with the duplicates if they are numerically the same
 */
class CRM_Xdedupe_Resolver_DropSamePhones extends CRM_Xdedupe_Resolver
{

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Drop Same Phones");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Will drop phones that are numerically the same.");
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
        $changes = false;

        // get main contact's phones
        $main_contact_phones = $this->getContactPhones($main_contact_id);

        // compare to the other contacts:
        foreach ($other_contact_ids as $other_contact_id) {
            $other_contact_phones = $this->getContactPhones($other_contact_id);
            foreach ($other_contact_phones as $phone_key => $phone) {
                if (isset($main_contact_phones[$phone_key])) {
                    // the main contact has the same phone with the same key
                    $main_contact_phone = $main_contact_phones[$phone_key];
                    if ($main_contact_phone['phone'] != $phone['phone']) {
                        // if these phones numerically the same, but not literally: that trips up the merger, so we delete
                        civicrm_api3('Phone', 'delete', ['id' => $phone['id']]);
                        $this->addMergeDetail(
                            E::ts(
                                "Deleted duplicate phone [%1] ('%3') from contact [%2] to avoid merge conflicts",
                                [
                                    1 => $phone['id'],
                                    2 => $phone['contact_id'],
                                    3 => $phone['phone']
                                ]
                            )
                        );
                        $changes = true;
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Get the given contact's phone records
     *
     * @param $contact_id int contact ID
     *
     * @return array key (numeric-location_type_id) => phone data
     */
    protected function getContactPhones($contact_id)
    {
        $phones = [];
        $query  = civicrm_api3(
            'Phone',
            'get',
            [
                'contact_id'   => $contact_id,
                'option.limit' => 0,
                'return'       => 'contact_id,phone_numeric,id,location_type_id,phone'
            ]
        );
        foreach ($query['values'] as $phone) {
            $phones["{$phone['phone_numeric']}-{$phone['location_type_id']}"] = $phone;
        }
        return $phones;
    }
}
