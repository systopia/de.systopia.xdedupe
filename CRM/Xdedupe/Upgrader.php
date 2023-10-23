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
 * Collection of upgrade steps.
 */
class CRM_Xdedupe_Upgrader extends CRM_Extension_Upgrader_Base
{

    /**
     * Create XDedupe configuration table
     */
    public function install()
    {
        $this->executeSqlFile('sql/civicrm_xdedupe_configuration.sql');
    }


    /**
     * Version 0.5 comes with a DB table for the configurations
     *
     * @return boolean
     *    TRUE on success
     * @throws Exception
     *    if something goes wrong
     */
    public function upgrade_0500()
    {
        $this->ctx->log->info('Creating configuration DB table');
        $this->executeSqlFile('sql/civicrm_xdedupe_configuration.sql');
        return true;
    }

    /**
     * Version 0.5 also comes with the scheduled job
     *
     * @return boolean
     *    TRUE on success
     * @throws Exception
     *    if something goes wrong
     */
    public function upgrade_0501()
    {
        $this->ctx->log->info('Configuring Scheduled Job');
        civicrm_api3(
            'Job',
            'create',
            [
                'run_frequency' => 'Daily',
                'api_entity'    => 'Xdedupe',
                'api_action'    => 'run',
                'name'          => E::ts("Scheduled Deduplication (X-Dedupe)"),
                'description'   => E::ts(
                    "Runs all X-Dedupe configurations that have been scheduled for automatic execution."
                ),
                'parameters'    => 'cid=scheduled',
                'is_active'     => 1,
            ]
        );
        return true;
    }

    /**
     * Make sure the new table is known to logging
     *
     * @return boolean
     *    TRUE on success
     * @throws Exception
     *    if something goes wrong
     */
    public function upgrade_0502()
    {
        $this->ctx->log->info('Registering new table to logging');
        $logging = new CRM_Logging_Schema();
        $logging->fixSchemaDifferences();
        return true;
    }

}
