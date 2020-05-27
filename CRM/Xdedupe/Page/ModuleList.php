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

class CRM_Xdedupe_Page_ModuleList extends CRM_Core_Page
{

    public function run()
    {
        // set title
        CRM_Utils_System::setTitle(E::ts('X-Dedupe Module Documentation'));

        // add data
        $this->assignModules('finders', CRM_Xdedupe_Finder::getFinderInstances());
        $this->assignModules('filters', CRM_Xdedupe_Filter::getFilterInstances());
        $this->assignModules('pickers', CRM_Xdedupe_Picker::getPickerInstances());
        $this->assignModules('resolvers', CRM_Xdedupe_Resolver::getResolverInstances());

        parent::run();
    }

    /**
     * Assign modules info to the page
     *
     * @param $variable string smarty variable name
     * @param $modules  array  list of module instances
     */
    protected function assignModules($variable, $modules)
    {
        $module_data = [];
        foreach ($modules as $module) {
            $module_data[] = [
                'class' => get_class($module),
                'name'  => $module->getName(),
                'help'  => $module->getHelp()
            ];
        }
        $this->assign($variable, $module_data);
    }
}
