<?php

declare(strict_types=1);

namespace Shared\Domain\Publication;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strtolower;

enum Ground: string implements TranslatableInterface
{
    public const string TRANS_DOMAIN = 'ground';

    case WOO_511A = '5.1.1a';
    case WOO_511B = '5.1.1b';
    case WOO_511C = '5.1.1c';
    case WOO_511D = '5.1.1d';
    case WOO_511E = '5.1.1e';
    case WOO_512A = '5.1.2a';
    case WOO_512B = '5.1.2b';
    case WOO_512C = '5.1.2c';
    case WOO_512D = '5.1.2d';
    case WOO_512E = '5.1.2e';
    case WOO_512F = '5.1.2f';
    case WOO_512G = '5.1.2g';
    case WOO_512H = '5.1.2h';
    case WOO_512I = '5.1.2i';
    case WOO_515 = '5.1.5';
    case WOO_52 = '5.2';
    case WOO_54 = '5.4';
    case WOO_88 = '8.8';

    case WOB_101A = '10.1a';
    case WOB_101B = '10.1b';
    case WOB_101C = '10.1c';
    case WOB_101D = '10.1d';
    case WOB_102A = '10.2a';
    case WOB_102B = '10.2b';
    case WOB_102C = '10.2c';
    case WOB_102D = '10.2d';
    case WOB_102E = '10.2e';
    case WOB_102G = '10.2g';
    case WOB_111 = '11.1';

    case DUBBEL = 'dubbel';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            strtolower($this->name),
            domain: self::TRANS_DOMAIN,
            locale: $locale,
        );
    }
}
