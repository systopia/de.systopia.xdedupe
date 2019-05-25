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
class CRM_Xdedupe_Filter_Group extends CRM_Xdedupe_Filter {

  protected $group_id = NULL;

  public function __construct($alias, $params) {
    parent::__construct($alias, $params);
    if (isset($params['group_id'])) {
      $this->group_id = (int) $params['group_id'];
    }
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Group %1", [1 => $this->group_id]);
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Filter for contacts in the given group");
  }

  /**
   * Add this finder's JOIN clauses to the list
   *
   * @param $joins array
   */
  public function addJOINS(&$joins) {
    if ($this->group_id) {
      $joins[] = "LEFT JOIN civicrm_group_contact {$this->alias} ON {$this->alias}.contact_id = contact.id 
                                                                 AND {$this->alias}.group_id = {$this->group_id}
                                                                 AND {$this->alias}.status = 'Added'";
    }
  }

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param $wheres array
   */
  public function addWHERES(&$wheres) {
    $wheres[] = "{$this->alias}.id IS NOT NULL";
  }
}
