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
 * Implement a Tag filter, i.e. will restrict the result set by tag
 */
class CRM_Xdedupe_Filter_Tag extends CRM_Xdedupe_Filter {

  protected $tag_id;

  public function __construct($alias, $params) {
    parent::__construct($alias, $params);
    if (isset($params['tag_id'])) {
      $this->tag_id = (int) $params['tag_id'];
    }
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts('Tag %1', [1 => $this->tag_id]);
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts('Filter for contacts in the given tag');
  }

  /**
   * @inheritDoc
   */
  public function addJOINS(&$joins): void {
    if ($this->tag_id) {
      $joins[] = "LEFT JOIN civicrm_entity_tag $this->alias ON $this->alias.entity_id = contact.id
                                                              AND $this->alias.entity_table = 'civicrm_contact'
                                                              AND $this->alias.tag_id = $this->tag_id";
    }
  }

  /**
   * @inheritDoc
   */
  public function addWHERES(&$wheres): void {
    $wheres[] = "$this->alias.id IS NOT NULL";
  }

}
