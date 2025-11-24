<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\WooDecision;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Api\Publication\V1\Attachment\AttachmentRequestDto;
use Shared\Api\Publication\V1\Dossier\AbstractDossierProcessor;
use Shared\Api\Publication\V1\Dossier\AbstractDossierRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Service\AttachmentService;
use Shared\Service\DossierService;
use Shared\Service\MainDocumentService;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class WooDecisionProcessor extends AbstractDossierProcessor
{
    public function __construct(
        AttachmentService $attachmentService,
        DepartmentRepository $departmentRepository,
        private readonly DossierDispatcher $dossierDispatcher,
        DossierService $dossierService,
        EntityManagerInterface $entityManagerInterface,
        MainDocumentService $mainDocumentService,
        OrganisationRepository $organisationRepository,
        SubjectRepository $subjectRepository,
        private readonly WooDecisionDispatcher $wooDecisionDispatcher,
        private readonly WooDecisionRepository $wooDecisionRepository,
    ) {
        parent::__construct(
            $attachmentService,
            $departmentRepository,
            $dossierDispatcher,
            $dossierService,
            $entityManagerInterface,
            $mainDocumentService,
            $organisationRepository,
            $subjectRepository,
        );
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?WooDecisionDto
    {
        unset($context);
        Assert::isInstanceOf($data, AbstractDossierRequestDto::class);

        $organisation = $this->getOrganisation($uriVariables);
        $subject = $this->getSubject($data, $organisation);
        $department = $this->getDepartment($organisation, $data->departmentId);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, WooDecisionCreateRequestDto::class);
            $wooDecision = $this->create($organisation, $department, $subject, $data);

            return WooDecisionMapper::fromEntity($wooDecision);
        }

        if ($operation instanceof Put) {
            Assert::isInstanceOf($data, WooDecisionUpdateRequestDto::class);
            $wooDecision = $this->wooDecisionRepository->find($uriVariables['wooDecisionId']);
            Assert::isInstanceOf($wooDecision, WooDecision::class);

            $this->update($wooDecision, $organisation, $department, $subject, $data);

            return WooDecisionMapper::fromEntity($wooDecision);
        }

        return null;
    }

    private function create(
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        WooDecisionCreateRequestDto $wooDecisionCreateRequestDto,
    ): WooDecision {
        $wooDecision = WooDecisionMapper::create($wooDecisionCreateRequestDto, $organisation, $department, $subject);
        $mainDocument = WooDecisionMainDocumentMapper::create($wooDecision, $wooDecisionCreateRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionCreateRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $wooDecision->setMainDocument($mainDocument);
        $this->addAttachments($wooDecision, $attachments);

        $this->validateDossier($wooDecision);
        $this->dispatchCreateDossierCommand($wooDecision);

        return $wooDecision;
    }

    private function update(
        WooDecision $wooDecision,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        WooDecisionUpdateRequestDto $wooDecisionUpdateRequestDto,
    ): void {
        $wooDecision = WooDecisionMapper::update($wooDecision, $wooDecisionUpdateRequestDto, $organisation, $department, $subject);
        $mainDocument = WooDecisionMainDocumentMapper::update($wooDecision, $wooDecisionUpdateRequestDto->mainDocument);
        $attachments = $this->getAttachments($wooDecision, $wooDecisionUpdateRequestDto->attachments);

        $this->validateMainDocument($mainDocument);
        $this->validateAttachments($attachments);

        $wooDecision->setMainDocument($mainDocument);
        $this->removeDossierAttachments($wooDecision);
        $this->addAttachments($wooDecision, $attachments);

        $this->validateDossier($wooDecision);

        $this->wooDecisionDispatcher->dispatchUpdateDecisionCommand($wooDecision);
        $this->dispatchUpdateDossierCommand($wooDecision);
    }

    /**
     * @param array<array-key,AttachmentRequestDto> $attachments
     *
     * @return array<array-key,WooDecisionAttachment>
     */
    private function getAttachments(WooDecision $wooDecision, array $attachments): array
    {
        return array_map(fn (AttachmentRequestDto $attachment): WooDecisionAttachment => WooDecisionAttachmentMapper::create(
            $wooDecision,
            $attachment,
        ), $attachments);
    }

    protected function dispatchUpdateDossierCommand(AbstractDossier $wooDecision): void
    {
        $this->dossierDispatcher->dispatchUpdateDossierDetailsCommand($wooDecision);
    }
}
