<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\NoticeNotPublic\ViewModel;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic as NoticeNotPublicEntity;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_key_exists;

final class NoticeNotPublicViewFactoryTest extends UnitTestCase
{
    /**
     * @param list<string> $grounds
     */
    #[DataProvider('getMakeScenarios')]
    public function testMake(
        ?string $documentName,
        string $expectedTitle,
        array $grounds,
    ): void {
        $uuid = Mockery::mock(Uuid::class);
        $uuid->expects('toRfc4122')->andReturn($expectedId = 'my-uuid');

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects('generate')
            ->with(
                'app_advice_notice_not_public_detail',
                Mockery::on(static fn (array $params) => array_key_exists('documentPrefix', $params) && array_key_exists('dossierNumber', $params)),
            )
            ->andReturn($expectedDetailsUrl = 'http://details.test');

        $translator = Mockery::mock(TranslatorInterface::class);
        if ($documentName === null) {
            $translator
                ->expects('trans')
                ->with('global.dossiers.notice_not_public')
                ->andReturn($expectedTitle);
        } else {
            $translator->shouldNotReceive('trans');
        }

        $noticeNotPublic = Mockery::mock(NoticeNotPublicEntity::class);
        $noticeNotPublic->expects('getId')->andReturn($uuid);
        $noticeNotPublic->expects('getDocumentName')->times(2)->andReturn($documentName);
        $noticeNotPublic->expects('getFormalDate')->andReturn(PlainDate::create($expectedFormalDate = '2024-01-15'));
        $noticeNotPublic->expects('getGrounds')->andReturn($grounds);
        $noticeNotPublic->expects('getExplanation')->andReturn($expectedExplanation = 'some explanation');

        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getNoticeNotPublic')->andReturn($noticeNotPublic);
        $dossier->expects('getDocumentPrefix')->andReturn('PRE');
        $dossier->expects('getDossierNumber')->andReturn('2024-001');
        $dossier->expects('getType')->andReturn(DossierType::ADVICE);

        $result = new NoticeNotPublicViewFactory($urlGenerator, $translator)->make($dossier);

        $this->assertInstanceOf(NoticeNotPublic::class, $result);
        $this->assertSame($expectedId, $result->id);
        $this->assertSame($documentName, $result->documentName);
        $this->assertSame($expectedFormalDate, $result->formalDate);
        $this->assertSame($grounds, $result->grounds);
        $this->assertSame($expectedExplanation, $result->explanation);
        $this->assertSame($expectedDetailsUrl, $result->detailsUrl);
        $this->assertSame($expectedTitle, $result->title);
    }

    public function testMakeThrowsWhenNoticeNotPublicIsNull(): void
    {
        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getNoticeNotPublic')->andReturn(null);

        $this->expectException(NoticeNotPublicNotFoundException::class);

        new NoticeNotPublicViewFactory(
            Mockery::mock(UrlGeneratorInterface::class),
            Mockery::mock(TranslatorInterface::class),
        )->make($dossier);
    }

    /**
     * @return array<string, array{documentName: ?string, expectedTitle: string, grounds: list<string>}>
     */
    public static function getMakeScenarios(): array
    {
        return [
            'with document name' => [
                'documentName' => 'My custom document name',
                'expectedTitle' => 'My custom document name',
                'grounds' => ['5.1.2.e'],
            ],
            'without document name' => [
                'documentName' => null,
                'expectedTitle' => 'Mededeling niet openbaar',
                'grounds' => ['5.1.2.e'],
            ],
        ];
    }
}
