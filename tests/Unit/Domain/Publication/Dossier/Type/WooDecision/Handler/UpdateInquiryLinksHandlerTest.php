<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateInquiryLinksCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\UpdateInquiryLinksHandler;
use App\Entity\Organisation;
use App\Repository\OrganisationRepository;
use App\Service\Inquiry\InquiryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class UpdateInquiryLinksHandlerTest extends UnitTestCase
{
    private OrganisationRepository&MockInterface $organisationRepository;
    private LoggerInterface&MockInterface $logger;
    private InquiryService&MockInterface $inquiryService;
    private UpdateInquiryLinksHandler $handler;

    public function setUp(): void
    {
        $this->organisationRepository = \Mockery::mock(OrganisationRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->inquiryService = \Mockery::mock(InquiryService::class);

        $this->handler = new UpdateInquiryLinksHandler(
            $this->organisationRepository,
            $this->logger,
            $this->inquiryService,
        );
    }

    public function testInvokeLogsWarningWhenOrganisationIsNotFound(): void
    {
        $message = new UpdateInquiryLinksCommand(
            $organisationId = Uuid::v6(),
            'foo-123',
            [],
            [],
            [],
        );

        $this->organisationRepository->expects('find')->with($organisationId)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsErrorForException(): void
    {
        $message = new UpdateInquiryLinksCommand(
            $organisationId = Uuid::v6(),
            'foo-123',
            [],
            [],
            [],
        );

        $this->organisationRepository->expects('find')->with($organisationId)->andThrows(new \RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new UpdateInquiryLinksCommand(
            $organisationId = Uuid::v6(),
            $caseNr = 'foo-123',
            $docIdsToAdd = [Uuid::v6()],
            $docIdsToRemove = [Uuid::v6()],
            $dossierIdsToAdd = [Uuid::v6()],
        );

        $organisation = \Mockery::mock(Organisation::class);
        $this->organisationRepository->expects('find')->with($organisationId)->andReturn($organisation);

        $this->inquiryService->expects('updateInquiryLinks')->with(
            $organisation,
            $caseNr,
            $docIdsToAdd,
            $docIdsToRemove,
            $dossierIdsToAdd,
        );

        $this->handler->__invoke($message);
    }
}
