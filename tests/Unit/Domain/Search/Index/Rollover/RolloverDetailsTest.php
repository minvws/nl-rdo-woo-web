<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use App\Domain\Search\Index\Rollover\MainTypeCount;
use App\Domain\Search\Index\Rollover\RolloverDetails;
use App\Tests\Unit\UnitTestCase;

class RolloverDetailsTest extends UnitTestCase
{
    public function testGetValues(): void
    {
        $details = new RolloverDetails(
            $indexDetails = new ElasticIndexDetails(
                'index-123',
                'yellow',
                'open',
                '65',
                '69MB',
                '3',
                ['woopie-read', 'woopie-write'],
            ),
            $counts = [
                new MainTypeCount(ElasticDocumentType::COVENANT, 10, 20),
                new MainTypeCount(ElasticDocumentType::WOO_DECISION, 5, 5),
            ]
        );

        self::assertEquals($indexDetails, $details->index);
        self::assertEquals($counts, $details->counts);
    }
}
