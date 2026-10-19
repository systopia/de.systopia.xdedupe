<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| Author: Rich Lott / Artful Robot                       |
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
 * If there is conflicting addresses with the same type,
 *   make a judgement about whether the addresses are actually
 *   different; if so, prefer the main one so long as it has
 *   street and postal code.
 */
class CRM_Xdedupe_Resolver_AddressHarmonise extends CRM_Xdedupe_Resolver
{

    static $handled_address_fields = [
        'location_type_id:name',
        'street_address',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'city',
        'postal_code',
        'state_province_id',
        'county_id',
        'country_id:abbr'
    ];

    /**
     * get the name of the resolver
     * @return string name
     */
    public function getName()
    {
        return E::ts("Address Harmoniser");
    }

    /**
     * get an explanation what the resolver does
     * @return string name
     */
    public function getHelp()
    {
        return E::ts(
            "Move all addresses onto the main contact and try to de-dupe them on a per-location-type basis, preferring fuller data and using similarity matching."
        );
    }

    /**
     * Resolve anything in addresses that could cause a conflict.
     *
     * This is quite brute-force. For each location type:
     * - delete 'incomplete' addresses (those without street, city, postal)
     *   if there's a complete one.
     * - delete same and similar addresses, preferring the one that has the most data
     *   and if the same amount of data, delete the one belonging to the other
     *   contact.
     * - move any remaining addresses onto the main contact.
     *
     * Note that this can result in one contact having many addresses per
     * location type. This is not great, but this is a feature of the input data
     * already, so it's not making anything worse.
     *
     * An alternate version could force-choose a single address per location type.
     *
     * At the end of this process, none of the 'other' contacts have any
     * addresses.
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
        $changesMade = FALSE;

        // Load addresses.
        $unsorted = Civi\Api4\Address::get(FALSE)
            ->addWhere('contact_id', 'IN', [$main_contact_id, ...$other_contact_ids])
            ->setSelect(['contact_id', ... static::$handled_address_fields])
            ->execute()->getArrayCopy();

        // Combine all addresses per loc type.
        // Within each loc type, dedupe, preferring fuller addresses, or the
        // main contact's address.

        $addressesByLocType = [];
        foreach ($unsorted as $x) {
            $addressesByLocType[$x['location_type_id:name']][] = $x
                + ['isMainContact' => (int) ($x['contact_id'] == $main_contact_id)];
        }

        // Dedupe addresses for each loc type, within this one contact.
        foreach ($addressesByLocType as $locType => $ads) {
            [$deletions, $moves] = $this->dedupeAddresses($ads);
            if ($deletions) {
                $this->addMergeDetail(implode("\n", $deletions));
                Civi\Api4\Address::delete(FALSE)->addWhere('id', 'IN', array_keys($deletions))->execute();
                $changesMade = TRUE;
            }
            if ($moves) {
                $this->addMergeDetail(implode("\n", $moves));
                Civi\Api4\Address::update(FALSE)
                    ->addValue('contact_id', $main_contact_id)
                    ->addWhere('id', 'IN', array_keys($moves))
                    ->execute();
                $changesMade = TRUE;
            }
        }

        return $changesMade;
    }

    /**
     * Try to establish whether any of the addresses in the array are the same.
     *
     * @param array $addresses
     *
     * @return array
     *    Two values: deletions, moves. Each is itself an array whose keys are
     *    address ID, value is log message.
     */
    protected function dedupeAddresses(array $addresses)
    {
        $deletions = [];
        $moves = [];
        $addressFields = [
            'street_address',
            'supplemental_address_1',
            'supplemental_address_2',
            'supplemental_address_3',
            'city',
            'postal_code',
            'state_province_id',
            'county_id',
            'country_id:abbr'
        ];
        // If an address is missing any of these details, we consider it
        // is low value; incomplete.
        $addressFieldsKey = [ 'street_address', 'city', 'postal_code' ];
        // Note: 'a' and 'b' here are local; they don't refer to main/other
        // contact. Each address contains an isMainContact key for that purpose.
        while ($a = array_shift($addresses)) {
            // this is constant across all addresses.
            $locTypeName = $a['location_type_id:name'];
            // If country is missing, add in US.
            $a['country_id:abbr'] = $a['country_id:abbr'] ?: 'US';

            // Compare to the rest of the addresses.
            $aCountValues = 0;
            $aMissing = [];
            foreach ($addressFields as $field) {
                $a[$field] = $this->normalise($a[$field] ?? '');
                $aCountValues += (int) !empty($a[$field]);
                if (in_array($field, $addressFieldsKey) && empty($a[$field])) {
                    $aMissing[] = $field;
                }
            }
            $aMissing = implode(', ', $aMissing);
            // Cache these for log messages.
            $aNormal = implode("\t", $a);
            $aDesc = "$locTypeName address ($a[id]) for contact $a[contact_id]";

            // Assume we'll keep this address until proven otherwise.
            if (!$a['isMainContact']) {
                $moves[$a['id']] = "Moving $aDesc to main contact.";
            }

            // Compare against remaining addresses.
            foreach ($addresses as $bIdx => $b) {
                $b['country_id:abbr'] = $b['country_id:abbr'] ?: 'US';

                $bCountValues = 0;
                $bMissing = [];
                $similarScore = 0;
                foreach ($addressFields as $field) {
                    $b[$field] = $this->normalise($b[$field] ?? '');
                    $bCountValues += (int) !empty($b[$field]);
                    if (in_array($field, $addressFieldsKey) && empty($b[$field])) {
                        $bMissing[] = $field;
                    }
                    if (!empty($b[$field]) && !empty($a[$field])) {
                        $similarScore += $this->similar($a[$field], $b[$field]);
                    }
                }
                $bMissing = implode(', ', $bMissing);
                $bNormal = implode("\t", $b);

                // Reusable strings for log messages.
                $bDesc = "$locTypeName address ($b[id]) for contact $b[contact_id]";

                $this->addMergeDetail("Comparing\n$aNormal\n$bNormal\nsimilar score: $similarScore");

                if (!$aMissing) {
                    // The 'a' address looks plausible.
                    if ($bMissing) {
                        // The 'b' address is incomplete, prefer a.
                        $deletions[$b['id']] = "Deleting {$bDesc} because it is incomplete (missing $bMissing) and we already have a complete one.\n$aNormal\n$bNormal";
                        // Remove the 'b' address from the list
                        unset($addresses[$bIdx]);
                        continue;
                    }
                    // a and b both have the key fields.
                    if ($similarScore === 0) {
                        // a and b are identical.
                        $deletions[$b['id']] = "Deleting {$bDesc} because it is identical to another.\n$aNormal\n$bNormal";
                        unset($addresses[$bIdx]);
                        continue;
                    }
                    // Allow one metaphone difference per item compared.
                    if ($similarScore <= max($aCountValues, $bCountValues)) {
                        // a and b are probably the same. If one has
                        // 2+ more data items, keep that one.
                        $moreDataInA = $aCountValues - $bCountValues;
                        $isMainDiff = $a['isMainContact'] - $b['isMainContact'];
                        if ($isMainDiff === 0) {
                            // Both Main, or neither is Main. Just go on which
                            // has more data, keeping 'a' if equal.
                            if ($moreDataInA >= 0) {
                                $deletions[$b['id']] = "Deleting {$bDesc} because it is a likely-duplicate of another, which has $moreDataInA details.\n$aNormal\n$bNormal";
                                unset($addresses[$bIdx]);
                            }
                            else {
                                // more data in B
                                $moreDataInB = - $moreDataInA;
                                $deletions[$a['id']] = "Deleting {$aDesc} because it is a less-complete likely-duplicate of another which has {$moreDataInB} more details.\n$aNormal\n$bNormal";
                                unset($moves[$a['id']]);
                                continue 2;
                            }
                        }
                        else if (abs($moreDataInA) >= 2) {
                            // Allow 2 data items more to be more important than
                            // isMainContact.
                            if ($moreDataInA >= 0) {
                                $deletions[$b['id']] = "Deleting {$bDesc} because it is a likely-duplicate of another, which has $moreDataInA more details.\n$aNormal\n$bNormal";
                                unset($addresses[$bIdx]);
                            }
                            else {
                                // more data in B
                                $moreDataInB = - $moreDataInA;
                                $deletions[$a['id']] = "Deleting {$aDesc} because it is a less-complete likely-duplicate of another which has {$moreDataInB} more details.\n$aNormal\n$bNormal";
                                unset($moves[$a['id']]);
                                continue 2;
                            }
                        }
                        else {
                            // zero or one extra vals between the addresses,
                            // prefer Main contact's.
                            if ($isMainDiff >= 0) {
                                $deletions[$b['id']] = "Deleting {$bDesc} because it is a likely-duplicate of another, which belongs to the main contact.\n$aNormal\n$bNormal";
                                unset($addresses[$bIdx]);
                            }
                            else {
                                $deletions[$a['id']] = "Deleting {$aDesc} because it is a likely-duplicate of another, which belongs to the main contact.\n$aNormal\n$bNormal";
                                unset($moves[$a['id']]);
                                continue 2;
                            }
                        }
                    }
                    else {
                        // a and b seem quite different. Keep them both.
                    }
                }
                else {
                    // 'a' is incomplete...
                    if (!$bMissing) {
                        $deletions[$a['id']] = "Deleting {$a['location_type_id:name']} address $a[id] because it is incomplete (missing $aMissing) and we have another which is complete.\n$aNormal\n$bNormal";
                        unset($moves[$a['id']]);
                        continue 2;
                    }
                    else {
                        // 'a' is incomplete, but so is 'b'... Leave it for now,
                        // 'a' might get removed if there's another 'b' which
                        // is complete.
                    }
                }
            } /* end of 'b' addresses to compare with */
        } /* no more addresses to compare */

        return [$deletions, $moves];
    }

