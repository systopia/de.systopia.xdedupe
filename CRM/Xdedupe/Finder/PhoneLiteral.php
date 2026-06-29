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

declare(strict_types = 1);

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Filter by the stripped, purely numeric phone number
 */
class CRM_Xdedupe_Finder_PhoneLiteral extends CRM_Xdedupe_Finder_Phone {

  public function __construct(?string $alias, ?array $params) {
    parent::__construct($alias, $params, 'phone');
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Phone (literal)');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Looks for phone number entries (without extensions) that are 100% identical as strings');
  }

}
