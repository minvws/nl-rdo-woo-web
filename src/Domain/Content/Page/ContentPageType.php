<?php

declare(strict_types=1);

namespace App\Domain\Content\Page;

enum ContentPageType: string
{
    case BALIE_CONTACT = 'balie-contact';
    case COOKIES = 'cookies';
    case PRIVACY = 'privacy';
    case KWETSBAARHEID_MELDEN = 'kwetsbaarheid-melden';
    case CONTACT = 'contact';
    case COPYRIGHT = 'copyright';
    case OVER_DIT_PLATFORM = 'over-dit-platform';
    case TOEGANKELIJKHEID = 'toegankelijkheid';
    case HOMEPAGE_INTRO = 'homepage-intro';
    case HOMEPAGE_OTHER_PUBLICATIONS = 'homepage-andere-publicaties';
    case HOMEPAGE_WOO_REQUEST = 'homepage-woo-verzoek';

    public function getSlug(): string
    {
        return $this->value;
    }

    public function getDefaultTitle(): string
    {
        return match ($this) {
            self::BALIE_CONTACT => 'Contact',
            self::COOKIES => 'Cookies',
            self::PRIVACY => 'Privacyverklaring Wet open overheid (Woo)',
            self::KWETSBAARHEID_MELDEN => 'Kwetsbaarheid melden',
            self::CONTACT => 'Contact',
            self::COPYRIGHT => 'Copyright',
            self::OVER_DIT_PLATFORM => 'Over dit platform',
            self::TOEGANKELIJKHEID => 'Toegankelijkheid',
            self::HOMEPAGE_INTRO => 'Openbaar gemaakte informatie',
            self::HOMEPAGE_OTHER_PUBLICATIONS => 'Andere publicaties',
            self::HOMEPAGE_WOO_REQUEST => 'Een Woo-verzoek indienen',
        };
    }
}
