<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\RequestForAdvice;

use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class RequestForAdviceTest extends TestCase
{
    public function testGetAndSetLink(): void
    {
        $requestForAdvice = new RequestForAdvice();
        self::assertEquals('', $requestForAdvice->getLink());

        $requestForAdvice->setLink($link = 'http://foo.bar');

        self::assertEquals($link, $requestForAdvice->getLink());
    }

    public function testSetDateFromSetsDateTo(): void
    {
        $dossier = new RequestForAdvice();

        $date = new CarbonImmutable();

        $dossier->setDateFrom($date);

        self::assertEquals($date, $dossier->getDateFrom());
        self::assertEquals($date, $dossier->getDateTo());
    }

    public function testGetAndSetAdvisoryBodies(): void
    {
        $requestForAdvice = new RequestForAdvice();
        self::assertEquals([], $requestForAdvice->getAdvisoryBodies());

        $requestForAdvice->setAdvisoryBodies($advisoryBodies = ['foo']);

        self::assertEquals($advisoryBodies, $requestForAdvice->getAdvisoryBodies());
    }
}
