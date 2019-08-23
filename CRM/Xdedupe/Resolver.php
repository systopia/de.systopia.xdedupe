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
 * Implement a "Resolver", i.e. a class that can automatically resolve certain merge conflicts
 */
abstract class CRM_Xdedupe_Resolver {

  /** @var $merge CRM_Xdedupe_Merge  */
  protected $merge = NULL;

  public function __construct($merge) {
    $this->merge = $merge;
  }

  /**
   * Get the merge object, this is running in
   * @return CRM_Xdedupe_Merge context
   */
  public function getContext() {
    return $this->merge;
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
  public abstract function resolve($main_contact_id, $other_contact_ids);

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return array list of contact attributes
   */
  public function getContactAttributes() {
    return [];
  }

  /**
   * get the name of the finder
   * @return string name
   */
  public abstract function getName();

  /**
   * get an explanation what the finder does
   * @return string name
   */
  public abstract function getHelp();

  /**
   * Get a list of all available finder classes
   *
   * @return array list of class names
   */
  public static function getResolvers() {
    // todo: use symfony
    return [
        'CRM_Xdedupe_Resolver_ExternalIdentifier',
        'CRM_Xdedupe_Resolver_Language',
        'CRM_Xdedupe_Resolver_OrganisationName',
        'CRM_Xdedupe_Resolver_DropSamePhones',
//        'CRM_Xdedupe_Resolver_DoNotMail',
    ];
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getResolverList() {
    $resolver_list = [];
    $resolver_classes = self::getResolvers();
    foreach ($resolver_classes as $resolver_class) {
      $resolver = new $resolver_class(NULL);
      $resolver_list[$resolver_class] = $resolver->getName();
    }
    return $resolver_list;
  }
}
