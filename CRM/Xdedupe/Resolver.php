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
    $resolver_list = [];
    \Civi::dispatcher()->dispatch('civi.xdedupe.resolvers', \Civi\Core\Event\GenericHookEvent::create(['list' => &$resolver_list]));
    return $resolver_list;
  }

  /**
   * Get a list of all available finder classes
   *
   * @return array class => name
   */
  public static function getResolverList() {
    $resolver_list = [];
    $resolver_instances = self::getResolverInstances();
    foreach ($resolver_instances as $resolver) {
      $resolver_list[get_class($resolver)] = $resolver->getName();
    }
    return $resolver_list;
  }

  /**
   * Get an instance of each finder
   */
  public static function getResolverInstances() {
    $resolver_list = [];
    $resolver_classes = self::getResolvers();
    foreach ($resolver_classes as $resolver_class) {
      if (class_exists($resolver_class)) {
        $resolver_list[] = new $resolver_class(null); // dirty, i know...
      }
    }
    return $resolver_list;
  }

  /**
   * Add a merge detail (detailed merge changes)
   *
   * @param $information string info
   */
  public function addMergeDetail($information) {
    $resolver_name = $this->getName();
    $this->merge->addMergeDetail("{$information} (resolver '{$resolver_name}')");
  }
}
