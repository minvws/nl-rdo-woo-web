<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use LogicException;

use function rtrim;
use function sprintf;

final readonly class SubjectPreviewUrlGenerator
{
    private string $baseUrl;

    public function __construct(string $publicBaseUrl)
    {
        $this->baseUrl = rtrim($publicBaseUrl, '/');
    }

    public function generatePreviewUrl(Subject $subject): ?string
    {
        if ($subject->getLandingPageStatus() !== SubjectLandingPageStatus::CONCEPT) {
            return null;
        }

        $token = $subject->getLandingPagePreviewToken();
        if ($token === null) {
            throw new LogicException(
                sprintf(
                    'Subject "%s" has CONCEPT landing page status but no preview token. '
                    . 'This invariant should be guaranteed by Subject::setLandingPage.',
                    $subject->getId()->toRfc4122(),
                ),
            );
        }

        return sprintf(
            '%s/onderwerp/%s/preview/%s',
            $this->baseUrl,
            $subject->getId()->toRfc4122(),
            $token->toRfc4122(),
        );
    }
}
