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
 * Implement a filter that includes contacts, that are in the dedupe exception list
 */
class CRM_Xdedupe_Filter_UserAccounts extends CRM_Xdedupe_Filter
{

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Exclude System Users");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Exclude contacts (not tuples!) that are connected to a user account.");
    }

    /**
     * Add this finder's JOIN clauses to the list
     *
     * @param $joins array
     */
    public function addJOINS(&$joins)
    {
        $joins[] = "LEFT JOIN civicrm_uf_match {$this->alias} ON {$this->alias}.contact_id = contact.id";
    }

    /**
     * Add this finder's WHERE clauses to the list
     *
     * @param $wheres array
     */
    public function addWHERES(&$wheres)
    {
        $wheres[] = "{$this->alias}.uf_id IS NULL";
    }
}
