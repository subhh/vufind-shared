<?php

/*
 * This file is part of VuFind Shared Components.
 *
 * VuFind Shared Components is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * VuFind Shared Components is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with VuFind Shared Components. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright (c) 2023 by Staats- und Universitätsbibliothek Hamburg
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

declare(strict_types=1);

namespace SUBHH\VuFind\Shared;

use SUBHH\VuFind\RecordDescription\DescriptionProviderInterface;
use SUBHH\VuFind\RecordDescription\DisplayValue;
use SUBHH\VuFind\RecordDescription\DisplayValueSequence;
use SUBHH\VuFind\RecordDescription\DisplayValueInterface;
use SUBHH\VuFind\RecordDescription\ExternalLink;
use SUBHH\VuFind\RecordDescription\SearchLink;

use VuFind\RecordDriver\SolrMarc;
use VuFind\Marc\MarcReader;

use UnexpectedValueException;

class K10PlusFullDescriptionProvider implements DescriptionProviderInterface
{
    /** @return array<string, DisplayValueInterface[]> */
    final public function createDescription (SolrMarc $record) : array
    {
        $reader = $record->getMarcReader();
        $description = array();
        $description['Title'] = [$this->getTitle($reader)];
        $description['Identifier'] = [new DisplayValue($record->getUniqueID())];
        $description['Preceding Title'] = $this->getPrecedingTitle($reader);
        $description['Succeeding Title'] = $this->getSucceedingTitle($reader);
        $description['Title Variant'] = $this->getTitleVariant($reader);
        $description['Work Title'] = $this->getUniformTitle($reader);
        $description['Summary'] = $this->getSummary($reader);
        $description['Persons'] = $this->getPersons($reader);
        $description['Corporate Bodies'] = $this->getCorporateBody($reader);
        $description['Congresses'] = $this->getCongresses($reader);
        $description['Media Type'] = DisplayValue::newInstances($record->getFormats(), true);
        $description['Form Note'] = $this->getFormNote($reader);
        $description['Language'] = $this->getLanguages($reader);
        $description['Extent'] = DisplayValue::newInstances($reader->getFieldsSubfields('300', ['a', 'b', 'c', 'e']));
        $description['Published'] = $this->getPublicationStatement($reader);
        $description['Containing Work'] = $this->getHostItem($reader);
        $description['Included Items'] = $this->getIncludedItems($reader);
        $description['Edition'] = DisplayValue::newInstances($reader->getFieldsSubfields('250', ['a'], null));
        $description['Note'] = DisplayValue::newInstances($reader->getFieldsSubfields('500', ['a'], null));
        $description['Bibliographic Context'] = $this->getBibliographicContext($reader);
        $description['Basisklassifikation'] = $this->getKeywordsBKL($reader);
        $description['RVK'] = $this->getKeywordsRVK($reader);
        $description['Other Classifications'] = $this->getOtherClassifications($reader);
        $description['Keywords'] = $this->getKeywordChain($reader);
        $description['Other Keywords'] = $this->getKeywords($reader);
        $description['Links'] = $this->getExternalLinks($reader);
        $description['ISBN'] = $this->getIsbn($reader);
        $description['ISSN'] = $this->getIssn($reader);
        $description['Series'] = $this->getSeries($reader);
        return array_filter($description);
    }

    /** @return DisplayValueInterface[] */
    protected function getFormNote (MarcReader $reader) : array
    {
        $notes = array();

        foreach ($reader->getFieldsSubfields('655', ['a', 'b', 'v', 'x', 'y', 'z'], ' ; ') as $value) {
            $notes[] = new DisplayValue($value);
        }

        return $notes;
    }

    /** @return DisplayValueInterface[] */
    protected function getOtherClassifications (MarcReader $reader) : array
    {
        $classes = array();
        foreach ($reader->getFields('983') as $field) {
            if ($reader->getSubfield($field, '2') === '22') {
                if ($term = $reader->getSubfield($field, 'a')) {
                    $value = new SearchLink($term);
                    $value->setSearchType('Subject');
                    $value->setSearchTerm($term);
                    $classes[] = $value;
                }
            }
        }
        return $classes;
    }

    /** @return DisplayValueInterface[] */
    protected function getBibliographicContext (MarcReader $reader) : array
    {
        $context = array();
        foreach ($reader->getFields('776') as $field) {
            if ($title = $reader->getSubfield($field, 't')) {
                $value = new SearchLink($title);
                $this->annotateRelatedSearchLink($reader, $field, $value);
                if ($prefix = array_filter($this->getSubfields($field, 'i', 'n'))) {
                    $value->setPrefix(implode(' ', $prefix) . ': ');
                }
                if ($suffix = $reader->getSubfield($field, 'd')) {
                    $value->setSuffix(' – ' . $suffix);
                }
                $context[] = $value;
            }
        }
        return $context;
    }

    /** @return DisplayValueInterface[] */
    protected function getUniformTitle (MarcReader $reader) : array
    {
        $titles = array();
        foreach ($reader->getFields('240') as $field) {
            if ($term = $reader->getSubfield($field, 'a')) {
                $value = new DisplayValueSequence();

                $title = new SearchLink($term);
                $title->setSearchTerm($term);
                $title->setSearchTermQuote('"');

                $value->append($title);

                if ($authorField = $this->getDataField($reader, '100')) {
                    if ($name = $reader->getSubfield($authorField, 'a')) {
                        $value->append(new DisplayValue($name));

                        $rolecode = $reader->getSubfield($field, '4') ?: 'oth';
                        $role = new DisplayValue($rolecode);
                        $role->setPrefix('[');
                        $role->setSuffix(']');
                        $role->setIsTranslatable(true);
                        $role->setTextDomain('CreatorRoles');
                        $value->append($role);
                    }
                }
                $titles[] = $value;
            }
        }
        return $titles;
    }

    /** @return DisplayValueInterface[] */
    protected function getPrecedingTitle (MarcReader $reader) : array
    {
        $titles = array();
        foreach ($reader->getFields('780') as $field) {
            if ($title = $reader->getSubfield($field, 't')) {
                $value = new SearchLink($title);
                $this->annotateRelatedSearchLink($reader, $field, $value);
                $titles[] = $value;
            }
        }
        return $titles;
    }

    /** @return DisplayValueInterface[] */
    protected function getSucceedingTitle (MarcReader $reader) : array
    {
        $titles = array();
        foreach ($reader->getFields('785') as $field) {
            if ($title = $reader->getSubfield($field, 't')) {
                $value = new SearchLink($title);
                $this->annotateRelatedSearchLink($reader, $field, $value);
                $titles[] = $value;
            }
        }
        return $titles;
    }

    /** @return DisplayValueInterface[] */
    protected function getHostItem (MarcReader $reader) : array
    {
        $hosts = array();
        foreach ($reader->getFields('773') as $field) {
            if ($field['i1'] === '0') {
                $title = $reader->getSubfield($field, 't') ?: implode($reader->getFieldsSubfields('245', ['a']));
                if ($title) {
                    list($relationship, $pubinfo, $parts) = $this->getSubfields($field, 'i', 'd', 'g');

                    $value = new SearchLink($title);
                    if ($relationship) {
                        $value->setPrefix($relationship . ': ');
                    }
                    if ($pubinfo || $parts) {
                        if ($pubinfo && $parts) {
                            $value->setSuffix('.- ' . $pubinfo . ', ' . $parts);
                        } else {
                            $value->setSuffix('.- ' . $pubinfo . $parts);
                        }
                    }

                    $this->annotateRelatedSearchLink($reader, $field, $value);
                    $hosts[] = $value;
                }
            }
        }
        return $hosts;
    }

    /** @return DisplayValueInterface[] */
    protected function getCongresses (MarcReader $reader) : array
    {
        $congresses = array();
        foreach ($reader->getFields('111') as $field) {
            list($name, $date, $place) = $this->getSubfields($field, 'a', 'd', 'c');
            if ($name) {
                if ($date) {
                    $name .= ', ' . $date;
                }
                if ($place) {
                    $name .= ', ' . $place;
                }
                $congress = new SearchLink($name);
                $congress->setSearchType('Title');
                $congresses[] = $congress;
            }
        }
        return $congresses;
    }


    /** @return DisplayValueInterface[] */
    protected function getIncludedItems (MarcReader $reader) : array
    {
        $items = array_merge(
            DisplayValue::newInstances($reader->getFieldsSubfields('501', ['a'], null)),
            DisplayValue::newInstances($reader->getFieldsSubfields('505', ['a'], null))
        );
        return $items;
    }

    /** @return DisplayValueInterface[] */
    protected function getSeries (MarcReader $reader) : array
    {
        $series = array();
        $fields = array_merge(
            $reader->getFields('490'),
            $reader->getFields('830')
        );
        foreach ($fields as $field) {
            if ($label = $reader->getSubfield($field, 'a')) {
                $value = new SearchLink($label);
                $this->annotateRelatedSearchLink($reader, $field, $value);
                if ($volume = $reader->getSubfield($field, 'v')) {
                    $value->setSuffix(' - ' . $volume);
                }
                $series[] = $value;
            }
        }
        return $series;
    }

    /** @return DisplayValueInterface[] */
    protected function getExternalLinks (MarcReader $reader) : array
    {
        $links = array();
        foreach ($reader->getFields('856') as $field) {
            if ($field['i2'] === '2') {
                if ($linkTarget = $reader->getSubfield($field, 'u')) {
                    $link = new ExternalLink($linkTarget);
                    $description = implode(' ', $this->getSubfields($field, '3', 'z'));
                    $link->setLinkLabel($description ?: $linkTarget);
                    $links[] = $link;
                }
            }
        }
        return $links;
    }

    /** @return DisplayValueInterface[] */
    protected function getCorporateBody (MarcReader $reader) : array
    {
        $corporations = array();
        $fields = array_merge(
            [$this->getDataField($reader, '110')],
            $reader->getFields('710')

        );
        foreach ($fields as $field) {
            if ($name = $reader->getSubfield($field, 'a')) {
                $value = new DisplayValueSequence();

                $corporation = new SearchLink($name);
                $corporation->setSearchTermQuote('"');
                $corporation->setSearchType('Person');
                $value->append($corporation);

                if ($date = $reader->getSubfield($field, 'd')) {
                    $value->append(new DisplayValue($date));
                }

                $rolecode = $reader->getSubfield($field, '4') ?: 'oth';
                $role = new DisplayValue($rolecode);
                $role->setPrefix('[');
                $role->setSuffix(']');
                $role->setIsTranslatable(true);
                $role->setTextDomain('CreatorRoles');
                $value->append($role);

                $corporations[] = $value;
            }
        }
        return $corporations;
    }

    /** @return DisplayValueInterface[] */
    protected function getPublicationStatement (MarcReader $reader) : array
    {
        $publicationStatements = array();
        $fields = array_merge($reader->getFields('264'), $reader->getFields('260'));
        foreach ($fields as $field) {
            list($place, $publisher, $date) = $this->getSubfields($field, 'a', 'b', 'c');
            $statement = $place;
            if ($place && ($publisher || $date)) {
                $statement .= ': ';
            }
            if ($publisher) {
                $statement .= $publisher;
            }
            if ($publisher && $date) {
                $statement .= ' ';
            }
            if ($date) {
                $statement .= $date;
            }
            if ($statement) {
                $publicationStatements[] = new DisplayValue($statement);
            }
        }
        foreach ($reader->getFields('501') as $field) {
            $publicationStatements[] = new DisplayValue($reader->getSubfield($field, 'a'));
        }
        return $publicationStatements;
    }

    /** @return DisplayValueInterface[] */
    protected function getKeywords (MarcReader $reader) : array
    {
        $keywords = array();

        $fields = array_merge(
            $reader->getFields('600'),
            $reader->getFields('610'),
            $reader->getFields('630'),
            $reader->getFields('650'),
            $reader->getFields('651'),
        );

        foreach ($fields as $field) {
            if ($term = $reader->getSubfield($field, 'a')) {
                $keyword = new SearchLink($term);
                $keyword->setSearchTerm($term);
                $keyword->setSearchTermQuote('"');
                $keyword->setSearchType('Subject');
                $keywords[] = $keyword;
            }
        }

        return $keywords;
    }

    /** @return DisplayValueInterface[] */
    protected function getKeywordChain (MarcReader $reader) : array
    {
        $keywords = array();
        $fields = array();
        foreach ($reader->getFields('689') as $field) {
            if (ctype_digit($field['i1']) && ctype_digit($field['i2'])) {
                $index = intval($field['i1']);
                $pos = intval($field['i2']);
                $term = $reader->getSubfield($field, 'a');
                $member = new SearchLink($term);
                $member->setSearchTerm('"' . addcslashes($term, '"') . '"');
                $member->setSearchType('Subject');
                $fields[$index][$pos] = $member;
            }
        }
        ksort($fields);
        foreach ($fields as $members) {
            $chain = new DisplayValueSequence(' / ');
            foreach ($members as $member) {
                $chain->append($member);
            }
            $keywords[] = $chain;
        }
        return $keywords;
    }

    /** @return DisplayValueInterface[] */
    protected function getKeywordsRVK (MarcReader $reader) : array
    {
        $keywords = array();
        foreach ($reader->getFields('936') as $field) {
            if ($field['i1'] === 'r' && $field['i2'] === 'v') {
                if ($term = $reader->getSubfield($field, 'a')) {
                    if ($label = $reader->getSubfield($field, 'b')) {
                        $label = $term . ' / ' . $label;
                    } else {
                        $label = $term;
                    }
                    $keyword = new SearchLink($label);
                    $keyword->setSearchTerm($term);
                    $keyword->setSearchType('Class');
                    if ($suffix = $reader->getSubfields($field, 'k')) {
                        $keyword->setSuffix(' [' . implode(', ', $suffix) . ']');
                    }
                    $keywords[] = $keyword;
                }
            }
        }
        return $keywords;
    }

    /** @return DisplayValueInterface[] */
    protected function getKeywordsBKL (MarcReader $reader) : array
    {
        $keywords = array();
        foreach ($reader->getFields('936') as $field) {
            if ($field['i1'] === 'b' && $field['i2'] === 'k') {
                if ($term = $reader->getSubfield($field, 'a')) {
                    $label = array_filter($this->getSubfields($field, 'a', 'j', 'x'));
                    $keyword = new SearchLink(implode(' ', $label));
                    $keyword->setSearchTerm($term);
                    $keyword->setSearchType('BK');
                    $keywords[] = $keyword;
                }
            }
        }
        return $keywords;
    }

    /** @return DisplayValueInterface[] */
    protected function getPersons (MarcReader $reader) : array
    {
        $persons = array();
        $fields = array_merge(
            [$this->getDataField($reader, '100')],
            $reader->getFields('700')
        );
        foreach ($fields as $field) {
            if ($name = $reader->getSubfield($field, 'a')) {
                $value = new DisplayValueSequence();

                $person = new SearchLink($name);
                $person->setSearchType('Person');
                $person->setSearchTermQuote('"');
                $value->append($person);

                if ($date = $reader->getSubfield($field, 'd')) {
                    $value->append(new DisplayValue($date));
                }

                if ($rolecode = $reader->getSubfield($field, '4')) {
                    $role = new DisplayValue($rolecode);
                    $role->setPrefix('[');
                    $role->setSuffix(']');
                    $role->setIsTranslatable(true);
                    $role->setTextDomain('CreatorRoles');
                    $value->append($role);
                }
                $persons[] = $value;
            }
        }
        return $persons;
    }

    /** @return DisplayValueInterface[] */
    protected function getLanguages (MarcReader $reader) : array
    {
        $languages = array();
        foreach ($reader->getFieldsSubfields('041', ['a'], null) as $code) {
            $language = new DisplayValue($code);
            $language->setIsTranslatable(true);
            $languages[] = $language;
        }
        return $languages;
    }

    /** @return DisplayValueInterface[] */
    protected function getSummary (MarcReader $reader) : array
    {
        return DisplayValue::newInstances($reader->getFieldsSubfields('520', ['a'], null));
    }

    /** @return DisplayValue */
    protected function getTitle (MarcReader $reader) : DisplayValue
    {
        // Note: 245 is non-repeatable
        if ($field = $this->getDataField($reader, '245')) {
            $title = $reader->getSubfield($field, 'a');
            if ($subtitle = $reader->getSubfield($field, 'b')) {
                $title .= ' : ' . $subtitle;
            }
            if ($medium = $reader->getSubfield($field, 'h')) {
                $title .= ' / ' . $medium;
            }
            foreach ($field['subfields'] as $subfield) {
                if ($subfield['code'] === 'n') {
                    $title .= ' / ' . $subfield['data'];
                }
                if ($subfield['code'] === 'p') {
                    $title .= ', ' . $subfield['data'];
                }
            }
            if ($resp = $reader->getSubfield($field, 'c')) {
                $title .= ' / ' . $resp;
            }
            return new DisplayValue($title);
        }
        return new DisplayValue('no title');
    }

    /** @return DisplayValueInterface[] */
    protected function getTitleVariant (MarcReader $reader) : array
    {
        $titles = array();
        foreach ($reader->getFields('246') as $field) {
            if ($field['i2'] !== '3') {
                if ($title = $reader->getSubfield($field, 'a')) {
                    $value = new DisplayValue($title);
                    $titles[] = $value;
                }
            }
        }

        return $titles;
    }

    /** @return DisplayValueInterface[] */
    protected function getIssn (MarcReader $reader) : array
    {
        $issns = array();
        foreach ($reader->getFields('022') as $field) {
            if ($issn = $reader->getSubfield($field, 'a')) {
                $value = new SearchLink($issn);
                $value->setSearchType('ISN');
                $value->setSearchTerm($issn);
                $issns[] = $value;
            }
        }
        return $issns;
    }

    /** @return DisplayValueInterface[] */
    protected function getIsbn (MarcReader $reader) : array
    {
        $isbns = array();
        foreach ($reader->getFields('020') as $field) {
            list($isbn, $label) = $this->getSubfields($field, 'a', '9');
            if ($isbn) {
                $value = new SearchLink($label ?: $isbn);
                $value->setSearchType('ISN');
                $value->setSearchTerm($isbn);
                $isbns[] = $value;
            }
        }
        return $isbns;
    }

    // protected function getControlField (MarcReader $reader, string $tag) : ?string
    // {
    //     $field = $reader->getField($tag);
    //     if ($field) {
    //         if (is_array($field)) {
    //             throw new UnexpectedValueException();
    //         }
    //         return $field;
    //     }
    //     return null;
    // }

    /** @return array<mixed> */
    protected function getDataField (MarcReader $reader, string $tag) : array
    {
        $field = $reader->getField($tag);
        if ($field && !is_array($field)) {
            throw new UnexpectedValueException();
        }
        return (array)$field;
    }

    /**
     * @param array<mixed> $field
     * @return string[] | null[]
     */
    protected function getSubfields (array $field, string ...$codes) : array
    {
        $values = array_fill(0, count($codes), null);
        foreach ($field['subfields'] as $subfield) {
            $key = array_search($subfield['code'], $codes, true);
            if ($key !== false) {
                $values[$key] = $subfield['data'];
                unset($codes[$key]);
            }
        }
        return $values;
    }

    /** @param mixed[] $field */
    protected function annotateRelatedSearchLink (MarcReader $reader, array $field, SearchLink $value) : void
    {
        foreach ($reader->getSubfields($field, 'w') as $id) {
            if (str_starts_with($id, '(DE-627)')) {
                $value->setSearchTerm(substr($id, 8));
                $value->setSearchType('Id');
                return;
            }
        }
        foreach ($reader->getSubfields($field, 'w') as $id) {
            if (str_starts_with($id, '(DE-600)')) {
                $value->setSearchTerm('(DE-599)ZDB' . substr($id, 8));
                $value->setSearchType('Numbers');
                $value->setSearchTermQuote('"');
                return;
            }
        }
        if ($issn = $reader->getSubfield($field, 'x')) {
            $value->setSearchTerm($issn);
            $value->setSearchType('Isn');
            return;
        }
        $value->setSearchType('Title');
        $value->setSearchTermQuote('"');
    }
}
