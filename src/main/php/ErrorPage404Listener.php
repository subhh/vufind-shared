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


use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Response;

final class ErrorPage404Listener
{
    public function attach (SharedEventManagerInterface $events) : void
    {
        $events->attach('*', MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'onErrorEvent']);
    }

    public function onErrorEvent (MvcEvent $event) : void
    {
        $response = $event->getResponse();
        if ($response instanceof Response) {
            if ($response->getStatusCode() === 404) {
                $model = new ViewModel();
                $model->setTemplate('error/404');
                $event->setResult($model);
            }
        }
    }

}
