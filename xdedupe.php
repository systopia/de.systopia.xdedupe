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

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require_once 'xdedupe.civix.php';
// phpcs:enable

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function xdedupe_civicrm_config(\CRM_Core_Config $config): void {
  _xdedupe_civix_civicrm_config($config);

  Civi::dispatcher()->addSubscriber(new CRM_Xdedupe_Config());
}

/**
 * Make sure that the last_run column is not logged
 *
 * @param array<string, array<string, mixed>> $logTableSpec
 */
function xdedupe_civicrm_alterLogTables(&$logTableSpec): void {
  if (isset($logTableSpec['civicrm_xdedupe_configuration'])) {
    $logTableSpec['civicrm_xdedupe_configuration']['exceptions'] = ['last_run'];
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function xdedupe_civicrm_install(): void {
  _xdedupe_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function xdedupe_civicrm_enable(): void {
  _xdedupe_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
 * function xdedupe_civicrm_preProcess($formName, &$form) {
 *
 * } // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function xdedupe_civicrm_navigationMenu(&$menu): void {
  // add automation section
  if (!_xdedupe_menu_exists($menu, 'Administer/automation')) {
    _xdedupe_civix_insert_navigation_menu($menu, 'Administer', [
      'label' => E::ts('Automation'),
      'name' => 'automation',
      'url' => NULL,
      'permission' => 'administer CiviCRM',
      'operator' => NULL,
      'separator' => 0,
    ]);
  }

  _xdedupe_civix_insert_navigation_menu(
        $menu,
        'Administer/automation',
        [
          'label'      => E::ts('Extended Deduplication (X-Dedupe)'),
          'name'       => 'xdedupe_manage',
          'url'        => 'civicrm/xdedupe/manage',
          'permission' => 'administer CiviCRM',
          'operator'   => 'OR',
          'separator'  => 0,
        ]
    );
  _xdedupe_civix_navigationMenu($menu);
}

/**
 * Helper function to see if the menu item is already there
 *
 * @param array $menu current menu
 * @param string $path path to look for
 *
 * @return bool
 */
function _xdedupe_menu_exists(&$menu, $path): bool {
  // Find a recurse into the next level down
  $pathParts = explode('/', $path);
  $first = array_shift($pathParts);
  foreach ($menu as &$entry) {
    if ($entry['attributes']['name'] === $first) {
      if (count($pathParts) === 0) {
        return TRUE;
      }
      if (_xdedupe_menu_exists($entry['child'], implode('/', $pathParts))) {
        return TRUE;
      }
    }
  }
  return FALSE;
}
