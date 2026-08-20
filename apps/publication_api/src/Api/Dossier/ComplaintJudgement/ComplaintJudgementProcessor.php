<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\ExternalIdInUseException;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicMapper;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicService;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Document\DocumentPrefixDeterminer;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<ComplaintJudgementRequestDto,?ComplaintJudgementResponseDto>
 */
final readonly class ComplaintJudgementProcessor implements ProcessorInterface
{
    public function __construct(
        private DossierNumberValidator $dossierNumberValidator,
        private DossierSupportService $dossierSupportService,
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private DossierUpdateGuard $dossierUpdateGuard,
        private DossierRepository $dossierRepository,
        private DossierValidator $dossierValidator,
        private ComplaintJudgementMapper $complaintJudgementMapper,
        private DocumentPrefixDeterminer $documentPrefixDeterminer,
        private OrganisationResolver $organisationResolver,
        private NoticeNotPublicService $noticeNotPublicService,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?ComplaintJudgementResponseDto
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::string($uriVariables['dossierExternalId']);
        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);
        Assert::isInstanceOf($data, ComplaintJudgementRequestDto::class);

        $organisation = $this->organisationResolver->resolve($uriVariables);
        $subject = $this->dossierSupportService->getSubject($data, $organisation);
        $department = $this->dossierSupportService->getDepartment($organisation, $data->departmentId);
        $dossier = $this->dossierRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);

        if ($dossier !== null && ! $dossier instanceof ComplaintJudgement) {
            throw ExternalIdInUseException::forExternalIdAlreadyUsed($dossier->getType());
        }

        if ($dossier === null) {
            $documentPrefix = $this->documentPrefixDeterminer->forOrganisation($organisation);
            $this->dossierNumberValidator->validate($data->dossierNumber, $documentPrefix);
            $dossier = $this->create($organisation, $department, $subject, $data, $dossierExternalId, $documentPrefix);

            return $this->complaintJudgementMapper->fromEntity($dossier);
        }

        $this->dossierUpdateGuard->assertDossierIsEditable($dossier);

        $this->dossierNumberValidator->validate($data->dossierNumber, $dossier->getDocumentPrefix(), $dossier->getId());
        $this->update($dossier, $organisation, $department, $subject, $data);

        return $this->complaintJudgementMapper->fromEntity($dossier);
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): ComplaintJudgement {
        $complaintJudgement = ComplaintJudgementMapper::create(
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($complaintJudgementRequestDto->mainDocument !== null) {
            $mainDocument = ComplaintJudgementMainDocumentMapper::create($complaintJudgement, $complaintJudgementRequestDto->mainDocument);
            $complaintJudgement->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            $noticeNotPublic = $complaintJudgementRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublic);

            $complaintJudgement->setNoticeNotPublic(
                NoticeNotPublicMapper::create($complaintJudgement, $noticeNotPublic),
            );
        }

        $this->dossierValidator->validateDossier($complaintJudgement);
        $this->dossierSupportService->autoPublish($complaintJudgement);
        $this->dossierSupportService->validateCompletionAndPersist($complaintJudgement);
        $this->dossierSupportService->synchronizeArtifacts($complaintJudgement);

        return $complaintJudgement;
    }

    private function update(
        ComplaintJudgement $complaintJudgement,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
    ): void {
        $complaintJudgement = ComplaintJudgementMapper::update(
            $complaintJudgement,
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
        );

        if ($complaintJudgementRequestDto->mainDocument !== null) {
            if ($complaintJudgement->getNoticeNotPublic() !== null) {
                $this->noticeNotPublicService->deleteFromDossier($complaintJudgement);
            }

            $mainDocument = $complaintJudgement->getMainDocument() !== null
                ? ComplaintJudgementMainDocumentMapper::update($complaintJudgement, $complaintJudgementRequestDto->mainDocument)
                : ComplaintJudgementMainDocumentMapper::create($complaintJudgement, $complaintJudgementRequestDto->mainDocument);
            $complaintJudgement->setMainDocument($mainDocument);
            $this->dossierMainDocumentValidator->validate($mainDocument);
        } else {
            if ($complaintJudgement->getMainDocument() !== null) {
                $this->messageBus->dispatch(new DeleteMainDocumentCommand($complaintJudgement->getId()));
            }

            $noticeNotPublicDto = $complaintJudgementRequestDto->noticeNotPublic;
            Assert::notNull($noticeNotPublicDto);

            $notice = $complaintJudgement->getNoticeNotPublic() !== null
                ? $this->noticeNotPublicService->updateForDossier($complaintJudgement, $noticeNotPublicDto)
                : $this->noticeNotPublicService->createForDossier($complaintJudgement, $noticeNotPublicDto);
            $complaintJudgement->setNoticeNotPublic($notice);
        }

        $this->dossierValidator->validateDossier($complaintJudgement);
        $this->dossierSupportService->autoPublish($complaintJudgement);
        $this->dossierSupportService->validateCompletionAndPersist($complaintJudgement);
        $this->dossierSupportService->synchronizeArtifacts($complaintJudgement);
    }
}
