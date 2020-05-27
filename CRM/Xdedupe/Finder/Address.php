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
abstract class CRM_Xdedupe_Finder_Address extends CRM_Xdedupe_Finder
{

    protected $address_fields = [];

    public function __construct($alias, $params, $address_fields)
    {
        parent::__construct($alias, $params);
        $this->address_fields = $address_fields;
    }

    /**
     * Add this finder's JOIN clauses to the list
     *
     * @param $joins array
     */
    public function addJOINS(&$joins)
    {
        $joins[] = "LEFT JOIN civicrm_address {$this->alias} ON {$this->alias}.contact_id = contact.id";
    }

    /**
     * Add this finder's GROUP BY clauses to the list
     *
     * @param $groupbys array
     */
    public function addGROUPBYS(&$groupbys)
    {
        foreach ($this->address_fields as $address_field) {
            $groupbys[] = "{$this->alias}.{$address_field}";
        }
    }

    /**
     * Add this finder's WHERE clauses to the list
     *
     * @param $wheres array
     */
    public function addWHERES(&$wheres)
    {
        $wheres[] = "{$this->alias}.id IS NOT NULL";
        foreach ($this->address_fields as $address_field) {
            $wheres[] = "{$this->alias}.{$address_field} IS NOT NULL";
            //$wheres[] = "{$this->alias}.{$address_field} <> ''";
        }
    }
}
