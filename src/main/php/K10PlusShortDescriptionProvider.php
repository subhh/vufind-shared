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
use SUBHH\VuFind\RecordDescription\DisplayValueInterface;
use SUBHH\VuFind\RecordDescription\SearchLink;

use VuFind\RecordDriver\SolrMarc;
use VuFind\Marc\MarcReader;

use UnexpectedValueException;

class K10PlusShortDescriptionProvider implements DescriptionProviderInterface
{
    /** @return array<string, DisplayValueInterface[]> */
    final public function createDescription (SolrMarc $record) : array
    {
        $reader = $record->getMarcReader();
        $description = array();
        $description['Title'] = $this->getTitle($record);
        $description['Creator'] = $this->getPersons($reader);
        $description['Publication'] = $this->getPublicationStatement($reader);
        return array_filter($description);
    }

    /** @return DisplayValueInterface[] */
    protected function getTitle (SolrMarc $record) : array
    {
        $title = $record->getShortTitle();
        if ($subtitle = $record->getSubTitle()) {
            $title .= ' : ' . $subtitle;
        }
        return [new DisplayValue($title)];
    }

    /** @return DisplayValueInterface[] */
    protected function getPersons (MarcReader $reader) : array
    {
        if ($field = $this->getDataField($reader, '100')) {
            if ($name = $reader->getSubfield($field, 'a')) {
                $person = new SearchLink($name);
                $person->setSearchTerm($name);
                $person->setSearchType('Person');
                $person->setSearchTermQuote('"');
                return [$person];
            }
        }
        return [];
    }

    /** @return DisplayValueInterface[] */
    protected function getPublicationStatement (MarcReader $reader) : array
    {
        $publicationStatement = array();
        if ($field = $this->getDataField($reader, '264')) {
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
                $publicationStatement[] = new DisplayValue($statement);
            }
        }
        return $publicationStatement;
    }

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
}
