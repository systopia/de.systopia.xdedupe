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
abstract class CRM_Xdedupe_Finder extends CRM_Xdedupe_QueryPlugin
{
    /**
     * Get a list of all available finder classes
     *
     * @return array list of class names
     */
    public static function getFinders()
    {
        $finder_list = [];
        \Civi::dispatcher()->dispatch(
            'civi.xdedupe.finders',
            \Civi\Core\Event\GenericHookEvent::create(['list' => &$finder_list])
        );
        return $finder_list;
    }

    /**
     * Get a list of all available finder classes
     *
     * @return array class => name
     */
    public static function getFinderList()
    {
        $finder_list      = [];
        $finder_instances = self::getFinderInstances();
        foreach ($finder_instances as $finder) {
            $finder_list[get_class($finder)] = $finder->getName();
        }
        return $finder_list;
    }

    /**
     * Get an instance of each finder
     */
    public static function getFinderInstances()
    {
        $finder_list    = [];
        $finder_classes = self::getFinders();
        foreach ($finder_classes as $finder_class) {
            if (class_exists($finder_class)) {
                $finder_list[] = new $finder_class(null, null); // dirty, i know...
            }
        }
        return $finder_list;
    }
}
