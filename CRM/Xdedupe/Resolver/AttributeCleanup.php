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

declare(strict_types = 1);

use CRM_Xdedupe_ExtensionUtil as E;

/**
 * Implements a resolver for basic contact fields
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Resolver_AttributeCleanup extends CRM_Xdedupe_Resolver {
// phpcs:enable
  /**
   * @var string name of the attribute to clean up */
  protected $attribute_name;

  /**
   * @var string human-readable label of the attribute to clean up */
  protected $attribute_label;

  /**
   * @var array list of preg_replace patterns as a tuple [search pattern, replace pattern] */
  protected $regular_expressions;

  public function __construct(?CRM_Xdedupe_Merge $merge, $attribute_name) {
    parent::__construct($merge);
    $this->attribute_name = $attribute_name;
    $this->attribute_label = $attribute_name;
  }

  /**
   * Get a human-readable attribute name
   */
  public function getAttributeName(): string {
    return $this->attribute_name;
  }

  /**
   * Get a human-readable attribute name
   */
  public function getAttributeLabel(): string {
    return $this->attribute_label;
  }

  /**
   * @inheritDoc
   */
  public function getContactAttributes(): array {
    return [$this->attribute_name];
  }

  /**
   * @inheritDoc
   */
  public function resolve($main_contact_id, $other_contact_ids): bool {
    $something_changed = FALSE;
    $contact_ids = array_merge([$main_contact_id], $other_contact_ids);
    foreach ($contact_ids as $contact_id) {
      $contact   = $this->getContext()?->getContact($contact_id);
      $new_value = $old_value = $contact[$this->attribute_name] ?? NULL;
      foreach ($this->regular_expressions as $search_replace) {
        $new_value = preg_replace($search_replace[0], $search_replace[1], $new_value);
      }
      if ($new_value != $old_value) {
        civicrm_api3(
        'Contact',
        'create',
        [
          'id'                  => $contact_id,
          $this->attribute_name => $new_value,
        ]
        );
        $this->getContext()?->unloadContact($contact_id);
        $this->addMergeDetail(
        E::ts(
          // phpcs:ignore Generic.Files.LineLength.TooLong
            "Changed %1 from '<code style='white-space: pre;'>%2</code>' to '<code style='white-space: pre;'>%3</code>' in contact [%4] to avoid merge conflicts",
            [
              1 => $this->getAttributeLabel(),
              2 => $old_value,
              3 => $new_value,
              4 => $contact_id,
            ]
        )
        );
        $something_changed = TRUE;
      }
    }
    return $something_changed;
  }

  /**
   * @inheritDoc
   */
  public function getName(): string {
    return E::ts("Cleanup '%1'", [1 => $this->getAttributeName()]);
  }

  /**
   * @inheritDoc
   */
  public function getHelp(): string {
    return E::ts(
        "Cleans up the '%1' attribute before merging to avoid conflicts"
    );
  }

}
