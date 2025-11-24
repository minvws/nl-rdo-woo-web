<?php

declare(strict_types=1);

namespace Shared;

use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;
use Shared\Doctrine\EncryptedArray;
use Shared\Doctrine\EncryptedString;
use Shared\Service\Encryption\EncryptionService;
use Shared\Service\Encryption\EncryptionServiceInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug, private ApplicationId $applicationId)
    {
        parent::__construct($environment, $debug);
    }

    public function getApplicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function getSharedConfigDir(): string
    {
        return sprintf('%s/config', $this->getProjectDir());
    }

    public function getAppConfigDir(): string
    {
        return sprintf('%s/apps/%s/config', $this->getProjectDir(), $this->getApplicationId()->value);
    }

    public function registerBundles(): iterable
    {
        /** @var array<string,array<string,bool>> $sharedBundles */
        $sharedBundles = require $this->getSharedConfigDir() . '/bundles.php';

        /** @var array<string,array<string,bool>> $appBundles */
        $appBundles = is_dir($this->getAppConfigDir()) && is_file($this->getAppConfigDir() . '/bundles.php')
            ? (require $this->getAppConfigDir() . '/bundles.php')
            : [];

        // load common bundles, such as the FrameworkBundle, as well as
        // specific bundles required exclusively for the app itself
        foreach (array_merge($sharedBundles, $appBundles) as $class => $envs) {
            if ($envs[$this->getEnvironment()] ?? $envs['all'] ?? false) {
                /** @var class-string<BundleInterface> $class */
                yield new $class();
            }
        }
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    #[\Override]
    public function getCacheDir(): string
    {
        $appCacheDir = $_SERVER['APP_CACHE_DIR'] ?? null;
        Assert::nullOrstring($appCacheDir);

        // divide cache for each application
        return sprintf(
            '%s/%s/%s',
            $appCacheDir ?? sprintf('%s/var/cache', $this->getProjectDir()),
            $this->getApplicationId()->value,
            $this->getEnvironment(),
        );
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    #[\Override]
    public function getLogDir(): string
    {
        $appLogDir = $_SERVER['APP_LOG_DIR'] ?? null;
        Assert::nullOrString($appLogDir);

        // divide logs for each application
        return ($appLogDir ?? $this->getProjectDir() . '/var/log') . '/' . $this->getApplicationId()->value;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // load common config files, such as the framework.yaml, as well as
        // specific configs required exclusively for the app itself
        $this->doConfigureContainer($container, $this->getSharedConfigDir());
        $this->doConfigureContainer($container, $this->getAppConfigDir());
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // load common routes files, such as the routes/framework.yaml, as well as
        // specific routes required exclusively for the app itself
        $this->doConfigureRoutes($routes, $this->getSharedConfigDir());
        $this->doConfigureRoutes($routes, $this->getAppConfigDir());
    }

    private function doConfigureContainer(ContainerConfigurator $container, string $configDir): void
    {
        if (! is_dir($configDir)) {
            return;
        }

        $container->import($configDir . '/{packages}/*.{php,yaml}');
        $container->import($configDir . '/{packages}/' . $this->getEnvironment() . '/*.{php,yaml}');

        if (is_file($configDir . '/services.yaml')) {
            $container->import($configDir . '/services.yaml');
            $container->import($configDir . '/{services}_' . $this->getEnvironment() . '.yaml');
        } else {
            $container->import($configDir . '/{services}.php');
        }
    }

    private function doConfigureRoutes(RoutingConfigurator $routes, string $configDir): void
    {
        if (! is_dir($configDir)) {
            return;
        }

        $routes->import($configDir . '/{routes}/' . $this->getEnvironment() . '/*.{php,yaml}');
        $routes->import($configDir . '/{routes}/*.{php,yaml}');

        if (is_file($configDir . '/routes.yaml')) {
            $routes->import($configDir . '/routes.yaml');
        } else {
            $routes->import($configDir . '/{routes}.php');
        }

        $fileName = (new \ReflectionObject($this))->getFileName();
        if ($fileName !== false) {
            $routes->import($fileName, 'attribute');
        }
    }

    #[\Override]
    public function boot(): void
    {
        parent::boot();

        $this->injectEncryptionServiceIntoDbalTypes();
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->registerExtension(new DependencyInjection\AuthMatrixExtension());
    }

    protected function injectEncryptionServiceIntoDbalTypes(): void
    {
        $serviceLocator = new ServiceLocator([
            EncryptionServiceInterface::class => fn () => $this->container->get(EncryptionService::class),
            LoggerInterface::class => fn () => $this->container->get(LoggerInterface::class),
        ]);

        if (! Type::hasType(EncryptedString::TYPE)) {
            Type::addType(EncryptedString::TYPE, new EncryptedString($serviceLocator));
        }

        if (! Type::hasType(EncryptedArray::TYPE)) {
            Type::addType(EncryptedArray::TYPE, new EncryptedArray($serviceLocator));
        }
    }
}
