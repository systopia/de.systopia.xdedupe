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
 * Implements a resolver to move contact details (emails, phones, etc)
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Resolver_DetailMover extends CRM_Xdedupe_Resolver {

  // phpcs:enable

  /**
   * Get the entity name
   *
   * @return string
   */
  abstract protected function getEntity(): string;

  /**
   * Get the list of relevant fields for this entity
   *
   * @return list<string>
   */
  abstract protected function getFieldList(): array;

  /**
   * Get a one-line representation of the detail data
   *
   * @param array<string, mixed> $detail detail data
   *
   * @return string
   */
  abstract protected function getOneLiner(array $detail): string;

  /**
   * @inheritDoc
   */
  public function resolve(int $main_contact_id, array $other_contact_ids): bool {
    $changes = FALSE;
    $details = $this->getDetails(array_merge([$main_contact_id], $other_contact_ids));
    $main_details = $details[$main_contact_id];
    foreach ($other_contact_ids as $other_contact_id) {
      foreach ($details[$other_contact_id] as $detail) {
        if ($this->isDetailPresent($detail, $main_details)) {
          // this detail already exists => DELETE
          $this->deleteDetail($detail);
          $changes = TRUE;
        }
        else {
          // this detail does not yet exist => MOVE
          $this->moveDetail($detail, $main_contact_id);
          $changes = TRUE;
        }
      }
    }
    return $changes;
  }

  /**
   * Check if the detail list contains the given detail
   *
   * @param array $detail detail data
   * @param array $main_details array of detail data
   *
   * @return bool
   */
  protected function isDetailPresent(array $detail, array $main_details): bool {
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
   * @param array<string, mixed> $detail1 detail data
   * @param array<string, mixed> $detail2 detail data
   *
   * @return boolean
   */
  protected function detailsEqual(array $detail1, array $detail2): bool {
    foreach ($this->getFieldList() as $attribute) {
      $value1 = $detail1[$attribute] ?? NULL;
      $value2 = $detail2[$attribute] ?? NULL;
      if ($value1 != $value2) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the list of details currenty present for the given contacts
   *
   * @param list<int> $contact_ids contact IDs
   *
   * @return array contact ID => list of details
   */
  protected function getDetails(array $contact_ids): array {
    // prepare return structure
    $details_by_contact = [];
    foreach ($contact_ids as $contact_id) {
      $details_by_contact[$contact_id] = [];
    }

    // query all details
    $query = civicrm_api3(
      $this->getEntity(),
      'get',
      [
        'contact_id' => ['IN' => $contact_ids],
        'option.limit' => 0,
        'return' => 'id,contact_id,' . implode(',', $this->getFieldList()),
      ]
    );
    foreach ($query['values'] as $detail) {
      $details_by_contact[$detail['contact_id']][] = $detail;
    }
    return $details_by_contact;
  }

  /**
   * Delete the given detail
   *
   * @param array $detail detail data, including id
   */
  protected function deleteDetail(array $detail): void {
    civicrm_api3($this->getEntity(), 'delete', ['id' => $detail['id']]);
    $this->addMergeDetail(
      E::ts(
        "Deleted duplicate %1 [%2] ('%4') from contact [%3] to avoid merge conflicts",
        [
          1 => $this->getEntity(),
          2 => $detail['id'],
          3 => $detail['contact_id'],
          4 => $this->getOneLiner($detail),
        ]
      )
    );
  }

  /**
   * Move the given detail to the contact ID
   *
   * @param array $detail detail data, including id
   * @param int $contact_id target contact
   */
  protected function moveDetail(array $detail, int $contact_id): void {
    civicrm_api3(
      $this->getEntity(),
      'create',
      [
        'id' => $detail['id'],
        'contact_id' => $contact_id,
        'is_primary' => 0,
      ]
    );
    $this->addMergeDetail(
      E::ts(
        'Moved %1 [%2] from contact [%3] to contact [%4] to avoid merge conflicts',
        [
          1 => $this->getEntity(),
          2 => $detail['id'],
          3 => $detail['contact_id'],
          4 => $contact_id,
        ]
      )
    );
  }

}
