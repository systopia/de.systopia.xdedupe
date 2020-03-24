<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2020 SYSTOPIA                            |
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
 * XDedupe run: run a single configuration or all
 */
function _civicrm_api3_xdedupe_run_spec(&$spec)
{
    $spec['cid'] = [
        'name'         => 'cid',
        'api.required' => 1,
        'type'         => CRM_Utils_Type::T_STRING,
        'title'        => 'Configuration ID',
        'description'  => "Either an configuration ID, a comma separated list of configuration IDs or 'scheduled' to run all scheduled configurations",
    ];
}

/**
 * API Specs:Xdedupe.run: run and merge a configuration
 *
 * @param array $params see specs
 * @return array result merge result
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_xdedupe_run($params)
{
    // first, check the cid parameter, to get the list of configurations to run
    if ($params['cid'] == 'scheduled') {
        $configs_to_run = CRM_Xdedupe_Configuration::getAllScheduled();
    } elseif (preg_match('/[0-9, ]+/', $params['cid'])) {
        $configs_to_run = [];
        foreach (explode(',', $params['cid']) as $cid) {
            $cid = (int) $cid;
            if ($cid) {
                $config = CRM_Xdedupe_Configuration::get($cid);
                if ($config) {
                    $configs_to_run[] = $config;
                }
            }
        }
    } else {
        return civicrm_api3_create_error("Invalid cid/list '{$params['cid']}'!");
    }

    $all_stats = [];
    foreach ($configs_to_run as $config_to_run) {
        try {
            $all_stats[] = $config_to_run->run($params);
        } catch (Exception $ex) {
            $config_id = $config_to_run->getID();
            return civicrm_api3_create_error("An exception occurred with configuration [{$config_id}]: " . $ex->getMessage());
        }
    }

    // combine stats
    $final_stats = [];
    foreach ($all_stats as $stats) {
        foreach ($stats as $key => $value) {
            if (is_numeric($value)) {
                $final_stats[$key] = $value + CRM_Utils_Array::value($key, $final_stats, 0);
            } elseif (is_array($value)) {
                $final_stats[$key] = $value + CRM_Utils_Array::value($key, $final_stats, []);
            }
        }
    }
    $final_stats['configs_executed'] = count($configs_to_run);
    return civicrm_api3_create_success($all_stats);
}

