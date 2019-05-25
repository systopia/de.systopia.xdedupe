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
 * Implement a "Filter", i.e. a class that will restrict the set of duplicates found
 */
abstract class CRM_Xdedupe_Filter extends  CRM_Xdedupe_QueryPlugin {

  /**
   * Get a list of all available finder classes
   *
   * @return array list of class names
   */
  public static function getFilters() {
    // todo: use symfony
    return [
        'CRM_Xdedupe_Filter_DedupeException'
    ];
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getFilterList() {
    $filter_list = [];
    $filter_classes = self::getFilters();
    foreach ($filter_classes as $filter_class) {
      $filter = new $filter_class(null, null); // dirty, i know...
      $filter_list[$filter_class] = $filter->getName();
    }
    return $filter_list;
  }
}
