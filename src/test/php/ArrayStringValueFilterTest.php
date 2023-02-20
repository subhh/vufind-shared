<?php

declare(strict_types=1);

namespace SUBHH\VuFind\Shared;

use PHPUnit\Framework\TestCase;

final class ArrayStringValueFilterTest extends TestCase
{
    public function testAcceptValue () : void
    {
        $filter = new ArrayStringValueFilter(['foobar']);
        $this->assertTrue($filter->accept('foobar'));
        $this->assertFalse($filter->accept('foo'));
    }
}
