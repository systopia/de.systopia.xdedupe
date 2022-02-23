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
 * If there is conflicting addresses with the same type, the other contact will have it's type changed
 *  to the (new) location type 'conflict'
 */
class CRM_Xdedupe_Resolver_BumpAddressConflicts extends CRM_Xdedupe_Resolver
{

    /** @todo is this enough? */
    static $relevant_address_fields = [
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'city',
        'postal_code',
        'location_type_id',
        'country_id'
    ];

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Bump Address Conflicts");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts(
            "If there is conflicting addresses with the same type, the address will be changed to the (new) location type 'conflict'"
        );
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
        // get main contact's phones
        $main_contact_addresses = $this->getContactAddresses($main_contact_id);
        if ($this->containsConflictAddress($main_contact_addresses)) {
            // if there already is a conflict address, there's nothing we can do
            return false;
        }

        // compare to the other contacts:
        foreach ($other_contact_ids as $other_contact_id) {
            $other_contact_addresses = $this->getContactAddresses($other_contact_id);
            if ($this->containsConflictAddress($other_contact_addresses)) {
                // if there already is a conflict address, there's nothing we can do
                continue;
            }

            // compare all addresses
            foreach ($main_contact_addresses as $main_address_id => $main_address) {
                foreach ($other_contact_addresses as $other_address_id => $other_address) {
                    if ($main_address['location_type_id'] == $other_address['location_type_id']) {
                        // address location type clash!
                        if (!$this->addressEquals($main_address, $other_address)) {
                            // and they differ! -> we need to act: bump location type to conflict
                            civicrm_api3(
                                'Address',
                                'create',
                                [
                                    'id'               => $other_address_id,
                                    'location_type_id' => CRM_Xdedupe_Config::getConflictLocationTypeID()
                                ]
                            );
                            $this->addMergeDetail(
                                E::ts(
                                    "Address [%1] from contact [%2] was bumped to 'conflict' location type.",
                                    [
                                        1 => $other_address_id,
                                        2 => $other_address['contact_id']
                                    ]
                                )
                            );
                            return true; // we cannot add more 'conflict' addresses
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the given contact's address records
     *
     * @param $contact_id int contact ID
     *
     * @return array id => address data
     */
    protected function getContactAddresses($contact_id)
    {
        $query = civicrm_api3(
            'Address',
            'get',
            [
                'contact_id'   => $contact_id,
                'option.limit' => 0,
                'sequential'   => 0,
                'return'       => 'id,' . implode(',', self::$relevant_address_fields)
            ]
        );
        return $query['values'];
    }

    /**
     * Check if the list of addresses contain a 'conflict' address
     * @param $addresses array list of address data
     * @return boolean true if it does
     */
    protected function containsConflictAddress($addresses)
    {
        $conflict_location_type_id = CRM_Xdedupe_Config::getConflictLocationTypeID();
        foreach ($addresses as $address) {
            if ($address['location_type_id'] == $conflict_location_type_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the address is the same according to the attributes
     * @param $address1 array address data
     * @param $address2 array address data
     * @return boolean are they equal?
     */
    protected function addressEquals($address1, $address2)
    {
        foreach (self::$relevant_address_fields as $attribute) {
            $value1 = CRM_Utils_Array::value($attribute, $address1, '');
            $value2 = CRM_Utils_Array::value($attribute, $address2, '');
            if ($value1 != $value2) {
                return false;
            }
        }
        return true;
    }
}
