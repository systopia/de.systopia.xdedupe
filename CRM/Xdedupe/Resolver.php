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

declare(strict_types = 1);

use Civi\Core\Event\GenericHookEvent;

/**
 * Implement a "Resolver", i.e., a class that can automatically resolve certain merge conflicts
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Resolver {
  // phpcs:enable

  protected ?CRM_Xdedupe_Merge $merge = NULL;

  public function __construct(?CRM_Xdedupe_Merge $merge) {
    $this->merge = $merge;
  }

  /**
   * Get the merge object, this is running in
   *
   * @return \CRM_Xdedupe_Merge|null context
   */
  public function getContext(): ?CRM_Xdedupe_Merge {
    return $this->merge;
  }

  /**
   * Get the spec (i.e., class name) that refers to this resolver
   *
   * @return string spec string
   */
  public function getSpec(): string {
    return static::class;
  }

  /**
   * Resolve the merge conflicts by editing the contact
   *
   * CAUTION: IT IS PARAMOUNT TO UNLOAD A CONTACT FROM THE CACHE IF CHANGED AS FOLLOWS:
   *  $this->merge->unloadContact($contact_id)
   *
   * @param int $main_contact_id the main contact ID
   * @param list<int> $other_contact_ids other contact IDs
   *
   * @return bool TRUE, if there was a conflict to be resolved
   * @throws Exception if the conflict couldn't be resolved
   */
  abstract public function resolve(int $main_contact_id, array $other_contact_ids): bool;

  /**
   * Run some postprocessing, e.g., cleanup or similar, after the merge was successful
   *
   * @param int $main_contact_id the main contact ID
   * @param array $other_contact_ids other contact IDs
   *
   * @throws Exception if the conflict couldn't be resolved
   */
  public function postProcess(int $main_contact_id, array $other_contact_ids): void {}

  /**
   * Report the contact attributes that this resolver requires
   *
   * @return list<string> list of contact attributes
   */
  public function getContactAttributes(): array {
    return [];
  }

  /**
   * get the name of the resolver
   *
   * @return string name
   */
  abstract public function getName(): string;

  /**
   * get an explanation what the resolver does
   *
   */
  abstract public function getHelp(): string;

  /**
   * Get a list of all available resolver classes
   *
   * @return list<string> list of class names
   */
  public static function getResolvers(): array {
    $resolver_list = [];
    Civi::dispatcher()->dispatch(
      'civi.xdedupe.resolvers',
      GenericHookEvent::create(['list' => &$resolver_list])
    );
    return $resolver_list;
  }

  /**
   * Get a list of all available resolver classes
   *
   * @return array<string, string> class => name
   */
  public static function getResolverList(): array {
    $resolver_list = [];
    foreach (self::getResolverInstances() as $resolver) {
      $resolver_list[$resolver->getSpec()] = $resolver->getName();
    }
    return $resolver_list;
  }

  /**
   * @return array<CRM_Xdedupe_Resolver>
   */
  public static function getResolverInstances(): array {
    $resolver_list = [];
    foreach (self::getResolvers() as $resolver_class) {
      $resolver = self::getResolverInstance($resolver_class);
      if ($resolver) {
        $resolver_list[] = $resolver;
      }
    }
    return $resolver_list;
  }

  /**
   * Generate a resolver instance
   *
   * @param string $resolver_spec spec or class name
   * @param CRM_Xdedupe_Merge|NULl $merge merge object
   *
   * @return CRM_Xdedupe_Resolver|NULL resolver instance
   */
  public static function getResolverInstance(string $resolver_spec, ?CRM_Xdedupe_Merge $merge = NULL): ?self {
    $resolver_parameter = NULL;
    if (str_contains($resolver_spec, ':')) {
      // this is a spec, i.e., a class name: parameter
      [$resolver_spec, $resolver_parameter] = explode(':', $resolver_spec, 2);
    }
    if (class_exists($resolver_spec)) {
      if ($resolver_parameter === NULL) {
        return new $resolver_spec($merge);
      }

      return new $resolver_spec($merge, $resolver_parameter);
    }
    return NULL;
  }

  /**
   * Add a merge detail (detailed merge changes)
   *
   * @param string $information info
   */
  public function addMergeDetail(string $information): void {
    $resolver_name = $this->getName();
    $merge_detail = "$information (resolver '$resolver_name')";
    $this->merge->addMergeDetail($merge_detail);
    Civi::log()->debug('X-Dedupe: ' . $merge_detail);
  }

}
