<?php

declare(strict_types=1);

namespace Admin\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function get_debug_type;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

final class RoleTransformer implements DataTransformerInterface
{
    /**
     * @return array<array-key, string>
     */
    public function transform(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        Assert::isArray($value);
        Assert::allString($value);

        return array_map(static function (string $role): string {
            return strtolower(str_replace('ROLE_', '', $role));
        }, $value);
    }

    /**
     * @return array<array-key, string>
     */
    public function reverseTransform(mixed $value): array
    {
        Assert::isArray($value);

        return array_map(static function (mixed $role): string {
            Assert::string($role, sprintf('Expected role to be a string, got "%s"', get_debug_type($role)));

            return sprintf('ROLE_%s', strtoupper($role));
        }, $value);
    }
}
