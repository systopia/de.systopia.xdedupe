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
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
// phpcs:ignore Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Finder_Address extends CRM_Xdedupe_Finder {


  protected array $address_fields = [];

  /**
   * @param string|NULL $alias
   * @param array|NULL $params
   * @param list<string> $address_fields
   */
  public function __construct(?string $alias, ?array $params, array $address_fields) {
    parent::__construct($alias, $params);
    $this->address_fields = $address_fields;
  }

  /**
   * @inheritDoc
   */
  public function addJOINS(&$joins): void {
    $joins[] = "LEFT JOIN civicrm_address $this->alias ON $this->alias.contact_id = contact.id";
  }

  /**
   * @inheritDoc
   */
  public function addGROUPBYS(&$groupbys): void {
    foreach ($this->address_fields as $address_field) {
      $groupbys[] = "$this->alias.$address_field";
    }
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = "$this->alias.id IS NOT NULL";
    foreach ($this->address_fields as $address_field) {
      $wheres[] = "$this->alias.$address_field IS NOT NULL";
    }
  }

}
