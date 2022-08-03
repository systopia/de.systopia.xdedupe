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
 * Find people by last name
 */
class CRM_Xdedupe_Finder_OrganizationNameNoCase extends CRM_Xdedupe_Finder_OrganizationName
{

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Identical Organization Name (case insensitive)");
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Looks for fully identical organisation names, while ignoring upper/lower case differences. Keep in mind that this might already be the case depending on your DB's collation.");
    }

    /**
     * Add this finder's GROUP BY clauses to the list
     *
     * @param $groupbys array
     */
    public function addGROUPBYS(&$groupbys)
    {
        $groupbys[] = "LOWER(contact.organization_name)";
    }
}
