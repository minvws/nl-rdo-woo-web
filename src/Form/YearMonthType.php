<?php

declare(strict_types=1);

namespace App\Form;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeImmutableToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

class YearMonthType extends ChoiceType
{
    public const MODE_FROM = 'first day of';
    public const MODE_TO = 'last day of';

    public const MIN_YEARS = 'min_years';
    public const PLUS_YEARS = 'plus_years';
    public const DAY_MODE = 'day_mode';
    public const REVERSE = 'reverse';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['choices'] = $this->getChoices(...$this->getChoicesArgsFromOptions($options));

        $builder->addModelTransformer(
            new DateTimeToStringTransformer(null, null, \DateTimeInterface::ATOM)
        );

        $builder->addModelTransformer(
            new DateTimeImmutableToDateTimeTransformer()
        );

        parent::buildForm($builder, $options);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getChoices(string $mode, int $minYears, int $plusYears, bool $reverse): array
    {
        $options = [];

        $period = CarbonPeriod::create(
            Carbon::now()->addMonth()->startOfMonth()->subYears($minYears),
            '1 month',
            Carbon::now()->subMonth()->startOfMonth()->addYears($plusYears),
        );

        foreach ($period as $date) {
            Assert::notNull($date);

            $year = $date->format('Y');
            if (! array_key_exists($year, $options)) {
                $options[$year] = [];
            }

            $date = $mode === self::MODE_TO
                ? $date->lastOfMonth()
                : $date->firstOfMonth();

            $description = strval(datefmt_format_object($date, 'MMMM y'));
            $options[$year][$description] = $date->format(\DateTimeInterface::ATOM);
        }

        if ($reverse) {
            return array_reverse($options, preserve_keys: true);
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined(self::DAY_MODE);
        $resolver->setDefault(self::DAY_MODE, self::MODE_FROM);
        $resolver->setAllowedValues(self::DAY_MODE, [self::MODE_FROM, self::MODE_TO]);

        $resolver->setDefined(self::MIN_YEARS);
        $resolver->setDefault(self::MIN_YEARS, 10);
        $resolver->setAllowedTypes(self::MIN_YEARS, 'int');

        $resolver->setDefined(self::PLUS_YEARS);
        $resolver->setDefault(self::PLUS_YEARS, 0);
        $resolver->setAllowedTypes(self::PLUS_YEARS, 'int');

        $resolver->setDefined(self::REVERSE);
        $resolver->setDefault(self::REVERSE, false);
        $resolver->setAllowedTypes(self::REVERSE, 'bool');
    }

    /**
     * @param array<array-key,mixed> $options
     *
     * @return array{mode:string,minYears:int,plusYears:int,reverse:bool}
     */
    private function getChoicesArgsFromOptions(array $options): array
    {
        $choiceOptions = [
            'mode' => $options[self::DAY_MODE],
            'minYears' => $options[self::MIN_YEARS],
            'plusYears' => $options[self::PLUS_YEARS],
            'reverse' => $options[self::REVERSE],
        ];

        Assert::string($choiceOptions['mode']);
        Assert::integer($choiceOptions['minYears']);
        Assert::integer($choiceOptions['plusYears']);
        Assert::boolean($choiceOptions['reverse']);

        return $choiceOptions;
    }
}
