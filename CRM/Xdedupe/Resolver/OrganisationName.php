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
 * Implements a resolver for Organisation Name
 */
class CRM_Xdedupe_Resolver_OrganisationName extends CRM_Xdedupe_Resolver_SimpleAttribute
{

    public function __construct($merge)
    {
        parent::__construct($merge, 'organization_name');
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Main Organisation Name");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("In case of conflicts, keep the organisation name of the main contact.");
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
        // set all names to the chosen one
        return $this->resolveTheGreatEqualiser($main_contact_id, $other_contact_ids);
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
}
