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
class CRM_Xdedupe_Resolver_CustomGroupByField extends CRM_Xdedupe_Resolver {

  /**
   * @var int ID of the custom field
   */
  protected int $custom_group_id;

  /**
   * @param int $custom_group_id
   */
  public function __construct(?CRM_Xdedupe_Merge $merge, $custom_group_id) {
    $this->custom_group_id = (int) $custom_group_id;
    parent::__construct($merge);
  }

  /**
   * @inheritDoc
   */
  public function getSpec(): string {
    return "CRM_Xdedupe_Resolver_CustomGroupByField:$this->custom_group_id";
  }

  /**
   * @inheritDoc
   */
  public function getContactAttributes(): array {
    return ["custom_$this->custom_group_id"];
  }

  /**
   * get the name of the finder
   *
   * @inheritDoc
   */
  public function getName(): string {
    $group_name = civicrm_api4('CustomGroup', 'get', [
      'select' => ['title'],
      'where' => [['id', '=', $this->custom_group_id]],
    ]);
    return E::ts("Merge by field for group '%1'", [1 => $group_name[0]['title']]);
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    $field_name = civicrm_api4('CustomField', 'get', [
      'select' => ['label'],
      'where' => [['id', '=', $this->custom_group_id]],
    ]);
    return E::ts(
    // phpcs:ignore Generic.Files.LineLength.TooLong
      "The field '%1' is a custom field. This resolver will merge the values of all duplicates. It will fill empty fields with the first found value and it will add new options to a multi select field..",
      [1 => $field_name[0]['label']]
    );
  }

  /**
   * Get the contact's field values
   *
   * @param int $contact_id
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function getValues(int $contact_id): array {
    // get group name
    $group_name = civicrm_api4('CustomGroup', 'get', [
      'select' => ['name'],
      'where' => [['id', '=', $this->custom_group_id]],
    ]);
    // get values for contact
    $group_values = civicrm_api4('Contact', 'get', [
      'select' => [$group_name[0]['name'] . '.*'],
      'where' => [['id', '=', $contact_id]],
    ]);

    $values = $group_values[0];

    // id is always set, so unset
    unset($values['id']);

    return $values;
  }

  /**
   * @inheritDoc
   */
  public function resolve($main_contact_id, $other_contact_ids): bool {
    $main_contact_values = $this->getValues($main_contact_id);
    // seems not to work
    $this->addMergeDetail(
      E::ts('Merge by field group id: [%1]', [1 => $this->custom_group_id])
    );
    $new_main_contact_values = $main_contact_values;
    // collect values in array
    foreach ($other_contact_ids as $other_contact_id) {
      $other_contact_values = $this->getValues($other_contact_id);
      foreach ($new_main_contact_values as $key => $value) {
        // merge array - add if empty
        if (is_array($value)) {
          foreach ($other_contact_values[$key] as $v) {
            if (array_search($v, $value) === FALSE) {
              $new_main_contact_values[$key][] = $v;
            }
          }
        }
        elseif ($value === '' || $value === NULL) {
          // merge textfield
          $new_main_contact_values[$key] = $other_contact_values[$key];
        }
      }
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
   *
   * @param array $list list of resolver specs
   */
  public static function addAllResolvers(&$list): void {
    $contact_custom_groups = civicrm_api4(
      'CustomGroup',
      'get',
      [
        'select' => [
          'id',
          'title',
        ],
        'where' => [
          ['extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization']],
          ['is_active', '=', TRUE],
        ],
      ]
    );

    if (count($contact_custom_groups) === 0) {
      return;
    }

    foreach ($contact_custom_groups as $custom_group) {
      $list[] = "CRM_Xdedupe_Resolver_CustomGroupByField:{$custom_group['id']}";
    }
  }

}
