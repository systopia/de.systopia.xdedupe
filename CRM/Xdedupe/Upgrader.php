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
class CRM_Xdedupe_Upgrader extends CRM_Xdedupe_Upgrader_Base
{

    /**
     * Create XDedupe configuration table
     */
    public function install()
    {
        $this->executeSqlFile('sql/civicrm_xdedupe_configuration.sql');
    }


    /**
     * Version 0.5 comes with a DB table for the configrations
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
        return TRUE;
    }
}
