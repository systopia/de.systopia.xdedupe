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
 * Implements a resolver to move contact details (emails, phones, etc)
 */
class CRM_Xdedupe_Resolver_EmailMover extends CRM_Xdedupe_Resolver_DetailMover
{

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Email Mover");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Move all email addresses to the main contact, unless they're duplicates");
    }

    /**
     * Get a one-line representation of the detail data
     *
     * @param $detail array detail data
     * @return string
     */
    protected function getOneLiner($detail)
    {
        $location_type = CRM_Xdedupe_Config::resolveLocationType($detail['location_type_id']);
        return "{$detail['email']} ({$location_type})";
    }

    /**
     * Get the entity name
     * @return string
     */
    protected function getEntity()
    {
        return 'Email';
    }

    /**
     * Get the list of relevant fields for this entity
     * @return array
     */
    protected function getFieldList()
    {
        return ['email', 'location_type_id'];
    }

    /**
     * Are these two details identical?
     *
     * @param $detail1 array detail data
     * @param $detail2 array detail data
     * @return boolean
     */
    protected function detailsEqual($detail1, $detail2)
    {
        return $detail1['email'] == $detail2['email'];
    }
}
