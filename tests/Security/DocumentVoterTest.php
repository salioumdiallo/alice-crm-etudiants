<?php

namespace App\Tests\Security;

use App\Entity\Document;
use App\Entity\User;
use App\Security\DocumentVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class DocumentVoterTest extends TestCase
{
    /** @dataProvider adminPermissions */
    public function testAdminIsGrantedEveryDocumentPermission(string $permission): void
    {
        $admin = (new User())->setRoles(['ROLE_ADMIN']);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, new Document(), $permission));
    }

    public static function adminPermissions(): iterable
    {
        yield [DocumentVoter::VIEW];
        yield [DocumentVoter::EDIT];
        yield [DocumentVoter::DELETE];
    }

    public function testAssignedUserCanViewDocument(): void
    {
        $user = new User();
        $document = (new Document())->addUser($user);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $document, DocumentVoter::VIEW));
    }

    /** @dataProvider deniedUserPermissions */
    public function testRegularUserCannotUseUnauthorizedPermission(string $permission): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(new User(), new Document(), $permission));
    }

    public static function deniedUserPermissions(): iterable
    {
        yield 'view unassigned document' => [DocumentVoter::VIEW];
        yield 'edit document' => [DocumentVoter::EDIT];
        yield 'delete document' => [DocumentVoter::DELETE];
    }

    private function vote(User $user, Document $document, string $permission): int
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return (new DocumentVoter())->vote($token, $document, [$permission]);
    }
}
