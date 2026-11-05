<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Citation;

use function array_keys;
use function array_map;
use function array_values;
use function key_exists;

readonly class GroundViewFactory
{
    private const string WOO_URL = 'https://wetten.overheid.nl/BWBR0045754/#Hoofdstuk5';
    private const string WOB_URL = 'https://wetten.overheid.nl/BWBR0005252/#HoofdstukV';

    public function make(string $citation): Ground
    {
        return new Ground(
            citation: $citation,
            classification: Citation::toClassification($citation),
            url: match (Citation::getCitationType($citation)) {
                Citation::TYPE_WOO => self::WOO_URL,
                Citation::TYPE_WOB => self::WOB_URL,
                default => null,
            },
        );
    }

    /**
     * @param list<string> $citations
     *
     * @return list<Ground>
     */
    public function makeCollection(array $citations): array
    {
        return array_map($this->make(...), $citations);
    }

    /**
     * @return list<array{citation:string,label:string}>
     */
    public function makeOptions(): array
    {
        $grounds = Citation::$wooCitations;

        if (key_exists(Citation::DUBBEL, $grounds)) {
            unset($grounds[Citation::DUBBEL]);
        }

        $grounds = array_map(
            static fn (string $label, string $citation): array => [
                'citation' => $citation,
                'label' => $label,
            ],
            array_values($grounds),
            array_keys($grounds),
        );

        return array_values($grounds);
    }
}
