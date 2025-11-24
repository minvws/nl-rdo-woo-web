<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use Shared\Domain\Search\Index\Rollover\MainTypeCount;
use Shared\Domain\Search\Index\Rollover\RolloverDetails;
use Shared\Tests\Unit\UnitTestCase;

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
