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
 * Implements a resolver to move contact details (emails, phones, etc)
 */
class CRM_Xdedupe_Resolver_PhoneMover extends CRM_Xdedupe_Resolver_DetailMover {

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Phone Mover");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Move all phone numbers to the main contact, unless they're duplicates");
  }

  /**
   * Get the entity name
   * @return string
   */
  protected function getEntity() {
    return 'Phone';
  }

  /**
   * Get the list of relevant fields for this entity
   * @return array
   */
  protected function getFieldList() {
    return ['phone_numeric', 'location_type_id', 'phone_type_id'];
  }

  /**
   * Are these two details identical?
   *
   * @param $detail1 array detail data
   * @param $detail2 array detail data

   * @return boolean
   */
  protected function detailsEqual($detail1, $detail2) {
    return $detail1['phone_numeric'] == $detail2['phone_numeric']
        && $detail1['phone_type_id'] == $detail2['phone_type_id'];
  }


}
