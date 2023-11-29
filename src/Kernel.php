<?php

declare(strict_types=1);

namespace App;

use App\Doctrine\EncryptedArray;
use App\Doctrine\EncryptedString;
use App\Service\Encryption\EncryptionService;
use App\Service\Encryption\EncryptionServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        $this->injectEncryptionServiceIntoDbalTypes();
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->registerExtension(new \App\DependencyInjection\AuthMatrixExtension());
    }

    protected function injectEncryptionServiceIntoDbalTypes(): void
    {
        // Doctrine/DBAL types makes us very sad :( This seems the most feasible way to inject
        // stuff into DBAL types.

        /** @var EncryptionServiceInterface $encryptionService */
        $encryptionService = $this->container->get(EncryptionService::class);

        // We need to fetch the public_logger service, as this is a public service. See services.yaml
        /** @var LoggerInterface $logger */
        $logger = $this->container->get('public_logger');

        // Inject services into the DBAL types
        EncryptedString::injectServices($encryptionService, $logger);
        EncryptedArray::injectServices($encryptionService, $logger);
    }
}
