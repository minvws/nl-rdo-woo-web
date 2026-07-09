<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Department;

use ApiPlatform\Symfony\Bundle\Test\Client;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Tests\Factory\DepartmentFactory;
use Symfony\Component\HttpFoundation\Request;

use function array_column;
use function array_diff;
use function array_intersect;
use function array_merge;
use function sort;
use function sprintf;

final class DepartmentPaginationPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetCollectionWithFullPaginationFlow(): void
    {
        $allCreatedIds = [];

        for ($i = 0; $i < 11; $i++) {
            $allCreatedIds[] = (string) DepartmentFactory::createOne()->getId();
        }

        sort($allCreatedIds);

        $client = self::createPublicationApiClient();

        ['ids' => $page1Ids, 'data' => $page1Data] = $this->fetchPage(
            $client,
            '/api/publication/v1/department',
            5,
            true,
            $allCreatedIds,
            'Page 1',
        );
        $page2Url = $this->extractNextHref($page1Data, 'Page 1');

        ['ids' => $page2Ids, 'data' => $page2Data] = $this->fetchPage(
            $client,
            $page2Url,
            5,
            true,
            $allCreatedIds,
            'Page 2',
        );
        $page3Url = $this->extractNextHref($page2Data, 'Page 2');

        ['ids' => $page3Ids] = $this->fetchPage(
            $client,
            $page3Url,
            1,
            false,
            $allCreatedIds,
            'Page 3',
        );

        self::assertEmpty(array_intersect($page1Ids, $page2Ids), 'Page 1 and page 2 must not share any items');
        self::assertEmpty(array_intersect($page1Ids, $page3Ids), 'Page 1 and page 3 must not share any items');
        self::assertEmpty(array_intersect($page2Ids, $page3Ids), 'Page 2 and page 3 must not share any items');

        $allReturnedIds = array_merge($page1Ids, $page2Ids, $page3Ids);
        sort($allReturnedIds);
        self::assertSame($allCreatedIds, $allReturnedIds, 'All 11 items must appear exactly once across all pages');
    }

    /**
     * @param array<array-key, string> $knownIds
     *
     * @return array{
     *     ids: list<string>,
     *     data: array{items: list<array<string, mixed>>, hasNextPage: bool, _links?: array<string, array<string, string>>}
     *     }
     */
    private function fetchPage(Client $client, string $url, int $expectedCount, bool $expectedHasNext, array $knownIds, string $pageLabel): array
    {
        $response = $client->request(Request::METHOD_GET, $url);
        self::assertResponseIsSuccessful();

        /** @var array{items: list<array<string, mixed>>, hasNextPage: bool, _links?: array<string, array<string, string>>} $data */
        $data = $response->toArray();
        self::assertArrayHasKey('items', $data, sprintf("%s: missing 'items'", $pageLabel));
        self::assertArrayHasKey('hasNextPage', $data, sprintf("%s: missing 'hasNextPage'", $pageLabel));
        self::assertCount($expectedCount, $data['items'], sprintf('%s: wrong item count', $pageLabel));
        self::assertSame($expectedHasNext, $data['hasNextPage'], sprintf('%s: wrong hasNextPage', $pageLabel));

        if ($expectedHasNext) {
            self::assertArrayHasKey('_links', $data, sprintf('%s: must have _links when hasNextPage=true', $pageLabel));
        } else {
            self::assertArrayNotHasKey('_links', $data, sprintf('%s: must NOT have _links on last page', $pageLabel));
        }

        /** @var list<string> $ids */
        $ids = array_column($data['items'], 'id');
        self::assertEmpty(array_diff($ids, $knownIds), sprintf('%s: returned unknown IDs', $pageLabel));

        return ['ids' => $ids, 'data' => $data];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function extractNextHref(array $data, string $pageLabel): string
    {
        /** @var array<string, array<string, string>> $links */
        $links = $data['_links'] ?? [];
        self::assertArrayHasKey('next', $links, sprintf("%s: _links must contain 'next'", $pageLabel));

        $href = $links['next']['href'] ?? '';
        self::assertNotEmpty($href, sprintf('%s: next href must not be empty', $pageLabel));

        return $href;
    }
}
