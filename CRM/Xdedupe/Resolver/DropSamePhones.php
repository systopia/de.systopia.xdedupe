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
 * Drop phone numbers with the duplicates if they are numerically the same
 */
class CRM_Xdedupe_Resolver_DropSamePhones extends CRM_Xdedupe_Resolver {

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Drop Same Phones');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Will drop phones that are numerically the same.');
  }

  /**
   * @inheritDoc
   */
  public function resolve(int $main_contact_id, array $other_contact_ids): bool {
    $changes = FALSE;

    // get main contact's phones
    $main_contact_phones = $this->getContactPhones($main_contact_id);

    // compare to the other contacts:
    foreach ($other_contact_ids as $other_contact_id) {
      $other_contact_phones = $this->getContactPhones($other_contact_id);
      foreach ($other_contact_phones as $phone_key => $phone) {
        if (isset($main_contact_phones[$phone_key])) {
          // the main contact has the same phone with the same key
          $main_contact_phone = $main_contact_phones[$phone_key];
          if ($main_contact_phone['phone'] != $phone['phone']) {
            // if these phones numerically the same, but not literally: that trips up the merger, so we delete
            civicrm_api3('Phone', 'delete', ['id' => $phone['id']]);
            $this->addMergeDetail(
            E::ts(
            "Deleted duplicate phone [%1] ('%3') from contact [%2] to avoid merge conflicts",
            [
              1 => $phone['id'],
              2 => $phone['contact_id'],
              3 => $phone['phone'],
            ]
            )
            );
            $changes = TRUE;
          }
        }
      }
    }

    return $changes;
  }

  /**
   * Get the given contact's phone records
   *
   * @param int $contact_id contact ID
   *
   * @return array key (numeric-location_type_id) => phone data
   */
  protected function getContactPhones(int $contact_id) {
    $phones = [];
    $query  = civicrm_api3(
        'Phone',
        'get',
        [
          'contact_id'   => $contact_id,
          'option.limit' => 0,
          'return'       => 'contact_id,phone_numeric,id,location_type_id,phone',
        ]
    );
    foreach ($query['values'] as $phone) {
      $phones["{$phone['phone_numeric']}-{$phone['location_type_id']}"] = $phone;
    }
    return $phones;
  }

}
