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
    private const DAY_MODE = 'day_mode';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['choices'] = $this->getChoices(strval($options[self::DAY_MODE]));

        $builder->addModelTransformer(
            new DateTimeToStringTransformer(null, null, \DateTimeInterface::ATOM)
        );

        $builder->addModelTransformer(
            new DateTimeImmutableToDateTimeTransformer()
        );

        parent::buildForm($builder, $options);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getChoices(string $day): array
    {
        $options = [];

        $maxDate = new \DateTimeImmutable('last day of this month');
        $years = range(date('Y'), date('Y') - 9);
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
    }
}
