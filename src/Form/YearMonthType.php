<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Publication\Dossier\AbstractDossier;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
    public const DOSSIER = 'dossier';

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
    public function getChoices(string $mode, Carbon $minDate, int $plusYears, bool $reverse): array
    {
        $options = [];

        $period = CarbonPeriod::create(
            $minDate->addMonth()->startOfMonth(),
            '1 month',
            Carbon::now()->subMonth()->startOfMonth()->addYears($plusYears),
        );

        /** @var CarbonImmutable $date */
        foreach ($period->getIterator() as $date) {
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

        $resolver->setDefined(self::DOSSIER);
        $resolver->setDefault(self::DOSSIER, null);
        $resolver->setAllowedTypes(self::DOSSIER, ['null', AbstractDossier::class]);
    }

    /**
     * @param array<array-key,mixed> $options
     *
     * @return array{mode:string,minDate:Carbon,plusYears:int,reverse:bool}
     */
    private function getChoicesArgsFromOptions(array $options): array
    {
        /** @var int $minYears */
        $minYears = $options[self::MIN_YEARS];

        $choiceOptions = [
            'mode' => $options[self::DAY_MODE],
            'minDate' => Carbon::now()->subYears($minYears),
            'plusYears' => $options[self::PLUS_YEARS],
            'reverse' => $options[self::REVERSE],
        ];

        if (isset($options['dossier']) && $options['dossier'] instanceof AbstractDossier && $options['dossier']->hasCreatedAt()) {
            $createdAt = Carbon::createFromImmutable($options['dossier']->getCreatedAt());
            $choiceOptions['minDate'] = $createdAt->modify('- ' . $minYears . ' years');
        }

        Assert::string($choiceOptions['mode']);
        Assert::isInstanceOf($choiceOptions['minDate'], Carbon::class);
        Assert::integer($choiceOptions['plusYears']);
        Assert::boolean($choiceOptions['reverse']);

        return $choiceOptions;
    }
}
