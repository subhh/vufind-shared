<?php

declare(strict_types=1);

namespace SUBHH\VuFind\Shared;

use PHPUnit\Framework\TestCase;

final class RegexpStringValueFilterTest extends TestCase
{
    public function testAcceptValue () : void
    {
        $filter = new RegexpStringValueFilter('|^FID_|u');
        $this->assertTrue($filter->accept('FID_ROM'));
        $this->assertFalse($filter->accept('FID'));
    }
}
