<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2022 SYSTOPIA                            |
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
 * Implements a resolver for Organisation Name
 */
class CRM_Xdedupe_Resolver_OrganisationNameCleanup extends CRM_Xdedupe_Resolver_AttributeCleanup
{

    public function __construct($merge)
    {
        parent::__construct($merge, 'organization_name');
        $this->regular_expressions = [
            ['/\s+/', ' '], // remove multiple whitespaces
        ];
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Clean Organisation Name");
    }
}
