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
 * Implements a resolver for option value fields,
 *   selects the value with the lowest weight in the option group
 */
class CRM_Xdedupe_Resolver_Language extends CRM_Xdedupe_Resolver_OptionValueAttribute
{

    protected $option_group_name;

    public function __construct($merge)
    {
        parent::__construct($merge, 'preferred_language', 'languages');
    }

    /**
     * Get the attribute used as the value in CiviCRM. 'value' is default, obviously, but e.g. preferred_language stores 'name'
     * @return string the attribute name
     */
    protected function getValueAttribute()
    {
        return 'name';
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Preferred Communication Language");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Will pick the highest ranking language");
    }
}
