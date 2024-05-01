<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-import-type AttachmentTypeBranchArray from AttachmentTypeBranch
 * @phpstan-import-type AttachmentTypeArray from AttachmentType
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
final readonly class AttachmentTypeFactory
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
                name: $this->trans('adviesdocument'),
                attachmentTypes: [
                    AttachmentType::ADVICE,
                    AttachmentType::REQUEST_FOR_ADVICE,
                    AttachmentType::ADVISORT_PROPOSAL,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('beleidsdocument'),
                attachmentTypes: [
                    AttachmentType::BUDGET,
                    AttachmentType::POLICY_NOTE,
                    AttachmentType::DECISION_NOTE,
                    AttachmentType::ANNUAL_PLAN,
                    AttachmentType::ANNUAL_REPORT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('beleidsdocument'),
                branch: new AttachmentTypeBranch(
                    name: $this->trans('rapport'),
                    branch: null,
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
                    AttachmentType::TERM_AGENDA,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('beschikking'),
                branch: new AttachmentTypeBranch(
                    name: $this->trans('beschikking tot handhaving'),
                    attachmentTypes: [
                        AttachmentType::DECISION_TO_IMPOSE_A_FINE,
                        AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_UNDER_ADMINISTRATIVE_ENFORCEMENT,
                        AttachmentType::DECISION_TO_IMPOSE_AN_ORDER_SUBJECT_TO_PENALTY,
                    ],
                ),
                attachmentTypes: [
                    AttachmentType::DESIGNATION_DECISION,
                    AttachmentType::APPOINTMENT_DECISION,
                    AttachmentType::DECISION_ON_REQUEST_ART3_WOB,
                    AttachmentType::DECISION_ON_REQUEST_ART4_1_WOO,
                    AttachmentType::CONCESSION,
                    AttachmentType::RECOGNITION_DECISION,
                    AttachmentType::CONSENT_DECISION,
                    AttachmentType::EXEMPTION_DECISION,
                    AttachmentType::SUBSIDY_DECISION,
                    AttachmentType::PERMIT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('brief'),
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

            new AttachmentTypeBranch(
                name: $this->trans('document van burger'),
                attachmentTypes: [
                    AttachmentType::APPLICATION_ART_4_1_WOO,
                    AttachmentType::OBJECTION,
                    AttachmentType::COMPLAINT,
                    AttachmentType::EXEMPTION_REQUEST,
                    AttachmentType::SUBSIDY_APPLICATION,
                    AttachmentType::PERMIT_APPLICATION,
                    AttachmentType::WOB_REQUEST,
                    AttachmentType::POINT_OF_VIEW,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('parlementair document'),
                branch: new AttachmentTypeBranch(
                    name: $this->trans('Kamervraag'),
                    attachmentTypes: [
                        AttachmentType::PARLIAMENTARY_QUSTION_WITH_ANSWER,
                        AttachmentType::PARLIAMENTARY_QUSTION_WITHOUT_ANSWER,
                    ],
                ),
                attachmentTypes: [
                    AttachmentType::ACTIONS,
                    AttachmentType::PARLIAMENTARY_DOCUMENT,
                    AttachmentType::NON_DOSSIER_DOCUMENT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('publicatieblad'),
                attachmentTypes: [
                    AttachmentType::GOVERNMENT_GAZETTE,
                    AttachmentType::STAATSCOURANT,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('regelgeving'),
                attachmentTypes: [
                    AttachmentType::POLICY,
                    AttachmentType::PLAN,
                ],
            ),

            new AttachmentTypeBranch(
                name: $this->trans('vergaderstuk'),
                attachmentTypes: [
                    AttachmentType::AGENDA,
                    AttachmentType::DECISION_LIST,
                    AttachmentType::INCOMING_DOCUMENT,
                    AttachmentType::MEETING_REPORT,
                ],
            ),
        ]);
    }

    /**
     * @return array<int,AttachmentTypeBranchArray|AttachmentTypeArray>
     */
    public function makeAsArray(?AttachmentType $exclude = null): array
    {
        $collection = $this->make();

        if ($exclude !== null) {
            $collection->removeElement($exclude);
        }

        /** @var array<int,AttachmentTypeBranchArray|AttachmentTypeArray> */
        return $collection
            ->map(fn (AttachmentTypeBranch|AttachmentType $item): array => $item->toArray($this->translator))
            ->toArray();
    }

    private function trans(string $id): string
    {
        return $this->translator->trans($id, domain: AttachmentType::TRANS_DOMAIN);
    }
}
