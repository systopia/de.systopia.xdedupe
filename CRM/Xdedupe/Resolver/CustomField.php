<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2025 SYSTOPIA                            |
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
class CRM_Xdedupe_Resolver_CustomField extends CRM_Xdedupe_Resolver
{

    /** @var integer ID of the custom field */
    protected $custom_field_id = null;

    public function __construct($merge, $custom_field_id)
    {
        $this->custom_field_id = $custom_field_id;
        parent::__construct($merge);
    }

    /**
     * Get the spec (i.e. class name) that refers to this resolver
     * @return string spec string
     */
    public function getSpec()
    {
        return "CRM_Xdedupe_Resolver_CustomField:{$this->custom_field_id}";
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return ["custom_{$this->custom_field_id}"];
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        // $field_name = civicrm_api3('CustomField', 'getvalue', ['id' => $this->custom_field_id, 'return' => 'label']);
        $field_name = civicrm_api4('CustomField', 'get', [ 'select' => [ 'label','custom_group_id',], 'where' => [ [ 'id', '=', $this->custom_field_id ] , ], ]);
        $group_name = civicrm_api4('CustomGroup', 'get', [ 'select' => [ 'title',], 'where' => [ [ 'id', '=', $field_name[0]['custom_group_id'] ] , ], ]);
        return E::ts("'%1' Custom Field of Group '%2'", [1 => $field_name[0]['label'], 2 =>  $group_name[0]['title']]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        $field_name = civicrm_api4('CustomField', 'get', [ 'select' => [ 'label',], 'where' => [ [ 'id', '=', $this->custom_field_id ] , ], ]);
        return E::ts(
            "The field '%1' is a custom field. This resolver will merge the values of all duplicates, so that the main contact will have all.",
            [1 => $field_name[0]['label']]
        );
    }

    /**
     * Get the contact's field value
     *
     * @param $contact_id integer contact ID
     * @return mixed
     */
    protected function getValue($contact_id)
    {
        $field_name = "custom_{$this->custom_field_id}";
        $contact = $this->getContext()->getContact($contact_id);
        return CRM_Utils_Array::value($field_name, $contact, '');
    }

    /**
     * Resolve simple custom field conflicts by maintaining the main contact's one
     *
     * @param $main_contact_id    int     the main contact ID
     * @param $other_contact_ids  array   other contact IDs
     * @return boolean TRUE, if there was a conflict to be resolved
     * @throws Exception if the conflict couldn't be resolved
     */
    public function resolve($main_contact_id, $other_contact_ids)
    {
        // get the resolved value
        $resolved_contact_value = $this->getValue($main_contact_id);
        if ($resolved_contact_value) {
            $merged_value_origin_contact_id = $this->getValue($main_contact_id);
        }
        foreach ($other_contact_ids as $other_contact_id) {
            if (empty($resolved_contact_value)) {
                $resolved_contact_value = $this->getValue($other_contact_id);
                if (!empty($resolved_contact_value)) {
                    $merged_value_origin_contact_id = $other_contact_id;
                }
            }
        }

        // now copy the value to all contacts
        if ($resolved_contact_value) {
            $this->addMergeDetail(E::ts("Picked value(s) '{$resolved_contact_value}' from contact [%1]", [1 => $merged_value_origin_contact_id]));

        }

        // now set the value for all contacts - first main
        $field_name = "custom_{$this->custom_field_id}";
        civicrm_api3('Contact', 'create',
                ['id' => $main_contact_id, $field_name => $resolved_contact_value]);
        $this->getContext()->unloadContact($main_contact_id);

        // then all the others
        foreach ($other_contact_ids as $other_contact_id) {
            civicrm_api3('Contact', 'create',
                    ['id' => $other_contact_id, $field_name => $resolved_contact_value]);
            $this->getContext()->unloadContact($other_contact_id);
        }
        return true;
    }


    /**
     * Add a resolver spec for each INDIVIDUAL Multi-Select field to the list
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
                        'id',
                      ],
                      'where' => [
                        ['extends', 'IN', ['Contact', 'Individual', 'Household', 'Organization']],
                        ['is_active', '=', TRUE],
                      ],
                ]
        );

        foreach ($contact_custom_groups as $contact_custom_group) {
            $contact_custom_group_ids[] = $contact_custom_group['id'];
        }
        if (empty($contact_custom_group_ids)) {
            return;
        }

        $all_custom_fields = civicrm_api4(
            'CustomField',
            'get',
            [
                    'select' => [
                            'id',
                    ],
                    'where' => [
                            ['custom_group_id', 'IN', $contact_custom_group_ids],
                            ['is_active', '=', TRUE],
                            ['data_type', 'IN', ['Text', 'String', 'Int']],
                    ],
            ]
        );

        foreach ($all_custom_fields as $custom_field) {
            $list[] = "CRM_Xdedupe_Resolver_CustomField:{$custom_field['id']}";
        }
    }
}
