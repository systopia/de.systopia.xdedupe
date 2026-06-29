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
 * Implements a resolver for option value fields,
 *   selects the value with the lowest weight in the option group
 */
class CRM_Xdedupe_Resolver_Language extends CRM_Xdedupe_Resolver_OptionValueAttribute {

  public function __construct(?CRM_Xdedupe_Merge $merge) {
    parent::__construct($merge, 'preferred_language', 'languages');
  }

  /**
   * @inheritDoc
   */
  protected function getValueAttribute(): string {
    return 'name';
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Preferred Communication Language');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Will pick the highest ranking language');
  }

}
