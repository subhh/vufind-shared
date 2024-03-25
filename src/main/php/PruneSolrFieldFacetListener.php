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

use VuFindSearch\Service;
use VuFindSearch\ParamBag;
use VuFindSearch\Command\CommandInterface;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollection;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\SharedEventManagerInterface;

final class PruneSolrFieldFacetListener
{
    /** @var string */
    private $field;

    /** @var StringValueFilter */
    private $filter;

    public function __construct (string $field, StringValueFilter $filter)
    {
        $this->field = $field;
        $this->filter = $filter;
    }

    public function attach (SharedEventManagerInterface $events) : void
    {
        $events->attach('VuFindSearch', Service::EVENT_POST, [$this, 'onSearchPost']);
    }

    /** @param EventInterface<Service, ParamBag> $event */
    public function onSearchPost (EventInterface $event) : void
    {
        $command = $event->getParam('command');
        if ($command instanceof CommandInterface) {
            $response = $command->getResult();
            if ($response instanceof RecordCollection) {
                $facets = $response->getFacets();
                if (array_key_exists($this->field, $facets)) {
                    $facets[$this->field] = array_filter($facets[$this->field], array($this, 'filter'), ARRAY_FILTER_USE_KEY);
                }
                $response->setFacets($facets);
            }
        }
    }

    private function filter (mixed $key) : bool
    {
        return $this->filter->accept($key);
    }
}
