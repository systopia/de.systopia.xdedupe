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
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Identical Prefix');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Looks for fully identical prefix');
  }

  /**
   * @inheritDoc
   */
  public function addJOINS(&$joins): void {
  }

  /**
   * @inheritDoc
   */
  public function addGROUPBYS(&$groupbys): void {
    $groupbys[] = 'contact.prefix_id';
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = 'contact.prefix_id IS NOT NULL';
    $wheres[] = "contact.prefix_id <> ''";
  }

}
