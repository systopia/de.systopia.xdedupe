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
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
class CRM_Xdedupe_Finder_Email extends CRM_Xdedupe_Finder
{

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Identical Email");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Looks for fully identical email addresses");
    }

    /**
     * Add this finder's JOIN clauses to the list
     *
     * @param $joins array
     */
    public function addJOINS(&$joins)
    {
        $joins[] = "LEFT JOIN civicrm_email {$this->alias} ON {$this->alias}.contact_id = contact.id";
    }

    /**
     * Add this finder's GROUP BY clauses to the list
     *
     * @param $groupbys array
     */
    public function addGROUPBYS(&$groupbys)
    {
        $groupbys[] = "{$this->alias}.email";
    }

    /**
     * Add this finder's WHERE clauses to the list
     *
     * @param $wheres array
     */
    public function addWHERES(&$wheres)
    {
        $wheres[] = "{$this->alias}.email IS NOT NULL";
    }
}
