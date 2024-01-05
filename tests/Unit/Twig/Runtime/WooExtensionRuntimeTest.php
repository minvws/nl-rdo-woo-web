<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Runtime;

use App\Repository\DocumentRepository;
use App\Service\DocumentUploadQueue;
use App\Service\HistoryService;
use App\Service\Search\Query\Facet\FacetMappingService;
use App\Service\Security\OrganisationSwitcher;
use App\Service\Storage\ThumbnailStorageService;
use App\Twig\Runtime\WooExtensionRuntime;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\Translator;

class WooExtensionRuntimeTest extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    private RequestStack|MockInterface $requestStack;
    private WooExtensionRuntime $runtime;

    public function setUp(): void
    {
        $this->requestStack = \Mockery::mock(RequestStack::class);

        $this->runtime = new WooExtensionRuntime(
            $this->requestStack,
            \Mockery::mock(ThumbnailStorageService::class),
            \Mockery::mock(Translator::class),
            \Mockery::mock(DocumentRepository::class),
            \Mockery::mock(UrlGeneratorInterface::class),
            \Mockery::mock(FacetMappingService::class),
            \Mockery::mock(DocumentUploadQueue::class),
            \Mockery::mock(OrganisationSwitcher::class),
            \Mockery::mock(HistoryService::class),
        );
    }

    /**
     * @dataProvider queryStringWithoutParamProvider
     */
    public function testQueryStringWithoutParam(
        string $queryString,
        string $paramToRemove,
        string $valueToRemove,
        string $expectedQuery
    ): void {
        $request = \Mockery::mock(Request::class);
        $request->expects('getQueryString')->zeroOrMoreTimes()->andReturn($queryString);

        $this->requestStack->expects('getCurrentRequest')->zeroOrMoreTimes()->andReturn($request);

        $this->assertEquals(
            $expectedQuery,
            urldecode($this->runtime->queryStringWithoutParam($paramToRemove, $valueToRemove))
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function queryStringWithoutParamProvider(): array
    {
        return [
            'remove-a-non-existing-param-does-nothing' => [
                'queryString' => '?a=1&b[]=2&b[]=3&c[x]=4',
                'paramToRemove' => 'foo',
                'value' => '',
                'expectedQuery' => '?a=1&b[]=2&b[]=3&c[x]=4',
            ],
            'remove-a-basic-param' => [
                'queryString' => '?a=1&b=2&c=3',
                'paramToRemove' => 'b',
                'value' => '',
                'expectedQuery' => '?a=1&c=3',
            ],
            'remove-a-single-value-from-a-multivalue-param' => [
                'queryString' => '?a=1&b[]=2&b[]=3',
                'paramToRemove' => 'b',
                'value' => '2',
                'expectedQuery' => '?a=1&b[]=3',
            ],
            'remove-only-one-named-subparam' => [
                'queryString' => '?a=1&dt[from]=a&dt[to]=b',
                'paramToRemove' => 'dt[from]',
                'value' => '',
                'expectedQuery' => '?a=1&dt[to]=b',
            ],
        ];
    }
}
