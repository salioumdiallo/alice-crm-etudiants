<?php

namespace App\Security;

use App\Entity\Document;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DocumentVoter extends Voter
{
    public const VIEW = 'DOCUMENT_VIEW';
    public const EDIT = 'DOCUMENT_EDIT';
    public const DELETE = 'DOCUMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Document
            && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (self::VIEW !== $attribute) {
            return false;
        }

        /** @var Document $document */
        $document = $subject;

        return $document->getUser()->contains($user);
    }
}
