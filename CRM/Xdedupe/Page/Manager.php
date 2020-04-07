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
        $this->processAutomergeCommand();


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

        // render the statistics
        $rendered_stats = [];
        foreach ($configurations as $configuration) {
            $rendered_stats[$configuration->getID()] = $this->renderStats($configuration->getStats());
        }
        CRM_Core_Resources::singleton()->addVars('xdedeupe', ['stats' => $rendered_stats]);

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
     * Process Merge Run
     */
    protected function processAutomergeCommand()
    {
        $config_id = CRM_Utils_Request::retrieve('run', 'Integer');
        if ($config_id) {
            // load config ID
            $configuration = CRM_Xdedupe_Configuration::get($config_id);
            $config = $configuration->getConfig();

            // create + configure dedupe run
            $dedupe_run = $configuration->find();

            $configuration->setStats([
                'tuples_found'    => $dedupe_run->getTupleCount(),
                'finder_runtime'  => $dedupe_run->getFinderRuntime(),
                'merger_runtime'  => 0.0,
                'tuples_merged'   => 0,
                'contacts_merged' => 0,
                'last_run'        => date('YmdHis'),
                'type'            => 'manual',
                'aborted'         => 1,  // will be set to 0 at the end
            ], true);

            // and call the runner for the merge
            CRM_Xdedupe_MergeJob::launchMergeRunner(
                $dedupe_run->getID(),
                [
                    'force_merge' => empty($config['force_merge']) ? '0' : '1',
                    'resolvers'   => $config['auto_resolve'],
                    'pickers'     => $config['main_contact'],
                    'config_id'   => $configuration->getID(),
                ],
                CRM_Utils_System::url('civicrm/xdedupe/manage', 'reset=1')
            );
        }
    }



    /**
     * Process the 'enable' and 'disable' command
     */
    protected function processEnableDisableCommand()
    {
        foreach (['manual', 'automatic', 'scheduled'] as $mode) {
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
     *
     * @param CRM_Xdedupe_Configuration $configuration
     *   the configuration
     *
     * @return array
     *   data structure to be passed to the template engine
     */
    protected function renderConfiguration($configuration)
    {
        $data = [
            'id'           => $configuration->getID(),
            'name'         => $configuration->getAttribute('name'),
            'description'  => $configuration->getAttribute('description'),
            'is_manual'    => $configuration->getAttribute('is_manual'),
            'is_automatic' => $configuration->getAttribute('is_automatic'),
            'is_scheduled' => $configuration->getAttribute('is_scheduled'),
            'last_run'     => $this->renderDate(CRM_Utils_Array::value('last_run', $configuration->getStats())),
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
            return date('Y-m-d H:i', strtotime($string));
        }
    }

    /**
     * render stats
     */
    protected function renderStats($stats)
    {
        if (empty($stats)) {
            return E::ts("No statistics available");
        }

        // else: render a nice table
        $html = '<table class="xdedupe-stats-popup">';
        foreach ($stats as $key => $raw_value) {
            $value = $raw_value;
            switch ($key) {
                case 'last_run':
                    continue 2;

                case 'tuples_found':
                    $label = E::ts("Tuples Found");
                    break;

                case 'finder_runtime':
                    $label = E::ts("Runtime (Finder)");
                    $value = sprintf("%0.1fs", $raw_value);
                    break;

                case 'merger_runtime':
                    $label = E::ts("Runtime (Merger)");
                    $value = sprintf("%0.1fs", $raw_value);
                    break;

                case 'errors':
                    $label = E::ts("Errors");
                    if (empty($value)) {
                        $value = E::ts("none");
                    } else {
                        $value = implode(',', $value);
                    }
                    break;

                case 'failed':
                    $label = E::ts("Merge Failures");
                    if (empty($value)) {
                        $value = E::ts("none");
                    } else {
                        $value = implode(',', $value);
                    }
                    break;

                case 'tuples_merged':
                    $label = E::ts("Tuples Merged");
                    break;

                case 'contacts_merged':
                    $label = E::ts("Contacts Merged");
                    break;

                case 'conflicts_resolved':
                    $label = E::ts("Conflicts Resolved");
                    break;

                case 'type':
                    $label = E::ts("Run Type");
                    if ($value == 'scheduled') {
                        $value = E::ts("Scheduled");
                    } else {
                        $value = E::ts("Manual");
                    }
                    break;

                case 'aborted':
                    $label = E::ts("Was aborted?");
                    if (empty($value)) {
                        $value = E::ts("No");
                    } else {
                        $value = E::ts("Yes");
                    }
                    break;

                default:
                    $label = $key;
            }
            $html .= "<tr><td>{$label}</td><td>{$value}</td></tr>";
        }
        $html .= '</table>';
        return $html;
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
