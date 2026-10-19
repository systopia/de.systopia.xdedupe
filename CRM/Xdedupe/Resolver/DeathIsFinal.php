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
 * Simple ExternalIdentifier Resolver
 */
class CRM_Xdedupe_Resolver_DeathIsFinal extends CRM_Xdedupe_Resolver
{
    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Death is final");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("If any contact is marked deceased ('closed' for Organizations), this is assumed to have happened.");
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return ['is_deceased'];
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

        $isAlive = ! $main_contact['is_deceased'];
        $living = $isAlive ? [$main_contact_id] : [];
        foreach ($other_contact_ids as $contact_id) {
            $contact = $this->getContext()->getContact($contact_id);
            if ($contact['is_deceased']) {
                $isAlive = false;
            }
            else {
                $living[] = $contact['id'];
            }
        }
        if (!$isAlive && $living) {
            // This contact is deceased, but we have others that are alive.
            Civi\Api4\Contact::update(FALSE)
                ->addWhere('id', 'IN', $living)
                ->addValue('is_deceased', 1)
                ->execute();
            foreach ($living as $contact_id) {
                $this->getContext()->unloadContact($contact_id);
            }
            $this->addMergeDetail(E::ts(
                "Marking contacts %1 as deceased because at least one of the others is.",
                [ 1 => implode(', ', $living) ]
            ));
            return true;
        }

        return false;
    }
}