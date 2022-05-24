<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2022 SYSTOPIA                            |
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
abstract class CRM_Xdedupe_Resolver_AttributeCleanup extends CRM_Xdedupe_Resolver
{

    /** @var string name of the attribute to clean up */
    protected $attribute_name;

    /** @var string human-readable label of the attribute to clean up */
    protected $attribute_label;

    /** @var array list of preg_replace patterns as a tuple [search pattern, replace pattern] */
    protected $regular_expressions;

    public function __construct($merge, $attribute_name)
    {
        parent::__construct($merge);
        $this->attribute_name = $attribute_name;
        $this->attribute_label = $attribute_name;
    }

    /**
     * Get a human-readable attribute name
     */
    public function getAttributeName()
    {
        return $this->attribute_name;
    }

    /**
     * Get a human-readable attribute name
     */
    public function getAttributeLabel()
    {
        return $this->attribute_label;
    }

    /**
     * Report the contact attributes that this resolver requires
     *
     * @return array list of contact attributes
     */
    public function getContactAttributes()
    {
        return [$this->attribute_name];
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
        $something_changed = false;
        $contact_ids = array_merge([$main_contact_id], $other_contact_ids);
        foreach ($contact_ids as $contact_id) {
            $contact   = $this->getContext()->getContact($contact_id);
            $new_value = $old_value = CRM_Utils_Array::value($this->attribute_name, $contact);
            foreach ($this->regular_expressions as $search_replace) {
                $new_value = preg_replace($search_replace[0], $search_replace[1], $new_value);
            }
            if ($new_value != $old_value) {
                civicrm_api3(
                    'Contact',
                    'create',
                    [
                        'id'                  => $contact_id,
                        $this->attribute_name => $new_value
                    ]
                );
                $this->getContext()->unloadContact($contact_id);
                $this->addMergeDetail(
                    E::ts(
                        "Changed %1 from '<code style='white-space: pre;'>%2</code>' to '<code style='white-space: pre;'>%3</code>' in contact [%4] to avoid merge conflicts",
                        [
                            1 => $this->getAttributeLabel(),
                            2 => $old_value,
                            3 => $new_value,
                            4 => $contact_id
                        ]
                    )
                );
                $something_changed = true;
            }
        }
        return $something_changed;
    }

    /**
     * get the name of the finder
     * @return string name
     */
    public function getName()
    {
        return E::ts("Cleanup '%1'", [1 => $this->getAttributeName()]);
    }

    /**
     * get an explanation what the finder does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts(
            "Cleans up the '%1' attribute before merging to avoid conflicts"
        );
    }
}
