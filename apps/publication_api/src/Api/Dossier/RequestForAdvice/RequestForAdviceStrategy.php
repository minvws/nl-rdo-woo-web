<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<RequestForAdvice,RequestForAdviceRequestDto>
 */
readonly class RequestForAdviceStrategy implements DossierStrategy
{
    public function __construct(
        private RequestForAdvicePersister $requestForAdvicePersister,
        private RequestForAdviceRequestMapper $requestForAdviceRequestMapper,
        private RequestForAdviceAttachmentSynchronizer $requestForAdviceAttachmentSynchronizer,
        private RequestForAdviceMainDocumentSynchronizer $requestForAdviceMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return RequestForAdvice::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, RequestForAdviceRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): RequestForAdvice {
        Assert::isInstanceOf($dossierRequestDto, RequestForAdviceRequestDto::class);

        $requestForAdvice = $this->requestForAdviceRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->requestForAdviceMainDocumentSynchronizer->create($requestForAdvice, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($requestForAdvice, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->requestForAdviceAttachmentSynchronizer->create($requestForAdvice, $dossierRequestDto->attachments);

        $this->requestForAdvicePersister->persist($requestForAdvice, null, $attachmentEvents);

        return $requestForAdvice;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, RequestForAdvice::class);
        Assert::isInstanceOf($dossierRequestDto, RequestForAdviceRequestDto::class);

        $this->requestForAdviceRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $requestForAdviceSnapshot = $this->requestForAdvicePersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->requestForAdviceMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->requestForAdviceMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->requestForAdviceAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->requestForAdvicePersister->persist($dossier, $requestForAdviceSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(RequestForAdviceRequestDto $requestForAdviceRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $requestForAdviceRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
