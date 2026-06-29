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
 * Implements a resolver for basic contact fields
 */
class CRM_Xdedupe_Resolver_MultiSelect extends CRM_Xdedupe_Resolver {

  /**
   * @var int ID of the custom field of type Multi-Select
   */
  protected int $custom_field_id;

  public function __construct(?CRM_Xdedupe_Merge $merge, int|string $custom_field_id) {
    $this->custom_field_id = (int) $custom_field_id;
    parent::__construct($merge);
  }

  /**
   * @inheritDoc
   */
  public function getSpec(): string {
    return "CRM_Xdedupe_Resolver_MultiSelect:$this->custom_field_id";
  }

  /**
   * @inheritDoc
   */
  public function getContactAttributes(): array {
    return ["custom_$this->custom_field_id"];
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    $field_name = civicrm_api3('CustomField', 'getvalue', ['id' => $this->custom_field_id, 'return' => 'label']);
    return E::ts("Merge '%1' Multi-Select Field", [1 => $field_name]);
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    $field_name = civicrm_api3('CustomField', 'getvalue', ['id' => $this->custom_field_id, 'return' => 'label']);
    return E::ts(
    // phpcs:ignore Generic.Files.LineLength.TooLong
      "The field '%1' is a multi-select field. This resolver will merge the values of all duplicates, so that the main contact will have all.",
      [1 => $field_name]
    );
  }

  /**
   * Get the contact's field values
   *
   * @param int $contact_id contact ID
   *
   * @return array
   */
  protected function getValues(int $contact_id): array {
    $field_name = "custom_$this->custom_field_id";
    $contact = $this->getContext()?->getContact($contact_id);
    $values = $contact[$field_name] ?? [];
    if ($values === '' || $values === NULL || $values === FALSE) {
      $values = [];
    }
    elseif (!is_array($values)) {
      $values = [$values];
    }
    sort($values);
    return $values;
  }

  /**
   * @inheritDoc
   */
  public function resolve(int $main_contact_id, array $other_contact_ids): bool {
    $main_contact_values = $this->getValues($main_contact_id);
    $new_main_contact_values = $main_contact_values;
    foreach ($other_contact_ids as $other_contact_id) {
      $other_contact_values = $this->getValues($other_contact_id);
      $only_other_contact_values = array_diff($other_contact_values, $main_contact_values);
      if (count($only_other_contact_values) > 0) {
        // there are values that are only set in the other contact
        $new_main_contact_values = array_merge($new_main_contact_values, $only_other_contact_values);
        $new_values = implode(',', $only_other_contact_values);
        $this->addMergeDetail(
          E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id])
        );
      }
    }

    // now, perform the contact updates if necessary
    sort($new_main_contact_values);
    $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
    $field_name = "custom_$this->custom_field_id";
    foreach ($all_contact_ids as $contact_id) {
      $current_values = $this->getValues($contact_id);
      if ($current_values != $new_main_contact_values) {
        civicrm_api3(
          'Contact',
          'create',
          [
            'id' => $contact_id,
            $field_name => $new_main_contact_values,
          ]
        );
        $this->getContext()?->unloadContact($contact_id);
      }
    }

    return TRUE;
  }

  /**
   * Add a resolver spec for each Multi-Select field to the list
   *
   * @param array $list list of resolver specs
   */
  public static function addAllResolvers(&$list): void {
    $contact_custom_group_ids = [];
    $contact_custom_groups = civicrm_api3(
      'CustomGroup',
      'get',
      [
        'option.limit' => 0,
        'extends' => ['IN' => ['Contact', 'Individual', 'Organization', 'Household']],
        'is_active' => 1,
        'return' => 'id',
      ]
    );
    foreach ($contact_custom_groups['values'] as $contact_custom_group) {
      $contact_custom_group_ids[] = $contact_custom_group['id'];
    }
    if (count($contact_custom_group_ids) === 0) {
      return;
    }

    $all_multi_selects = civicrm_api3(
      'CustomField',
      'get',
      [
        'option.limit' => 0,
        'html_type' => 'Multi-Select',
        'custom_group_id' => ['IN' => $contact_custom_group_ids],
        'is_active' => 1,
        'return' => 'id',
      ]
    );
    foreach ($all_multi_selects['values'] as $multi_select) {
      $list[] = "CRM_Xdedupe_Resolver_MultiSelect:{$multi_select['id']}";
    }
  }

}
