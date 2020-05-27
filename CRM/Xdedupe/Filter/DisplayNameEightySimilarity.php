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
 * Implement a filter that excludes contacts that don't meet the string similarity
 */
class CRM_Xdedupe_Filter_DisplayNameEightySimilarity extends CRM_Xdedupe_Filter_Similarity
{

    public function __construct($alias, $params)
    {
        parent::__construct($alias, $params);
        $this->attributes = ['display_name'];
        $this->threshold  = 0.80;
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("(!) %1% %2 Similarity", [1 => (int)($this->threshold * 100), 2 => E::ts("Display Name")]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Remove contacts that don't have a similar %1", [1 => E::ts('display name')]);
    }
}
