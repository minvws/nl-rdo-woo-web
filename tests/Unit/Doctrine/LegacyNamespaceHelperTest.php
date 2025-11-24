<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Shared\Doctrine\LegacyNamespaceHelper;
use Shared\Domain\Department\Department;
use Shared\Tests\Unit\UnitTestCase;

final class LegacyNamespaceHelperTest extends UnitTestCase
{
    public function testNormalizeClassNameConvertsAppNamespaceToShared(): void
    {
        /** @var class-string<object> $legacyClassName */
        $legacyClassName = 'App\Domain\Department\Department';
        $expectedClassName = 'Shared\Domain\Department\Department';

        $result = LegacyNamespaceHelper::normalizeClassName($legacyClassName);

        $this->assertSame($expectedClassName, $result);
    }

    public function testNormalizeClassNameReturnsOriginalWhenNotAppNamespace(): void
    {
        /** @var class-string<object> $className */
        $className = 'Shared\Domain\Department\Department';

        $result = LegacyNamespaceHelper::normalizeClassName($className);

        $this->assertSame($className, $result);
    }

    public function testNormalizeClassNameReturnsOriginalWhenSharedClassDoesNotExist(): void
    {
        /** @var class-string<object> $nonExistentClassName */
        $nonExistentClassName = 'App\NonExistent\FakeClass';

        $result = LegacyNamespaceHelper::normalizeClassName($nonExistentClassName);

        $this->assertSame($nonExistentClassName, $result);
    }

    public function testNormalizeClassNameHandlesFullyQualifiedClassNames(): void
    {
        /** @var class-string<object> $legacyClassName */
        $legacyClassName = '\App\Domain\Department\Department';

        $result = LegacyNamespaceHelper::normalizeClassName($legacyClassName);

        $this->assertSame($legacyClassName, $result);
    }

    public function testNormalizeClassNamePreservesGenericType(): void
    {
        /** @var class-string<object> $legacyClassName */
        $legacyClassName = 'App\Domain\Department\Department';

        $result = LegacyNamespaceHelper::normalizeClassName($legacyClassName);

        $this->assertTrue(class_exists($result));
        $this->assertSame(Department::class, $result);
    }

    public function testNormalizeClassNameWithNestedNamespace(): void
    {
        /** @var class-string<object> $legacyClassName */
        $legacyClassName = 'App\Domain\Publication\Dossier\Type\WooDecision\WooDecision';
        $expectedClassName = 'Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision';

        $result = LegacyNamespaceHelper::normalizeClassName($legacyClassName);

        $this->assertSame($expectedClassName, $result);
    }

    public function testNormalizeClassNameIsCaseSensitive(): void
    {
        /** @var class-string<object> $wrongCaseClassName */
        $wrongCaseClassName = 'app\Domain\Department\Department';

        $result = LegacyNamespaceHelper::normalizeClassName($wrongCaseClassName);

        $this->assertSame($wrongCaseClassName, $result);
    }

    public function testNormalizeClassNameWithEmptyString(): void
    {
        /**
         * @var class-string<object> $emptyString
         *
         * @phpstan-ignore varTag.nativeType
         */
        $emptyString = '';

        $result = LegacyNamespaceHelper::normalizeClassName($emptyString);

        $this->assertSame($emptyString, $result);
    }
}
