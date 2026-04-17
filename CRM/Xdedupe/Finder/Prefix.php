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

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Find people by last name
 */
class CRM_Xdedupe_Finder_Prefix extends CRM_Xdedupe_Finder {

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName(): string {
    return E::ts('Identical Prefix');
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp(): string {
    return E::ts('Looks for fully identical prefix');
  }

  /**
   * Add this finder's JOIN clauses to the list
   *
   * @param $joins array
   */
  public function addJOINS(&$joins): void {
  }

  /**
   * Add this finder's GROUP BY clauses to the list
   *
   * @param $groupbys array
   */
  public function addGROUPBYS(&$groupbys): void {
    $groupbys[] = 'contact.prefix_id';
  }

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param $wheres array
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = 'contact.prefix_id IS NOT NULL';
    $wheres[] = "contact.prefix_id <> ''";
  }

}
