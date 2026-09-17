<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Form;

use Admin\Form\Organisation\OrganisationFormData;
use Admin\Form\Organisation\OrganisationFormType;
use Admin\Tests\Integration\AdminWebTestCase;
use Admin\Validator\Organisation\UniqueOrganisationValidator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;

use function array_key_exists;
use function iterator_to_array;
use function str_repeat;

final class OrganisationFormTypeTest extends AdminWebTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testCreateFormHasAnEditableRequiredPrefixField(): void
    {
        $form = $this->formFactory->create(OrganisationFormType::class, new OrganisationFormData());

        self::assertTrue($form->has('prefix'));
        self::assertSame(false, $form->get('prefix')->getConfig()->getOption('disabled'));
        self::assertSame(true, $form->get('prefix')->getConfig()->getOption('required'));
    }

    public function testModifyFormWithPrefixEditableFalseDoesNotExposeThePrefixField(): void
    {
        $organisation = $this->anOrganisationWithPrefix('OLD-01');
        $data = OrganisationFormData::fromEntity($organisation);

        $form = $this->formFactory->create(
            OrganisationFormType::class,
            $data,
            ['prefix_editable' => false],
        );

        self::assertFalse($form->has('prefix'));
    }

    public function testModifyFormWithPrefixEditableTrueAllowsChangingAnExistingPrefix(): void
    {
        $organisation = $this->anOrganisationWithPrefix('OLD-01');
        $data = OrganisationFormData::fromEntity($organisation);

        $form = $this->formFactory->create(
            OrganisationFormType::class,
            $data,
            ['prefix_editable' => true],
        );

        self::assertTrue($form->has('prefix'));

        $form->submit([
            'name' => 'Organisation name',
            'prefix' => 'new-02',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertNotNull($data->prefix);
        self::assertSame('NEW-02', $data->prefix->toString());
    }

    public function testSubmittingAValidPrefixNormalizesItToUppercase(): void
    {
        $data = new OrganisationFormData();
        $form = $this->formFactory->create(OrganisationFormType::class, $data);

        $form->submit([
            'name' => 'Organisation name',
            'prefix' => 'abc-12',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->get('prefix')->isValid());
        self::assertNotNull($data->prefix);
        self::assertSame('ABC-12', $data->prefix->toString());
    }

    public function testModifyFormPreservesTheOriginalPrefixWhenItIsNotEditable(): void
    {
        $organisation = $this->anOrganisationWithPrefix('OLD-01');
        $data = OrganisationFormData::fromEntity($organisation);

        $form = $this->formFactory->create(
            OrganisationFormType::class,
            $data,
            ['prefix_editable' => false],
        );

        $form->submit([
            'name' => 'Changed organisation name',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('Changed organisation name', $data->name);
        self::assertNotNull($data->prefix);
        self::assertSame('OLD-01', $data->prefix->toString());

        self::assertSame('Organisation name', $organisation->getName());
        self::assertSame('OLD-01', $organisation->getPrefix()->toString());
    }

    public function testSubmittingWithoutANameAddsARequiredValidationError(): void
    {
        $form = $this->formFactory->create(OrganisationFormType::class, new OrganisationFormData());

        $form->submit([
            'name' => '',
            'prefix' => 'ABC-12',
        ]);

        self::assertFalse($form->isValid());
        self::assertGreaterThanOrEqual(1, $form->get('name')->getErrors(true)->count());
        self::assertCount(0, $form->get('prefix')->getErrors(true));
    }

    #[DataProvider('invalidPrefixProvider')]
    public function testSubmittingAnInvalidPrefixAddsASingleTransformationError(string $prefix): void
    {
        $form = $this->formFactory->create(OrganisationFormType::class, new OrganisationFormData());

        $form->submit([
            'name' => 'Organisation name',
            'prefix' => $prefix,
        ]);

        self::assertFalse($form->isValid());
        self::assertFalse($form->get('prefix')->isSynchronized());

        $errors = $form->get('prefix')->getErrors(true);

        self::assertCount(1, $errors);
        self::assertContainsOnlyInstancesOf(FormError::class, iterator_to_array($errors, false));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPrefixProvider(): array
    {
        return [
            'empty' => [''],
            'only whitespace' => ['   '],
            'space in the middle' => ['ABC 12'],
            'disallowed character' => ['ABC_12'],
            'too long' => [str_repeat('A', 50)],
        ];
    }

    public function testSubmittingADuplicatePrefixAddsAUniqueValidationErrorOnThePrefixFieldOnly(): void
    {
        $duplicate = new Organisation();

        $organisationRepository = Mockery::mock(OrganisationRepository::class);
        $organisationRepository->expects('findOneBy')
            ->with(['name' => 'Organisation name'])
            ->andReturnNull();
        $organisationRepository->expects('findOneBy')
            ->with(Mockery::on(static function (array $criteria): bool {
                return array_key_exists('prefix', $criteria)
                    && $criteria['prefix'] instanceof OrganisationPrefix
                    && $criteria['prefix']->toString() === 'ABC-12';
            }))
            ->andReturn($duplicate);

        self::getContainer()->set(
            UniqueOrganisationValidator::class,
            new UniqueOrganisationValidator($organisationRepository),
        );

        $form = $this->formFactory->create(OrganisationFormType::class, new OrganisationFormData());

        $form->submit([
            'name' => 'Organisation name',
            'prefix' => 'abc-12',
        ]);

        self::assertFalse($form->isValid());
        self::assertTrue($form->get('prefix')->isSynchronized());

        $prefixErrors = iterator_to_array($form->get('prefix')->getErrors(true), false);
        self::assertCount(1, $prefixErrors);
        $prefixError = $prefixErrors[0];
        self::assertInstanceOf(FormError::class, $prefixError);
        self::assertSame('organisation.prefix_already_exists', $prefixError->getMessageTemplate());

        self::assertCount(0, $form->get('name')->getErrors(true));
    }

    public function testSubmittingTheSameOrganisationsOwnUnchangedPrefixDoesNotTriggerAUniqueError(): void
    {
        $organisation = $this->anOrganisationWithPrefix('OLD-01');

        $organisationRepository = Mockery::mock(OrganisationRepository::class);
        $organisationRepository->expects('findOneBy')
            ->with(['name' => 'Organisation name'])
            ->andReturn($organisation);
        $organisationRepository->expects('findOneBy')
            ->with(Mockery::on(static fn (array $criteria): bool => array_key_exists('prefix', $criteria)))
            ->andReturn($organisation);

        self::getContainer()->set(
            UniqueOrganisationValidator::class,
            new UniqueOrganisationValidator($organisationRepository),
        );

        $data = OrganisationFormData::fromEntity($organisation);
        $form = $this->formFactory->create(
            OrganisationFormType::class,
            $data,
            ['prefix_editable' => true],
        );

        $form->submit([
            'name' => 'Organisation name',
            'prefix' => 'OLD-01',
        ]);

        self::assertTrue($form->get('prefix')->isValid());
        self::assertCount(0, $form->get('prefix')->getErrors(true));
    }

    private function anOrganisationWithPrefix(string $prefix): Organisation
    {
        $organisation = new Organisation();
        $organisation->setName('Organisation name');
        $organisation->setPrefix(OrganisationPrefix::create($prefix));

        return $organisation;
    }
}
