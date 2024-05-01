<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeImmutableToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YearMonthType extends ChoiceType
{
    public const MODE_FROM = 'first day of';
    public const MODE_TO = 'last day of';
    private const MIN_YEARS = 'min_years';
    private const PLUS_YEARS = 'plus_years';
    private const DAY_MODE = 'day_mode';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['choices'] = $this->getChoices(
            strval($options[self::DAY_MODE]),
            intval($options[self::MIN_YEARS]),
            intval($options[self::PLUS_YEARS]),
        );

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
    private function getChoices(string $day, int $minYears, int $plusYears): array
    {
        $options = [];

        $maxDate = new \DateTimeImmutable('last day of this month +' . $plusYears . ' year');
        $years = range(
            date('Y') + $plusYears,
            date('Y') - $minYears,
        );

        foreach ($years as $year) {
            $months = [];
            foreach (range(12, 1) as $monthNr) {
                $date = new \DateTimeImmutable("$day $year-$monthNr");
                if ($date > $maxDate) {
                    continue;
                }

                $description = strval(datefmt_format_object($date, 'MMMM y'));
                $months[$description] = $date->format(\DateTimeInterface::ATOM);
            }

            $options[strval($year)] = $months;
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined([self::DAY_MODE]);
        $resolver->setDefault(self::DAY_MODE, self::MODE_FROM);
        $resolver->setAllowedValues(self::DAY_MODE, [self::MODE_FROM, self::MODE_TO]);

        $resolver->setDefined([self::MIN_YEARS]);
        $resolver->setDefault(self::MIN_YEARS, 10);

        $resolver->setDefined([self::PLUS_YEARS]);
        $resolver->setDefault(self::PLUS_YEARS, 0);
    }
}
