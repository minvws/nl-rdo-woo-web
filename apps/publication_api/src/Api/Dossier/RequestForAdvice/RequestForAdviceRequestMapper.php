<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierRequestMapper<RequestForAdvice,RequestForAdviceRequestDto>
 */
readonly class RequestForAdviceRequestMapper implements DossierRequestMapper
{
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): RequestForAdvice {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setExternalId($dossierExternalId);
        $requestForAdvice->setStatus(DossierStatus::CONCEPT);
        $requestForAdvice->setDocumentPrefix($documentPrefix);

        $this->update($requestForAdvice, $dossierRequestDto, $organisation, $department, $subject);

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

        $dossier->setDateFrom($dossierRequestDto->dossierDate);
        $dossier->setDepartments([$department]);
        $dossier->setDossierNumber($dossierRequestDto->dossierNumber);
        $dossier->setOrganisation($organisation);
        if (! $dossier->getStatus()->isPublished()) {
            $dossier->setPublicationDate($dossierRequestDto->publicationDate);
        }
        $dossier->setSubject($subject);
        $dossier->setSummary($dossierRequestDto->summary);
        $dossier->setTitle($dossierRequestDto->title);
        $dossier->setLink($dossierRequestDto->link);
        $dossier->setAdvisoryBodies($dossierRequestDto->advisoryBodies);
    }
}
