<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use App\Domain\Search\Index\Rollover\RolloverDetails;
use App\Tests\Unit\UnitTestCase;

class RolloverDetailsTest extends UnitTestCase
{
    private RolloverDetails $rolloverDetails;

    public function setUp(): void
    {
        $this->rolloverDetails = new RolloverDetails(
            new ElasticIndexDetails(
                'index-123',
                'yellow',
                'open',
                '65',
                '69MB',
                '3',
                ['woopie-read', 'woopie-write'],
            ),
            10,
            20,
            100,
            5,
            5,
            0
        );

        parent::setUp();
    }

    public function testGetDossierPercentage(): void
    {
        $this->assertEquals(50, $this->rolloverDetails->getDossierPercentage());
    }

    public function testGetDocumentPercentage(): void
    {
        $this->assertEquals(25, $this->rolloverDetails->getDocumentPercentage());
    }

    public function testGetPagePercentage(): void
    {
        $this->assertEquals(0, $this->rolloverDetails->getPagePercentage());
    }
}
