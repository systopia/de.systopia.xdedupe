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
 * Implements a resolver for option value fields,
 *   selects the value with the lowest weight in the option group
 */
abstract class CRM_Xdedupe_Resolver_OptionValueAttribute extends CRM_Xdedupe_Resolver_SimpleAttribute
{

    protected $option_group_name;

    public function __construct($merge, $attribute_name, $option_group_name)
    {
        parent::__construct($merge, $attribute_name);
        $this->option_group_name = $option_group_name;
    }

    /**
     * Resolve the merge conflicts by editing the contact
     *
     * CAUTION: IT IS PARAMOUNT TO UNLOAD A CONTACT FROM THE CACHE IF CHANGED AS FOLLOWS:
     *  $this->merge->unloadContact($contact_id)
     *
     * @param $main_contact_id    int     the main contact ID
     * @param $other_contact_ids  array   other contact IDs
     * @return boolean TRUE, if there was a conflict to be resolved
     * @throws Exception if the conflict couldn't be resolved
     */
    public function resolve($main_contact_id, $other_contact_ids)
    {
        $all_contact_ids = array_merge($other_contact_ids, [$main_contact_id]);
        $values          = $this->getValuesFromContacts($all_contact_ids);
        if (count($values) < 2) {
            // only with two or more values there is a conflict
            return false;
        }

        // pick the value highest up in the group
        $value2weight  = $this->getValueToWeight();
        $top_value     = '';
        $lowest_weight = PHP_INT_MAX;
        foreach ($values as $value) {
            $weight = CRM_Utils_Array::value($value, $value2weight, PHP_INT_MAX);
            if ($weight <= $lowest_weight) {
                // we have a new top contender!
                $lowest_weight = $weight;
                $top_value     = $value;
            }
        }

        // set the value for all
        $this->setValueForContacts($all_contact_ids, $top_value);
        return true;
    }

    /**
     * Load all the option values along with their weight
     */
    protected function getValueToWeight()
    {
        static $value2weight = null;
        if ($value2weight === null) {
            $value2weight    = [];
            $value_attribute = $this->getValueAttribute();
            $values          = civicrm_api3(
                'OptionValue',
                'get',
                [
                    'option_group_id' => $this->option_group_name,
                    'return'          => "weight,{$value_attribute}",
                    'option.limit'    => 0
                ]
            );
            foreach ($values['values'] as $value) {
                $value2weight[$value[$value_attribute]] = $value['weight'];
            }
        }
        return $value2weight;
    }

    /**
     * Get the attribute used as the value in CiviCRM. 'value' is default, obviously, but e.g. preferred_language stores 'name'
     * @return string the attribute name
     */
    protected function getValueAttribute()
    {
        return 'value';
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Select '%1'", [1 => $this->attribute_name]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts("Will resolve the '%1' attribute by picking the one that is highest up in the option group");
    }
}
