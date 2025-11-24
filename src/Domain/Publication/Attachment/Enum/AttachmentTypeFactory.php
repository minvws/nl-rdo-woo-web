<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Enum;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-import-type AttachmentTypeBranchArray from AttachmentTypeBranch
 * @phpstan-import-type AttachmentTypeArray from AttachmentType
 *
 * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
 */
readonly class AttachmentTypeFactory
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return ArrayCollection<int,AttachmentType|AttachmentTypeBranch>
     */
    public function make(): ArrayCollection
    {
        /** @var ArrayCollection<int,AttachmentType|AttachmentTypeBranch> */
        return new ArrayCollection([
            new AttachmentTypeBranch(
                name: $this->trans('branch.advice_document'),
                attachmentTypes: [
                    AttachmentType::ADVICE,
                    AttachmentType::REQUEST_FOR_ADVICE,
                    AttachmentType::ADVISORT_PROPOSAL,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.policy_document'),
                branch: new AttachmentTypeBranch(
                    name: $this->trans('branch.report'),
                    attachmentTypes: [
                        AttachmentType::OFFICIAL_MESSAGE,
                        AttachmentType::EVALUATION_REPORT,
                        AttachmentType::INSPECTION_REPORT,
                        AttachmentType::RESEARCH_REPORT,
                        AttachmentType::ACCOUNTABILITY_REPORT,
                        AttachmentType::PROGRESS_REPORT,
                    ],
                ),
                attachmentTypes: [
                    AttachmentType::BUDGET,
                    AttachmentType::POLICY_NOTE,
                    AttachmentType::DECISION_NOTE,
                    AttachmentType::ANNUAL_PLAN,
                    AttachmentType::ANNUAL_REPORT,
                    AttachmentType::TERM_AGENDA,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.decision_to_impose'),
                branch: new AttachmentTypeBranch(
                    name: $this->trans('branch.decision_to_impose_enforcement'),
                    attachmentTypes: [
                        AttachmentType::DECISION_TO_IMPOSE_A_FINE,
                        AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_UNDER_ADMINISTRATIVE_ENFORCEMENT,
                        AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_SUBJECT_TO_PENALTY,
                    ],
                ),
                attachmentTypes: [
                    AttachmentType::DESIGNATION_DECISION,
                    AttachmentType::APPOINTMENT_DECISION,
                    AttachmentType::CONCESSION,
                    AttachmentType::RECOGNITION_DECISION,
                    AttachmentType::CONSENT_DECISION,
                    AttachmentType::EXEMPTION_DECISION,
                    AttachmentType::SUBSIDY_DECISION,
                    AttachmentType::PERMIT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.communications'),
                attachmentTypes: [
                    AttachmentType::LETTER,
                    AttachmentType::BROCHURE,
                    AttachmentType::CIRCULAR,
                    AttachmentType::NEWS_ITEM,
                    AttachmentType::ORGANIZATION_DETAILS,
                    AttachmentType::PRESS_RELEASE,
                    AttachmentType::SPEECH,
                ],
            ),

            AttachmentType::COVENANT,

            AttachmentType::COMPLAINT_JUDGEMENT,

            new AttachmentTypeBranch(
                name: $this->trans('branch.citizen_document'),
                attachmentTypes: [
                    AttachmentType::OBJECTION,
                    AttachmentType::COMPLAINT,
                    AttachmentType::EXEMPTION_REQUEST,
                    AttachmentType::SUBSIDY_APPLICATION,
                    AttachmentType::PERMIT_APPLICATION,
                    AttachmentType::POINT_OF_VIEW,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.parliamentary_document'),
                branch: new AttachmentTypeBranch(
                    name: $this->trans('branch.parliamentary_question'),
                    attachmentTypes: [
                        AttachmentType::PARLIAMENTARY_QUESTION_WITH_ANSWER,
                        AttachmentType::PARLIAMENTARY_QUESTION_WITHOUT_ANSWER,
                    ],
                ),
                attachmentTypes: [
                    AttachmentType::ACTIONS,
                    AttachmentType::PARLIAMENTARY_DOCUMENT,
                    AttachmentType::MOTION,
                    AttachmentType::NON_DOSSIER_DOCUMENT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.publication_sheet'),
                attachmentTypes: [
                    AttachmentType::GOVERNMENT_GAZETTE,
                    AttachmentType::STAATSCOURANT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.regulation'),
                attachmentTypes: [
                    AttachmentType::POLICY,
                    AttachmentType::PLAN,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.meeting_document'),
                attachmentTypes: [
                    AttachmentType::AGENDA,
                    AttachmentType::DECISION_LIST,
                    AttachmentType::INCOMING_DOCUMENT,
                    AttachmentType::MEETING_REPORT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('branch.woo_procedure'),
                attachmentTypes: [
                    AttachmentType::DECISION_ON_OBJECTION,
                    AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST,
                    AttachmentType::WOB_WOO_REQUEST,
                ],
            ),
            new AttachmentTypeBranch(
                name: $this->trans('branch.other'),
                attachmentTypes: [
                    AttachmentType::OTHER,
                ],
            ),
        ]);
    }

    /**
     * @param ?array<array-key,AttachmentType> $allowedTypes
     *
     * @return array<int,AttachmentTypeBranchArray|AttachmentTypeArray>
     */
    public function makeAsArray(?array $allowedTypes = null): array
    {
        $collection = $this->make();

        if ($allowedTypes !== null) {
            /** @var ArrayCollection<int,AttachmentType|AttachmentTypeBranch> $newCollection */
            $newCollection = new ArrayCollection();

            foreach ($collection as $item) {
                if ($item instanceof AttachmentTypeBranch) {
                    $branch = $item->filter($allowedTypes);
                    if ($branch !== null) {
                        $newCollection->add($branch);
                    }
                } elseif (in_array($item, $allowedTypes, true)) {
                    $newCollection->add($item);
                }
            }

            $collection = $newCollection;
        }

        return $collection
            ->map(fn (AttachmentTypeBranch|AttachmentType $item): array => $item->toArray($this->translator))
            ->toArray();
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id, domain: AttachmentType::TRANS_DOMAIN);
    }
}
