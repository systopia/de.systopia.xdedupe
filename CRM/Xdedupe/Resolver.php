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

  /**
   * Select the main contact from a set of contacts
   *
   * @param $main_contact_id   int   the main contact ID
   * @param $other_contact_id  int   other contact ID
   * @return boolean TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  public abstract function resolve($main_contact_id, $other_contact_id);

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
        'CRM_Xdedupe_Resolver_DoNotMail',
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
      $resolver = new $resolver_class();
      $resolver_list[$resolver_class] = $resolver->getName();
    }
    return $resolver_list;
  }
}
