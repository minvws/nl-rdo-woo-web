<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Form\Organisation;

use Admin\Form\Organisation\OrganisationPrefixType;
use PHPUnit\Framework\TestCase;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class OrganisationPrefixTypeTest extends TestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new OrganisationPrefixType())
            ->getFormFactory();
    }

    public function testParentIsTextType(): void
    {
        $type = new OrganisationPrefixType();

        self::assertSame(TextType::class, $type->getParent());
    }

    public function testSubmitWritesUppercaseOrganisationPrefixToModel(): void
    {
        $form = $this->formFactory->create(OrganisationPrefixType::class);

        $form->submit('abc-12');

        self::assertTrue($form->isSynchronized());
        $data = $form->getData();

        self::assertInstanceOf(OrganisationPrefix::class, $data);
        self::assertSame('ABC-12', $data->toString());
    }
}
