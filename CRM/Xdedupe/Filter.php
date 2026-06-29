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
 * Implement a "Filter", i.e. a class that will restrict the set of duplicates found
 *
 *  You can either add criteria to the SQL finder query,
 *  AND/OR filter the resulting duplicates
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Filter extends CRM_Xdedupe_QueryPlugin {

  // phpcs:enable

  /**
   * Filter dedupe run, i.e. remove items that don't match the criteria
   *
   * @param CRM_Xdedupe_DedupeRun $run
   */
  public function purgeResults(CRM_Xdedupe_DedupeRun $run): void {}

  /**
   * Get a list of all available finder classes
   *
   * @return list<string> list of class names
   */
  public static function getFilters(): array {
    $filter_list = [];
    Civi::dispatcher()->dispatch(
      'civi.xdedupe.filters',
      GenericHookEvent::create(['list' => &$filter_list])
    );
    return $filter_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array<string, string> class => name
   */
  public static function getFilterList(): array {
    $filter_list = [];
    foreach (self::getFilterInstances() as $filter) {
      $filter_list[get_class($filter)] = $filter->getName();
    }
    return $filter_list;
  }

  /**
   * Get an instance of each finder
   *
   * @return array<CRM_Xdedupe_Filter>
   */
  public static function getFilterInstances(): array {
    $filter_list = [];
    foreach (self::getFilters() as $filter_class) {
      if (class_exists($filter_class)) {
        // dirty, i know...
        $filter_list[] = new $filter_class(NULL, NULL);
      }
    }
    return $filter_list;
  }

}
