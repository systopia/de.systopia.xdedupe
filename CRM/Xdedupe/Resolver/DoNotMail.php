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
 * Simple ExternalIdentifier Resolver
 */
class CRM_Xdedupe_Resolver_DoNotMail extends CRM_Xdedupe_Resolver_MaxAttribute {
  public function __construct() {
    parent::__construct('do_not_mail');
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("Do Not Mail");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Will preserve the Do Not Mail flag.");
  }
}
