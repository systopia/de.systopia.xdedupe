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

/**
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Finder extends CRM_Xdedupe_QueryPlugin {

  // phpcs:enable

  /**
   * Get a list of all available finder classes
   *
   * @return array list of class names
   */
  public static function getFinders(): array {
    $finder_list = [];
    Civi::dispatcher()->dispatch(
      'civi.xdedupe.finders',
      GenericHookEvent::create(['list' => &$finder_list])
    );
    return $finder_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getFinderList(): array {
    $finder_list = [];
    foreach (self::getFinderInstances() as $finder) {
      $finder_list[get_class($finder)] = $finder->getName();
    }
    return $finder_list;
  }

  /**
   * Get an instance of each finder
   *
   * @return array<CRM_Xdedupe_Finder>
   */
  public static function getFinderInstances(): array {
    $finder_list = [];
    foreach (self::getFinders() as $finder_class) {
      if (class_exists($finder_class)) {
        // dirty, i know...
        $finder_list[] = new $finder_class(NULL, NULL);
      }
    }
    return $finder_list;
  }

}
