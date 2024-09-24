<?php

declare(strict_types=1);

namespace App\Form;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeImmutableToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

class YearType extends ChoiceType
{
    public const MIN_YEARS = 'min_years';
    public const PLUS_YEARS = 'plus_years';
    public const REVERSE = 'reverse';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['choices'] = $this->getChoices(...$this->getChoicesArgsFromOptions($options));

        $builder->addModelTransformer(
            new DateTimeToStringTransformer(null, null, \DateTimeInterface::ATOM),
        );

        $builder->addModelTransformer(
            new DateTimeImmutableToDateTimeTransformer(),
        );

        parent::buildForm($builder, $options);
    }

    /**
     * @return array<int, string>
     */
    public function getChoices(int $minYears, int $plusYears, bool $reverse): array
    {
        $options = [];

        $period = CarbonPeriod::create(
            Carbon::now()->subYears($minYears),
            '1 year',
            Carbon::now()->addYears($plusYears),
        );

        /** @var CarbonImmutable $date */
        foreach ($period->getIterator() as $date) {
            Assert::notNull($date);

            $date = $date->firstOfYear();
            $options[$date->format('Y')] = $date->format(\DateTimeInterface::ATOM);
        }

        if ($reverse) {
            return array_reverse($options, preserve_keys: true);
        }

        return $options;
    }

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
