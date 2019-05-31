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
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
abstract class CRM_Xdedupe_Finder extends  CRM_Xdedupe_QueryPlugin {
  /**
   * Get a list of all available finder classes
   *
   * @return array list of class names
   */
  public static function getFinders() {
    // todo: use symfony
    return [
        'CRM_Xdedupe_Finder_Email',
        'CRM_Xdedupe_Finder_LastName',
        'CRM_Xdedupe_Finder_BirthDate',
        'CRM_Xdedupe_Finder_FirstName',
    ];
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getFinderList() {
    $finder_list = [];
    $finder_classes = self::getFinders();
    foreach ($finder_classes as $finder_class) {
      $finder = new $finder_class(null, null); // dirty, i know...
      $finder_list[$finder_class] = $finder->getName();
    }
    return $finder_list;
  }
}
