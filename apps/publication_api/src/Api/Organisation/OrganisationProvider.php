<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
        private SubjectPreviewUrlGenerator $subjectPreviewUrlGenerator,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|OrganisationDetailResponseDto
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($operation, $context);
        }

        try {
            $organisationId = Uuid::fromString($uriVariables['organisationId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Organisation', $uriVariables['organisationId']);
        }

        return $this->provideSingle($organisationId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(
        Operation $operation,
        array $context,
    ): CursorPage {
        $organisations = $this->organisationRepository->getPaginated(
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = OrganisationMapper::fromEntitiesWithDetail($organisations, $this->subjectPreviewUrlGenerator);

        /** @var list<HasId> $organisations */
        return $this->cursorPageFactory->create(
            $organisations,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            [],
        );
    }

    private function provideSingle(Uuid $organisationId): OrganisationDetailResponseDto
    {
        $organisation = $this->organisationRepository->find($organisationId);
        if ($organisation === null) {
            throw EntityNotFoundException::for('Organisation', $organisationId);
        }

        return OrganisationMapper::fromEntityWithDetail($organisation, $this->subjectPreviewUrlGenerator);
    }
}
