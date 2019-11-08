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
 * Find people by last name
 */
class CRM_Xdedupe_Finder_PartialOrganizationName extends CRM_Xdedupe_Finder {

  /**
   * @var int number of prefix characters to be considered, if negative check the suffix
   */
  protected $substring_length = -8;

  /**
   * @var int number of prefix characters to be considered, if negative check the suffix
   */
  protected $minimum_compare_characters = 3;

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    $substring_length = (int) $this->substring_length;
    if ($substring_length >= 0) {
      return E::ts("Identical first %1 Organization Name Characters", [1 => abs($substring_length)]);
    } else {
      return E::ts("Identical last %1 Organization Name Characters", [1 => abs($substring_length)]);
    }
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Looks for partly identical organisation names");
  }

  /**
   * Add this finder's JOIN clauses to the list
   *
   * @param $joins array
   */
  public function addJOINS(&$joins) {
  }

  /**
   * Add this finder's GROUP BY clauses to the list
   *
   * @param $groupbys array
   */
  public function addGROUPBYS(&$groupbys) {
    $groupbys[] = "SUBSTR(contact.organization_name, {$this->substring_length})";
  }

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param $wheres array
   */
  public function addWHERES(&$wheres) {
    $minimum_length = (int) abs($this->substring_length) + $this->minimum_compare_characters;
    $wheres[] = "contact.organization_name IS NOT NULL";
    $wheres[] = "CHAR_LENGTH(contact.organization_name) >= {$minimum_length}";
  }
}
