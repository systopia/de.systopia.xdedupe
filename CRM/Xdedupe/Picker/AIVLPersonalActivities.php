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
class CRM_Xdedupe_Picker_AIVLPersonalActivities extends CRM_Xdedupe_Picker_Activities {

  protected static $exclude_names = ['Bulk Email', 'Change Membership Status', 'Mass SMS', 'Pledge Reminder', 'Membership Renewal Reminder',
      'Uitgaande papieren bulk mailing', 'SDD Bulk Correction', 'importError', 'FWTM call assignment',
      'Donorjourney DD1Y_H12/L6', 'Verschil in contact gegevens uit webformulier', 'DDchurnprevention',
      'historic_recruitment', 'fraudWarning', 'Migration SDD mandaten', 'organizationDiscrepancy', 'Prenotificatie'];

  public function __construct() {
    // look up activity ids
    $this->exclude_activity_ids = $this->resolveActivityTypes(self::$exclude_names);
    $this->minimum_activity_date = "(NOW() - INTERVAL 5 YEAR)";
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    return E::ts("AIVL Most Personalised Activities");
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    return E::ts("Picks the contact with the most non-mass activities in the last 5 years. Excluded activity types: Bulk Email, Change Membership Status, Mass SMS, Pledge Reminder, Membership Renewal Reminder, Uitgaande papieren bulk mailing, SDD Bulk Correction, importError, FWTM call assignment, Donorjourney DD1Y_H12/L6, Verschil in contact gegevens uit webformulier, DDchurnprevention, historic_recruitment, fraudWarning, Migration SDD mandaten, organizationDiscrepancy, Prenotificatie");
  }
}
