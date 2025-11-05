<?php

declare(strict_types=1);

namespace App\Api\Publication\V1\Prefix;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Organisation\Organisation;
use App\Domain\Organisation\OrganisationRepository;
use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Domain\Publication\Dossier\DocumentPrefixRepository;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 *
 * @implements ProcessorInterface<PrefixCreateDto,?PrefixDto>
 */
final readonly class PrefixProcessor implements ProcessorInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private DocumentPrefixRepository $documentPrefixRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?PrefixDto
    {
        unset($context);

        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        Assert::isInstanceOf($organisation, Organisation::class);

        if ($operation instanceof Post) {
            Assert::isInstanceOf($data, PrefixCreateDto::class);
            $documentPrefix = $this->create($organisation, $data);

            return PrefixMapper::fromEntity($documentPrefix);
        }

        $documentPrefix = $this->documentPrefixRepository->find($uriVariables['prefixId']);
        Assert::isInstanceOf($documentPrefix, DocumentPrefix::class);

        if ($operation instanceof Delete) {
            $this->delete($documentPrefix);
        }

        return null;
    }

    private function create(Organisation $organisation, PrefixCreateDto $prefixCreateDto): DocumentPrefix
    {
        $documentPrefix = PrefixMapper::fromCreateDto($prefixCreateDto, $organisation);
        $this->documentPrefixRepository->save($documentPrefix, true);

        return $documentPrefix;
    }

    private function delete(DocumentPrefix $documentPrefix): void
    {
        $documentPrefix->archive();

        $this->documentPrefixRepository->save($documentPrefix, true);
    }
}
