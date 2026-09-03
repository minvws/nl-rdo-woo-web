<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;
use Shared\Form\SubjectLandingPageType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class SubjectLandingPageTypeTest extends TestCase
{
    private const string PUBLIC_BASE_URL = 'https://open.example.org';

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new SubjectLandingPageType(self::PUBLIC_BASE_URL))
            ->getFormFactory();
    }

    public function testSlugHelpContainsThePublicBaseUrl(): void
    {
        $form = $this->createForm(new Subject());

        self::assertSame(
            ['publicBaseUrl' => self::PUBLIC_BASE_URL],
            $form->get('landing_page_slug')->getConfig()->getOption('help_translation_parameters'),
        );
    }

    public function testSubmittingAnEmptyDescriptionResultsInAFieldError(): void
    {
        $subject = new Subject();
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => 'some-slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => '',
        ]);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('landing_page_description')->getErrors());
        self::assertNull($subject->getLandingPageDescription());
    }

    public function testSubmittingAWhitespaceOnlyDescriptionResultsInAFieldError(): void
    {
        $subject = new Subject();
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => 'some-slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => "   \n\t ",
        ]);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('landing_page_description')->getErrors());
        self::assertNull($subject->getLandingPageDescription());
    }

    public function testExistingLandingPageIsPrefilled(): void
    {
        $subject = new Subject();
        $subject->setLandingPage(
            LandingPageSlug::create('some-slug'),
            LandingPageTitle::create('Some title'),
            'Some description',
            SubjectLandingPageStatus::PUBLISHED,
            [],
        );

        $form = $this->createForm($subject);

        self::assertSame('some-slug', $form->get('landing_page_slug')->getViewData());
        self::assertSame('Some title', $form->get('landing_page_title')->getViewData());
        self::assertSame('Some description', $form->get('landing_page_description')->getViewData());
        self::assertSame(SubjectLandingPageStatus::PUBLISHED, $form->get('landing_page_status')->getData());
    }

    public function testStatusDefaultsToConceptForSubjectWithoutLandingPage(): void
    {
        $form = $this->createForm(new Subject());

        self::assertSame(SubjectLandingPageStatus::CONCEPT, $form->get('landing_page_status')->getData());
        self::assertSame('', $form->get('landing_page_slug')->getViewData());
        self::assertSame('', $form->get('landing_page_title')->getViewData());
    }

    public function testSubmitWritesValueObjectsToTheSubject(): void
    {
        $subject = new Subject();
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::PUBLISHED->value,
            'landing_page_slug' => 'Some-Slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => 'Some description',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertEquals(LandingPageSlug::create('some-slug'), $subject->getLandingPageSlug());
        self::assertEquals(LandingPageTitle::create('Some title'), $subject->getLandingPageTitle());
        self::assertSame('Some description', $subject->getLandingPageDescription());
        self::assertSame(SubjectLandingPageStatus::PUBLISHED, $subject->getLandingPageStatus());
        self::assertNull($subject->getLandingPagePreviewToken());
    }

    public function testSubmittingConceptStatusGeneratesAPreviewToken(): void
    {
        $subject = new Subject();
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => 'some-slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => 'Some description',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertNotNull($subject->getLandingPagePreviewToken());
    }

    public function testVisibleContentTreeIsUncheckedByDefault(): void
    {
        $form = $this->createForm(new Subject());

        self::assertFalse($form->get('has_visible_landing_page_content_tree')->getData());
    }

    public function testSubmittingTheVisibleContentTreeCheckboxWritesItToTheSubject(): void
    {
        $subject = new Subject();
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => 'some-slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => 'Some description',
            'has_visible_landing_page_content_tree' => '1',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($subject->hasVisibleLandingPageContentTree());
    }

    public function testNotSubmittingTheVisibleContentTreeCheckboxLeavesItDisabled(): void
    {
        $subject = new Subject();
        $subject->setHasVisibleLandingPageContentTree(true);
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => 'some-slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => 'Some description',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($subject->hasVisibleLandingPageContentTree());
    }

    public function testSubmittingAnInvalidSlugResultsInAFieldError(): void
    {
        $subject = new Subject();
        $form = $this->createForm($subject);

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => 'not a valid slug',
            'landing_page_title' => 'Some title',
            'landing_page_description' => '',
        ]);

        self::assertFalse($form->isValid());
        self::assertFalse($form->get('landing_page_slug')->isSynchronized());
        self::assertNull($subject->getLandingPageSlug());
    }

    public function testSubmittingAnEmptySlugResultsInAFieldError(): void
    {
        $form = $this->createForm(new Subject());

        $form->submit([
            'landing_page_status' => SubjectLandingPageStatus::CONCEPT->value,
            'landing_page_slug' => '',
            'landing_page_title' => 'Some title',
            'landing_page_description' => '',
        ]);

        self::assertFalse($form->get('landing_page_slug')->isSynchronized());
    }

    private function createForm(Subject $subject): FormInterface
    {
        return $this->formFactory->create(SubjectLandingPageType::class, $subject);
    }
}
