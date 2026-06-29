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

/**
 * Implement a filter that excludes contacts that don't meet the string similarity
 */
// phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Xdedupe_Filter_Similarity extends CRM_Xdedupe_Filter {
// phpcs:enable

  /**
   * @var array<string> list of attributes to compare.
   */
  protected array $attributes = [];
  protected float $threshold = 0.95;
  protected int $batch_size = 250;

  /**
   * @var array<string, array<string, string>>
   */
  protected array $data_cache = [];

  /**
   * @inheritDoc
   */
  public function purgeResults(CRM_Xdedupe_DedupeRun $run): void {
    $offset      = 0;
    $tuple_count = $run->getTupleCount();

    // work in chunks of ($this->batch_size)
    while ($offset < $tuple_count) {
      // prepare this chunk
      $tuples = $run->getTuples($this->batch_size, $offset);
      $offset += $this->batch_size;

      // collect contact IDs
      $all_contact_ids = [];
      foreach ($tuples as $main_contact_id => $contact_ids) {
        $all_contact_ids[$main_contact_id] = 1;
        foreach ($contact_ids as $contact_id) {
          $all_contact_ids[$contact_id] = 1;
        }
      }
      $all_contact_ids = array_keys($all_contact_ids);
      $this->cacheDataForContacts($all_contact_ids);

      // evaluate all tuples
      foreach ($tuples as $main_contact_id => $contact_ids) {
        // create similarity matrix
        // main contact ID is not included in the list
        $contact_ids[] = $main_contact_id;
        sort($contact_ids);
        $matrix = $this->buildSimilarityMatrix($contact_ids);

        // extract best tuple
        $best_tuple = $this->getBestTuple($matrix, $contact_ids);

        // evaluate
        if ($best_tuple) {
          // if there is a best tuple...
          if ($best_tuple !== $contact_ids) {
            $run->updateTuple($main_contact_id, $best_tuple);
          }
        }
        else {
          // there is no best tuple -> remove
          $run->removeTuple($main_contact_id);
          $offset      -= 1;
          $tuple_count -= 1;
        }
      }
    }
  }

  /**
   * Create a matrix of all similarities between the contacts
   *
   * @param list<int> $contact_ids
   * @return array two-dimensional array
   */
  protected function buildSimilarityMatrix(array $contact_ids): array {
    $matrix = [];
    $contactIdsCount = count($contact_ids);
    foreach ($contact_ids as $i => $contact_id_a) {
      for ($j = ($i + 1); $j < $contactIdsCount; $j++) {
        $contact_id_b = $contact_ids[$j];
        $similarity = $this->similarity($contact_id_a, $contact_id_b);
        if ($similarity >= $this->threshold) {
          $matrix[$contact_id_a][$contact_id_b] = $similarity;
          $matrix[$contact_id_b][$contact_id_a] = $similarity;
        }
      }
    }
    return $matrix;
  }

  /**
   * Find the largest tuple where the minimum similarity is given
   *
   * @param array<array<int, float>> $matrix
   * @param array<int> $contact_ids
   */
  protected function getBestTuple(array $matrix, array $contact_ids) {
    // TODO: better algorithm!?
    //  Benedikt proposes "growing" tuples
    // try finding maximum sized tuples
    for ($size = count($contact_ids); $size > 1; $size--) {
      // try to get the best tuple of size $size
      $tuples = $this->getTuplesOfSize($size, $contact_ids, $matrix);
      if ($tuples) {
        // simply select the best
        $best_rating = 0;
        $best_tuple  = NULL;
        foreach ($tuples as $tuple) {
          $rating = $this->rateTuple($tuple, $matrix);
          if ($rating > $best_rating) {
            $best_rating = $rating;
            $best_tuple  = $tuple;
          }
        }
        return $best_tuple;
      }
    }
    return NULL;
  }

  /**
   * calculate the average similarity of all elements
   *
   * @param array<array-key, int> $tuple tuple
   * @param array<int, array<int, float>> $matrix similarity matrix
   *
   * @return float average rating
   */
  protected function rateTuple(array $tuple, array $matrix): float {
    $rating_count = 0;
    $rating_sum = 0.0;
    $tupleCount = count($tuple);
    foreach ($tuple as $i => $contact_id_a) {
      for ($j = ($i + 1); $j < $tupleCount; $j++) {
        $contact_id_b = $tuple[$j];
        ++$rating_count;
        $rating_sum += $matrix[$contact_id_a][$contact_id_b];
      }
    }
    if ($rating_count) {
      return $rating_sum / (float) $rating_count;
    }

    return 0.0;
  }

  /**
   * Get all (viable) tuples of size $size
   *
   * @param int $size size of tuples wanted
   * @param array<int> $contact_ids list of elements
   * @param array<array<int, float>> $matrix matrix defining which combinations are allowed
   * @param array|null $cache internal tuple cache
   *
   * @return array tuples
   * @todo better algorithm!?
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  protected function getTuplesOfSize(int $size, array $contact_ids, array $matrix, ?array &$cache = NULL): array {
  // phpcs:enable
    if ($size <= 0) {
      exit('WTF?');
    } // todo: remove
    if ($cache === NULL) {
      $cache = [];
    }

    $cache_key = $size . '-' . implode(',', $contact_ids);
    if (isset($cache[$cache_key])) {
      return $cache[$cache_key];
    }

    $tuples = [];
    if ($size === count($contact_ids)) {
      // for the (one) full tuple, this is a yes or no decision:
      $contactIdsCount = count($contact_ids);
      foreach ($contact_ids as $i => $contact_id_a) {
        for ($j = ($i + 1); $j < $contactIdsCount; $j++) {
          $contact_id_b = $contact_ids[$j];
          if (!isset($matrix[$contact_id_a][$contact_id_b])) {
            // at least one pair is not similar, so: NO
            return [];
          }
        }
      }
      // all combinations are similar
      return [$contact_ids];
    }

    if ($size === 1) {
      // 2-tuples we can create
      foreach ($contact_ids as $contact_id) {
        $tuples[] = [$contact_id];
      }
      // es we use the lower function and combine
    }
    else {
      $lower_tuples = $this->getTuplesOfSize($size - 1, $contact_ids, $matrix, $cache);
      foreach ($lower_tuples as $lower_tuple) {
        // see if we can add any of the contact IDs
        $candidates = array_diff($contact_ids, $lower_tuple);
        foreach ($candidates as $candidate_id) {
          // let's see if we can add $contact ID
          foreach ($lower_tuple as $tuple_element_id) {
            if (!isset($matrix[$candidate_id][$tuple_element_id])) {
              // this won't work
              break 2;
            }
          }
          // we got here, because the new element fits
          $new_tuple = array_merge($lower_tuple, [$candidate_id]);
          sort($new_tuple);
          if (!in_array($new_tuple, $tuples)) {
            $tuples[] = $new_tuple;
          }
        }
      }
    }

    $cache[$cache_key] = $tuples;
    return $tuples;
  }

  /**
   * Calculate the similarity between two contacts
   *
   * @param int $contact_id_a first contact
   * @param int $contact_id_b second contact
   * @return float 0...1
   */
  protected function similarity(int $contact_id_a, int $contact_id_b): float {
    if (count($this->attributes) > 0) {
      $similarity = 0.00;
      foreach ($this->attributes as $attribute) {
        $value_a    = $this->data_cache[$contact_id_a][$attribute] ?? '';
        $value_b    = $this->data_cache[$contact_id_b][$attribute] ?? '';
        $max_length = max(strlen($value_a), strlen($value_b));
        if ($max_length > 0) {
          $similarity += (float) levenshtein($value_a, $value_b) / (float) $max_length;
        }
      }
      return 1.0 - ($similarity / (float) count($this->attributes));
    }
    return 0.0;
  }

  /**
   * Cache the data to support the contact comparison
   *   Overwrite to support _your_ implementation
   *
   * Default implementation: cache the "this->attributes" of each contact
   *
   * @param array<int> $contact_ids list of contact IDs
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function cacheDataForContacts(array $contact_ids): void {
    if (count($this->attributes) > 0) {
      $query = civicrm_api3(
        'Contact',
        'get',
        [
          'id' => ['IN' => $contact_ids],
          'option.limit' => 0,
          'sequential' => 0,
          'return' => 'id,' . implode($this->attributes),
        ]
      );
      $this->data_cache = $query['values'];
    }
  }

}
