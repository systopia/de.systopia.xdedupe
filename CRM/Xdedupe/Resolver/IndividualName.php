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
class CRM_Xdedupe_Resolver_IndividualName extends CRM_Xdedupe_Resolver {

  /**
   * @var list<string>
   */
  protected static array $name_attributes = ['first_name', 'middle_name', 'last_name'];

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Main Individual Names');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('In case of conflicts, keep the first, middle, and last name of the main contact.');
  }

  /**
   * @inheritDoc
   */
  public function getContactAttributes(): array {
    return self::$name_attributes;
  }

  /**
   * @inheritDoc
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function resolve(int $main_contact_id, array $other_contact_ids): bool {
  // phpcs:enable
    // set all names to the chosen one
    $main_contact = $this->getContext()?->getContact($main_contact_id);

    foreach ($other_contact_ids as $contact_id) {
      $contact = $this->getContext()?->getContact($contact_id);
      $contact_update = [];
      foreach (self::$name_attributes as $attribute) {
        $main_value = $main_contact[$attribute] ?? '';
        $contact_value = $contact[$attribute] ?? '';
        if ($main_value != $contact_value) {
          $contact_update[$attribute] = $main_value;
        }
      }

      if (count($contact_update) > 0) {
        $contact_update['id'] = $contact_id;
        civicrm_api3('Contact', 'create', $contact_update);
        $this->addMergeDetail(
          E::ts(
            "Discarded name '%1' of contact [%2] in favour of '%3' in order to resolve merge conflicts",
            [
              1 => $this->renderName($contact ?? []),
              2 => $contact_id,
              3 => $this->renderName($main_contact ?? []),
            ]
          )
        );
        $this->getContext()?->unloadContact($contact_id);
      }
    }

    return TRUE;
  }

  /**
   * Create a textual representation of the contact's name
   *
   * @param array<string, string> $contact contact data
   *
   * @return string contact name
   */
  protected function renderName(array $contact): string {
    $name = '';
    foreach (self::$name_attributes as $attribute) {
      $name .= ' ' . ($contact[$attribute] ?? '');
    }
    return str_replace('/ +/', ' ', $name);
  }

}
