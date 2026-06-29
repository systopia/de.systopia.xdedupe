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
 * Implements a resolver for the contact source
 */
class CRM_Xdedupe_Resolver_Source extends CRM_Xdedupe_Resolver_SimpleAttribute {

  public function __construct(?CRM_Xdedupe_Merge $merge) {
    parent::__construct($merge, 'source');
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Main Source');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('In case of conflicts, keep the source of the main contact.');
  }

  /**
   * @inheritDoc
   */
  public function resolve($main_contact_id, $other_contact_ids): bool {
    // set all names to the chosen one
    return $this->resolveTheGreatEqualiser($main_contact_id, $other_contact_ids);
  }

  /**
   * REMARK: Overwritten: API does not return source (in some versions)
   *
   * @inheritDoc
   */
  protected function getDistinctValuesFromContacts($contact_ids): array {
    $values = [];
    $contact_id_list = implode(',', $contact_ids);
    if ($contact_id_list !== '') {
      $query = CRM_Core_DAO::executeQuery(
        "SELECT source AS source, id AS contact_id FROM civicrm_contact WHERE id IN ($contact_id_list)"
      );
      while ($query->fetch()) {
        // @phpstan-ignore property.notFound
        if (isset($values[$query->source])) {
          // @phpstan-ignore property.notFound
          $values[$query->source][] = $query->contact_id;
        }
        else {
          // @phpstan-ignore property.notFound, property.notFound
          $values[$query->source] = [$query->contact_id];
        }
      }
    }
    return $values;
  }

}
