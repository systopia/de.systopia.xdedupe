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

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Civi\Core\Event\GenericHookEvent;

/**
 * Implement the general configuration
 */
class CRM_Xdedupe_Config implements EventSubscriberInterface
{

    /**
     * Subscribe to the list events, so we can plug the built-in ones
     */
    public static function getSubscribedEvents()
    {
        return [
            'civi.xdedupe.finders'   => ['addBuiltinFinders', Events::W_MIDDLE],
            'civi.xdedupe.filters'   => ['addBuiltinFilters', Events::W_MIDDLE],
            'civi.xdedupe.resolvers' => ['addBuiltinResolvers', Events::W_MIDDLE],
            'civi.xdedupe.pickers'   => ['addBuiltinPickers', Events::W_MIDDLE],
        ];
    }

    /**
     * Return the list of built-in finders
     */
    public function addBuiltinFinders(GenericHookEvent $xdedupe_list)
    {
        $xdedupe_list->list = array_merge(
            $xdedupe_list->list,
            [
                'CRM_Xdedupe_Finder_Email',
                'CRM_Xdedupe_Finder_LastName',
                'CRM_Xdedupe_Finder_OrganizationName',
                'CRM_Xdedupe_Finder_OrganizationNameNoCase',
                'CRM_Xdedupe_Finder_BirthDate',
                'CRM_Xdedupe_Finder_FirstName',
                'CRM_Xdedupe_Finder_FirstNameInitial',
                'CRM_Xdedupe_Finder_Prefix',
                'CRM_Xdedupe_Finder_City',
                'CRM_Xdedupe_Finder_PostalCode',
                'CRM_Xdedupe_Finder_StreetAddress',
                'CRM_Xdedupe_Finder_PostalCodeCity',
                'CRM_Xdedupe_Finder_PostalCodeStreet',
                'CRM_Xdedupe_Finder_PostalCodeStreetCity',
                'CRM_Xdedupe_Finder_PartialOrganizationName',
                'CRM_Xdedupe_Finder_SanitisedPostalCodeStreet',
                'CRM_Xdedupe_Finder_SanitisedPostalCodeStreetCity',
                'CRM_Xdedupe_Finder_PhoneNumeric',
                'CRM_Xdedupe_Finder_PhoneLiteral',
            ]
        );
    }

    /**
     * Return the list of built-in filters
     */
    public function addBuiltinFilters(GenericHookEvent $xdedupe_list)
    {
        $xdedupe_list->list = array_merge(
            $xdedupe_list->list,
            [
                'CRM_Xdedupe_Filter_DedupeException',
                'CRM_Xdedupe_Filter_UserAccounts',
                'CRM_Xdedupe_Filter_DisplayNameNinetyFiveSimilarity',
                'CRM_Xdedupe_Filter_DisplayNameEightySimilarity'
            ]
        );
    }

    /**
     * Return the list of built-in resolvers
     */
    public function addBuiltinResolvers(GenericHookEvent $xdedupe_list)
    {
        $xdedupe_list->list = array_merge(
            $xdedupe_list->list,
            [
                'CRM_Xdedupe_Resolver_ExternalIdentifier',
                'CRM_Xdedupe_Resolver_Language',
                'CRM_Xdedupe_Resolver_IndividualName',
                'CRM_Xdedupe_Resolver_OrganisationName',
                'CRM_Xdedupe_Resolver_OrganisationNameLongest',
                'CRM_Xdedupe_Resolver_Addressee',
                'CRM_Xdedupe_Resolver_DropSamePhones',
                'CRM_Xdedupe_Resolver_BumpAddressConflicts',
                'CRM_Xdedupe_Resolver_PhoneMover',
                'CRM_Xdedupe_Resolver_EmailMover',
                'CRM_Xdedupe_Resolver_WebsiteMover',
                'CRM_Xdedupe_Resolver_IMMover',
                'CRM_Xdedupe_Resolver_Privacy',
                'CRM_Xdedupe_Resolver_Source',
                'CRM_Xdedupe_Resolver_OrganisationNameCleanup',
                'CRM_Xdedupe_Resolver_FirstNameCleanup',
                'CRM_Xdedupe_Resolver_LastNameCleanup',
                'CRM_Xdedupe_Resolver_CityCleanup',
                'CRM_Xdedupe_Resolver_StreetAddressCleanup',
            ]
        );

        // add configurable resolvers
        CRM_Xdedupe_Resolver_MultiSelect::addAllResolvers($xdedupe_list->list);

        // add custom group resolvers
        CRM_Xdedupe_Resolver_CustomGroupPicker::addAllResolvers($xdedupe_list->list);
        // todo: CRM_Xdedupe_Resolver_CustomGroupMerger::addAllResolvers($xdedupe_list->list);
    }

