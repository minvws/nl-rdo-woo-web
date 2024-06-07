<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\ViewModel;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\Exception\AttachmentRuntimeException;
use App\Domain\Publication\Attachment\ViewModel\Attachment;
use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\DecisionAttachment;
use App\Entity\FileInfo;
use App\Enum\ApplicationMode;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Matcher\Closure;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\UuidV6;

final class AttachmentViewFactoryTest extends UnitTestCase
{
    /**
     * @param list<string> $expectedParameterKeys
     */
    #[DataProvider('getMakeCollectiondata')]
    public function testMakeCollectionWithDossier(
        string $attachmentClass,
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

        $attachment = \Mockery::mock($attachmentClass);
        $attachment->shouldReceive('getId')->andReturn($uuid);
        $attachment->shouldReceive('getFormalDate')->andReturn(new \DateTimeImmutable($expectedFormalDate = '2021-05-10'));
        $attachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $attachment->shouldReceive('getType')->andReturn(AttachmentType::ADVICE);
        $attachment->shouldReceive('getLanguage')->andReturn(AttachmentLanguage::DUTCH);
        $attachment->shouldReceive('getInternalReference')->andReturn($expectedInternalReference = 'internal reference');
        $attachment->shouldReceive('getGrounds')->andReturn($expectedGrounds = ['bar', 'foo']);

        $dossier = \Mockery::mock(WooDecision::class); // TODO replace with AbstractDossier & EntityWithAttachments
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix);
        $dossier->shouldReceive('getDossierNr')->andReturn($expectedDossierId);
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([$attachment]));

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
    }

    /**
     * @return array<string,array{
     *     attachmentClass:class-string<AbstractAttachment>,
     *     applicationMode:ApplicationMode,
     *     expectedDownloadRouteName:string,
     *     expectedDetailsRouteName:string,
     *     expectedParameterKeys:list<string>,
     * }>
     */
    public static function getMakeCollectiondata(): array
    {
        return [
            'DecisionAttachment in public mode' => [
                'attachmentClass' => DecisionAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_woodecision_decisionattachment_download',
                'expectedDetailsRouteName' => 'app_woodecision_decisionattachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'DecisionAttachment in admin mode' => [
                'attachmentClass' => DecisionAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_woodecision_decisionattachment_download',
                'expectedDetailsRouteName' => 'app_woodecision_decisionattachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'CovenantAttachment in public mode' => [
                'attachmentClass' => CovenantAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_covenant_covenantattachment_download',
                'expectedDetailsRouteName' => 'app_covenant_covenantattachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'CovenantAttachment in admin mode' => [
                'attachmentClass' => CovenantAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_covenant_covenantattachment_download',
                'expectedDetailsRouteName' => 'app_covenant_covenantattachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'AnnualReportAttachment in public mode' => [
                'attachmentClass' => AnnualReportAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_annualreport_attachment_download',
                'expectedDetailsRouteName' => 'app_annualreport_attachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'AnnualReportAttachment in admin mode' => [
                'attachmentClass' => AnnualReportAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_annualreport_attachment_download',
                'expectedDetailsRouteName' => 'app_annualreport_attachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'InvestigationReportAttachment in public mode' => [
                'attachmentClass' => InvestigationReportAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_investigationreport_attachment_download',
                'expectedDetailsRouteName' => 'app_investigationreport_attachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'InvestigationReportAttachment in admin mode' => [
                'attachmentClass' => InvestigationReportAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_investigationreport_attachment_download',
                'expectedDetailsRouteName' => 'app_investigationreport_attachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],

            'DispositionAttachment in public mode' => [
                'attachmentClass' => DispositionAttachment::class,
                'applicationMode' => ApplicationMode::PUBLIC,
                'expectedDownloadRouteName' => 'app_disposition_attachment_download',
                'expectedDetailsRouteName' => 'app_disposition_attachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
            'DispositionAttachment in admin mode' => [
                'attachmentClass' => DispositionAttachment::class,
                'applicationMode' => ApplicationMode::ADMIN,
                'expectedDownloadRouteName' => 'app_admin_disposition_attachment_download',
                'expectedDetailsRouteName' => 'app_disposition_attachment_detail',
                'expectedParameterKeys' => ['prefix', 'dossierId', 'attachmentId'],
            ],
        ];
    }

    public function testItThrowsAnExceptionWhenGivenAnUnknownAttachment(): void
    {
        $attachment = \Mockery::mock(AbstractAttachment::class);

        /** @var AbstractDossier&EntityWithAttachments&MockInterface $dossier */
        $dossier = \Mockery::mock(sprintf('%s, %s', AbstractDossier::class, EntityWithAttachments::class));
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([$attachment]));

        $urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $applicationMode = ApplicationMode::PUBLIC;

        $this->expectExceptionObject(AttachmentRuntimeException::unknownAttachmentType($attachment::class));

        (new AttachmentViewFactory($urlGenerator))->makeCollection($dossier, $applicationMode);
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
