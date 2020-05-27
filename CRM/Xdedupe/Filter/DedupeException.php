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
class CRM_Xdedupe_Filter_DedupeException extends CRM_Xdedupe_Filter
{

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Exclude Dedupe Exceptions");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Exclude contacts (not tuples!) that are in the system's dedupe exception list.");
    }

    /**
     * Add this finder's JOIN clauses to the list
     *
     * @param $joins array
     */
    public function addJOINS(&$joins)
    {
        $joins[] = "LEFT JOIN civicrm_dedupe_exception {$this->alias}_a ON {$this->alias}_a.contact_id1 = contact.id";
        $joins[] = "LEFT JOIN civicrm_dedupe_exception {$this->alias}_b ON {$this->alias}_b.contact_id1 = contact.id";
    }

    /**
     * Add this finder's WHERE clauses to the list
     *
     * @param $wheres array
     */
    public function addWHERES(&$wheres)
    {
        $wheres[] = "({$this->alias}_a.id IS NULL AND {$this->alias}_b.id IS NULL)";
    }
}