    /**
     * Return the list of built-in pickers
     */
    public function addBuiltinPickers(GenericHookEvent $xdedupe_list)
    {
        $xdedupe_list->list = array_merge(
            $xdedupe_list->list,
            [
                'CRM_Xdedupe_Picker_Oldest',
                'CRM_Xdedupe_Picker_Youngest',
                'CRM_Xdedupe_Picker_Activities',
                'CRM_Xdedupe_Picker_PersonalActivities',
                'CRM_Xdedupe_Picker_AIVLPersonalActivities',
            ]
        );
    }

    /**
     * The 'conflict' location type is specificially created to remove location type conflicts by
     *  moving the second address to this new location type
     */
    public static function getConflictLocationTypeID()
    {
        static $conflict_location_type_id = null;
        if ($conflict_location_type_id === null) {
            $location_type = civicrm_api3('LocationType', 'get', ['name' => 'Conflict']);
            if (empty($location_type['id'])) {
                // create it
                $result                    = civicrm_api3(
                    'LocationType',
                    'create',
                    [
                        'name'         => 'Conflict',
                        'display_name' => E::ts('Conflict'),
                        'vcard_name'   => 'UNKNOWN',
                        'is_reserved'  => '0',
                        'is_active'    => '1',
                        'is_default'   => '0',
                        'description'  => E::ts("XDedupe location type conflict. Please clean up!"),
                    ]
                );
                $conflict_location_type_id = $result['id'];
            } else {
                $conflict_location_type_id = $location_type['id'];
            }
        }
        return $conflict_location_type_id;
    }

    /**
     * Resolve the location type to a display string
     *
     * @param $location_type_id
     * @return string
     */
    public static function resolveLocationType($location_type_id)
    {
        static $location_types = null;

        // look up location types
        if ($location_types === null) {
            $location_types = [];
            $query          = civicrm_api3(
                'LocationType',
                'get',
                [
                    'return'       => 'display_name,id',
                    'option.limit' => 0
                ]
            );
            foreach ($query['values'] as $location_type) {
                $location_types[$location_type['id']] = $location_type['display_name'];
            }
        }

        // resolve type
        if (isset($location_types[$location_type_id])) {
            return E::ts("%1 [%2]", [1 => $location_types[$location_type_id], 2 => $location_type_id]);
        } else {
            return E::ts("Location Type [%1]", [1 => $location_type_id]);
        }
    }

    /**
     * Get the merge activity type ID
     *
     * @return int|null
     */
    public static function getMergeActivityTypeID()
    {
        static $merge_activity_id = null;
        if ($merge_activity_id === null) {
            try {
                $merge_activity_id = civicrm_api3(
                    'OptionValue',
                    'getvalue',
                    [
                        'return'          => 'value',
                        'option_group_id' => 'activity_type',
                        'name'            => 'Contact Merged'
                    ]
                );
            } catch (Exception $ex) {
                // doesn't exist
                $merge_activity_id = 0;
            }
        }
        return $merge_activity_id;
    }
}
