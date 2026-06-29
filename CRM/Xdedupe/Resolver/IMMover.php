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
class CRM_Xdedupe_Resolver_IMMover extends CRM_Xdedupe_Resolver_DetailMover {

  /**
   * @return string name
   */
  public function getName(): string {
    return E::ts('IM Mover');
  }

  /**
   * @return string name
   */
  public function getHelp(): string {
    return E::ts("Move instant messenger contacts to the main contact, unless they're duplicates");
  }

  /**
   * @inheritDoc
   */
  protected function getOneLiner(array $detail): string {
    $location_type = CRM_Xdedupe_Config::resolveLocationType($detail['location_type_id']);
    return "{$detail['name']} ({$location_type})";
  }

  /**
   * @inheritDoc
   */
  protected function getEntity(): string {
    return 'Im';
  }

  /**
   * @inheritDoc
   */
  protected function getFieldList(): array {
    return ['name', 'provider_id', 'location_type_id'];
  }

  /**
   * @inheritDoc
   */
  protected function detailsEqual(array $detail1, array $detail2): bool {
    return $detail1['name'] == $detail2['name']
      && $detail1['provider_id'] == $detail2['provider_id'];
  }

}
