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
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
class CRM_Xdedupe_Finder_Email extends CRM_Xdedupe_Finder {

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Identical Email');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Looks for fully identical email addresses');
  }

  /**
   * @inheritDoc
   */
  public function addJOINS(&$joins): void {
    $joins[] = "LEFT JOIN civicrm_email $this->alias ON $this->alias.contact_id = contact.id";
  }

  /**
   * @inheritDoc
   */
  public function addGROUPBYS(&$groupbys): void {
    $groupbys[] = "$this->alias.email";
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = "$this->alias.email IS NOT NULL";
  }

}
