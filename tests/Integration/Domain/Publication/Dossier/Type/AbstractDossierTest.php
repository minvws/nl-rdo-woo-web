<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\ExternalId;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AbstractDossierTest extends SharedWebTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testSetAndGetExternalId(): void
    {
        $externalId = ExternalId::create('mocked-external-id');

        $disposition = DispositionFactory::new()->create();
        $disposition->setExternalId($externalId);

        self::assertSame($externalId, $disposition->getExternalId());
    }

    public function testSettingIdenticalExternalIdWithinSameOrganisationFails(): void
    {
        $existingExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $existingExternalId,
        ]);
        $dispositionTwo = DispositionFactory::new()->withoutPersisting()->create([
            'organisation' => $organisation,
            'externalId' => $existingExternalId,
        ]);

        $errors = $this->validator->validate($dispositionTwo);
        self::assertCount(1, $errors);

        $error = $errors->get(0);
        self::assertSame(UniqueEntity::NOT_UNIQUE_ERROR, $error->getCode());
    }

    public function testSettingIdenticalExternalIdUsingDifferentOrganisations(): void
    {
        $existingExternalId = $this->getFaker()->externalId();

        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();
        DispositionFactory::createOne([
            'organisation' => $organisationOne,
            'externalId' => $existingExternalId,
        ]);
        $dispositionTwo = DispositionFactory::new()->withoutPersisting()->create([
            'organisation' => $organisationTwo,
            'externalId' => $existingExternalId,
        ]);

        $errors = $this->validator->validate($dispositionTwo);
        self::assertCount(0, $errors);
    }

    public function testSavingIdenticalExternalIdWithinSameOrganisationFails(): void
    {
        $existingExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $existingExternalId,
        ]);

        self::expectException(UniqueConstraintViolationException::class);
        DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $existingExternalId,
        ]);
    }
}
