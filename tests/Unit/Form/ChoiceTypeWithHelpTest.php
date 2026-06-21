<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\Form\ChoiceTypeWithHelp;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Webmozart\Assert\Assert;

use function array_merge;

final class ChoiceTypeWithHelpTest extends TestCase
{
    /**
     * @param array<string, mixed> $extraOptions
     */
    #[DataProvider('choiceTypeWithHelpDataProvider')]
    public function testFinishView(array $extraOptions, mixed $expectedHelpA, mixed $expectedHelpB): void
    {
        $defaultOptions = [
            'choices' => [
                'Label A' => 'value_a',
                'Label B' => 'value_b',
            ],
            'expanded' => true,
        ];
        $form = Forms::createFormFactory()->create(ChoiceTypeWithHelp::class, null, array_merge($defaultOptions, $extraOptions));

        $view = $form->createView();

        $helpByValue = $this->getFormViewHelpValues($view->children);

        self::assertSame($expectedHelpA, $helpByValue['value_a']);
        self::assertSame($expectedHelpB, $helpByValue['value_b']);
    }

    /**
     * @return array<string, array{
     * extraOptions: array<string, mixed>,
     * expectedHelpA: string|array<array-key, mixed>,
     * expectedHelpB: string|array<array-key, mixed>,
     * }>
     */
    public static function choiceTypeWithHelpDataProvider(): array
    {
        return [
            'all choices have help labels' => [
                'extraOptions' => ['choice_help_labels' => ['value_a' => 'Help for A', 'value_b' => 'Help for B']],
                'expectedHelpA' => 'Help for A',
                'expectedHelpB' => 'Help for B',
            ],
            'partial help labels fall back to empty array' => [
                'extraOptions' => ['choice_help_labels' => ['value_a' => 'Help for A']],
                'expectedHelpA' => 'Help for A',
                'expectedHelpB' => [],
            ],
            'no help labels fall back to empty array' => [
                'extraOptions' => [],
                'expectedHelpA' => [],
                'expectedHelpB' => [],
            ],
            'non-string help label falls back to empty array' => [
                'extraOptions' => ['choice_help_labels' => ['value_a' => 42, 'value_b' => ['nested']]],
                'expectedHelpA' => [],
                'expectedHelpB' => [],
            ],
        ];
    }

    /**
     * @param iterable<FormView> $children
     *
     * @return array<string, mixed>
     */
    private function getFormViewHelpValues(iterable $children): array
    {
        $result = [];

        foreach ($children as $child) {
            $value = $child->vars['value'];
            Assert::string($value);

            $result[$value] = $child->vars['help'];
        }

        return $result;
    }
}
