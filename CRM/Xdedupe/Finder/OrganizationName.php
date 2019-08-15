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
class CRM_Xdedupe_Finder_OrganizationName extends CRM_Xdedupe_Finder {

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Identical Organization Name");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Looks for fully identical organisation names");
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
    $groupbys[] = "contact.organization_name";
  }

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param $wheres array
   */
  public function addWHERES(&$wheres) {
    $wheres[] = "contact.organization_name IS NOT NULL";
    $wheres[] = "contact.organization_name <> ''";
  }
}
