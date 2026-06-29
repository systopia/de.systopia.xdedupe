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
 * Implements a resolver for Organisation Name
 *   selects the longer name, and with more variety (upper/lower case)
 */
class CRM_Xdedupe_Resolver_OrganisationNameLongest extends CRM_Xdedupe_Resolver_SimpleAttribute {

  public function __construct(?CRM_Xdedupe_Merge $merge) {
    parent::__construct($merge, 'organization_name');
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Longest Organisation Name');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Will resolve the Organization Name by selecting the longer one, with better formatting.');
  }

  /**
   * @inheritDoc
   */
  public function resolve($main_contact_id, $other_contact_ids): bool {
    // set all names to the chosen one
    return $this->resolveTheGreatEqualiser($main_contact_id, $other_contact_ids);
  }

  /**
   * @inheritDoc
   */
  protected function getValueRating($value, $contact_ids, $main_contact_id): int {
    // pick the one used by most contacts, and of multiple ones, pick the longest
    return strlen($value) + (count($contact_ids) * 1000);
  }

}
