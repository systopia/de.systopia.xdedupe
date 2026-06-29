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

use Civi\Core\Event\GenericHookEvent;
use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Implement a "ContactPicker", i.e. a class that will identify the main contact from a list of contacts
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Picker {

  // phpcs:enable

  /**
   * Select the main contact from a set of contacts
   *
   * @param non-empty-list<int> $contact_ids list of contact IDs
   *
   * @return int|null one of the contacts in the list. null means "can't decide"
   */
  abstract public function selectMainContact(array $contact_ids): ?int;

  /**
   * get the name of the finder
   *
   * @return string name
   */
  abstract public function getName(): string;

  /**
   * get an explanation what the finder does
   *
   * @return string name
   */
  abstract public function getHelp(): string;

  /**
   * Get a list of all available finder classes
   *
   * @return list<string> list of class names
   */
  public static function getPickers(): array {
    $picker_list = [];
    Civi::dispatcher()->dispatch(
      'civi.xdedupe.pickers',
      GenericHookEvent::create(['list' => &$picker_list])
    );
    return $picker_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getPickerList(): array {
    $picker_list = [];
    $picker_classes = self::getPickers();
    foreach (self::getPickerInstances($picker_classes) as $picker) {
      $picker_list[get_class($picker)] = $picker->getName();
    }
    return $picker_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @param list<string>|NULL $picker_classes list of class names
   *
   * @return array<CRM_Xdedupe_Picker> picker instances
   */
  public static function getPickerInstances(array|null $picker_classes = NULL): array {
    $picker_list = [];
    if ($picker_classes === NULL) {
      $picker_classes = self::getPickers();
    }
    foreach ($picker_classes as $picker_class) {
      if (($picker_class ?? '') !== '') {
        if (class_exists($picker_class)) {
          $picker_list[] = new $picker_class();
        }
        else {
          CRM_Core_Session::setStatus(E::ts("Picker '%1' could not be found!", [1 => $picker_class]));
        }
      }
    }
    return $picker_list;
  }

}
