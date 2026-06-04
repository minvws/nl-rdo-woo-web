<?php

declare(strict_types=1);

namespace Shared\Form;

use Override;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

use function array_reverse;
use function is_string;

class YearType extends ChoiceType
{
    public const string MIN_YEARS = 'min_years';
    public const string PLUS_YEARS = 'plus_years';
    public const string REVERSE = 'reverse';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['choices'] = $this->getChoices(...$this->getChoicesArgsFromOptions($options));

        $builder->addModelTransformer(new CallbackTransformer(
            static function (?PlainDate $date): string {
                return $date ? $date->toString() : '';
            },
            static function (?string $value): ?PlainDate {
                return is_string($value) && $value !== '' ? PlainDate::create($value) : null;
            },
        ));

        parent::buildForm($builder, $options);
    }

    /**
     * @return array<int, string>
     */
    public function getChoices(int $minYears, int $plusYears, bool $reverse, ?PlainDate $plainDate = null): array
    {
        if ($plainDate === null) {
            $plainDate = PlainDate::today();
        }

        $options = [];

        $current = $plainDate->subYears($minYears)->firstOfYear();
        $end = $plainDate->addYears($plusYears);

        while (! $current->isAfter($end)) {
            $year = (int) $current->format('Y');
            $options[$year] = $current->format(PlainDate::DEFAULT_STRING_FORMAT);
            $current = $current->addYears(1);
        }

        if ($reverse) {
            return array_reverse($options, preserve_keys: true);
        }

        return $options;
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined(self::MIN_YEARS);
        $resolver->setDefault(self::MIN_YEARS, 10);
        $resolver->setAllowedTypes(self::MIN_YEARS, 'int');

        $resolver->setDefined(self::PLUS_YEARS);
        $resolver->setDefault(self::PLUS_YEARS, 0);
        $resolver->setAllowedTypes(self::PLUS_YEARS, 'int');

        $resolver->setDefined(self::REVERSE);
        $resolver->setDefault(self::REVERSE, true);
        $resolver->setAllowedTypes(self::REVERSE, 'bool');
    }

    /**
     * @param array<array-key,mixed> $options
     *
     * @return array{minYears:int,plusYears:int,reverse:bool}
     */
    private function getChoicesArgsFromOptions(array $options): array
    {
        $choiceOptions = [
            'minYears' => $options[self::MIN_YEARS],
            'plusYears' => $options[self::PLUS_YEARS],
            'reverse' => $options[self::REVERSE],
        ];

        Assert::integer($choiceOptions['minYears']);
        Assert::integer($choiceOptions['plusYears']);
        Assert::boolean($choiceOptions['reverse']);

        return $choiceOptions;
    }
}
