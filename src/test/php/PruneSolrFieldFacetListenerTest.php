<?php

declare(strict_types=1);

namespace SUBHH\VuFind\Shared;

use PHPUnit\Framework\TestCase;

use Laminas\EventManager\EventInterface;

use VuFindSearch\Command\CommandInterface;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollection;

require_once __DIR__ . '/../../../vufind-autoload.php';

final class PruneSolrFieldFacetListenerTest extends TestCase
{
    public function testPruneFieldFacet ()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../resources/response-facet-langcode.json'), true);
        $response = new RecordCollection($json);

        $command = $this->createMock(CommandInterface::class);
        $command->method('getResult')->willReturn($response);
        $event = $this->createMock(EventInterface::class);
        $event->method('getTarget')->willReturn($command);

        $facets = $response->getFacets()['lang_code'];
        $this->assertNotEquals(1, count($facets));

        $listener = new PruneSolrFieldFacetListener('lang_code', new ArrayStringValueFilter(['eng']));
        $listener->onSearchPost($event);

        $facets = $response->getFacets()['lang_code'];
        $this->assertEquals(1, count($facets));
    }
}
