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
 * Implement a Group "Filter", i.e. will restrict the result set by group membership
 */
class CRM_Xdedupe_Filter_Group extends CRM_Xdedupe_Filter
{

    protected $group_id = null;
    protected $include = true;

    public function __construct($alias, $params)
    {
        parent::__construct($alias, $params);
        if (isset($params['group_id'])) {
            $this->group_id = (int)$params['group_id'];
        }
        if (isset($params['exclude'])) {
            $this->include = false;
        }
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Group %1", [1 => $this->group_id]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Filter for contacts in the given group");
    }

    /**
     * Add this finder's JOIN clauses to the list
     *
     * @param $joins array
     */
    public function addJOINS(&$joins)
    {
        if ($this->group_id) {
            // if this is a smart group, we should refresh the smart group cache:
            $is_smart_group = CRM_Core_DAO::singleValueQuery(
                "SELECT saved_search_id FROM civicrm_group WHERE id = %1",
                [1 => [$this->group_id, 'Integer']]);
            $table = 'civicrm_group_contact';
            if ($is_smart_group) {
                CRM_Contact_BAO_GroupContactCache::loadAll([$this->group_id]);
                $table = 'civicrm_group_contact_cache';
            }

            // finally: add the join
            $joins[] = "LEFT JOIN {$table} {$this->alias} ON {$this->alias}.contact_id = contact.id 
                                                                 AND {$this->alias}.group_id = {$this->group_id}" . ($is_smart_group ? '' : " AND {$this->alias}.status = 'Added'");
        }
    }

    /**
     * Add this finder's WHERE clauses to the list
     *
     * @param $wheres array
     */
    public function addWHERES(&$wheres)
    {
        if ($this->include) {
            $wheres[] = "{$this->alias}.contact_id IS NOT NULL";
        } else {
            $wheres[] = "{$this->alias}.contact_id IS NULL";
        }
    }
}
