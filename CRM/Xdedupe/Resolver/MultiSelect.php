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
 * Implements a resolver for basic contact fields
 */
class CRM_Xdedupe_Resolver_MultiSelect extends CRM_Xdedupe_Resolver {

  /** @var integer ID of the custom field of type Multi-Select */
  protected $custom_field_id = NULL;

  public function __construct($merge, $custom_field_id) {
    $this->custom_field_id = $custom_field_id;
    parent::__construct($merge);
  }

  /**
   * Get the spec (i.e. class name) that refers to this resolver
   * @return string spec string
   */
  public function getSpec() {
    return "CRM_Xdedupe_Resolver_MultiSelect:{$this->custom_field_id}";
  }

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array list of contact attributes
   */
  public function getContactAttributes() {
    return ["custom_{$this->custom_field_id}"];
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public function getName() {
    $field_name = civicrm_api3('CustomField', 'getvalue', ['id' => $this->custom_field_id, 'return' => 'label']);
    return E::ts("Merge '%1' Multi-Select Field", [1 => $field_name]);
  }

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public function getHelp() {
    $field_name = civicrm_api3('CustomField', 'getvalue', ['id' => $this->custom_field_id, 'return' => 'label']);
    return E::ts("The field '%1' is a multi-select field. This resolver will merge the values of all duplicates, so that the main contact will have all.", [1 => $field_name]);
  }

  /**
   * Get the contact's field values
   *
   * @param $contact_id integer contact ID
   * @return array
   */
  protected function getValues($contact_id) {
    $field_name = "custom_{$this->custom_field_id}";
    $contact = $this->getContext()->getContact($contact_id);
    $values = CRM_Utils_Array::value($field_name, $contact, []);
    if (!is_array($values)) {
      $values = [$values];
    }
    sort($values);
    return $values;
  }

  /**
   * Resolve the privacy conflicts by maintaining any opt-opt-outs
   *
   * @param $main_contact_id    int     the main contact ID
   * @param $other_contact_ids  array   other contact IDs
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public function resolve($main_contact_id, $other_contact_ids) {
    $main_contact_values = $this->getValues($main_contact_id);
    $new_main_contact_values = $main_contact_values;
    foreach ($other_contact_ids as $other_contact_id) {
      $other_contact_values = $this->getValues($other_contact_id);
      $only_other_contact_values = array_diff($other_contact_values, $main_contact_values);
      if ($only_other_contact_values) {
        // there are values that are only set in the other contact
        $new_main_contact_values = array_merge($new_main_contact_values, $only_other_contact_values);
        $new_values = implode(',', $only_other_contact_values);
        $this->addMergeDetail(E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id]));
      }
    }

    // now, perform the contact updates if necessary
    sort($new_main_contact_values);
    $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
    $field_name = "custom_{$this->custom_field_id}";
    foreach ($all_contact_ids as $contact_id) {
      $current_values = $this->getValues($contact_id);
      if ($current_values != $new_main_contact_values) {
        civicrm_api3('Contact', 'create', [
            'id'        => $contact_id,
            $field_name => $new_main_contact_values]);
        $this->getContext()->unloadContact($contact_id);
      }
    }

    return TRUE;
  }


  /**
   * Add a resolver spec for each Multi-Select field to the list
   * @param $list array list of resolver specs
   */
  public static function addAllResolvers(&$list) {
    $contact_custom_group_ids = [];
    $contact_custom_groups = civicrm_api3('CustomGroup', 'get', [
        'option.limit' => 0,
        'extends'      => ['IN' => ['Contact', 'Individual', 'Organization', 'Household']],
        'is_active'    => 1,
        'return'       => 'id'
    ]);
    foreach ($contact_custom_groups['values'] as $contact_custom_group) {
      $contact_custom_group_ids[] = $contact_custom_group['id'];
    }
    if (empty($contact_custom_group_ids)) return;

    $all_multi_selects = civicrm_api3('CustomField', 'get', [
        'option.limit'    => 0,
        'html_type'       => 'Multi-Select',
        'custom_group_id' => ['IN' => $contact_custom_group_ids],
        'is_active'       => 1,
        'return'          => 'id'
    ]);
    foreach ($all_multi_selects['values'] as $multi_select) {
      $list[] = "CRM_Xdedupe_Resolver_MultiSelect:{$multi_select['id']}";
    }
  }
}
