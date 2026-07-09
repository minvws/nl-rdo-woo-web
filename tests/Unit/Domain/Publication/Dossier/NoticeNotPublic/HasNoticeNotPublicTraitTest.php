<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\NoticeNotPublic;

use Mockery;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\HasNoticeNotPublicTrait;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Tests\Unit\UnitTestCase;

final class HasNoticeNotPublicTraitTest extends UnitTestCase
{
    public function testGetNoticeNotPublicReturnsNullWhenNotSet(): void
    {
        $dossier = new class {
            use HasNoticeNotPublicTrait;
        };

        self::assertNull($dossier->getNoticeNotPublic());
    }

    public function testSetNoticeNotPublicSetsValueAndReturnsInstance(): void
    {
        $dossier = new class {
            use HasNoticeNotPublicTrait;
        };

        $noticeNotPublic = Mockery::mock(NoticeNotPublic::class);
        $result = $dossier->setNoticeNotPublic($noticeNotPublic);

        self::assertEquals($noticeNotPublic, $dossier->getNoticeNotPublic());
        self::assertEquals($dossier, $result);
    }

    public function testSetNoticeNotPublicWithNullSetsNullAndReturnsInstance(): void
    {
        $dossier = new class {
            use HasNoticeNotPublicTrait;

            public function __construct()
            {
                $this->noticeNotPublic = Mockery::mock(NoticeNotPublic::class);
            }
        };

        $result = $dossier->setNoticeNotPublic(null);

        self::assertNull($dossier->getNoticeNotPublic());
        self::assertEquals($dossier, $result);
    }
}
