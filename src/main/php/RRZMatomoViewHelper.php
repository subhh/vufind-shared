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

use Laminas\View\Helper\AbstractHelper;

final class RRZMatomoViewHelper extends AbstractHelper
{
    /** @var int */
    private $siteId;

    /** @var string[] */
    private $domains;

    /** @param string[] $domains */
    public function __construct (int $siteId, array $domains)
    {
        $this->siteId = $siteId;
        foreach ($domains as $domain) {
            $this->domains[] = sprintf('"%s"', $domain);
        }
    }

    public function __invoke () : ?string
    {
        if ($view = $this->getView()) {
            return $view->render('rrz-matomo.phtml', [ 'siteId' => $this->siteId, 'domains' => $this->domains ]);
        }
        return null;
    }
}
