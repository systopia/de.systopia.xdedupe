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
 * Implement a "ContactPicker", i.e. a class that will identify the main contact from a list of contacts
 */
class CRM_Xdedupe_Picker_Youngest extends CRM_Xdedupe_Picker {

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Youngest (by CiviCRM ID)");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Picks the contact with the highest CiviCRM ID");
  }

  /**
   * Select the main contact from a set of contacts
   *
   * @param $contact_ids array list of contact IDs
   * @return int|null one of the contacts in the list. null means "can't decide"
   */
  public function selectMainContact($contact_ids) {
    return max($contact_ids);
  }
}
