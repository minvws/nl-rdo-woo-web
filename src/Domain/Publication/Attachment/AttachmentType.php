<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-type AttachmentTypeArray array{
 *   type: string,
 *   value: string,
 *   label: string,
 * }
 *
 * The cases are based on https://identifier.overheid.nl/tooi/set/ccw_plooi_documentsoorten_aanlevering/1
 */
enum AttachmentType: string implements TranslatableInterface
{
    public const TRANS_DOMAIN = 'attachment';

    case ADVICE = 'c_d506b718';
    case REQUEST_FOR_ADVICE = 'c_a40458df';
    case ADVISORT_PROPOSAL = 'c_0e425c23';

    case BUDGET = 'c_dfa0ff1f';
    case POLICY_NOTE = 'c_9376c730';
    case DECISION_NOTE = 'c_2977c34f';
    case ANNUAL_PLAN = 'c_a6f44748';
    case ANNUAL_REPORT = 'c_3d782f30';

    case OFFICIAL_MESSAGE = 'c_8b92eab4';
    case EVALUATION_REPORT = 'c_38ba44de';
    case INSPECTION_REPORT = 'c_4efe1293';
    case RESEARCH_REPORT = 'c_6f49bf34';
    case ACCOUNTABILITY_REPORT = 'c_99d3e284';
    case PROGRESS_REPORT = 'c_cccba364';

    case TERM_AGENDA = 'c_a1dae55d';

    case DECISION_TO_IMPOSE_A_FINE = 'c_45be34e9';
    case DECISION_TO_IMPOSE_AN_ORDER_UNDER_ADMINISTRATIVE_ENFORCEMENT = 'c_a55b7649';
    case DECISION_TO_IMPOSE_AN_ORDER_SUBJECT_TO_PENALTY = 'c_566a4430';

    case DESIGNATION_DECISION = 'c_c1956ef0';
    case APPOINTMENT_DECISION = 'c_acb44d77';
    case DECISION_ON_REQUEST_ART3_WOB = 'c_e0865e4d';
    case DECISION_ON_REQUEST_ART4_1_WOO = 'c_bcaddc61';
    case CONCESSION = 'c_30e8b503';
    case RECOGNITION_DECISION = 'c_2ab17960';
    case CONSENT_DECISION = 'c_2c0438f4';
    case EXEMPTION_DECISION = 'c_f7dc55d9';
    case SUBSIDY_DECISION = 'c_aebfec50';
    case PERMIT = 'c_002fc258';

    case LETTER = 'c_7652d853';
    case BROCHURE = 'c_97f44ea5';
    case CIRCULAR = 'c_3eb5572a';
    case NEWS_ITEM = 'c_c2f56984';
    case ORGANIZATION_DETAILS = 'c_9ecc0007';
    case PRESS_RELEASE = 'c_7eba29ad';
    case SPEECH = 'c_2aedadff';

    case COVENANT = 'c_386e74cb';

    case APPLICATION_ART_4_1_WOO = 'c_8ec8e5e7';
    case OBJECTION = 'c_06a67c95';
    case COMPLAINT = 'c_ef935990';
    case EXEMPTION_REQUEST = 'c_d943ca24';
    case SUBSIDY_APPLICATION = 'c_5824891d';
    case PERMIT_APPLICATION = 'c_dad2a6ed';
    case WOB_REQUEST = 'c_dd451365';
    case POINT_OF_VIEW = 'c_df2cb56e';

    case ACTIONS = 'c_a17ef403';
    case PARLIAMENTARY_DOCUMENT = 'c_056a75e1';
    case NON_DOSSIER_DOCUMENT = 'c_f1652921';

    case PARLIAMENTARY_QUSTION_WITH_ANSWER = 'c_6d494ab6';
    case PARLIAMENTARY_QUSTION_WITHOUT_ANSWER = 'c_03c52ba0';

    case GOVERNMENT_GAZETTE = 'c_61e3099a';
    case STAATSCOURANT = 'c_0670bae1';

    case POLICY = 'c_fbaa7e4b';
    case PLAN = 'c_5b1055aa';

    case AGENDA = 'c_f90465b3';
    case DECISION_LIST = 'c_d4a4792f';
    case INCOMING_DOCUMENT = 'c_de27ae7a';
    case MEETING_REPORT = 'c_42e406dd';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            strtolower($this->name),
            domain: self::TRANS_DOMAIN,
            locale: $locale,
        );
    }

    /**
     * @phpstan-return AttachmentTypeArray
     *
     * @return array<string,string>
     */
    public function toArray(TranslatorInterface $translator, ?string $locale = null): array
    {
        return [
            'type' => 'AttachmentType',
            'value' => $this->value,
            'label' => $this->trans($translator, $locale),
        ];
    }
}
