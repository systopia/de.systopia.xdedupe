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
class CRM_Xdedupe_Resolver_WebsiteMover extends CRM_Xdedupe_Resolver_DetailMover {

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName(): string {
    return E::ts('Website Mover');
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp(): string {
    return E::ts("Move all websites to the main contact, unless they're duplicates");
  }

  /**
   * Get a one-line representation of the detail data
   *
   * @param $detail array detail data
   * @return string
   */
  protected function getOneLiner($detail): string {
    return "{$detail['url']} ({$detail['website_type_id']})";
  }

  /**
   * Get the entity name
   * @return string
   */
  protected function getEntity(): string {
    return 'Website';
  }

  /**
   * Get the list of relevant fields for this entity
   * @return array
   */
  protected function getFieldList(): array {
    return ['url', 'website_type_id'];
  }

}
