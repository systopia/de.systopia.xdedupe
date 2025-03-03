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
class CRM_Xdedupe_Resolver_CustomGroupByField extends CRM_Xdedupe_Resolver
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
        return "CRM_Xdedupe_Resolver_CustomGroupByField:{$this->custom_group_id}";
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
        // $field_name = civicrm_api4('CustomField', 'get', [ 'select' => [ 'label','custom_group_id',], 'where' => [ [ 'id', '=', $this->custom_group_id ] , ], ]);
        $group_name = civicrm_api4('CustomGroup', 'get', [ 'select' => [ 'title',], 'where' => [ [ 'id', '=',  $this->custom_group_id ] , ], ]);
        //\Civi::log('xdedupe')->debug('xdedupe: customfields {original}', ['original' => $group_name,]);
        return E::ts("Merge by field for group '%1'", [ 1 =>  $group_name[0]['title']]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        $field_name = civicrm_api4('CustomField', 'get', [ 'select' => [ 'label',], 'where' => [ [ 'id', '=', $this->custom_group_id ] , ], ]);
        return E::ts(
            "The field '%1' is a custom field. This resolver will merge the values of all duplicates. It will fill empty fields with the first found value and it will add new options to a multi select field..",
            [1 => $field_name[0]['label']]
        );
    }

    /**
     * Get the contact's field values
     *
     * @param $contact_id integer contact ID
     * @return array
     */
    protected function getValues($contact_id)
    {
        // get group name
        $group_name = civicrm_api4('CustomGroup', 'get', [ 'select' => [ 'name',], 'where' => [ [ 'id', '=', $this->custom_group_id ] , ], ]);
        // get values for contact
        $group_values =  civicrm_api4('Contact', 'get', ['select' => [ $group_name[0]['name'] . '.*',], 'where' => [['id', '=', $contact_id], ], ]);

        $values = $group_values[0];
        
        // id is always set, so unset
        unset($values['id']);

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
    public function resolve($main_contact_id, $other_contact_ids)
    {
        $main_contact_values     = $this->getValues($main_contact_id);
        //\Civi::log('xdedupe')->debug('xdedupe: merge by field group id: {group_id}', ['group_id' => $this->custom_group_id, "main_values" => $main_contact_values]);
         $this->addMergeDetail(
                            E::ts("Merge by field group id: [%1]", [1 => $this->custom_group_id])
                    );
        $new_main_contact_values = $main_contact_values;
        // collect values in
        foreach ($other_contact_ids as $other_contact_id) {
            $other_contact_values      = $this->getValues($other_contact_id);
            foreach ($new_main_contact_values as $key => $value) {
                // array
                if(is_array($value)) {
                    foreach ($other_contact_values[$key] as $v) {
                        \Civi::log('xdedupe')->debug('xdedupe: merge by field options: {group_id} - {other_option}', ['options' => $value, 'other_option' => $v]);
                        if(array_search($v, $value) === false) {
                            \Civi::log('xdedupe')->debug('xdedupe: merge option: ', ['add_option' => $v]);
                            $new_main_contact_values[$key][] = $v;
                        }
                    }
                } else {
                    // textfield
                    if(empty($value)) {
                        $new_main_contact_values[$key] = $other_contact_values[$key];
                    }
                }
            }

            /*$only_other_contact_values = array_diff($other_contact_values, $main_contact_values);
            \Civi::log('xdedupe')->debug('xdedupe: merge by field : ', ['other_id' => $other_contact_id, 'new_values' => $new_main_contact_values]);
            if ($only_other_contact_values) {
                // there are values that are only set in the other contact
                \Civi::log('xdedupe')->debug('xdedupe: merge by field : ', ['other_values' => $only_other_contact_values,]);
                $new_main_contact_values = array_merge($new_main_contact_values, $only_other_contact_values);
                $new_values              = implode(',', $only_other_contact_values);
                $this->addMergeDetail(
                    E::ts("Inherited value(s) '{$new_values}' from contact [%1]", [1 => $other_contact_id])
                );
            }*/
        }

        // now, perform the contact updates if necessary
        // sort($new_main_contact_values);
        $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
        // $field_name      = "custom_{$this->custom_group_id}";
        foreach ($all_contact_ids as $contact_id) {
            // $current_values = $this->getValues($contact_id);
            // if ($current_values != $new_main_contact_values) {
                civicrm_api4('Contact', 'update', [
                  'values' => $new_main_contact_values,
                  'where' => [
                    ['id', '=', $contact_id],
                  ],
                  'checkPermissions' => FALSE,
                ]);
                // $this->getContext()->unloadContact($contact_id);
            // }
        }

        return true;
    }


    /**
     * Add a resolver spec for each Multi-Select field to the list
     * @param $list array list of resolver specs
     */
    public static function addAllResolvers(&$list)
    {
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
            $list[] = "CRM_Xdedupe_Resolver_CustomGroupByField:{$custom_group['id']}";
        }
    }
}
