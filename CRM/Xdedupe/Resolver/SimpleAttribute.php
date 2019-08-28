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
   * Get a human-readable attribute name
   */
  public function getAttributeName() {
    return $this->attribute_name;
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
   * Resolve the merge conflicts setting the winning attribute to the main contact
   *  and everybody else to NULL
   *
   * @param $main_contact_id    int     the main contact ID
   * @param $other_contact_ids  array   other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public function resolveKingOfTheHill($main_contact_id, $other_contact_ids) {
    $main_contact  = $this->getContext()->getContact($main_contact_id);
    $all_contact_ids = array_merge([$main_contact_id], $other_contact_ids);
    $all_values = $this->getDistinctValuesFromContacts($all_contact_ids);
    if (count($all_values) > 1) {
      $value = $this->getBestValue($all_values, $main_contact_id);
      $change = FALSE;
      $change |= $this->unsetValueForContacts($other_contact_ids);
      $change |= $this->setValueForContacts([$main_contact_id], $value);
      return $change;

    } else {
      // nothing to do
      return FALSE;
    }
  }

  /**
   * Resolve the merge conflicts setting the winning attribute to every contact
   *
   * @param $main_contact_id    int     the main contact ID
   * @param $other_contact_ids  array   other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public function resolveTheGreatEqualiser($main_contact_id, $other_contact_ids) {
    $all_contact_ids = array_merge([$main_contact_id], $other_contact_ids);
    $all_values = $this->getDistinctValuesFromContacts($all_contact_ids);
    if (count($all_values) > 1) {
      $value = $this->getBestValue($all_values, $main_contact_id);
      return $this->setValueForContacts($all_contact_ids, $value);

    } else {
      // nothing to do
      return FALSE;
    }
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
      $current_value = $this->getValueFromContacts([$contact_id]);
      if (!$this->isValueEqual($current_value, $value)) {
        // we need to set the value
        civicrm_api3('Contact', 'create', [
            'id'                  => $contact_id,
            $this->attribute_name => $value
        ]);
        $this->addMergeDetail(E::ts("Changed '%1' from '%2' to '%3' in contact [%4] to avoid merge conflicts", [
            1 => $this->getAttributeName(),
            2 => $current_value,
            3 => $value,
            4 => $contact_id]));
        $change = TRUE;
        $this->getContext()->unloadContact($contact_id);
      }
    }
    return $change;
  }

  /**
   * Get the first non-empty value from the given contacts
   *
   * @param $contact_ids array contact_ids
   * @return string first attribute value
   */
  protected function getValueFromContacts($contact_ids) {
    foreach ($contact_ids as $contact_id) {
      $contact = $this->getContext()->getContact($contact_id);
      if (!empty($contact[$this->attribute_name])) {
        return $contact[$this->attribute_name];
      }
    }
    // no attribute found?
    return '';
  }

  /**
   * Get the all non-empty values from the given contacts
   *
   * @param $contact_ids array contact_ids
   * @return             array list off different values for the attribute
   */
  protected function getValuesFromContacts($contact_ids) {
    $values = [];
    foreach ($contact_ids as $contact_id) {
      $contact = $this->getContext()->getContact($contact_id);
      if (isset($contact[$this->attribute_name])) {
        $value = $contact[$this->attribute_name];
        if (!in_array($value, $values)) {
          $values[] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * Get the different values from the contacts in the contact list
   *
   * @param $contact_ids array contact_ids
   * @return             array value => [contact IDs]
   */
  protected function getDistinctValuesFromContacts($contact_ids) {
    $values = [];
    foreach ($contact_ids as $contact_id) {
      $contact = $this->getContext()->getContact($contact_id);
      $value = CRM_Utils_Array::value($this->attribute_name, $contact);
      if (!$this->isValueEmpty($value)) {
        $values[$value][] = $contact_id;
      }
    }
    return $values;
  }

  /**
   * Define whether the given value is considered empty
   *
   * @param $value string the value
   * @return boolean is this value empty
   */
  protected function isValueEmpty($value) {
    return $value === NULL || $value === '';
  }

  /**
   * Unset the given value for these contacts
   *
   * @param $contact_ids array  contact IDs
   *
   * @return TRUE if a change was performed
   * @throws CiviCRM_API3_Exception
   */
  protected function unsetValueForContacts($contact_ids) {
    $change = FALSE;
    foreach ($contact_ids as $contact_id) {
      $current_value = $this->getValueFromContacts([$contact_id]);
      if ($current_value) {
        // we need to unset the value
        $change = TRUE;
        civicrm_api3('Contact', 'create', [
            'id'                  => $contact_id,
            $this->attribute_name => '']);
        $this->addMergeDetail(E::ts("Cleared '%1' value '%2' in contact [%3] to avoid merge conflicts", [
            1 => $this->getAttributeName(),
            2 => $current_value,
            3 => $contact_id]));
        $this->getContext()->unloadContact($contact_id);
      }
    }
    return $change;
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
   * Get a value to represent the best of those values.
   *  The returned value does not have to be contained in the given values
   *
   * @param $values          array value => [contact IDs] - the values, and which contact they're used by
   * @param $main_contact_id int   the main contact ID (in case of doubt)
   * @return string the resulting value
   */
  protected function getBestValue($values, $main_contact_id) {
    // default implementation: pick the highest valued one
    $winning_rating = PHP_INT_MIN;
    $winning_value  = NULL;
    foreach ($values as $value => $contact_ids) {
      $rating = $this->getValueRating($value, $contact_ids, $main_contact_id);
      if ($rating > $winning_rating) {
        $winning_rating = $rating;
        $winning_value = $value;
      }
    }
    return $winning_value;
  }

  /**
   * Rate the given value, meant to be overwritten.
   *
   * Default implementation: pick the main contact's one
   *
   * @param $value            string value to be rated
   * @param $contact_ids      array list of contact_ids using it
   * @param $main_contact_id
   * @return int rating -> the higher the better
   */
  protected function getValueRating($value, $contact_ids, $main_contact_id) {
    if (in_array($main_contact_id, $contact_ids)) {
      return 1;
    } else {
      return 0;
    }
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Select '%1'", [1 => $this->getAttributeName()]);
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Will resolve the '%1' attribute by simply taking the value in the following order: main contact, other contacts in increasing ID");
  }
}
