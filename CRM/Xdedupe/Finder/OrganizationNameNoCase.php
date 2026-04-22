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

declare(strict_types = 1);

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Find people by last name
 */
class CRM_Xdedupe_Finder_OrganizationNameNoCase extends CRM_Xdedupe_Finder_OrganizationName {

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Identical Organization Name (case insensitive)');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    // phpcs:ignore Generic.Files.LineLength.TooLong
    return E::ts("Looks for fully identical organisation names, while ignoring upper/lower case differences. Keep in mind that this might already be the case depending on your DB's collation.");
  }

  /**
   * @inheritDoc
   */
  public function addGROUPBYS(&$groupbys): void {
    $groupbys[] = 'LOWER(contact.organization_name)';
  }

}
