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
 * Implements a resolver for contact custom fields
 */
class CRM_Xdedupe_Resolver_CustomGroupCoherent extends CRM_Xdedupe_Resolver
{

    /** @var integer ID of the custom field */
    protected $custom_group_id = null;

    public function __construct($merge, $custom_group_id)
    {
        $this->custom_group_id = $custom_group_id;
        parent::__construct($merge);
    }

    /**
     * Get the spec (i.e. class name) that refers to this resolver
     * @return string spec string
     */
    public function getSpec()
    {
        return "CRM_Xdedupe_Resolver_CustomGroupCoherent:{$this->custom_group_id}";
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return ["custom_{$this->custom_group_id}"];
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        // $field_name = civicrm_api3('CustomField', 'getvalue', ['id' => $this->custom_group_id, 'return' => 'label']);
        // $group_name = civicrm_api4('CustomField', 'get', [ 'select' => [ 'label','custom_group_id',], 'where' => [ [ 'id', '=', $this->custom_group_id ] , ], ]);
        $group_name = civicrm_api4('CustomGroup', 'get', [ 'select' => [ 'title',], 'where' => [ [ 'id', '=',  $this->custom_group_id ] , ], ]);
        // \Civi::log('xdedupe')->debug('xdedupe: customfields {original}', ['original' => $group_name,]);
        return E::ts("Merge coherent '%1' Custom Group", [1 =>  $group_name[0]['title']]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        $group_name = civicrm_api4('CustomGroup', 'get', [ 'select' => [ 'title',], 'where' => [ [ 'id', '=', $this->custom_group_id ] , ], ]);
        return E::ts(
            "The group '%1' is a custom group. This resolver will take the values for all fields of the custom group from the first duplicate that has values in the fields if all fields in the main contact are empty.",
            [1 => $group_name[0]['title']]
        );
    }



    protected function getValues($contact_id)
    {
        $contact    = $this->getContext()->getContact($contact_id);
        $group_name = civicrm_api4('CustomGroup', 'get', [ 'select' => [ 'name',], 'where' => [ [ 'id', '=', $this->custom_group_id ] , ], ]);
        // $group = $group_name[0]['name'];
        $group_values =  civicrm_api4('Contact', 'get', ['select' => [ $group_name[0]['name'] . '.*',], 'where' => [['id', '=', $contact_id], ], ]);

        /*
         * $custom_group_table_name = 'civicrm_value_'. strtolower($group_name[0]['name']) . "_" . $this->custom_group_id;
        $custom_group_entry_id = $contact[$custom_group_table_name . "_id"] ?? false;
        \Civi::log('xdedupe')->debug('xdedupe: contact {contact}', ['original' => $contact,]);


        if($custom_group_entry_id !== false) {
            \Civi::log('xdedupe')->debug('xdedupe: customgroupid {entry}', ['original' => $custom_group_entry_id,]);
            $values_query = CRM_Core_DAO::executeQuery(
                    "
            SELECT *
            FROM {$custom_group_table_name}
            WHERE id = {$custom_group_entry_id};"
            );
            $values = $values_query->fetch();
            \Civi::log('xdedupe')->debug('xdedupe: customfields1 {values1}', ['id' => $values->keys, ]);
        }

        \Civi::log('xdedupe')->debug('xdedupe: customfields2 {values2}', ['original' => $values->fields,]);
        // customfields are not part of context
        // get fields of group
        $all_custom_fields = civicrm_api4(
                'CustomField',
                'get',
                [
                        'select' => [
                                'name'
                        ],
                        'where' => [
                                ['custom_group_id', '=', $this->custom_group_id],
                                ['is_active', '=', TRUE],
                                ['data_type', 'IN', ['Text', 'String', 'Int']],
                        ],
                ]
        );
        $custom_field_names = [];
        foreach ($all_custom_fields as $custom_field) {
            $custom_field_names[] = $group_name[0]['name'] . "." . $custom_field['name'];
        }
        $contact_custom_field_data = civicrm_api4('Contact', 'get', [
          'select' => $custom_field_names,
          'where' => [
            ['id', '=', $contact_id],
          ],
          'checkPermissions' => FALSE,
        ]);
        $values     = CRM_Utils_Array::value($field_name, $contact, []);
        if ($values === '' || $values === null || $values === false) {
            $values = [];
        } elseif (!is_array($values)) {
            $values = [$values];
        }*/
        // unset ie

        $values = $group_values[0];
        \Civi::log('xdedupe')->debug('xdedupe: getValue {original}', ['original' => $values,]);
        // id is always set, so unset
        unset($values['id']);

        return $values;
    }

     /**
     * join array
     *
     * @param $customGroupFields array custom group fields
     * @return string
     */
     public function joinArray($customGroupFields)
    {
        $joinedFields = "";
        foreach ($customGroupFields as $customGroupField ) {
            if(is_array($customGroupField)) {
                $joinedFields .= $this->joinArray($customGroupField);
            }
            else $joinedFields .= $customGroupField;
        }
        return $joinedFields;
    }

    /**
     * Resolve the privacy conflicts by maintaining any opt-opt-outs
     *
     * @param $main_contact_id    int     the main contact ID
     * @param $other_contact_ids  array   other contact IDs
     * @return boolean TRUE, if there was a conflict to be resolved
     * @throws Exception if the conflict couldn't be resolved
     */
    public function resolve($main_contact_id, $other_contact_ids)
    {
        $main_contact_values     = $this->getValues($main_contact_id);
        \Civi::log('xdedupe')->debug('xdedupe: joinedArrays {original}', ['original' => strlen($this->joinArray($main_contact_values)),]);
        // if main contact custom group fields are not empty look at the duplicates
        if( strlen($this->joinArray($main_contact_values)) === 0 ) {
            $new_main_contact_values = array();
            foreach ($other_contact_ids as $other_contact_id) {
                $other_contact_values      = $this->getValues($other_contact_id);
                if(strlen($this->joinArray($main_contact_values)) > 0) {
                    // values found in other contact -> set all customvalues
                    $new_main_contact_values[] = $other_contact_values;
                    $new_values              = impode(',', $other_contact_values);
                    $this->addMergeDetail(
                            E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id])
                    );
                    break;
                }
                /*$only_other_contact_values = array_diff($other_contact_values, $main_contact_values);
                if ($only_other_contact_values) {
                    // there are values that are only set in the other contact
                    $new_main_contact_values = array_merge($new_main_contact_values, $only_other_contact_values);
                    $new_values              = implode(',', $only_other_contact_values);

                    $this->addMergeDetail(
                            E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id])
                    ); */
            }
        } else {
            // set main contact values as new for all
            $new_main_contact_values = $main_contact_values;
        }

