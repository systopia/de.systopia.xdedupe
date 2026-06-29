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
class CRM_Xdedupe_Finder_PhoneNumeric extends CRM_Xdedupe_Finder_Phone {

  public function __construct(?string $alias, ?array $params) {
    parent::__construct($alias, $params, 'phone_numeric');
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Phone (numeric)');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    // phpcs:ignore Generic.Files.LineLength.TooLong
    return E::ts("Finds contacts where all the digits of the phone number are equal. Formatting characters like '+', '-', or '/' are ignored.");
  }

}
