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
 * Implements Max resolver, i.e. take the highest of the values
 */
abstract class CRM_Xdedupe_Resolver_MaxAttribute extends CRM_Xdedupe_Resolver {

  protected $attribute_name;

  public function __construct($attribute_name) {
    $this->attribute_name = $attribute_name;
  }

  /**
   * Select the main contact from a set of contacts
   *
   * @param $main_contact_id   int   the main contact ID
   * @param $other_contact_ids array list of other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public function resolve($main_contact_id, $other_contact_ids) {
    // TODO: implement
    throw new Exception("IMPLEMENT ME");
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Max '%1'", [1 => $this->attribute_name]);
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Will resolve the '%1' attribute by taking the highest value");
  }
}
