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
abstract class CRM_Xdedupe_Resolver_SimpleAttribute extends CRM_Xdedupe_Resolver {

  protected $attribute_name;

  public function __construct($merge, $attribute_name) {
    parent::__construct($merge);
    $this->attribute_name = $attribute_name;
  }

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array list of contact attributes
   */
  public function getContactAttributes() {
    return [$this->attribute_name];
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
    $main_contact  = $this->merge->getContact($main_contact_id);
    if (empty($main_contact[$this->attribute_name])) {
      // contact itself doesn't have it => pick one from the others
      $value = $this->getValueFromContacts($other_contact_ids);
      if ($value) {
        $this->unsetValueForContacts($other_contact_ids);
        $this->setValueForContacts([$main_contact_id], $value);
        return TRUE;
      } else {
        // the others don't have a value either => no conflict
        return FALSE;
      }
    } else {
      // main contact's attribute is set, delete the others
      return $this->unsetValueForContacts($other_contact_ids);
    }

    $other_contact = $this->merge->getContact($other_contact_id);
    // TODO: implement
    throw new Exception("IMPLEMENT ME");
  }

  /**
   * Set the given value for these contacts
   *
   * @param $contact_ids array  contact IDs
   * @param $value       string attribute value to set
   *
   * @return TRUE if a change was performed
   */
  protected function setValueForContacts($contact_ids, $value) {
    $change = FALSE;
    foreach ($contact_ids as $contact_id) {
      $contact = $this->merge->getContact($contact_id);
      $current_value = $this->getValueFromContacts([$contact_id]);
      if (!$this->isValueEqual($current_value, $value)) {
        // we need to set the value


      }
    }
  }

  /**
   * Check if the two values for this attribute are to be considered equal
   *
   * Override if needed.
   *
   * @param $value1 string value
   * @param $value2 string value
   */
  protected function isValueEqual($value1, $value2) {
    return $value1 == $value2;
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Select '%1'", [1 => $this->attribute_name]);
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Will resolve the '%1' attribute by simply taking the value in the following order: main contact, other contacts in increasing ID");
  }
}
