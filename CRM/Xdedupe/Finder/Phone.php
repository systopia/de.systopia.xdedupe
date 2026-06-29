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
  protected string $column_name;

  public function __construct(?string $alias, ?array $params, string $column_name) {
    parent::__construct($alias, $params);
    $this->column_name = $column_name;
  }

  /**
   * @inheritDoc
   */
  public function addJOINS(&$joins): void {
    $joins[] = "LEFT JOIN civicrm_phone $this->alias ON $this->alias.contact_id = contact.id";
  }

  /**
   * @inheritDoc
   */
  public function addGROUPBYS(&$groupbys): void {
    $groupbys[] = "$this->alias.$this->column_name";
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = "$this->alias.id IS NOT NULL";
  }

}
