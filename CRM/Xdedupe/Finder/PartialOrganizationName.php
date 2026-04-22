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
class CRM_Xdedupe_Finder_PartialOrganizationName extends CRM_Xdedupe_Finder {

  /**
   * @var int number of prefix characters to be considered, if negative check the suffix
   */
  protected int $substring_length = 5;

  /**
   * @var int number of prefix characters to be considered, if negative check the suffix
   */
  protected int $minimum_compare_characters = 3;

  /**
   * @inheritDoc
   */
  public function getName(): string {
    $substring_length = $this->substring_length;
    if ($substring_length >= 0) {
      return E::ts('Identical first %1 Organization Name Characters', [1 => abs($substring_length)]);
    }

    return E::ts('Identical last %1 Organization Name Characters', [1 => abs($substring_length)]);
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Looks for partly identical organisation names');
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
    if ($this->substring_length < 0) {
      $groupbys[] = "SUBSTR(contact.organization_name, $this->substring_length)";
    }
    else {
      $groupbys[] = "SUBSTR(contact.organization_name, 1, $this->substring_length)";
    }
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $minimum_length = abs($this->substring_length) + $this->minimum_compare_characters;
    $wheres[]       = 'contact.organization_name IS NOT NULL';
    $wheres[]       = "CHAR_LENGTH(contact.organization_name) >= $minimum_length";
  }

}
