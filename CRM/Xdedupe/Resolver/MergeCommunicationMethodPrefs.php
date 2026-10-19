<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| Author: Rich Lott / Artful Robot                       |
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
 * Implements a resolver for email and postal greeting fields.
 *   Overwrites 'other' contacts' data with the main contact's.
 */
class CRM_Xdedupe_Resolver_MergeCommunicationMethodPrefs extends CRM_Xdedupe_Resolver
{
    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Merge preferred communication methods");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("All preferred methods apply.");
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return ['preferred_communication_method'];
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
        $methodsPerContact = Civi\Api4\Contact::get(FALSE)
            ->addWhere('id', 'IN', [$main_contact_id, ...$other_contact_ids])
            ->addSelect('preferred_communication_method:name')
            ->execute()->column('preferred_communication_method:name', 'id');

        // Make unique, sorted list of all that apply.
        $all = [];
        foreach ($methodsPerContact as $ct_id => $methods) {
            $methods = (array) $methods;
            foreach($methods as $method) {
                $all[$method] = 1;
            }
        }
        $all = array_keys($all);
        sort($all);
        $contactsToUpdate = [];
        foreach ($methodsPerContact as $ct_id => $methods) {
            // Api4 helpfully returns single values as a string, and multiple as
            // an array.
            $methods = (array) $methods;
            // Turns out it is important NOT to sort this.
            // api3's get merge conflicts considers [A, B] != [B, A]
            // so we do the same here.
            // sort($methods);
            if ($methods != $all) {
                $contactsToUpdate[] = $ct_id;
                $this->getContext()->unloadContact($ct_id);
            }
        }
        if ($contactsToUpdate) {
            $r = Civi\Api4\Contact::update(FALSE)
                ->addWhere('id', 'IN', $contactsToUpdate)
                ->addValue('preferred_communication_method:name', $all)
                ->execute();
            $this->addMergeDetail(E::ts(
                    "Updating Preferred Communication Methods for contact(s) %1 to %2 to resolve merge conflicts",
                    [ 1 => implode(', ', $contactsToUpdate), 2 => implode(', ', $all) ]
                ));
            return true;
        }

        return false;
    }
}