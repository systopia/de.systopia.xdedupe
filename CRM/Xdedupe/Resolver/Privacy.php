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
 * Implements a resolver for basic contact fields
 */
class CRM_Xdedupe_Resolver_Privacy extends CRM_Xdedupe_Resolver {

  /**
   * @var list<string>
   */
  protected static array $privacy_attributes = [
    'do_not_email',
    'do_not_phone',
    'do_not_mail',
    'do_not_sms',
    'do_not_trade',
    'is_opt_out',
  ];

  /**
   * @inheritDoc
   */
  public function getContactAttributes(): array {
    return self::$privacy_attributes;
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Privacy');
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts("Conservative resolves the contacts' privacy settings, i.e. preserve all opt-outs.");
  }

  /**
   * @inheritDoc
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function resolve(int $main_contact_id, array $other_contact_ids): bool {
  // phpcs:enable
    $combined_settings = [];
    $copied_opt_outs = [];
    $main_contact = $this->getContext()?->getContact($main_contact_id);
    $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);

    // combine the values
    foreach (self::$privacy_attributes as $attribute) {
      $combined_settings[$attribute] = 0;
      foreach ($all_contact_ids as $contact_id) {
        $contact = $this->getContext()?->getContact($contact_id);
        $other_value = $contact[$attribute] ?? 0;
        if ($other_value !== 0) {
          $combined_settings[$attribute] = 1;
          if (!isset($main_contact[$attribute])) {
            // this opt_out will override to the main contact's
            $copied_opt_outs[$attribute][] = $contact_id;
          }
        }
      }
    }

    // now update all contacts
    foreach ($all_contact_ids as $contact_id) {
      $contact = $this->getContext()?->getContact($contact_id);
      $contact_update = [];
      foreach (self::$privacy_attributes as $attribute) {
        $contact_value = $contact[$attribute] ?? 0;
        if ($contact_value != $combined_settings[$attribute]) {
          $contact_update[$attribute] = $combined_settings[$attribute];
        }
      }

      // update contacts
      if (count($contact_update) > 0) {
        $contact_update['id'] = $contact_id;
        civicrm_api3('Contact', 'create', $contact_update);
        $this->getContext()?->unloadContact($contact_id);
      }
    }

    // add detailed text
    foreach ($copied_opt_outs as $attribute => $contact_ids) {
      $contact_list = '[' . implode('], [', $contact_ids) . ']';
      $this->addMergeDetail(E::ts('Inherited %1 from contact(s): %2', [1 => $attribute, 2 => $contact_list]));
    }

    return TRUE;
  }

}
