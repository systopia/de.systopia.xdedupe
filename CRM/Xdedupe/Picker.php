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
   * @return int|null one of the contacts in the list. null means "can't decide"
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
    $picker_list = [];
    \Civi::dispatcher()->dispatch('civi.xdedupe.pickers', \Civi\Core\Event\GenericHookEvent::create(['list' => &$picker_list]));
    return $picker_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getPickerList() {
    $picker_list = [];
    $picker_classes = self::getPickers();
    $pickers = self::getPickerInstances($picker_classes);
    foreach ($pickers as $picker) {
      $picker_list[get_class($picker)] = $picker->getName();
    }
    return $picker_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @param $picker_classes array list of class names
   * @return array picker instances
   */
  public static function getPickerInstances($picker_classes = NULL) {
    $picker_list = [];
    if ($picker_classes === NULL) {
      $picker_classes = self::getPickers();
    }
    foreach ($picker_classes as $picker_class) {
      if (!empty($picker_class)) {
        if (class_exists($picker_class)) {
          $picker_list[] = new $picker_class();
        } else {
          CRM_Core_Session::setStatus("Picker '%1' could not be found!", [1 => $picker_class]);
        }
      }
    }
    return $picker_list;
  }
}
