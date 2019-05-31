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
 * API Specs:Xdedupe.merge: Automatically merge multiple contacts
 *  using resolvers
 */
function _civicrm_api3_xdedupe_merge_spec(&$spec) {
  $spec['main_contact_id'] = array(
      'name'         => 'main_contact_id',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_INT,
      'title'        => 'Main Contact ID',
      'description'  => 'Contact ID of the contact to prevail in the merging process',
  );
  $spec['other_contact_ids'] = array(
      'name'         => 'other_contact_ids',
      'api.required' => 1,
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Other Contact IDs',
      'description'  => 'Comma-separated list of other contact IDs to be merged into the main contact.',
  );
  $spec['force_merge'] = array(
      'name'         => 'force_merge',
      'api.default'  => 0,
      'type'         => CRM_Utils_Type::T_BOOLEAN,
      'title'        => 'Force-Merge?',
      'description'  => 'Should the contacts be force-merged, i.e. merged even if that means losing data?',
  );
  $spec['resolvers'] = array(
      'name'         => 'resolvers',
      'api.default'  => '',
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Resolver List',
      'description'  => 'Comma-separated list of resolver classes, to automatically deal with data conflicts.',
  );
  $spec['dedupe_run'] = array(
      'name'         => 'dedupe_run',
      'api.default'  => '',
      'type'         => CRM_Utils_Type::T_STRING,
      'title'        => 'Dedupe Run ID',
      'description'  => 'If given, the tuple will be removed from this dedupe run, if the merge was successful',
  );
}

/**
 * API Specs:Xdedupe.merge: Automatically merge multiple contacts
 *  using resolvers
 *
 * @param array $params see specs
 * @return array result merge result
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_xdedupe_merge($params) {
  try {
    $merger = new CRM_Xdedupe_Merge($params);
    $merger->multiMerge($params['main_contact_id'], explode(',', $params['other_contact_ids']));
    $result = $merger->getStats();

    if (!empty($result['tuples_merged']) && !empty($params['dedupe_run'])) {
      // merge successful -> remove from dedupe run
      try {
        $dedupe_run = new CRM_Xdedupe_DedupeRun($params['dedupe_run']);
        $dedupe_run->removeTuple($params['main_contact_id']);
      } catch (Exception $ex) {
        // probably means that the run doesn't exist, no problem
      }
    }

    $null = NULL;
    return civicrm_api3_create_success([], $params, 'Xdedupe', 'merge', $null, $result);
  } catch (Exception $ex) {
    throw new CiviCRM_API3_Exception($ex->getMessage(), $ex->getCode());
  }
}

