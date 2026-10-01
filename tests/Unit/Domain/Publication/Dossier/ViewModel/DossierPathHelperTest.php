<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

use function sprintf;

final class DossierPathHelperTest extends UnitTestCase
{
    private RouterInterface&MockInterface $router;
    private string $baseUrl = 'https://foo.bar';
    private DossierPathHelper $pathHelper;

    protected function setUp(): void
    {
        $this->router = Mockery::mock(RouterInterface::class);

        $this->pathHelper = new DossierPathHelper(
            $this->router,
            $this->baseUrl,
        );

        parent::setUp();
    }

    public function testGetDetailsPathWithDossierReference(): void
    {
        $reference = new DossierReference(
            'dos-nr',
            'doc-prefix',
            DossierTitle::create('dos-title'),
            DossierType::COVENANT,
        );

        $this->router->expects('generate')->with(
            'app_covenant_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($reference),
        );
    }

    public function testGetDetailsPathWithDossier(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getDossierNumber')->andReturn('dos-nr');
        $dossier->expects('getDocumentPrefix')->andReturn('doc-prefix');
        $dossier->expects('getType')->andReturn(DossierType::COVENANT);

        $this->router->expects('generate')->with(
            'app_covenant_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetAbsoluteDetailsPathWithDossierReference(): void
    {
        $reference = new DossierReference(
            'dos-nr',
            'doc-prefix',
            DossierTitle::create('dos-title'),
            DossierType::COMPLAINT_JUDGEMENT,
        );

        $this->router->expects('generate')->with(
            'app_complaintjudgement_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('/foo-bar');

        self::assertEquals(
            'https://foo.bar/foo-bar',
            $this->pathHelper->getAbsoluteDetailsPath($reference),
        );
    }

    public function testGetDetailsPathWithOtherPublication(): void
    {
        $dossier = Mockery::mock(OtherPublication::class);
        $dossier->expects('getDossierNumber')->andReturn('dos-nr');
        $dossier->expects('getDocumentPrefix')->andReturn('doc-prefix');
        $dossier->expects('getType')->andReturn(DossierType::OTHER_PUBLICATION);

        $this->router->expects('generate')->with(
            'app_otherpublication_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetDetailsPathWithAdvice(): void
    {
        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getDossierNumber')->andReturn('dos-nr');
        $dossier->expects('getDocumentPrefix')->andReturn('doc-prefix');
        $dossier->expects('getType')->andReturn(DossierType::ADVICE);

        $this->router->expects('generate')->with(
            'app_advice_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetDetailsPathWithRequestForAdvice(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getDossierNumber')->andReturn('dos-nr');
        $dossier->expects('getDocumentPrefix')->andReturn('doc-prefix');
        $dossier->expects('getType')->andReturn(DossierType::REQUEST_FOR_ADVICE);

        $this->router->expects('generate')->with(
            'app_requestforadvice_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetAbsoluteMainDocumentDetailsPath(): void
    {
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $baseUrl = $this->getFaker()->url();

        $reference = new DossierReference(
            $dossierNumber,
            $documentPrefix,
            DossierTitle::create($this->getFaker()->sentence()),
            DossierType::COVENANT,
        );

        $router = Mockery::mock(RouterInterface::class);
        $router->expects('generate')->with(
            'app_covenant_document_detail',
            [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
            ],
        )->andReturn('/foo-bar');

        $pathHelper = new DossierPathHelper($router, $baseUrl);

        self::assertSame($baseUrl . '/foo-bar', $pathHelper->getAbsoluteMainDocumentDetailsPath($reference));
    }

    public function testGetAbsoluteAttachmentDetailsPath(): void
    {
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $attachmentId = Uuid::v6();
        $baseUrl = $this->getFaker()->url();
        $slug = $this->getFaker()->slug();

        $reference = new DossierReference(
            $dossierNumber,
            $documentPrefix,
            DossierTitle::create($this->getFaker()->sentence()),
            DossierType::COVENANT,
        );

        $router = Mockery::mock(RouterInterface::class);
        $router->expects('generate')->with(
            'app_covenant_attachment_detail',
            [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'attachmentId' => $attachmentId,
            ],
        )->andReturn(sprintf('/%s', $slug));

        $pathHelper = new DossierPathHelper($router, $baseUrl);

        self::assertSame(sprintf('%s/%s', $baseUrl, $slug), $pathHelper->getAbsoluteAttachmentDetailsPath($reference, $attachmentId));
    }

    public function testGetDetailsPathWithDraftDecision(): void
    {
        $dossier = Mockery::mock(DraftDecision::class);
        $dossier->expects('getDossierNumber')->andReturn('dos-nr');
        $dossier->expects('getDocumentPrefix')->andReturn('doc-prefix');
        $dossier->expects('getType')->andReturn(DossierType::DRAFT_DECISION);

        $this->router->expects('generate')->with(
            'app_draftdecision_detail',
            [
                'documentPrefix' => 'doc-prefix',
                'dossierNumber' => 'dos-nr',
            ],
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }
}
