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
class CRM_Xdedupe_Resolver_GreetingsPreferMain extends CRM_Xdedupe_Resolver
{
    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Main Greetings");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("In case of conflicts, keep the greetings of the main contact.");
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return ['email_greeting_id', 'email_greeting_custom', 'postal_greeting_id', 'postal_greeting_custom'];
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
        $main_contact = $this->getContext()->getContact($main_contact_id);

        foreach ($other_contact_ids as $contact_id) {
            $contact = $this->getContext()->getContact($contact_id);
            $records = [];
            foreach (['email', 'postal'] as $type) {
                if (
                    ($main_contact["{$type}_greeting_id"] ?? '' != $contact["{$type}_greeting_id"] ?? '')
                    ||
                    ($main_contact["{$type}_greeting_custom"] ?? '' != $contact["{$type}_greeting_custom"] ?? '')
                ) {
                    $records[] = [
                        'id' => $contact['id'],
                        "{$type}_greeting_id" => $main_contact["{$type}_greeting_id"],
                        "{$type}_greeting_custom" => $main_contact["{$type}_greeting_custom"],
                    ];
                    $this->addMergeDetail(
                        E::ts(
                            "Discarding %1 greeting of contact [%2] in favour of main contact in order to resolve merge conflicts",
                            [ 1 => $type, 2 => $contact_id ]
                        )
                    );
                    $this->getContext()->unloadContact($contact_id);
                }
            }
            if ($records) {
                Civi\Api4\Contact::save(FALSE)
                    ->setRecords($records)
                    ->execute();
            }
        }

        return true;
    }
}