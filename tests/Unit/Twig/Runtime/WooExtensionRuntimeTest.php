<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Runtime;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\DossierNotifications;
use App\Domain\Publication\Dossier\ViewModel\DossierNotificationsFactory;
use App\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use App\Service\HistoryService;
use App\Service\Security\OrganisationSwitcher;
use App\Twig\Runtime\WooExtensionRuntime;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WooExtensionRuntimeTest extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    private RequestStack&MockInterface $requestStack;
    private WooExtensionRuntime $runtime;
    private DossierNotificationsFactory&MockInterface $dossierNotificationsFactory;

    public function setUp(): void
    {
        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->dossierNotificationsFactory = \Mockery::mock(DossierNotificationsFactory::class);

        $this->runtime = new WooExtensionRuntime(
            $this->requestStack,
            \Mockery::mock(OrganisationSwitcher::class),
            \Mockery::mock(HistoryService::class),
            \Mockery::mock(DossierPathHelper::class),
            $this->dossierNotificationsFactory,
        );
    }

    #[DataProvider('queryStringWithoutParamProvider')]
    public function testQueryStringWithoutParam(
        string $queryString,
        string $paramToRemove,
        string $valueToRemove,
        string $expectedQuery,
    ): void {
        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('getQueryString')->andReturn($queryString);

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($request);

        self::assertEquals(
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
                'valueToRemove' => '',
                'expectedQuery' => '?a=1&b[]=2&b[]=3&c[x]=4',
            ],
            'remove-a-basic-param' => [
                'queryString' => '?a=1&b=2&c=3',
                'paramToRemove' => 'b',
                'valueToRemove' => '',
                'expectedQuery' => '?a=1&c=3',
            ],
            'remove-a-single-value-from-a-multivalue-param' => [
                'queryString' => '?a=1&b[]=2&b[]=3',
                'paramToRemove' => 'b',
                'valueToRemove' => '2',
                'expectedQuery' => '?a=1&b[]=3',
            ],
            'remove-only-one-named-subparam' => [
                'queryString' => '?a=1&dt[from]=a&dt[to]=b',
                'paramToRemove' => 'dt[from]',
                'valueToRemove' => '',
                'expectedQuery' => '?a=1&dt[to]=b',
            ],
        ];
    }

    public function testGetDossierNotifications(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $notifications = \Mockery::mock(DossierNotifications::class);

        $this->dossierNotificationsFactory->expects('make')->with($dossier)->andReturn($notifications);

        $this->assertSame(
            $notifications,
            $this->runtime->getDossierNotifications($dossier),
        );
    }
}