    protected function normalise(string $a): string {
        // normalise strings. trim, internal spaces normalised to one space,
        // punctuation removed, lowercase.
        return mb_strtolower(
                preg_replace('/\s+/', ' ',
                    preg_replace(
                        '@[.;,;:\'’‘_()\[\]{}#$-\/]+@u',
                        '',
                        trim($a)
                    )
                )
        );
    }

    protected function similar(string $a, string $b) {
        // normalise strings. trim, internal spaces normalised to one space,
        // punctuation removed, lowercase.
        [$a, $b] = array_map([$this, 'normalise'], [$a, $b]);
        if ($a === $b) {
            // Same.
            return 0;
        }
        // Some difference.
        // If there are any numbers in the strings, these *must* match.
        [$nA, $nB] = array_map(
            fn($x) => preg_replace('/[^0-9]+/', '', $x), 
            [$a, $b]
        );
        if ($nA && $nB && $nA !== $nB) {
            // Numbers are different.
            return 100;
        }

        // Strip out any numbers, re-normalise spaces.
        // since 1 2 3 would result in two spaces.
        [$a, $b] = array_map(
            fn($x) => preg_replace('/\s+/', ' ', preg_replace('/[0-9]+/', '', $x)), 
            [$a, $b]
        );

        // If the characters are ascii, we can compare the sound of the text
        // using metaphone and levenshtein. Otherwise we give up.
        [$aAscii, $bAscii] = array_map(
            fn($x) => preg_match('/^[a-zA-Z ]+$/', $x),
            [$a, $b]
        );
        if (!$aAscii || !$bAscii) {
            // Can't compare further, assume different
            return 101;
        }

        // How similar do these sound?
        $difference = levenshtein(metaphone($a), metaphone($b));

        return $difference;
    }

}