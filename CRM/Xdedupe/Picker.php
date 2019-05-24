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
abstract class CRM_Xdedupe_Picker {

  /**
   * Select the main contact from a set of contacts
   *
   * @param $contact_ids array list of contact IDs
   * @return int one of the contacts in the list
   */
  public abstract function selectMainContact($contact_ids);

  /**
   * get the name of the finder
   * @return string name
   */
  public abstract function getName();

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public abstract function getHelp();

  /**
   * Get a list of all available finder classes
   *
   * @return array list of class names
   */
  public static function getPickers() {
    // todo: use symfony
    return [
        'CRM_Xdedupe_Picker_Oldest',
    ];
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getPickerList() {
    $picker_list = [];
    $picker_classes = self::getPickers();
    foreach ($picker_classes as $picker_class) {
      $picker = new $picker_class();
      $picker_list[$picker_class] = $picker->getName();
    }
    return $picker_list;
  }
}
