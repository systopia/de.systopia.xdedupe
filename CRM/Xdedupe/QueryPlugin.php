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
  protected ?string $alias = NULL;

  protected ?array $params = NULL;

  /**
   * @param string|NULL $alias
   * @param array|NULL $params
   */
  public function __construct(?string $alias, ?array $params) {
    $this->params = $params;
    $this->alias = $alias;
  }

  /**
   * get the name of the finder
   *
   * @return string name
   */
  abstract public function getName(): string;

  /**
   * get an explanation what the finder does
   *
   * @return string name
   */
  abstract public function getHelp(): string;

  /**
   * Add this finder's JOIN clauses to the list
   *
   * @param list<string> $joins array
   */
  public function addJOINS(array &$joins): void {}

  /**
   * Add this finder's WHERE clauses to the list
   *
   * @param list<string> $wheres array
   *
   * @return void
   */
  public function addWHERES(array &$wheres): void {}

  /**
   * Add this finder's GROUP BY clauses to the list
   *
   * @param list<string> $groupbys array
   */
  public function addGROUPBYS(array &$groupbys): void {}

}
