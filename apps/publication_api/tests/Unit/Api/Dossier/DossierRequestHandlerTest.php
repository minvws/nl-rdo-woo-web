<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestDto;
use PublicationApi\Api\Dossier\DossierNumberValidator;
use PublicationApi\Api\Dossier\DossierRequestHandler;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\ExternalIdInUseException;
use PublicationApi\FeatureFlag\DossierUpdateGuard;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Uid\Uuid;

final class DossierRequestHandlerTest extends UnitTestCase
{
    public function testHandleCreatesDossier(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $dossierExternalId = $this->getFaker()->externalId();
        $organisationPrefix = OrganisationPrefix::create($this->getFaker()->documentPrefix());
        $dossierRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $dossierNumber,
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
        );
        $wooDecision = new WooDecision();

        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn($organisationPrefix);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('getSubject')->with($dossierRequestDto, $organisation)->andReturn($subject);
        $dossierSupportService->expects('getDepartment')->with($organisation, $dossierRequestDto->departmentId)->andReturn($department);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findByOrganisationAndExternalId')->with($organisation, $dossierExternalId)->andReturnNull();

        $dossierNumberValidator = Mockery::mock(DossierNumberValidator::class);
        $dossierNumberValidator->expects('validate')->with($dossierNumber, $organisationPrefix->toString());

        $dossierStrategy = Mockery::mock(DossierStrategy::class);
        $dossierStrategy->expects('dossierType')->andReturn(WooDecision::class);
        $dossierStrategy->expects('validateRequest')->with($dossierRequestDto);
        $dossierStrategy->expects('create')->with(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $organisationPrefix->toString(),
        )->andReturn($wooDecision);

        $dossierRequestHandler = new DossierRequestHandler(
            $dossierNumberValidator,
            $dossierRepository,
            $dossierSupportService,
            Mockery::mock(DossierUpdateGuard::class),
        );

        self::assertSame($wooDecision, $dossierRequestHandler->handle($dossierRequestDto, $organisation, $dossierExternalId, $dossierStrategy));
    }

    public function testHandleUpdatesDossier(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $dossierNumber,
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
        );

        $wooDecision = new WooDecision();
        $wooDecision->setDocumentPrefix($documentPrefix);

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('getSubject')->with($dossierRequestDto, $organisation)->andReturn($subject);
        $dossierSupportService->expects('getDepartment')->with($organisation, $dossierRequestDto->departmentId)->andReturn($department);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findByOrganisationAndExternalId')->with($organisation, $dossierExternalId)->andReturn($wooDecision);

        $dossierUpdateGuard = Mockery::mock(DossierUpdateGuard::class);
        $dossierUpdateGuard->expects('assertDossierIsEditable')->with($wooDecision);

        $dossierNumberValidator = Mockery::mock(DossierNumberValidator::class);
        $dossierNumberValidator->expects('validate')->with($dossierNumber, $documentPrefix, $wooDecision->getId());

        $dossierStrategy = Mockery::mock(DossierStrategy::class);
        $dossierStrategy->expects('dossierType')->andReturn(WooDecision::class);
        $dossierStrategy->expects('validateRequest')->with($dossierRequestDto);
        $dossierStrategy->expects('update')->with($wooDecision, $dossierRequestDto, $organisation, $department, $subject);

        $dossierRequestHandler = new DossierRequestHandler($dossierNumberValidator, $dossierRepository, $dossierSupportService, $dossierUpdateGuard);

        self::assertSame($wooDecision, $dossierRequestHandler->handle($dossierRequestDto, $organisation, $dossierExternalId, $dossierStrategy));
    }

    public function testHandleThrowsWhenTheExternalIdExistsForOtherDossierType(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
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
        $covenant = new Covenant();

        $organisation = Mockery::mock(Organisation::class);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('getSubject')->andReturnNull();
        $dossierSupportService->expects('getDepartment')->andReturn(Mockery::mock(Department::class));

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findByOrganisationAndExternalId')->with($organisation, $dossierExternalId)->andReturn($covenant);

        $dossierStrategy = Mockery::mock(DossierStrategy::class);
        $dossierStrategy->expects('dossierType')->andReturn(WooDecision::class);

        $dossierRequestHandler = new DossierRequestHandler(
            Mockery::mock(DossierNumberValidator::class),
            $dossierRepository,
            $dossierSupportService,
            Mockery::mock(DossierUpdateGuard::class),
        );

        $this->expectException(ExternalIdInUseException::class);

        $dossierRequestHandler->handle($dossierRequestDto, $organisation, $dossierExternalId, $dossierStrategy);
    }
}
