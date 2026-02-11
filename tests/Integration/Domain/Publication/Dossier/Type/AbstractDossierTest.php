<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Regex;
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
        $disposition = DispositionFactory::new()->create();
        $disposition->setExternalId('mocked-external-id');

        self::assertSame('mocked-external-id', $disposition->getExternalId());
    }

    public function testSetInvalidExternalId(): void
    {
        $disposition = DispositionFactory::new()->create();
        $disposition->setExternalId('*(&#*&ˆ  #*&ˆˆ&%$');

        $errors = $this->validator->validateProperty($disposition, 'externalId');
        self::assertCount(1, $errors);

        $error = $errors->get(0);
        self::assertSame(Regex::REGEX_FAILED_ERROR, $error->getCode());
        self::assertSame('externalId', $error->getPropertyPath());
    }

    public function testSettingIdenticalExternalIdWithinSameOrganisationFails(): void
    {
        $organisation = OrganisationFactory::createOne();
        DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => 'existing-external-id',
        ]);
        $dispositionTwo = DispositionFactory::new()->withoutPersisting()->create([
            'organisation' => $organisation,
            'externalId' => 'existing-external-id',
        ]);

        $errors = $this->validator->validate($dispositionTwo);
        self::assertCount(1, $errors);

        $error = $errors->get(0);
        self::assertSame(UniqueEntity::NOT_UNIQUE_ERROR, $error->getCode());
    }

    public function testSettingIdenticalExternalIdUsingDifferentOrganisations(): void
    {
        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();
        DispositionFactory::createOne([
            'organisation' => $organisationOne,
            'externalId' => 'existing-external-id',
        ]);
        $dispositionTwo = DispositionFactory::new()->withoutPersisting()->create([
            'organisation' => $organisationTwo,
            'externalId' => 'existing-external-id',
        ]);

        $errors = $this->validator->validate($dispositionTwo);
        self::assertCount(0, $errors);
    }

    public function testSavingIdenticalExternalIdWithinSameOrganisationFails(): void
    {
        $organisation = OrganisationFactory::createOne();
        DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => 'existing-external-id',
        ]);

        self::expectException(UniqueConstraintViolationException::class);
        DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => 'existing-external-id',
        ]);
    }
}
