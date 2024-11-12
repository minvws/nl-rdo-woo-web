<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\MainDocument\ViewModel;

use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Entity\FileInfo;
use App\Enum\ApplicationMode;
use App\SourceType;
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
     * @param list<string> $expectedDownloadParameterKeys
     * @param list<string> $expectedDetailsParameterKeys
     */
    #[DataProvider('getMakeScenarios')]
    public function testMake(
        DossierType $dossierType,
        string $mainDocumentClass,
        ApplicationMode $applicationMode,
        string $expectedDownloadRouteName,
        array $expectedDownloadParameterKeys,
        string $expectedDetailsRouteName,
        array $expectedDetailsParameterKeys,
    ): void {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($expectedFileName = 'file name');
        $fileInfo->shouldReceive('getMimetype')->andReturn($expectedMimeType = 'file mime type');
        $fileInfo->shouldReceive('getSize')->andReturn($expectedSize = 101);
        $fileInfo->shouldReceive('getSourceType')->andReturn($expectedSourceType = SourceType::PDF);
        $fileInfo->shouldReceive('getPageCount')->andReturn($expectedPageCount = 12);

        $uuid = \Mockery::mock(UuidV6::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my-uuid');

        $expectedDocumentPrefix = 'prefix';
        $expectedDossierId = 'dossier nr';

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->shouldReceive('generate')
            ->with(
                $expectedDownloadRouteName,
                $this->assertParameters($expectedDownloadParameterKeys),
            )
            ->andReturn($expectedDownloadUrl = 'http://download.test');
        $urlGenerator
            ->shouldReceive('generate')
            ->with(
                $expectedDetailsRouteName,
                $this->assertParameters($expectedDetailsParameterKeys),
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
        $dossier->shouldReceive('getType')->andReturn($dossierType);

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
        $this->assertSame($expectedPageCount, $result->pageCount);
    }

    /**
     * @return array<string,array{
     *     mainDocumentClass:class-string<AbstractMainDocument>,
     *     applicationMode:ApplicationMode,
     *     expectedDownloadRouteName:string,
     *     expectedDownloadParameterKeys:list<string>,
     *     expectedDetailsRouteName:string,
     *     expectedDetailsParameterKeys:list<string>,
     * }>
     */
    public static function getMakeScenarios(): array
    {
        return [
            'CovenantDocument in public mode' => [
                'dossierType' => DossierType::COVENANT,
                'mainDocumentClass' => CovenantMainDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_covenant_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],
            'CovenantDocument in admin mode' => [
                'dossierType' => DossierType::COVENANT,
                'mainDocumentClass' => CovenantMainDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_covenant_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],

            'AnnualReportDocument in public mode' => [
                'dossierType' => DossierType::ANNUAL_REPORT,
                'mainDocumentClass' => AnnualReportMainDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_annualreport_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],
            'AnnualReportDocument in admin mode' => [
                'dossierType' => DossierType::ANNUAL_REPORT,
                'mainDocumentClass' => AnnualReportMainDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_annualreport_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],

            'InvestigationReportDocument in public mode' => [
                'dossierType' => DossierType::INVESTIGATION_REPORT,
                'mainDocumentClass' => InvestigationReportMainDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_investigationreport_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],
            'InvestigationReportDocument in admin mode' => [
                'dossierType' => DossierType::INVESTIGATION_REPORT,
                'mainDocumentClass' => InvestigationReportMainDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_investigationreport_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],

            'DispositionDocument in public mode' => [
                'dossierType' => DossierType::DISPOSITION,
                'mainDocumentClass' => DispositionMainDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_disposition_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],
            'DispositionDocument in admin mode' => [
                'dossierType' => DossierType::DISPOSITION,
                'mainDocumentClass' => DispositionMainDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_disposition_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],

            'ComplaintJudgementDocument in public mode' => [
                'dossierType' => DossierType::COMPLAINT_JUDGEMENT,
                'mainDocumentClass' => ComplaintJudgementMainDocument::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_complaintjudgement_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],
            'ComplaintJudgementDocument in admin mode' => [
                'dossierType' => DossierType::COMPLAINT_JUDGEMENT,
                'mainDocumentClass' => ComplaintJudgementMainDocument::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_complaintjudgement_document_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId'],
            ],
        ];
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
