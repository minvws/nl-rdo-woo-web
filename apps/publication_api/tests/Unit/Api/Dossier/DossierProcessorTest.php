<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Metadata\Put;
use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantProcessor;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestDto;
use PublicationApi\Api\Dossier\DossierRequestHandler;
use PublicationApi\Api\Dossier\DossierResponseDtoInterface;
use PublicationApi\Api\Dossier\DossierResponseMapper;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Organisation\OrganisationResolver;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

final class DossierProcessorTest extends UnitTestCase
{
    public function testProcess(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $uriVariables = [
            'organisationId' => Uuid::v6()->toRfc4122(),
            'dossierExternalId' => $dossierExternalId->toString(),
        ];

        $dossierRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
        );
        $dossier = new Covenant();
        $organisation = Mockery::mock(Organisation::class);
        $dossierStrategy = Mockery::mock(DossierStrategy::class);

        $organisationResolver = Mockery::mock(OrganisationResolver::class);
        $organisationResolver->expects('resolve')->with($uriVariables)->andReturn($organisation);

        $dossierRequestHandler = Mockery::mock(DossierRequestHandler::class);
        $dossierRequestHandler->expects('handle')->with(
            $dossierRequestDto,
            $organisation,
            Mockery::on(static function (ExternalId $externalId) use ($dossierExternalId): bool {
                return $externalId->toString() === $dossierExternalId->toString();
            }),
            $dossierStrategy,
        )->andReturn($dossier);

        $dossierResponseDto = Mockery::mock(DossierResponseDtoInterface::class);
        $dossierResponseMapper = Mockery::mock(DossierResponseMapper::class);
        $dossierResponseMapper->expects('fromEntity')->with($dossier)->andReturn($dossierResponseDto);

        $dossierProcessor = new CovenantProcessor(
            $dossierRequestHandler,
            $organisationResolver,
            $dossierStrategy,
            $dossierResponseMapper,
        );

        self::assertSame($dossierResponseDto, $dossierProcessor->process($dossierRequestDto, new Put(), $uriVariables));
    }
}
