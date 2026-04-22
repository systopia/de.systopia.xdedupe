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

/**
 * Implement a "Finder", i.e. a class that will identify potential dupes in the DB
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Finder_SanitisedAddress extends CRM_Xdedupe_Finder_Address {
// phpcs:enable
  protected array $filter_strings = [
    'street_address' => [',', ';', '-', ' ', "\\'", '.', '/'],
    'city'           => ['-', ' '],
  ];

  /**
   * CRM_Xdedupe_Finder_SanitisedAddress constructor.
   *
   * @param string|NULL $alias internal alias
   * @param array|NULL $params parameters
   * @param list<string> $address_fields address fields
   * @param array<string, list<string>>|NULL $filter_strings field_name => array of strings to be removed from the value
   */
  public function __construct(?string $alias, ?array $params, array $address_fields, ?array $filter_strings = NULL) {
    parent::__construct($alias, $params, $address_fields);
    if ($filter_strings !== NULL) {
      $this->filter_strings = $filter_strings;
    }
  }

  /**
   * @inheritDoc
   */
  public function addGROUPBYS(&$groupbys): void {
    foreach ($this->address_fields as $address_field) {
      // general group by is the address field
      $group_by = "$this->alias.$address_field";

      $strings_to_be_removed = $this->filter_strings[$address_field] ?? [];
      // but, we want to strip the special characters/strings
      foreach ($strings_to_be_removed as $filter_character) {
        $group_by = "REPLACE($group_by, '$filter_character', '')";
      }

      // finally: add to the group by list
      $groupbys[] = $group_by;
    }
  }

}
