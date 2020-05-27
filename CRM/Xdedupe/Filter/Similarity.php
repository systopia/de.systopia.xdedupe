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
 * Implement a filter that excludes contacts that don't meet the string similarity
 */
abstract class CRM_Xdedupe_Filter_Similarity extends CRM_Xdedupe_Filter
{

    protected $attributes = [];
    protected $threshold = 0.95;
    protected $batch_size = 250;
    protected $data_cache = [];

    /**
     * Filter dedupe run, i.e. remove items that don't match the criteria
     *
     * @param $run CRM_Xdedupe_DedupeRun
     */
    public function purgeResults($run)
    {
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
                $contact_ids[] = $main_contact_id; // main contact ID is not included in the list
                sort($contact_ids);
                $matrix = $this->buildSimilarityMatrix($contact_ids);

                // extract best tuple
                $best_tuple = $this->getBestTuple($matrix, $contact_ids);

                // evaluate
                if ($best_tuple) {
                    // if there is a best tuple...
                    if ($best_tuple != $contact_ids) {
                        $run->updateTuple($main_contact_id, $best_tuple);
                    }
                } else {
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
     * @param $contact_ids array
     * @return array two-dimensional array
     */
    protected function buildSimilarityMatrix($contact_ids)
    {
        $matrix = [];
        for ($i = 0; $i < count($contact_ids); $i++) {
            $contact_id_a = $contact_ids[$i];
            for ($j = $i + 1; $j < count($contact_ids); $j++) {
                $contact_id_b = $contact_ids[$j];
                $similarity   = $this->similarity($contact_id_a, $contact_id_b);
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
     * @param $matrix
     * @param $contact_ids
     */
    protected function getBestTuple($matrix, $contact_ids)
    {
        // TODO: better algorithm!?
        //  Benedikt proposes "growing" tuples
        // try finding maximum sized tuples
        for ($size = count($contact_ids); $size > 1; $size--) {
            // try to get the best tuple of size $size
            $tuples = $this->getTuplesOfSize($size, $contact_ids, $matrix);
            if ($tuples) {
                // simply select the best
                $best_rating = 0;
                $best_tuple  = null;
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
        return null;
    }

    /**
     * calculate the average similarity of all elements
     *
     * @param $tuple  array tuple
     * @param $matrix array similarity matrix
     *
     * @return float average rating
     */
    protected function rateTuple($tuple, $matrix)
    {
        $rating_count = 0;
        $rating_sum   = 0.0;
        for ($i = 0; $i < count($tuple); $i++) {
            $contact_id_a = $tuple[$i];
            for ($j = $i + 1; $j < count($tuple); $j++) {
                $contact_id_b = $tuple[$j];
                $rating_count += 1;
                $rating_sum   += $matrix[$contact_id_a][$contact_id_b];
            }
        }
        if ($rating_count) {
            return $rating_sum / (float)$rating_count;
        } else {
            return 0.0;
        }
    }

    /**
     * Get all (viable) tuples of size $size
     *
     * @param $size         integer size of tuples wanted
     * @param $contact_ids  array   list of elements
     * @param $matrix       array   matrix defining which combinations are allowed
     * @param $cache        array   internal tuple cache
     *
     * @return array tuples
     * @todo better algorithm!?
     */
    protected function getTuplesOfSize($size, $contact_ids, $matrix, &$cache = null)
    {
        if ($size <= 0) {
            exit("WTF?");
        } // todo: remove
        if ($cache === null) {
            $cache = [];
        }

        $cache_key = $size . '-' . implode(',', $contact_ids);
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $tuples = [];
        if ($size == count($contact_ids)) {
            // for the (one) full tuple, this is a yes or no decision:
            for ($i = 0; $i < count($contact_ids); $i++) {
                $contact_id_a = $contact_ids[$i];
                for ($j = $i + 1; $j < count($contact_ids); $j++) {
                    $contact_id_b = $contact_ids[$j];
                    if (!isset($matrix[$contact_id_a][$contact_id_b])) {
                        // at least one pair is not similar, so: NO
                        return [];
                    }
                }
            }
            // all combinations are similar
            return [$contact_ids];
        } elseif ($size == 1) {
            // 2-tuples we can create
            foreach ($contact_ids as $contact_id) {
                $tuples[] = [$contact_id];
            }
        } else {  // es we use the lower function and combine
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
     * @param $contact_id_a integer first contact
     * @param $contact_id_b integer second contact
     * @return float 0...1
     */
    protected function similarity($contact_id_a, $contact_id_b)
    {
        if ($this->attributes) {
            $similarity = 0.00;
            foreach ($this->attributes as $attribute) {
                $value_a    = CRM_Utils_Array::value($attribute, $this->data_cache[$contact_id_a], '');
                $value_b    = CRM_Utils_Array::value($attribute, $this->data_cache[$contact_id_b], '');
                $similarity += (float)levenshtein($value_a, $value_b) / (float)max(strlen($value_a), strlen($value_b));
            }
            return 1.0 - ($similarity / (float)count($this->attributes));
        }
        return 0.0;
    }

    /**
     * Cache the data to support the contact comparison
     *   Overwrite to support _your_ implementation
     *
     * Default implementation: cache the this->attributes of each contact
     *
     * @param $contact_ids array list of contact IDs
     */
    protected function cacheDataForContacts($contact_ids)
    {
        if ($this->attributes) {
            $this->data_cache = [];
            $query            = civicrm_api3(
                'Contact',
                'get',
                [
                    'id'           => ['IN' => $contact_ids],
                    'option.limit' => 0,
                    'sequential'   => 0,
                    'return'       => "id," . implode($this->attributes)
                ]
            );
            $this->data_cache = $query['values'];
        }
    }
}
