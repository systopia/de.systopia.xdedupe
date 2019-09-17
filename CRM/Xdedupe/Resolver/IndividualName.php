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
 * Implements a resolver for Organisation Name
 *   selects the longer name, and with more variety (upper/lower case)
 */
class CRM_Xdedupe_Resolver_IndividualName extends CRM_Xdedupe_Resolver {

  protected static $name_attributes = ['first_name', 'middle_name', 'last_name'];

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Main Individual Names");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("In case of conflicts, keep the first, middle, and last name of the main contact.");
  }

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array list of contact attributes
   */
  public function getContactAttributes() {
    return self::$name_attributes;
  }



  /**
   * Resolve the merge conflicts by editing the contact
   *
   * CAUTION: IT IS PARAMOUNT TO UNLOAD A CONTACT FROM THE CACHE IF CHANGED AS FOLLOWS:
   *  $this->merge->unloadContact($contact_id)
   *
   * @param $main_contact_id    int     the main contact ID
   * @param $other_contact_ids  array   other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public function resolve($main_contact_id, $other_contact_ids) {
    // set all names to the chosen one
    $main_contact = $this->getContext()->getContact($main_contact_id);

    foreach ($other_contact_ids as $contact_id) {
      $contact = $this->getContext()->getContact($contact_id);
      $contact_update = [];
      foreach (self::$name_attributes as $attribute) {
        $main_value    = CRM_Utils_Array::value($attribute, $main_contact, '');
        $contact_value = CRM_Utils_Array::value($attribute, $contact, '');
        if ($main_value != $contact_value) {
          $contact_update[$attribute] = $main_value;
        }
      }

      if (!empty($contact_update)) {
        $contact_update['id'] = $contact_id;
        civicrm_api3('Contact','create', $contact_update);
        $this->getContext()->unloadContact($contact_id);
      }
    }

    return TRUE;
  }
}
