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

/**
 * Abstract base class for query-modifying modules
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_QueryPlugin {
// phpcs:enable
  protected $alias = NULL;
  protected $params = NULL;

  public function __construct($alias, $params) {
    $this->params = $params;
    $this->alias  = $alias;
  }

  /**
   * get the name of the finder
   * @return string name
   */
  abstract public function getName(): string;

  /**
   * get an explanation what the finder does
   * @return string name
   */
  abstract public function getHelp(): string;

  /**
   * Add this finder's JOIN clauses to the list
   *
   * @param $joins array
   */
  public function addJOINS(&$joins): void {
  }

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param $wheres array
   */
  public function addWHERES(&$wheres): void {
  }

  /**
   * Add this finder's GROUP BY clauses to the list
   *
   * @param $groupbys array
   */
  public function addGROUPBYS(&$groupbys): void {
  }

}
