<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument\ViewModel;

use Mockery;
use Mockery\Matcher\Closure;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocument;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\UuidV6;
use Webmozart\Assert\Assert;

use function sprintf;

final class MainDocumentViewFactoryTest extends UnitTestCase
{
    /**
     * @param class-string<AbstractMainDocument> $mainDocumentClass
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
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($expectedFileName = 'file name');
        $fileInfo->expects('getMimetype')->andReturn($expectedMimeType = 'file mime type');
        $fileInfo->expects('getSize')->andReturn($expectedSize = 101);
        $fileInfo->expects('getSourceType')->andReturn($expectedSourceType = SourceType::PDF);
        $fileInfo->expects('getPageCount')->andReturn($expectedPageCount = 12);

        $uuid = Mockery::mock(UuidV6::class);
        $uuid->expects('toRfc4122')->andReturn($expectedUuid = 'my-uuid');

        $expectedDocumentPrefix = 'prefix';
        $expectedDossierId = 'dossier nr';

        $urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects('generate')
            ->with(
                $expectedDownloadRouteName,
                $this->assertParameters($expectedDownloadParameterKeys),
            )
            ->andReturn($expectedDownloadUrl = 'http://download.test');
        $urlGenerator
            ->expects('generate')
            ->with(
                $expectedDetailsRouteName,
                $this->assertParameters($expectedDetailsParameterKeys),
            )
            ->andReturn($expectedDetailsUrl = 'http://details.test');

        $mainDocument = Mockery::mock($mainDocumentClass);
        Assert::isInstanceOf($mainDocument, AbstractMainDocument::class);
        Assert::isInstanceOf($mainDocument, MockInterface::class);

        $mainDocument->expects('getId')->times(2)->andReturn($uuid);
        $mainDocument->expects('getFormalDate')->andReturn(PlainDate::create($expectedFormalDate = '2021-05-10'));
        $mainDocument->expects('getFileInfo')->times(5)->andReturn($fileInfo);
        $mainDocument->expects('getType')->andReturn(AttachmentType::ADVICE);
        $mainDocument->expects('getLanguage')->andReturn(AttachmentLanguage::NLD);
        $mainDocument->expects('getInternalReference')->andReturn($expectedInternalReference = 'internal reference');
        $mainDocument->expects('getGrounds')->andReturn($expectedGrounds = ['bar', 'foo']);

        $dossier = Mockery::mock(Disposition::class);
        $dossier->expects('getDocumentPrefix')->times(2)->andReturn($expectedDocumentPrefix);
        $dossier->expects('getDossierNr')->times(2)->andReturn($expectedDossierId);
        $dossier->expects('getType')->andReturn($dossierType);

        $result = new MainDocumentViewFactory($urlGenerator)->make($dossier, $mainDocument, $applicationMode);

        $this->assertInstanceOf(MainDocument::class, $result);
        $this->assertSame($expectedUuid, $result->id);
        $this->assertSame($expectedFileName, $result->name);
        $this->assertSame($expectedFormalDate, $result->formalDate);
        $this->assertSame(AttachmentType::ADVICE, $result->type);
        $this->assertSame($expectedMimeType, $result->mimeType);
        $this->assertSame($expectedSourceType, $result->sourceType);
        $this->assertSame($expectedSize, $result->size);
        $this->assertSame($expectedInternalReference, $result->internalReference);
        $this->assertSame(AttachmentLanguage::NLD, $result->language);
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
        return Mockery::on(function (array $parameters) use ($expectedParameterKeys) {
            foreach ($expectedParameterKeys as $key) {
                $this->assertArrayHasKey($key, $parameters, sprintf('Missing parameter "%s"', $key));
            }

            return true;
        });
    }
}
