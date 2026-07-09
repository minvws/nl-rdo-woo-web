<?php

declare(strict_types=1);

namespace PublicationApi\Api\Pagination;

use ApiPlatform\Metadata\Operation;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\HasId;
use Shared\ValueObject\Url;
use Stringable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_slice;
use function base64_encode;
use function count;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class CursorPageFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param list<HasId> $fetchedEntities rows from the repository (max itemsPerPage + 1)
     * @param list<object> $mappedDtos mapped DTOs, in the same order as $fetchedEntities
     * @param array<array-key, string> $uriVariables URI variables from the current request
     */
    public function create(
        array $fetchedEntities,
        array $mappedDtos,
        int $itemsPerPage,
        Operation $operation,
        array $uriVariables,
    ): CursorPage {
        $hasNextPage = count($fetchedEntities) > $itemsPerPage;

        if (! $hasNextPage) {
            return new CursorPage(items: $mappedDtos, hasNextPage: false);
        }

        $trimmedDtos = array_slice($mappedDtos, 0, $itemsPerPage);

        $nextCursor = $this->buildCursor($fetchedEntities[$itemsPerPage - 1]);

        $routeParams = array_map(static fn (Stringable|string $v): string => (string) $v, $uriVariables);
        $routeParams['pagination[cursor]'] = $nextCursor;

        $routeName = $operation->getName();
        Assert::string($routeName, 'Operation must have a route name');

        $nextUrl = $this->urlGenerator->generate(
            $routeName,
            $routeParams,
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $halLinks = new LinkCollection();
        $halLinks->set('next', new Link(Url::create($nextUrl)));

        return new CursorPage(
            items: $trimmedDtos,
            hasNextPage: true,
            halLinks: $halLinks,
        );
    }

    private function buildCursor(HasId $entity): string
    {
        $json = json_encode(['id' => (string) $entity->getId()], JSON_THROW_ON_ERROR);

        return base64_encode($json);
    }
}
