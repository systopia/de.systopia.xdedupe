<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2022 SYSTOPIA                            |
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
 */
class CRM_Xdedupe_Resolver_Addressee extends CRM_Xdedupe_Resolver {

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Main Addressee');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('In case of conflicts, keep the addressee of the main contact.');
  }

  /**
   * @inheritDoc
   */
  public function resolve(int $main_contact_id, array $other_contact_ids): bool {
    // get the main contact's addressee (this is somewhat shady through the api)
    $main_values = CRM_Core_DAO::executeQuery('
            SELECT addressee_id, addressee_custom
            FROM civicrm_contact
            WHERE id = %1', [1 => [$main_contact_id, 'Integer']]);
    $main_values->fetch();

    // set for the other contacts
    foreach ($other_contact_ids as $other_contact_id) {
      civicrm_api3('Contact', 'create', [
        'id' => $other_contact_id,
        // @phpstan-ignore property.notFound
        'addressee_id' => $main_values->addressee_id ?? '',
        // @phpstan-ignore property.notFound
        'addressee_custom' => $main_values->addressee_custom ?? '',
      ]);
      $this->getContext()?->unloadContact($other_contact_id);
    }
    return TRUE;
  }

}
