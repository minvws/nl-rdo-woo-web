<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use PHPUnit\Framework\TestCase;

final class CovenantTest extends TestCase
{
    public function testGetAndSetDocument(): void
    {
        $covenant = new Covenant();
        self::assertEquals('', $covenant->getPreviousVersionLink());

        $covenant->setPreviousVersionLink($link = 'http://foo.bar');

        self::assertEquals($link, $covenant->getPreviousVersionLink());
    }

    public function testGetAndSetParties(): void
    {
        $covenant = new Covenant();
        self::assertEquals([], $covenant->getParties());

        $covenant->setParties($parties = ['foo', 'bar']);

        self::assertEquals($parties, $covenant->getParties());
    }
}
