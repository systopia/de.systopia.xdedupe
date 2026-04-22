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
 * Implement a filter that includes contacts, that are in the dedupe exception list
 */
class CRM_Xdedupe_Filter_DedupeException extends CRM_Xdedupe_Filter {

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Exclude Dedupe Exceptions');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts("Exclude contacts (not tuples!) that are in the system's dedupe exception list.");
  }

  /**
   * @inheritDoc
   */
  public function addJOINS(&$joins): void {
    $joins[] = "LEFT JOIN civicrm_dedupe_exception {$this->alias}_a ON {$this->alias}_a.contact_id1 = contact.id";
    $joins[] = "LEFT JOIN civicrm_dedupe_exception {$this->alias}_b ON {$this->alias}_b.contact_id1 = contact.id";
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = "({$this->alias}_a.id IS NULL AND {$this->alias}_b.id IS NULL)";
  }

}
