<?php

declare(strict_types=1);

namespace Shared\Form;

use DateTimeImmutable;
use Override;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_reverse;
use function datefmt_format_object;
use function is_string;
use function strval;

class YearMonthType extends ChoiceType
{
    public const string MODE_FROM = 'first day of';
    public const string MODE_TO = 'last day of';

    public const string MIN_YEARS = 'min_years';
    public const string PLUS_YEARS = 'plus_years';
    public const string DAY_MODE = 'day_mode';
    public const string REVERSE = 'reverse';
    public const string DOSSIER = 'dossier';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['choices'] = $this->getChoices(...$this->getChoicesArgsFromOptions($options));

        $builder->addModelTransformer(new CallbackTransformer(
            function (?PlainDate $date): string {
                return $date ? $date->toString() : '';
            },
            function (?string $value): ?PlainDate {
                return is_string($value) ? PlainDate::create($value) : null;
            },
        ));

        parent::buildForm($builder, $options);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getChoices(string $mode, PlainDate $minDate, int $plusYears, bool $reverse, ?PlainDate $now = null): array
    {
        $now ??= PlainDate::today();
        $options = [];

        $current = $minDate->addMonths(1)->firstOfMonth();
        $end = $now->subMonths(1)->firstOfMonth()->addYears($plusYears);

        while (! $current->isAfter($end)) {
            $year = (int) $current->format('Y');
            if (! array_key_exists($year, $options)) {
                $options[$year] = [];
            }

            $date = $mode === self::MODE_TO
                ? $current->lastOfMonth()
                : $current->firstOfMonth();

            $description = strval(datefmt_format_object(new DateTimeImmutable($date->toString()), 'MMMM y'));
            $options[$year][$description] = $date->format(PlainDate::DEFAULT_STRING_FORMAT);

            $current = $current->addMonths(1);
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
     * @return array{mode:string,minDate:PlainDate,plusYears:int,reverse:bool}
     */
    private function getChoicesArgsFromOptions(array $options): array
    {
        $minYears = $options[self::MIN_YEARS];
        Assert::integer($minYears);

        $choiceOptions = [
            'mode' => $options[self::DAY_MODE],
            'minDate' => PlainDate::today()->subYears($minYears),
            'plusYears' => $options[self::PLUS_YEARS],
            'reverse' => $options[self::REVERSE],
        ];

        if (array_key_exists('dossier', $options) && $options['dossier'] instanceof AbstractDossier) {
            $choiceOptions['minDate'] = PlainDate::create($options['dossier']->getCreatedAt()->format('Y-m-d'))->subYears($minYears);
        }

        Assert::string($choiceOptions['mode']);
        Assert::integer($choiceOptions['plusYears']);
        Assert::boolean($choiceOptions['reverse']);

        return $choiceOptions;
    }
}
