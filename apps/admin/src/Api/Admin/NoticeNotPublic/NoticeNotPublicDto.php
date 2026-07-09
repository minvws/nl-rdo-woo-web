<?php

declare(strict_types=1);

namespace Admin\Api\Admin\NoticeNotPublic;

use Admin\Api\Admin\Dossier\DossierReferenceDto;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicAlreadyExistsException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    uriTemplate: '/notice-not-public',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new Post(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: NoticeNotPublicInput::class,
        ),
        new Put(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: NoticeNotPublicInput::class,
        ),
        new Delete(
            security: "is_granted('AuthMatrix.dossier.update')",
        ),
    ],
    uriVariables: [
        'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
    ],
    routePrefix: '/dossiers/{dossierId}',
    stateless: false,
    openapi: new Operation(
        tags: ['NoticeNotPublic'],
    ),
    exceptionToStatus: [
        NoticeNotPublicAlreadyExistsException::class => 409,
        NoticeNotPublicNotFoundException::class => 404,
    ],
    provider: NoticeNotPublicProvider::class,
    processor: NoticeNotPublicProcessor::class,
)]
final readonly class NoticeNotPublicDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public DossierReferenceDto $dossier,
        public Uuid $id,
        public ?string $documentName,
        public PlainDate $formalDate,
        public array $grounds,
        public ?string $explanation,
    ) {
    }

    public static function fromEntity(NoticeNotPublic $notice): self
    {
        return new self(
            DossierReferenceDto::fromEntity($notice->getDossier()),
            $notice->getId(),
            $notice->getDocumentName(),
            $notice->getFormalDate(),
            $notice->getGrounds(),
            $notice->getExplanation(),
        );
    }
}
