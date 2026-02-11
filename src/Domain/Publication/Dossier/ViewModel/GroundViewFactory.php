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
    /**
     * @return list<array{citation:string,label:string}>
     */
    public function makeAsArray(): array
    {
        $grounds = Citation::$wooCitations;

        if (key_exists(Citation::DUBBEL, $grounds)) {
            unset($grounds[Citation::DUBBEL]);
        }

        $grounds = array_map(
            fn (string $label, string $citation): array => [
                'citation' => $citation,
                'label' => $label,
            ],
            array_values($grounds),
            array_keys($grounds),
        );

        return array_values($grounds);
    }
}
