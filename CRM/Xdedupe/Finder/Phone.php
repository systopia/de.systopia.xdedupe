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

declare(strict_types = 1);

/**
 * Implement a "Finder" based on the phone number
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Finder_Phone extends CRM_Xdedupe_Finder {
// phpcs:enable
  protected $column_name = [];

  public function __construct($alias, $params, $column_name) {
    parent::__construct($alias, $params);
    $this->column_name = $column_name;
  }

  /**
   * Add this finder's JOIN clauses to the list
   *
   * @param $joins array
   */
  public function addJOINS(&$joins): void {
    $joins[] = "LEFT JOIN civicrm_phone $this->alias ON $this->alias.contact_id = contact.id";
  }

  /**
   * Add this finder's GROUP BY clauses to the list
   *
   * @param $groupbys array
   */
  public function addGROUPBYS(&$groupbys): void {
    $groupbys[] = "$this->alias.$this->column_name";
  }

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param $wheres array
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = "$this->alias.id IS NOT NULL";
  }

}
