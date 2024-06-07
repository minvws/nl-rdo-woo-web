<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\MainDocument\ViewModel;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\Exception\MainDocumentRuntimeException;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Entity\FileInfo;
use App\Enum\ApplicationMode;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Matcher\Closure;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\UuidV6;

final class MainDocumentViewFactoryTest extends UnitTestCase
{
    /**
     * @param list<string> $expectedParameterKeys
     */
    #[DataProvider('getMakeScenarios')]
    public function testMake(
        string $mainDocumentClass,
        ApplicationMode $applicationMode,
        string $expectedDownloadRouteName,
        string $expectedDetailsRouteName,
        array $expectedParameterKeys,
    ): void {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($expectedFileName = 'file name');
        $fileInfo->shouldReceive('getMimetype')->andReturn($expectedMimeType = 'file mime type');
        $fileInfo->shouldReceive('getSize')->andReturn($expectedSize = 101);
        $fileInfo->shouldReceive('getSourceType')->andReturn($expectedSourceType = 'pdf');

        $uuid = \Mockery::mock(UuidV6::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my-uuid');

        $expectedDocumentPrefix = 'prefix';
        $expectedDossierId = 'dossier nr';

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->shouldReceive('generate')
            ->with(
                $expectedDownloadRouteName,
                $this->assertParameters($expectedParameterKeys),
            )
            ->andReturn($expectedDownloadUrl = 'http://download.test');
        $urlGenerator
            ->shouldReceive('generate')
            ->with(
                $expectedDetailsRouteName,
                $this->assertParameters($expectedParameterKeys),
            )
            ->andReturn($expectedDetailsUrl = 'http://details.test');

        /** @var AbstractMainDocument&MockInterface $mainDocument */
        $mainDocument = \Mockery::mock($mainDocumentClass);
        $mainDocument->shouldReceive('getId')->andReturn($uuid);
        $mainDocument->shouldReceive('getFormalDate')->andReturn(new \DateTimeImmutable($expectedFormalDate = '2021-05-10'));
        $mainDocument->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $mainDocument->shouldReceive('getType')->andReturn(AttachmentType::ADVICE);
        $mainDocument->shouldReceive('getLanguage')->andReturn(AttachmentLanguage::DUTCH);
        $mainDocument->shouldReceive('getInternalReference')->andReturn($expectedInternalReference = 'internal reference');
        $mainDocument->shouldReceive('getGrounds')->andReturn($expectedGrounds = ['bar', 'foo']);

        $dossier = \Mockery::mock(Disposition::class);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix);
        $dossier->shouldReceive('getDossierNr')->andReturn($expectedDossierId);
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([$mainDocument]));

        $result = (new MainDocumentViewFactory($urlGenerator))->make($dossier, $mainDocument, $applicationMode);

        $this->assertInstanceOf(MainDocument::class, $result);
        $this->assertSame($expectedUuid, $result->id);
        $this->assertSame($expectedFileName, $result->name);
        $this->assertSame($expectedFormalDate, $result->formalDate);
        $this->assertSame(AttachmentType::ADVICE, $result->type);
        $this->assertSame($expectedMimeType, $result->mimeType);
        $this->assertSame($expectedSourceType, $result->sourceType);
        $this->assertSame($expectedSize, $result->size);
        $this->assertSame($expectedInternalReference, $result->internalReference);
        $this->assertSame(AttachmentLanguage::DUTCH, $result->language);
        $this->assertSame($expectedGrounds, $result->grounds);
        $this->assertSame($expectedDownloadUrl, $result->downloadUrl);
        $this->assertSame($expectedDetailsUrl, $result->detailsUrl);
    }

    /**
     * @return array<string,array{
     *     mainDocumentClass:class-string<AbstractMainDocument>,
     *     applicationMode:ApplicationMode,
     *     expectedDownloadRouteName:string,
     *     expectedDetailsRouteName:string,
     *     expectedParameterKeys:list<string>,
     * }>
     */
    public static function getMakeScenarios(): array
    {
        return [
            'CovenantDocument in public mode' => [
                'mainDocumentClass' => CovenantDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_covenant_covenantdocument_download',
                'expectedDetailsRouteName' => 'app_covenant_covenantdocument_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],
            'CovenantDocument in admin mode' => [
                'mainDocumentClass' => CovenantDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_covenant_covenantdocument_download',
                'expectedDetailsRouteName' => 'app_covenant_covenantdocument_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],

            'AnnualReportDocument in public mode' => [
                'mainDocumentClass' => AnnualReportDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_annualreport_document_download',
                'expectedDetailsRouteName' => 'app_annualreport_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],
            'AnnualReportDocument in admin mode' => [
                'mainDocumentClass' => AnnualReportDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_annualreport_document_download',
                'expectedDetailsRouteName' => 'app_annualreport_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],

            'InvestigationReportDocument in public mode' => [
                'mainDocumentClass' => InvestigationReportDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_investigationreport_document_download',
                'expectedDetailsRouteName' => 'app_investigationreport_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],
            'InvestigationReportDocument in admin mode' => [
                'mainDocumentClass' => InvestigationReportDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_investigationreport_document_download',
                'expectedDetailsRouteName' => 'app_investigationreport_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],

            'DispositionDocument in public mode' => [
                'mainDocumentClass' => DispositionDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_disposition_document_download',
                'expectedDetailsRouteName' => 'app_disposition_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],
            'DispositionDocument in admin mode' => [
                'mainDocumentClass' => DispositionDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_disposition_document_download',
                'expectedDetailsRouteName' => 'app_disposition_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],

            'ComplaintJudgementDocument in public mode' => [
                'mainDocumentClass' => ComplaintJudgementDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_complaintjudgement_document_download',
                'expectedDetailsRouteName' => 'app_complaintjudgement_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],
            'ComplaintJudgementDocument in admin mode' => [
                'mainDocumentClass' => ComplaintJudgementDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_complaintjudgement_document_download',
                'expectedDetailsRouteName' => 'app_complaintjudgement_document_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId'],
            ],
        ];
    }

    public function testItThrowsAnExceptionWhenGivenAnUnknownMainDocumentType(): void
    {
        $mainDocument = \Mockery::mock(AbstractMainDocument::class);

        /** @var AbstractDossier&EntityWithMainDocument&MockInterface $dossier */
        $dossier = \Mockery::mock(sprintf('%s, %s', AbstractDossier::class, EntityWithMainDocument::class));
        $dossier->shouldReceive('getDocument')->andReturn($mainDocument);

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $applicationMode = ApplicationMode::PUBLIC;

        $this->expectExceptionObject(MainDocumentRuntimeException::unknownMainDocumentType($mainDocument::class));

        (new MainDocumentViewFactory($urlGenerator))->make($dossier, $mainDocument, $applicationMode);
    }

    /**
     * @param list<string> $expectedParameterKeys
     */
    private function assertParameters(array $expectedParameterKeys): Closure
    {
        return \Mockery::on(function (array $parameters) use ($expectedParameterKeys) {
            foreach ($expectedParameterKeys as $key) {
                $this->assertArrayHasKey($key, $parameters, sprintf('Missing parameter "%s"', $key));
            }

            return true;
        });
    }
}
