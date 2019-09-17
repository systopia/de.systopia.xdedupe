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

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Implements a resolver for basic contact fields
 */
class CRM_Xdedupe_Resolver_Privacy extends CRM_Xdedupe_Resolver {

  protected static $privacy_attributes = ['do_not_email', 'do_not_phone', 'do_not_mail', 'do_not_sms', 'do_not_trade', 'is_opt_out'];

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array list of contact attributes
   */
  public function getContactAttributes() {
    return self::$privacy_attributes;
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Privacy");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Conservative resolves the contacts' privacy settings, i.e. preserve all opt-outs.");
  }

  /**
   * Resolve the privacy conflicts by maintaining any opt-opt-outs
   *
   * @param $main_contact_id    int     the main contact ID
   * @param $other_contact_ids  array   other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public function resolve($main_contact_id, $other_contact_ids) {
    $combined_settings = [];
    $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);

    // combine the values
    foreach (self::$privacy_attributes as $attribute) {
      $combined_settings[$attribute] = 0;
      foreach ($all_contact_ids as $contact_id) {
        $contact       = $this->getContext()->getContact($contact_id);
        $current_value = CRM_Utils_Array::value($attribute, $contact, 0);
        if (!empty($current_value)) {
          $combined_settings[$attribute] = 1;
        }
      }
    }

    // now update all contacts
    foreach ($all_contact_ids as $contact_id) {
      $contact = $this->getContext()->getContact($contact_id);
      $contact_update  = [];
      foreach (self::$privacy_attributes as $attribute) {
        $contact_value = CRM_Utils_Array::value($attribute, $contact, 0);
        if ($contact_value != $combined_settings[$attribute]) {
          $contact_update[$attribute] = $combined_settings[$attribute];
        }
      }

      // update contacts
      if (!empty($contact_update)) {
        $contact_update['id'] = $contact_id;
        civicrm_api3('Contact', 'create', $contact_update);
        $this->getContext()->unloadContact($contact_id);
      }
    }

    return TRUE;
  }
}
