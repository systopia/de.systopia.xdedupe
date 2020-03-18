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
 * The "Xdedupe Manager" lets you control the various
 * configurations
 */
class CRM_Xdedupe_Page_Manager extends CRM_Core_Page
{

    public function run()
    {
        CRM_Utils_System::setTitle(E::ts('XDedupe Configuration Manager'));

        // first: process commands (if any)
        $this->processDeleteCommand();
        $this->processEnableDisableCommand();
        $this->processRearrangeCommand();


        // get configs
        $configurations = CRM_Xdedupe_Configuration::getAll();
        if (empty($configurations)) {
            // no configurations yet -> redirect to control room
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/xdedupe/controlroom', 'reset=1'));
        }

        // render the configurations
        $rendered_configurations = [];
        foreach ($configurations as $configuration) {
            $rendered_configurations[] = $this->renderConfiguration($configuration);
        }
        $this->assign('configs', $rendered_configurations);
        $this->assign('baseurl', CRM_Utils_System::url('civicrm/xdedupe/manage'));

        parent::run();
    }

    /**
     * Delete task
     */
    protected function processDeleteCommand()
    {
        $delete_id = CRM_Utils_Request::retrieve('delete', 'Integer');
        $confirmed = CRM_Utils_Request::retrieve('confirmed', 'Integer');
        if ($delete_id) {
            if ($confirmed) {
                CRM_Xdedupe_Configuration::delete($delete_id);
            } else {
                $task = CRM_Xdedupe_Configuration::get($delete_id);
                $this->assign('delete', $this->renderConfiguration($task));
            }
        }
    }

    /**
     * Process the 'enable' and 'disable' command
     */
    protected function processEnableDisableCommand()
    {
        foreach (['manual', 'automatic'] as $mode) {
            $enable_id  = CRM_Utils_Request::retrieve("enable_{$mode}", 'Integer');
            $disable_id = CRM_Utils_Request::retrieve("disable_{$mode}", 'Integer');

            if ($enable_id) {
                $configuration = CRM_Xdedupe_Configuration::get($enable_id);
                $configuration->setAttribute("is_{$mode}", 1);
                $configuration->store();
            }

            if ($disable_id) {
                $configuration = CRM_Xdedupe_Configuration::get($disable_id);
                $configuration->setAttribute("is_{$mode}", 0);
                $configuration->store();
            }
        }
    }

    /**
     * render the data representation of a task
     */
    protected function renderConfiguration($configuration)
    {
        $data = [
            'id'           => $configuration->getID(),
            'name'         => $configuration->getAttribute('name'),
            'description'  => $configuration->getAttribute('description'),
            'is_manual'    => $configuration->getAttribute('is_manual'),
            'is_automatic' => $configuration->getAttribute('is_automatic'),
        ];
        if (strlen($data['description']) > 64) {
            $data['short_desc'] = substr($data['description'], 0, 64) . '...';
        } else {
            $data['short_desc'] = $data['description'];
        }
        return $data;
    }

    /**
     * render a date
     */
    protected function renderDate($string)
    {
        if (empty($string)) {
            return E::ts('never');
        } else {
            return date('Y-m-d H:i:s', strtotime($string));
        }
    }

    /**
     * Process the order rearrangement commands
     */
    protected function processRearrangeCommand()
    {
        foreach (['top', 'up', 'down', 'bottom'] as $cmd) {
            $configuration_id = CRM_Utils_Request::retrieve($cmd, 'Integer');
            if (!$configuration_id) continue;

            $configuration_order = CRM_Xdedupe_Configuration::getAll();
            $original_order = $configuration_order;

            // find the task
            $index = FALSE;
            for ($i = 0; $i < count($configuration_order); $i++) {
                if ($configuration_order[$i]->getID() == $configuration_id) {
                    $index = $i;
                    break;
                }
            }

            if ($index !== FALSE) {
                switch ($cmd) {
                    case 'top':
                        $new_index = 0;
                        break;
                    case 'up':
                        $new_index = max(0, $index - 1);
                        break;
                    case 'down':
                        $new_index = min(count($configuration_order) - 1, $index + 1);
                        break;
                    default:
                    case 'bottom':
                        $new_index = count($configuration_order) - 1;
                        break;
                }
                // copied from https://stackoverflow.com/questions/12624153/move-an-array-element-to-a-new-index-in-php
                $out = array_splice($configuration_order, $index, 1);
                array_splice($configuration_order, $new_index, 0, $out);
            }

            // store the new task order
            if ($configuration_order != $original_order) {
                $weight = 10;
                foreach ($configuration_order as $task) {
                    $task->setAttribute('weight', $weight, TRUE);
                    $weight += 10;
                }
            }
        }
    }
}
