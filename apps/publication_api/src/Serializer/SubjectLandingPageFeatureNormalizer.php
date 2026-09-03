<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use LogicException;
use PublicationApi\Api\Subject\SubjectDetailResponse;
use PublicationApi\Api\Subject\SubjectResponse;
use PublicationApi\FeatureFlag\SubjectLandingPageGuard;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function is_array;

#[AutoconfigureTag('serializer.normalizer', ['priority' => 100])]
final class SubjectLandingPageFeatureNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const string NORMALIZED_CONTEXT_KEY = self::class . '.normalized';

    public function __construct(
        private readonly SubjectLandingPageGuard $subjectLandingPageGuard,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (! $data instanceof SubjectResponse && ! $data instanceof SubjectDetailResponse) {
            throw new LogicException('SubjectLandingPageFeatureNormalizer received an unsupported object.');
        }

        $normalized = $this->normalizer->normalize(
            $data,
            $format,
            [self::NORMALIZED_CONTEXT_KEY => true] + $context,
        );

        if (! is_array($normalized)) {
            throw new LogicException('Subject response normalization must return an array.');
        }

        if (! $this->subjectLandingPageGuard->isEnabled()) {
            unset($normalized['landingPage']);
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return ($data instanceof SubjectResponse || $data instanceof SubjectDetailResponse)
            && ! ($context[self::NORMALIZED_CONTEXT_KEY] ?? false);
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            SubjectResponse::class => false,
            SubjectDetailResponse::class => false,
        ];
    }
}