        // now, perform the contact updates if necessary
        // sort($new_main_contact_values);
        $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
        // $field_name      = "custom_{$this->custom_group_id}";
        // prepare new values for update
        foreach ($new_main_contact_values as $key_new_main_contact_value => $new_main_contact_value) {
            // reformat option values
            if(is_array($new_main_contact_value)) {

            }
        }
        foreach ($all_contact_ids as $contact_id) {
            // $current_values = $this->getValues($contact_id);
            if ($current_values != $new_main_contact_values) {
                civicrm_api4('Contact', 'update', [
                  'values' => $new_main_contact_values,
                  'where' => [
                    ['id', '=', $contact_id],
                  ],
                  'checkPermissions' => FALSE,
                ]);
                $this->getContext()->unloadContact($contact_id);
            }
        }

        return true;
    }


    /**
     * Add a resolver spec for each Multi-Select field to the list
     * @param $list array list of resolver specs
     */
    public static function addAllResolvers(&$list)
    {
        $contact_custom_group_ids = [];
        $contact_custom_groups    = civicrm_api4(
                'CustomGroup',
                'get',
                [
                      'select' => [
                        'id', 'title'
                      ],
                      'where' => [
                        ['extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization']],
                        ['is_active', '=', TRUE],
                      ],
                ]
        );

        if (empty($contact_custom_groups)) {
            return;
        }

        foreach ($contact_custom_groups as $custom_group) {
            $list[] = "CRM_Xdedupe_Resolver_CustomGroupCoherent:{$custom_group['id']}";
        }
    }
}
