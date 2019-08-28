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
 * Implements a resolver to move contact details (emails, phones, etc)
 */
abstract class CRM_Xdedupe_Resolver_DetailMover extends CRM_Xdedupe_Resolver {

  /**
   * Get the entity name
   * @return string
   */
  protected abstract function getEntity();

  /**
   * Get the list of relevant fields for this entity
   * @return array
   */
  protected abstract function getFieldList();

  /**
   * Get a one-line representation of the detail data
   *
   * @param $detail array detail data
   * @return string
   */
  protected abstract function getOneLiner($detail);

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
    $changes = FALSE;
    $details = $this->getDetails(array_merge([$main_contact_id], $other_contact_ids));
    $main_details = $details[$main_contact_id];
    foreach ($other_contact_ids as $other_contact_id) {
      foreach ($details[$other_contact_id] as $detail) {
        if ($this->isDetailPresent($detail, $main_details)) {
          // this detail already exists => DELETE
          $this->deleteDetail($detail);
          $changes = TRUE;

        } else {
          // this detail does not yet exist => MOVE
          $this->moveDetail($detail, $main_contact_id);
          $changes = TRUE;

        }
      }
    }
    return $changes;
  }

  /**
   * Check if the given detail is contained by the detail list
   *
   * @param $detail       array  detail data
   * @param $main_details array  array of detail data
   *
   * @return boolean
   */
  protected function isDetailPresent($detail, $main_details) {
    foreach ($main_details as $main_detail) {
      if ($this->detailsEqual($detail, $main_detail)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Are these two details identical?
   *
   * @param $detail1 array detail data
   * @param $detail2 array detail data

   * @return boolean
   */
  protected function detailsEqual($detail1, $detail2) {
    $attributes = $this->getFieldList();
    foreach ($attributes as $attribute) {
      $value1 = CRM_Utils_Array::value($attribute, $detail1);
      $value2 = CRM_Utils_Array::value($attribute, $detail2);
      if ($value1 != $value2) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the list of details currenty present for the given contacts
   *
   * @param $contact_ids array contact IDs
   * @return  array contact ID => list of details
   */
  protected function getDetails($contact_ids) {
    // prepare return structure
    $details_by_contact = [];
    foreach ($contact_ids as $contact_id) {
      $details_by_contact[$contact_id] = [];
    }

    // query all details
    $query = civicrm_api3($this->getEntity(), 'get', [
        'contact_id'   => ['IN' => $contact_ids],
        'option.limit' => 0,
        'return'       => 'id,contact_id,' . implode(',', $this->getFieldList())
    ]);
    foreach ($query['values'] as $detail) {
      $details_by_contact[$detail['contact_id']][] = $detail;
    }
    return $details_by_contact;
  }

  /**
   * Delete the given detail
   * @param $detail array detail data, including id
   */
  protected function deleteDetail($detail) {
    civicrm_api3($this->getEntity(), 'delete', ['id' => $detail['id']]);
    $this->addMergeDetail(E::ts("Deleted %1 [%2] ('%4') from contact [%3] to avoid merge conflicts", [
        1 => $this->getEntity(),
        2 => $detail['id'],
        3 => $detail['contact_id'],
        4 => $this->getOneLiner($detail)]));
  }

  /**
   * Move the given detail to the contact ID
   * @param $detail array detail data, including id
   * @param $contact_id int target contact
   */
  protected function moveDetail($detail, $contact_id) {
    civicrm_api3($this->getEntity(), 'create', [
        'id'         => $detail['id'],
        'contact_id' => $contact_id,
        'is_primary' => 0
    ]);
    $this->addMergeDetail(E::ts("Moved %1 [%2] from contact [%3] to contact [%4] to avoid merge conflicts", [
        1 => $this->getEntity(),
        2 => $detail['id'],
        3 => $detail['contact_id'],
        4 => $contact_id]));
  }
}
