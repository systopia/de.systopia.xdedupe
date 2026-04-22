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
 * Implements a resolver to move contact details (emails, phones, etc)
 */
class CRM_Xdedupe_Resolver_PhoneMover extends CRM_Xdedupe_Resolver_DetailMover {

  /**
   * @return string name
   */
  public function getName(): string {
    return E::ts('Phone Mover');
  }

  /**
   * @return string name
   */
  public function getHelp(): string {
    return E::ts("Move all phone numbers to the main contact, unless they're duplicates");
  }

  /**
   * @inheritDoc
   */
  protected function getEntity(): string {
    return 'Phone';
  }

  /**
   * @inheritDoc
   */
  protected function getOneLiner(array $detail): string {
    $location_type = CRM_Xdedupe_Config::resolveLocationType($detail['location_type_id']);
    return "{$detail['phone']} ({$location_type})";
  }

  /**
   * @inheritDoc
   */
  protected function getFieldList(): array {
    return ['phone_numeric', 'location_type_id', 'phone_type_id'];
  }

  /**
   * @inheritDoc
   */
  protected function detailsEqual(array $detail1, array $detail2): bool {
    return $detail1['phone_numeric'] == $detail2['phone_numeric']
      && $detail1['phone_type_id'] == $detail2['phone_type_id'];
  }

}
