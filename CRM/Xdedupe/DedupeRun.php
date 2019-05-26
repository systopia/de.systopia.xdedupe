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
 * This is the part of the algorithm
 */
class CRM_Xdedupe_DedupeRun {

  const MAX_TABLE_RETENTION = "2 days";

  /** name of the underlying temp table*/
  protected $identifier;
  protected $finders = [];
  protected $filters = [];

  public function __construct($identifier = NULL) {
    if (!$identifier) {
      $identifier = date('YmdHis') . '_' . substr(sha1(microtime()), 0, 32);
    }
    $this->identifier = $identifier;
    $this->verifyTable();
  }

  /**
   * Remove old tables from previous runs
   */
  public function cleanupDB() {
    $own_table_name     = $this->getTableName();
    $deletion_threshold = strtotime("now - " . self::MAX_TABLE_RETENTION);

    $dsn = DB::parseDSN(CIVICRM_DSN);
    $table_query = CRM_Core_DAO::executeQuery("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$dsn['database']}'  AND TABLE_NAME LIKE 'tmp_xdedupe_%'");
    while ($table_query->fetch()) {
      $table_name = $table_query->TABLE_NAME;
      if ($table_name == $own_table_name) continue; // don't want to drop our own table

      // parse table name
      if (preg_match("/^tmp_xdedupe_(?<date>[0-9]{14})_(?<hash>[0-9a-f]{32})$/", $table_name, $match)) {
        $table_date = strtotime($match['date']);
        if ($table_date < $deletion_threshold) {
          // this table is too old => drop it
          CRM_Core_DAO::executeQuery("DROP TABLE `{$table_name}`");
        }
      } else {
        CRM_Core_Error::debug_log_message("Unrecognised table found: '{$table_name}'. Please clean up manually.");
      }
    }
  }

  /**
   * Get the name of the DB table used for this run
   *
   * @return string table name
   */
  public function getTableName() {
    return 'tmp_xdedupe_' . $this->getID();
  }

  /**
   * Get the number of found matches
   *
   * @return int number of tuples found
   */
  public function getTupleCount() {
    $table_name = $this->getTableName();
    return (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM `{$table_name}`");
  }

  /**
   * Get a number of contact tuples
   *
   * @param $count  int number of tuples to add
   * @param $offset int offset/paging
   * @param $picker CRM_Xdedupe_Picker use this picker to determine the main contact
   * @return array [main_contact_id => duplicates' contact ids]
   */
  public function getTuples($count, $offset = 0, $picker = NULL) {
    $tuple_list = [];
    $count  = (int) $count;
    $offset = (int) $offset;
    $table_name = $this->getTableName();
    $query = CRM_Core_DAO::executeQuery("SELECT contact_ids, contact_id FROM `{$table_name}` LIMIT {$count} OFFSET {$offset}");
    while ($query->fetch()) {
      $contact_ids = explode(',', $query->contact_ids);
      if ($picker) {
        $main_contact_id = $picker->selectMainContact($contact_ids);
      } else {
        $main_contact_id = $query->contact_id;
      }
      // remove main contact from rest
      $key = array_search($main_contact_id, $contact_ids);
      unset($contact_ids[$key]);

      // store
      $tuple_list[$main_contact_id] = $contact_ids;
    }

    return $tuple_list;
  }

  /**
   * Get the number of contacts involved
   *
   * @return int number of tuples found
   */
  public function getContactCount() {
    $table_name = $this->getTableName();
    return (int) CRM_Core_DAO::singleValueQuery("SELECT SUM(match_count) FROM `{$table_name}`");
  }

  /**
   * Clear the results
   */
  public function clear() {
    $table_name = $this->getTableName();
    CRM_Core_DAO::singleValueQuery("DELETE FROM `{$table_name}`");
  }

  /**
   * Make sure the table is there
   */
  public function verifyTable() {
    $table_name = $this->getTableName();
    CRM_Core_DAO::executeQuery("
      CREATE TABLE IF NOT EXISTS `{$table_name}`(
       `contact_id`  int unsigned NOT NULL        COMMENT 'proposed main contact ID',
       `match_count` int unsigned NOT NULL        COMMENT 'number of contacts',
       `contact_ids` varchar(255) NOT NULL        COMMENT 'all contact ids, comma separated',
      PRIMARY KEY ( `contact_id` ),
      INDEX `match_count` (match_count)
      ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
  }

  /**
   * Get the unqiue identifier for this run
   *
   * @return string identifier
   */
  public function getID() {
    return $this->identifier;
  }

  /**
   * Add a filter to the run
   *
   * @param $filter_class string class name of the filter
   * @param $parameters   array  parameters
   */
  public function addFilter($filter_class, $parameters = []) {
    $filter_index = count($this->filters)+1;
    $this->filters[$filter_index] = new $filter_class("filter{$filter_index}", $parameters);
  }

  /**
   * Add a finder to the run
   *
   * @param $finder_class string class name of the finder
   * @param $parameters   array  parameters
   */
  public function addFinder($finder_class, $parameters = []) {
    $finder_index = count($this->finders)+1;
    $this->finders[$finder_index] = new $finder_class("finder{$finder_index}", $parameters);
  }

  /**
   * Find all contacts and put them in the list
   */
  public function find($params) {
    // build SQL query
    $JOINS     = [];
    $WHERES    = [];
    $GROUP_BYS = [];

    // add default stuff
    if (!empty($params['contact_type'])) {
      $WHERES[] = "contact.contact_type = '{$params['contact_type']}'";
    }
    $WHERES[] = "(contact.is_deleted = 0 OR contact.is_deleted IS NULL)";

    // add the finders/filters
    foreach ($this->finders as $finder) {
      $finder->addJOINS($JOINS);
      $finder->addWHERES($WHERES);
      $finder->addGROUPBYS($GROUP_BYS);
    }
    foreach ($this->filters as $filter) {
      $filter->addJOINS($JOINS);
      $filter->addWHERES($WHERES);
    }
    $JOINS = implode(" \n", $JOINS);
    if (empty($WHERES)) {
      $WHERES = 'TRUE';
    } else {
      $WHERES = '(' . implode(') AND (', $WHERES) . ')';
    }
    if (empty($GROUP_BYS)) {
      $GROUP_BYS = '';
    } else {
      $GROUP_BYS = 'GROUP BY ' . implode(', ', $GROUP_BYS);
    }

    $table_name = $this->getTableName();
    $sql = "
    INSERT IGNORE INTO `{$table_name}` (contact_id, match_count, contact_ids)
    SELECT
     MIN(contact.id)                    AS contact_id,
     COUNT(DISTINCT(contact.id))        AS match_count,
     GROUP_CONCAT(DISTINCT(contact.id)) AS contact_ids
    FROM civicrm_contact contact
    {$JOINS}
    WHERE {$WHERES}
    {$GROUP_BYS}
    HAVING match_count > 1";

    // TODO: remove logging
    CRM_Core_Error::debug_log_message("find: $sql");

    // run query
    CRM_Core_DAO::executeQuery($sql);
  }
}
