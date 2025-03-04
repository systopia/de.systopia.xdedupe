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
 * Implements a resolver for contact custom fields
 */
class CRM_Xdedupe_Resolver_CustomGroupByGroup extends CRM_Xdedupe_Resolver {

  /**
   * @var integer ID of the custom group
   */
  protected $custom_group_id;

    /**
     * @param CRM_Xdedupe_Merge $merge
     * @param integer $custom_group_id
     */
  public function __construct($merge, $custom_group_id) {
    $this->custom_group_id = $custom_group_id;
    parent::__construct($merge);
  }

  /**
   * Get the spec (i.e. class name) that refers to this resolver
   * @return string spec
   */
  public function getSpec() {
    return "CRM_Xdedupe_Resolver_CustomGroupByGroup:{$this->custom_group_id}";
  }

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array<string> list of contact attributes
   */
  public function getContactAttributes() {
    return ["id"];
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    $group_name = civicrm_api4(
            'CustomGroup', 'get',
            [
              'select' => ['title'],
              'where' => [['id', '=', $this->custom_group_id]],
            ]
    );
    return E::ts("Merge as a group '%1' Custom Group", [1 => $group_name[0]['title']]);
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    $group_name = civicrm_api4(
            'CustomGroup', 'get',
            [
              'select' => ['title'],
              'where' => [['id', '=', $this->custom_group_id]],
            ]
    );
    return E::ts(
        "The group '%1' is a custom group. This resolver will take the values for all
        fields of the custom group from the first duplicate that has values in the fields
        if all fields in the main contact are empty.",
        [1 => $group_name[0]['title']]
    );
  }
    /**
     * Get the contact's field values
     *
     * @param integer $contact_id contact ID
     * @return array<string> values custom group
     */
  protected function getValues($contact_id) {
    $group_name = civicrm_api4(
            'CustomGroup', 'get',
            [
              'select' => ['name'],
              'where' => [['id', '=', $this->custom_group_id]],
            ]
    );
    $group_values = civicrm_api4(
            'Contact', 'get',
            [
              'select' => [$group_name[0]['name'] . '.*'],
              'where' => [['id', '=', $contact_id]],
            ]
    );

    $values = $group_values[0];

    // id is always set, so unset
    unset($values['id']);

    return $values;
  }

  /**
   * join multidimensional array
   *
   * @param array<string> $customGroupFields custom group as array
   * @return string joined values of all fields
   */
  public function joinArray($customGroupFields) {
    $joinedFields = '';
    foreach ($customGroupFields as $customGroupField) {
      if (is_array($customGroupField)) {
        $joinedFields .= $this->joinArray($customGroupField);
      }
      else {
        $joinedFields .= $customGroupField;
      }
    }
    return $joinedFields;
  }

  /**
   * Resolve the privacy conflicts by maintaining any opt-opt-outs
   *
   * @param integer $main_contact_id  the main contact ID
   * @param array<integer> $other_contact_ids  other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   *
   */
  public function resolve($main_contact_id, $other_contact_ids) {
    $main_contact_values = $this->getValues($main_contact_id);
    // seems not to work
    $this->addMergeDetail(
                        E::ts('Merge as group id: [%1]', [1 => $this->custom_group_id])
                );
    // if main contact custom group fields are empty look at the duplicates
    if (strlen($this->joinArray($main_contact_values)) === 0) {
      $new_main_contact_values = [];
      foreach ($other_contact_ids as $other_contact_id) {
        $other_contact_values = $this->getValues($other_contact_id);
        if (strlen($this->joinArray($main_contact_values)) > 0) {
          // values found in other contact -> set all customvalues
          $new_main_contact_values[] = $other_contact_values;
          $new_values              = impode(',', $other_contact_values);
          // seems not to work
          $this->addMergeDetail(
            E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id])
          );
          break;
        }
      }
    }
    else {
      // set main contact values as new for all
      $new_main_contact_values = $main_contact_values;
    }

    // now, perform the contact updates if necessary
    $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
    foreach ($all_contact_ids as $contact_id) {
      civicrm_api4('Contact', 'update', [
        'values' => $new_main_contact_values,
        'where' => [
        ['id', '=', $contact_id],
        ],
        'checkPermissions' => FALSE,
      ]);
    }

    return TRUE;
  }

  /**
   * Add a resolver spec for each Multi-Select field to the list
   * @param array<string> $list list of resolver specs
   * @return void
   */
  public static function addAllResolvers(&$list) {
    $contact_custom_groups = civicrm_api4(
            'CustomGroup',
            'get',
            [
              'select' => [
                'id', 'title',
              ],
              'where' => [
                    ['extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization']],
                    ['is_active', '=', TRUE],
              ],
            ]
    );

    if (count($contact_custom_groups) > 1) {
      return;
    }

    foreach ($contact_custom_groups as $custom_group) {
      $list[] = "CRM_Xdedupe_Resolver_CustomGroupByGroup:{$custom_group['id']}";
    }
  }

}
