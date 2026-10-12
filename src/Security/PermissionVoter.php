<?php

namespace App\Security;

use App\Entity\Projet;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Evalue une permission `ressource.action` sur un projet donne.
 *
 * Le sujet peut etre un Projet (portee contextuelle) ou null (portee globale).
 */
final class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!str_contains($attribute, '.')) {
            return false;
        }

        return null === $subject || $subject instanceof Projet;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$user->isActif()) {
            return false;
        }

        if ($subject instanceof Projet && !$user->isSuperAdmin() && !$user->appartientAuMemeOrganismeQueProjet($subject)) {
            return false;
        }

        if ($subject instanceof Projet && !$user->isSuperAdmin()) {
            if ($user->hasRoleCode('DIRECTEUR')) {
                return $user->hasPermission($attribute);
            }

            return $user->hasPermissionSurProjetViaAffectation($attribute, $subject);
        }

        return $user->hasPermission($attribute, $subject);
    }
}
