<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum PublicationStatus: string implements TranslatableInterface
{
    /**
     * Publication is new and might not even be persisted yet.
     */
    case NEW = 'new';

    /**
     * Publication might not be complete and has no (scheduled) publication yet.
     */
    case CONCEPT = 'concept';

    /**
     * The publication is no longer a concept, but preview and/or publication is planned at a future date.
     */
    case SCHEDULED = 'scheduled';

    /**
     * Publication is in preview mode and not yet available for anybody.
     */
    case PREVIEW = 'preview';

    /**
     * Publication is published and available for anybody.
     */
    case PUBLISHED = 'published';

    /**
     * Publication is retracted (but not deleted) and not available for anybody.
     */
    case RETRACTED = 'retracted';

    /**
     * @return self[]
     */
    public static function filterCases(): array
    {
        return [
            self::CONCEPT,
            self::SCHEDULED,
            self::PREVIEW,
            self::PUBLISHED,
            self::RETRACTED,
        ];
    }

    /**
     * @return self[]
     */
    public static function conceptCases(): array
    {
        return [
            self::NEW,
            self::CONCEPT,
        ];
    }

    /**
     * @return self[]
     */
    public static function nonConceptCases(): array
    {
        return [
            self::SCHEDULED,
            self::PREVIEW,
            self::PUBLISHED,
            self::RETRACTED,
        ];
    }

    public function isNew(): bool
    {
        return $this === self::NEW;
    }

    public function isConcept(): bool
    {
        return $this === self::CONCEPT;
    }

    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    public function isPreview(): bool
    {
        return $this === self::PREVIEW;
    }

    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    public function isRetracted(): bool
    {
        return $this === self::RETRACTED;
    }

    public function isNewOrConcept(): bool
    {
        return $this->isNew() || $this->isConcept();
    }

    public function isConceptOrScheduled(): bool
    {
        return $this->isConcept() || $this->isScheduled();
    }

    public function isPublishedOrRetracted(): bool
    {
        return $this->isPublished() || $this->isRetracted();
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->isPublished() || $this->isPreview();
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->value, locale: $locale);
    }
}
