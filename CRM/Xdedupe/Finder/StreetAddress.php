<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2021 SYSTOPIA                            |
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
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
class CRM_Xdedupe_Finder_StreetAddress extends CRM_Xdedupe_Finder_Address {

  public function __construct(?string $alias, ?array $params) {
    parent::__construct($alias, $params, ['street_address']);
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Street Address (only)');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Looks for identical street address (of any address)');
  }

}
