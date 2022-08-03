<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2022 SYSTOPIA                            |
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
 * Implements a resolver for Organisation Name
 */
class CRM_Xdedupe_Resolver_Addressee extends CRM_Xdedupe_Resolver
{
    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Main Addressee");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("In case of conflicts, keep the addressee of the main contact.");
    }


    /**
     * Resolve the merge conflicts by copying the main contact's addressee to the others
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
        // get the main contact's addressee (this is somewhat shady through the api)
        $main_values = CRM_Core_DAO::executeQuery("
            SELECT addressee_id, addressee_custom 
            FROM civicrm_contact
            WHERE id = %1", [1 => [$main_contact_id, 'Integer']]);
        $main_values->fetch();

        // set for the other contacts
        foreach ($other_contact_ids as $other_contact_id) {
            civicrm_api3('Contact', 'create', [
                'id' => $other_contact_id,
                'addressee_id' => $main_values->addressee_id ?? '',
                'addressee_custom' => $main_values->addressee_custom ?? '',
            ]);
            $this->merge->unloadContact($other_contact_id);
        }
        return true;
    }
}
