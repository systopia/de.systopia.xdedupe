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
 * Implement a Tag filter, i.e. will restrict the result set by tag
 */
class CRM_Xdedupe_Filter_Tag extends CRM_Xdedupe_Filter
{

    protected $tag_id = null;

    public function __construct($alias, $params)
    {
        parent::__construct($alias, $params);
        if (isset($params['tag_id'])) {
            $this->tag_id = (int)$params['tag_id'];
        }
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Tag %1", [1 => $this->tag_id]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Filter for contacts in the given tag");
    }

    /**
     * Add this finder's JOIN clauses to the list
     *
     * @param $joins array
     */
    public function addJOINS(&$joins)
    {
        if ($this->tag_id) {
            $joins[] = "LEFT JOIN civicrm_entity_tag {$this->alias} ON {$this->alias}.entity_id = contact.id 
                                                              AND {$this->alias}.entity_table = 'civicrm_contact'
                                                              AND {$this->alias}.tag_id = {$this->tag_id}";
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
    }
}
