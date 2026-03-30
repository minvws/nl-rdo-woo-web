<?php

declare(strict_types=1);

namespace Shared;

use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use Shared\Doctrine\EncryptedArray;
use Shared\Doctrine\EncryptedString;
use Shared\Service\Encryption\EncryptionService;
use Shared\Service\Encryption\EncryptionServiceInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use UnitEnum;
use Webmozart\Assert\Assert;

use function array_merge;
use function get_parent_class;
use function is_dir;
use function is_file;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function ucfirst;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        getKernelParameters as getKernelParametersTrait;
    }

    public function __construct(
        string $environment,
        bool $debug,
        private ApplicationId $applicationId,
        private TenantId $tenantId,
    ) {
        parent::__construct($environment, $debug);
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->injectEncryptionServiceIntoDbalTypes();
    }

    public function getApplicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getSharedConfigDir(): string
    {
        return sprintf('%s/config', $this->getProjectDir());
    }

    public function getAppConfigDir(): string
    {
        return sprintf('%s/apps/%s/config', $this->getProjectDir(), $this->getApplicationId()->value);
    }

    public function getTenantConfigDir(): string
    {
        return sprintf('%s/tenants/%s/config', $this->getProjectDir(), $this->getTenantId()->value);
    }

    public function registerBundles(): iterable
    {
        /** @var array<string,array<string,bool>> $sharedBundles */
        $sharedBundles = require $this->getSharedConfigDir() . '/bundles.php';

        /** @var array<string,array<string,bool>> $tenantBundles */
        $tenantBundles = is_dir($this->getTenantConfigDir()) && is_file($this->getTenantConfigDir() . '/bundles.php')
            ? (require $this->getTenantConfigDir() . '/bundles.php')
            : [];

        /** @var array<string,array<string,bool>> $appBundles */
        $appBundles = is_dir($this->getAppConfigDir()) && is_file($this->getAppConfigDir() . '/bundles.php')
            ? (require $this->getAppConfigDir() . '/bundles.php')
            : [];

        // load common bundles, such as the FrameworkBundle, as well as
        // specific bundles required exclusively for the app itself
        foreach (array_merge($sharedBundles, $tenantBundles, $appBundles) as $class => $envs) {
            if ($envs[$this->getEnvironment()] ?? $envs['all'] ?? false) {
                /** @var class-string<BundleInterface> $class */
                yield new $class();
            }
        }
    }

    #[Override]
    public function getCacheDir(): string
    {
        $appCacheDir = $_SERVER['APP_CACHE_DIR'] ?? null;
        Assert::nullOrstring($appCacheDir);

        return $this->buildCachePath($appCacheDir);
    }

    #[Override]
    public function getBuildDir(): string
    {
        $appCacheDir = $_SERVER['APP_BUILD_DIR'] ?? null;
        Assert::nullOrstring($appCacheDir);

        return $this->buildCachePath($appCacheDir);
    }

    #[Override]
    public function getShareDir(): string
    {
        $appCacheDir = $_SERVER['APP_SHARE_DIR'] ?? null;
        Assert::nullOrstring($appCacheDir);

        return $this->buildCachePath($appCacheDir);
    }

    #[Override]
    public function getLogDir(): string
    {
        $appLogDir = $_SERVER['APP_LOG_DIR'] ?? null;
        Assert::nullOrString($appLogDir);

        return sprintf(
            '%s/%s/%s',
            $appLogDir ?? sprintf('%s/var/log', $this->getProjectDir()),
            $this->getTenantId()->value,
            $this->getApplicationId()->value,
        );
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // Load common config files, then app specific config files and finally tenant specific config files.
        $this->doConfigureContainer($container, $this->getSharedConfigDir());
        $this->doConfigureContainer($container, $this->getAppConfigDir());
        $this->doConfigureContainer($container, $this->getTenantConfigDir());
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Load common route files, then app specific route files and finally tenant specific route files.
        $this->doConfigureRoutes($routes, $this->getSharedConfigDir());
        $this->doConfigureRoutes($routes, $this->getAppConfigDir());
        $this->doConfigureRoutes($routes, $this->getTenantConfigDir());
    }

    #[Override]
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->registerExtension(new DependencyInjection\AuthMatrixExtension());
    }

    /**
     * Gets the container class.
     *
     * @throws InvalidArgumentException If the generated classname is invalid
     */
    #[Override]
    protected function getContainerClass(): string
    {
        $class = static::class;
        $class = str_contains($class, "@anonymous\0")
            ? get_parent_class($class) . str_replace('.', '_', ContainerBuilder::hash($class))
            : $class;
        $class = str_replace('\\', '_', $class)
            . ucfirst($this->getTenantId()->value)
            . ucfirst($this->getApplicationId()->value)
            . ucfirst($this->environment)
            . ($this->debug ? 'Debug' : '')
            . 'Container';

        if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The environment "%s" contains invalid characters, it can only contain characters allowed in PHP class names.',
                    $this->environment,
                ),
            );
        }

        return $class;
    }

    /**
     * Returns the kernel parameters.
     *
     * @return array<string,array<mixed>|bool|string|int|float|UnitEnum|null>
     */
    #[Override]
    protected function getKernelParameters(): array
    {
        $parameters = $this->getKernelParametersTrait();

        $parameters['kernel.application_id'] = $this->getApplicationId()->value;
        $parameters['kernel.tenant_id'] = $this->getTenantId()->value;

        // This was introduced to avod issues with the config cache. Every application wrote a different reference to
        // the same file. By setting this private parameter to the build dir, the file is written to a different location
        // for each application, avoiding the issue with the config cache. See issue #6336.
        $parameters['.kernel.config_dir'] = $this->getBuildDir();

        return $parameters;
    }

    private function buildCachePath(?string $basePath): string
    {
        return sprintf(
            '%s/%s/%s/%s',
            $basePath ?? sprintf('%s/var/cache', $this->getProjectDir()),
            $this->getTenantId()->value,
            $this->getApplicationId()->value,
            $this->getEnvironment(),
        );
    }

    private function doConfigureContainer(ContainerConfigurator $container, string $configDir): void
    {
        if (! is_dir($configDir)) {
            return;
        }

        $configDirPattern = preg_replace('{/config$}', '/{config}', $configDir);

        $container->import($configDirPattern . '/{packages}/*.{php,yaml}');
        $container->import($configDirPattern . '/{packages}/' . $this->getEnvironment() . '/*.{php,yaml}');

        if (is_file($configDir . '/services.yaml')) {
            $container->import($configDirPattern . '/services.yaml');
            $container->import($configDirPattern . '/{services}_' . $this->getEnvironment() . '.yaml');
        } else {
            $container->import($configDirPattern . '/{services}.php');
            $container->import($configDirPattern . '/{services}_' . $this->getEnvironment() . '.php');
        }
    }

    private function doConfigureRoutes(RoutingConfigurator $routes, string $configDir): void
    {
        if (! is_dir($configDir)) {
            return;
        }

        $configDirPattern = preg_replace('{/config$}', '/{config}', $configDir);

        $routes->import($configDirPattern . '/{routes}/' . $this->getEnvironment() . '/*.{php,yaml}');
        $routes->import($configDirPattern . '/{routes}/*.{php,yaml}');

        if (is_file($configDir . '/routes.yaml')) {
            $routes->import($configDirPattern . '/routes.yaml');
        } else {
            $routes->import($configDirPattern . '/{routes}.php');
        }

        $fileName = new ReflectionObject($this)->getFileName();
        if ($fileName !== false) {
            $routes->import($fileName, 'attribute');
        }
    }

    private function injectEncryptionServiceIntoDbalTypes(): void
    {
        Assert::isInstanceOf($this->container, ContainerInterface::class);

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
