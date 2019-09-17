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
 * Implement a "ContactPicker", i.e. a class that will identify the main contact from a list of contacts
 */
class CRM_Xdedupe_Picker_Activities extends CRM_Xdedupe_Picker {

  protected $include_activity_ids = NULL;
  protected $exclude_activity_ids = NULL;

  public function __construct($include_activity_ids = NULL, $exclude_activity_ids = NULL) {
    $this->include_activity_ids = $include_activity_ids;
    $this->exclude_activity_ids = $exclude_activity_ids;
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Most Activities");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Picks the contact with the most activities");
  }

  /**
   * Select the main contact from a set of contacts
   *
   * @param $contact_ids array list of contact IDs
   * @return int|null one of the contacts in the list. null means "can't decide"
   */
  public function selectMainContact($contact_ids) {
    $where_clauses = [];
    if (!empty($this->include_activity_ids)) {
      $id_list = implode(',',  $this->include_activity_ids);
      $where_clauses[] = "a.activity_type_id IN ($id_list)";
    }
    if (!empty($this->exclude_activity_ids)) {
      $id_list = implode(',',  $this->exclude_activity_ids);
      $where_clauses[] = "a.activity_type_id NOT IN ($id_list)";
    }

    // build where clause
    if (empty($where_clauses)) {
      $where_clause = 'TRUE';
    } else {
      $where_clause = implode(' AND ', $where_clauses);
    }

    $query = CRM_Core_DAO::executeQuery("
      SELECT 
        COUNT(*)                 AS activity_count,
        ANY_VALUE(ac.contact_id) AS contact_id
      FROM civicrm_activity_contact ac
      LEFT JOIN civicrm_activity    a   ON a.id = ac.activity_id 
      WHERE {$where_clause}
      GROUP BY ac.contact_id;");

    // find the best contact
    $highest_amount  = 0;
    $best_contact_id = NULL;
    while ($query->fetch()) {
      if ($query->activity_count > $highest_amount) {
        $highest_amount = $query->activity_count;
        $best_contact_id = $query->contact_id;
      } elseif ($query->activity_count == $highest_amount) {
        if (empty($best_contact_id)) {
          $best_contact_id = $query->contact_id;
        } else {
          // somebody else has the same amount
          $best_contact_id = NULL;
          $highest_amount += 1; // increase so nobody else can claim this
        }
      }
    }

    return $best_contact_id;
  }
}
