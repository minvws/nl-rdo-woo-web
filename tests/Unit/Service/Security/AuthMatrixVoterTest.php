<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;

use App\Roles;
use App\Service\Security\AuthMatrixVoter;
use App\Service\Security\Authorization\AuthorizationEntryRequestStore;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\Entry;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AuthMatrixVoterTest extends MockeryTestCase
{
    /**
     * @param array<array-key, Entry&MockInterface>|null $entries
     */
    #[DataProvider('voteProvider')]
    public function testVoter(
        string $input,
        int $expectedResult,
        ?string $prefix = null,
        ?string $permission = null,
        ?array $entries = null
    ): void {
        $entryStore = \Mockery::mock(AuthorizationEntryRequestStore::class);
        if ($entries) {
            $entryStore->expects('storeEntries')->with(...$entries);
        }

        $authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);
        if ($entries) {
            $authorizationMatrix->expects('isAuthorized')->with($prefix, $permission)->andReturnTrue();
            $authorizationMatrix->expects('getAuthorizedMatches')->with($prefix, $permission)->andReturn($entries);
        } elseif ($prefix !== null && $permission !== null) {
            $authorizationMatrix->expects('isAuthorized')->with($prefix, $permission)->andReturnFalse();
        }

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = \Mockery::mock(TokenInterface::class);

        $this->assertEquals($expectedResult, $voter->vote($token, null, [$input]));
    }

    /**
     * @return array<array-key, array<array-key, mixed>>
     */
    public static function voteProvider(): array
    {
        return [
            'abstain-for-regular-role' => [
                'input' => Roles::ROLE_SUPER_ADMIN,
                'expectedResult' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'grant-for-authmatrix-match' => [
                'input' => 'AuthMatrix.dossier.read',
                'expectedResult' => VoterInterface::ACCESS_GRANTED,
                'prefix' => 'dossier',
                'permission' => 'read',
                'entries' => [
                    \Mockery::mock(Entry::class),
                ],
            ],
            'denied-for-authmatrix-mismatch' => [
                'input' => 'AuthMatrix.dossier.delete',
                'expectedResult' => VoterInterface::ACCESS_DENIED,
                'prefix' => 'dossier',
                'permission' => 'delete',
            ],
            'denied-for-invalid-format' => [
                'input' => 'AuthMatrix.dossier',
                'expectedResult' => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }
}
