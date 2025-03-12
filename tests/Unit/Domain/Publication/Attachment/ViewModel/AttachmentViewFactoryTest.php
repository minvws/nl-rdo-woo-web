<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\ViewModel;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Attachment\ViewModel\Attachment;
use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\FileInfo;
use App\Enum\ApplicationMode;
use App\SourceType;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Matcher\Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\UuidV6;

final class AttachmentViewFactoryTest extends UnitTestCase
{
    /**
     * @param list<string> $expectedDetailsParameterKeys
     * @param list<string> $expectedDownloadParameterKeys
     */
    #[DataProvider('getMakeCollectiondata')]
    public function testMakeCollectionWithDossier(
        DossierType $dossierType,
        string $attachmentClass,
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
        $fileInfo->shouldReceive('getPageCount')->andReturn($expectedPageCount = 2);

        $uuid = \Mockery::mock(UuidV6::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my-uuid');

        $expectedDocumentPrefix = 'prefix';
        $expectedDossierId = 'dossier nr';

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);

        $urlGenerator
            ->expects('generate')
            ->with(
                $expectedDetailsRouteName,
                $this->assertParameters($expectedDetailsParameterKeys),
            )
            ->andReturn($expectedDetailsUrl = 'http://details.test');

        $urlGenerator
            ->expects('generate')
            ->with(
                $expectedDownloadRouteName,
                $this->assertParameters($expectedDownloadParameterKeys),
            )
            ->andReturn($expectedDownloadUrl = 'http://download.test');

        $attachment = \Mockery::mock($attachmentClass);
        $attachment->shouldReceive('getId')->andReturn($uuid);
        $attachment->shouldReceive('getFormalDate')->andReturn(new \DateTimeImmutable($expectedFormalDate = '2021-05-10'));
        $attachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $attachment->shouldReceive('getType')->andReturn(AttachmentType::ADVICE);
        $attachment->shouldReceive('getLanguage')->andReturn(AttachmentLanguage::DUTCH);
        $attachment->shouldReceive('getInternalReference')->andReturn($expectedInternalReference = 'internal reference');
        $attachment->shouldReceive('getGrounds')->andReturn($expectedGrounds = ['bar', 'foo']);
        $attachment->shouldReceive('isWithdrawn')->andReturnFalse();
        $attachment->shouldReceive('getWithdrawReason')->andReturnNull();
        $attachment->shouldReceive('getWithdrawDate')->andReturnNull();

        $dossier = \Mockery::mock(WooDecision::class); // TODO replace with AbstractDossier & EntityWithAttachments
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix);
        $dossier->shouldReceive('getDossierNr')->andReturn($expectedDossierId);
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([$attachment]));
        $dossier->shouldReceive('getType')->andReturn($dossierType);

        $result = (new AttachmentViewFactory($urlGenerator))->makeCollection($dossier, $applicationMode);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Attachment::class, $result[0]);
        $this->assertSame($expectedUuid, $result[0]->id);
        $this->assertSame($expectedFileName, $result[0]->name);
        $this->assertSame($expectedFormalDate, $result[0]->formalDate);
        $this->assertSame(AttachmentType::ADVICE, $result[0]->type);
        $this->assertSame($expectedMimeType, $result[0]->mimeType);
        $this->assertSame($expectedSourceType, $result[0]->sourceType);
        $this->assertSame($expectedSize, $result[0]->size);
        $this->assertSame($expectedInternalReference, $result[0]->internalReference);
        $this->assertSame(AttachmentLanguage::DUTCH, $result[0]->language);
        $this->assertSame($expectedGrounds, $result[0]->grounds);
        $this->assertSame($expectedDownloadUrl, $result[0]->downloadUrl);
        $this->assertSame($expectedDetailsUrl, $result[0]->detailsUrl);
        $this->assertSame($expectedPageCount, $result[0]->pageCount);
    }

    /**
     * @return array<string,array{
     *     attachmentClass:class-string<AbstractAttachment>,
     *     applicationMode:ApplicationMode,
     *     expectedDownloadRouteName:string,
     *     expectedDownloadParameterKeys:list<string>,
     *     expectedDetailsRouteName:string,
     *     expectedDetailsParameterKeys:list<string>,
     * }>
     */
    public static function getMakeCollectiondata(): array
    {
        return [
            'DecisionAttachment in public mode' => [
                'dossierType' => DossierType::WOO_DECISION,
                'attachmentClass' => WooDecisionAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_woodecision_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'DecisionAttachment in admin mode' => [
                'dossierType' => DossierType::WOO_DECISION,
                'attachmentClass' => WooDecisionAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_woodecision_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'CovenantAttachment in public mode' => [
                'dossierType' => DossierType::COVENANT,
                'attachmentClass' => CovenantAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_covenant_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'CovenantAttachment in admin mode' => [
                'dossierType' => DossierType::COVENANT,
                'attachmentClass' => CovenantAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_covenant_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'AnnualReportAttachment in public mode' => [
                'dossierType' => DossierType::ANNUAL_REPORT,
                'attachmentClass' => AnnualReportAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_annualreport_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'AnnualReportAttachment in admin mode' => [
                'dossierType' => DossierType::ANNUAL_REPORT,
                'attachmentClass' => AnnualReportAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_annualreport_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'InvestigationReportAttachment in public mode' => [
                'dossierType' => DossierType::INVESTIGATION_REPORT,
                'attachmentClass' => InvestigationReportAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_investigationreport_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'InvestigationReportAttachment in admin mode' => [
                'dossierType' => DossierType::INVESTIGATION_REPORT,
                'attachmentClass' => InvestigationReportAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_investigationreport_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'DispositionAttachment in public mode' => [
                'dossierType' => DossierType::DISPOSITION,
                'attachmentClass' => DispositionAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_disposition_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'DispositionAttachment in admin mode' => [
                'dossierType' => DossierType::DISPOSITION,
                'attachmentClass' => DispositionAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_dossier_file_download',
                'expectedDownloadParameterKeys' => ['prefix', 'dossierId', 'type', 'id'],
                'expectedDetailsRouteName' => 'app_disposition_attachment_detail',
                'expectedDetailsParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
        ];
    }

    public function testMakeCollectionReturnAnEmptyArrayWhenEntityHasNoAttachments(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $factory = new AttachmentViewFactory($urlGenerator);

        self::assertCount(0, $factory->makeCollection($dossier));
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
