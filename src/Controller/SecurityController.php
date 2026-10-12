<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SecurityController extends AbstractController
{
    /**
     * Point d'entree declare pour le firewall json_login.
     *
     * La requete est interceptee par le pare-feu et n'atteint jamais cette methode.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Cette route est prise en charge par le pare-feu de securite.');
    }

    /**
     * Profil de l'utilisateur authentifie et permissions globales associees.
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'actif' => $user->isActif(),
            'organisme' => [
                'id' => $user->getOrganisme()->getId(),
                'code' => $user->getOrganisme()->getCode(),
                'nom' => $user->getOrganisme()->getNom(),
            ],
            'roles' => $user->getRoles(),
            'permissions' => $user->getPermissionCodesToutesAffectations(),
            'preferences' => $user->getPreferences(),
        ]);
    }
}
