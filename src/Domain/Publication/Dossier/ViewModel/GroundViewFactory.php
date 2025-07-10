<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Citation;

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
