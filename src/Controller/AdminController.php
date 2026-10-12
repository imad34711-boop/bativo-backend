<?php

namespace App\Controller;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\Organisme;
use App\Entity\User;
use App\Entity\UserRoleAssignment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    #[Route('/permissions', name: 'api_admin_permissions_index', methods: ['GET'])]
    public function listerPermissions(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('permission.read');

        $permissions = $entityManager->getRepository(Permission::class)->findBy([], ['ressource' => 'ASC', 'action' => 'ASC']);

        return $this->json(array_map(static fn (Permission $permission): array => [
            'id' => $permission->getId(),
            'code' => $permission->getCode(),
            'ressource' => $permission->getRessource(),
            'action' => $permission->getAction(),
            'libelle' => $permission->getLibelle(),
        ], $permissions));
    }

    #[Route('/organismes', name: 'api_admin_organismes_index', methods: ['GET'])]
    public function listerOrganismes(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('user.read');

        $organismes = $entityManager->getRepository(Organisme::class)->findBy([], ['nom' => 'ASC']);

        return $this->json(array_map(fn (Organisme $organisme): array => $this->serialiserOrganisme($organisme), $organismes));
    }

    #[Route('/organismes', name: 'api_admin_organismes_create', methods: ['POST'])]
    public function creerOrganisme(Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('user.create');
        $this->assertSuperAdmin($acteur);

        $payload = $this->decoderJson($request);
        $code = strtoupper($this->extraireTexteObligatoire($payload, 'code'));
        $nom = $this->extraireTexteObligatoire($payload, 'nom');

        $organisme = new Organisme($code, $nom);
        $entityManager->persist($organisme);
        $entityManager->flush();

        return $this->json($this->serialiserOrganisme($organisme), Response::HTTP_CREATED);
    }

    #[Route('/organismes/{id}', name: 'api_admin_organismes_update', methods: ['PUT'])]
    public function mettreAJourOrganisme(int $id, Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('user.update');
        $this->assertSuperAdmin($acteur);

        $organisme = $entityManager->getRepository(Organisme::class)->find($id);
        if (!$organisme instanceof Organisme) {
            throw new NotFoundHttpException('Organisme introuvable.');
        }

        $payload = $this->decoderJson($request);
        if (array_key_exists('code', $payload)) {
            $organisme->setCode($this->extraireTexteObligatoire($payload, 'code'));
        }
        if (array_key_exists('nom', $payload)) {
            $organisme->setNom($this->extraireTexteObligatoire($payload, 'nom'));
        }

        $entityManager->flush();

        return $this->json($this->serialiserOrganisme($organisme));
    }

    #[Route('/roles', name: 'api_admin_roles_index', methods: ['GET'])]
    public function listerRoles(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('role.read');

        $roles = $entityManager->getRepository(Role::class)->findBy([], ['code' => 'ASC']);

        return $this->json(array_map(fn (Role $role): array => $this->serialiserRole($role), $roles));
    }

    #[Route('/roles', name: 'api_admin_roles_create', methods: ['POST'])]
    public function creerRole(Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('role.create');
        $this->assertSuperAdmin($acteur);

        $payload = $this->decoderJson($request);
        $code = strtoupper($this->extraireTexteObligatoire($payload, 'code'));
        $libelle = $this->extraireTexteObligatoire($payload, 'libelle');
        $description = $this->extraireTexteOptionnel($payload, 'description');
        $permissionCodes = $this->extraireTableauTexte($payload, 'permissionCodes');

        $rolesRepo = $entityManager->getRepository(Role::class);
        if ($rolesRepo->findOneBy(['code' => $code]) instanceof Role) {
            throw new UnprocessableEntityHttpException('Ce code role existe deja.');
        }

        $role = new Role($code, $libelle);
        $role->setDescription($description);
        foreach ($this->trouverPermissionsParCodes($permissionCodes, $entityManager) as $permission) {
            $role->addPermission($permission);
        }

        $entityManager->persist($role);
        $entityManager->flush();

        return $this->json($this->serialiserRole($role), Response::HTTP_CREATED);
    }

    #[Route('/roles/{id}', name: 'api_admin_roles_update', methods: ['PUT'])]
    public function mettreAJourRole(int $id, Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('role.update');
        $this->assertSuperAdmin($acteur);

        $role = $entityManager->getRepository(Role::class)->find($id);
        if (!$role instanceof Role) {
            throw new NotFoundHttpException('Role introuvable.');
        }

        $payload = $this->decoderJson($request);
        if (array_key_exists('code', $payload)) {
            $code = strtoupper($this->extraireTexteObligatoire($payload, 'code'));
            $existant = $entityManager->getRepository(Role::class)->findOneBy(['code' => $code]);
            if ($existant instanceof Role && $existant->getId() !== $role->getId()) {
                throw new UnprocessableEntityHttpException('Ce code role existe deja.');
            }
            $role->setCode($code);
        }
        if (array_key_exists('libelle', $payload)) {
            $role->setLibelle($this->extraireTexteObligatoire($payload, 'libelle'));
        }
        if (array_key_exists('description', $payload)) {
            $role->setDescription($this->extraireTexteOptionnel($payload, 'description'));
        }
        if (array_key_exists('permissionCodes', $payload)) {
            $permissionCodes = $this->extraireTableauTexte($payload, 'permissionCodes');
            $permissions = $this->trouverPermissionsParCodes($permissionCodes, $entityManager);
            foreach ($role->getPermissions()->toArray() as $permission) {
                $role->removePermission($permission);
            }
            foreach ($permissions as $permission) {
                $role->addPermission($permission);
            }
        }

        $entityManager->flush();

        return $this->json($this->serialiserRole($role));
    }

    #[Route('/roles/{id}', name: 'api_admin_roles_delete', methods: ['DELETE'])]
    public function supprimerRole(int $id, EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('role.delete');
        $this->assertSuperAdmin($acteur);

        $role = $entityManager->getRepository(Role::class)->find($id);
        if (!$role instanceof Role) {
            throw new NotFoundHttpException('Role introuvable.');
        }

        if ('ADMIN' === $role->getCode()) {
            throw new UnprocessableEntityHttpException('Le role ADMIN ne peut pas etre supprime.');
        }

        $entityManager->remove($role);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/roles/{id}/permissions', name: 'api_admin_roles_permissions_update', methods: ['PUT'])]
    public function mettreAJourPermissionsRole(int $id, Request $request, EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('role.update');
        $this->assertSuperAdmin($acteur);

        $role = $entityManager->getRepository(Role::class)->find($id);
        if (!$role instanceof Role) {
            throw new NotFoundHttpException('Role introuvable.');
        }

        $payload = $this->decoderJson($request);
        $permissionCodes = $this->extraireTableauTexte($payload, 'permissionCodes');
        $permissions = $this->trouverPermissionsParCodes($permissionCodes, $entityManager);

        foreach ($role->getPermissions()->toArray() as $permission) {
            $role->removePermission($permission);
        }

        foreach ($permissions as $permission) {
            $role->addPermission($permission);
        }

        $entityManager->flush();

        return $this->json($this->serialiserRole($role));
    }

    #[Route('/users', name: 'api_admin_users_index', methods: ['GET'])]
    public function listerUsers(EntityManagerInterface $entityManager, #[CurrentUser] User $acteur): JsonResponse
    {
        $this->denyAccessUnlessGranted('user.read');

        $criteria = [];
        if (!$acteur->isSuperAdmin()) {
            $criteria['organisme'] = $acteur->getOrganisme();
        }

        $users = $entityManager->getRepository(User::class)->findBy($criteria, ['email' => 'ASC']);

        return $this->json(array_map(fn (User $user): array => $this->serialiserUser($user), $users));
    }

    #[Route('/users', name: 'api_admin_users_create', methods: ['POST'])]
    public function creerUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        #[CurrentUser] User $acteur,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('user.create');

        $payload = $this->decoderJson($request);
        $email = $this->extraireTexteObligatoire($payload, 'email');
        $nom = $this->extraireTexteObligatoire($payload, 'nom');
        $motDePasse = $this->extraireTexteObligatoire($payload, 'password');
        $prenom = $this->extraireTexteOptionnel($payload, 'prenom');
        $actif = $this->extraireBooleen($payload, 'actif', true);
        $roleCodes = $this->extraireTableauTexte($payload, 'roleCodes');
        $organismeId = $this->extraireEntierOptionnel($payload, 'organismeId');

        $existant = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existant instanceof User) {
            throw new UnprocessableEntityHttpException('Cet e-mail existe deja.');
        }

        $roles = $this->trouverRolesParCodes($roleCodes, $entityManager);
        $organisme = $this->resoudreOrganismeCible($acteur, $organismeId, $entityManager);

        $user = new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setActif($actif);
        $user->setOrganisme($organisme);
        $user->setPassword($passwordHasher->hashPassword($user, $motDePasse));

        $entityManager->persist($user);

        foreach ($roles as $role) {
            $entityManager->persist(new UserRoleAssignment($user, $role));
        }

        $entityManager->flush();

        return $this->json($this->serialiserUser($user), 201);
    }

    #[Route('/users/{id}', name: 'api_admin_users_update', methods: ['PUT'])]
    public function mettreAJourUser(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        #[CurrentUser] User $acteur,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('user.update');

        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }
        if (!$acteur->isSuperAdmin() && $user->getOrganisme() !== $acteur->getOrganisme()) {
            throw new AccessDeniedHttpException('Acces refuse pour cet utilisateur.');
        }

        $payload = $this->decoderJson($request);

        if (array_key_exists('email', $payload)) {
            $email = $this->extraireTexteObligatoire($payload, 'email');
            $existant = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existant instanceof User && $existant->getId() !== $user->getId()) {
                throw new UnprocessableEntityHttpException('Cet e-mail existe deja.');
            }
            $user->setEmail($email);
        }

        if (array_key_exists('nom', $payload)) {
            $user->setNom($this->extraireTexteObligatoire($payload, 'nom'));
        }

        if (array_key_exists('prenom', $payload)) {
            $user->setPrenom($this->extraireTexteOptionnel($payload, 'prenom'));
        }

        if (array_key_exists('actif', $payload)) {
            $user->setActif($this->extraireBooleen($payload, 'actif', true));
        }

        if (array_key_exists('password', $payload)) {
            $motDePasse = $this->extraireTexteOptionnel($payload, 'password');
            if (null !== $motDePasse && '' !== $motDePasse) {
                $user->setPassword($passwordHasher->hashPassword($user, $motDePasse));
            }
        }

        if (array_key_exists('roleCodes', $payload)) {
            $roleCodes = $this->extraireTableauTexte($payload, 'roleCodes');
            $roles = $this->trouverRolesParCodes($roleCodes, $entityManager);
            $this->remplacerAffectationsGlobales($user, $roles, $entityManager);
        }

        if (array_key_exists('organismeId', $payload)) {
            $organismeId = $this->extraireEntierOptionnel($payload, 'organismeId');
            if (null === $organismeId) {
                throw new UnprocessableEntityHttpException('Le champ "organismeId" ne peut pas etre null.');
            }
            $user->setOrganisme($this->resoudreOrganismeCible($acteur, $organismeId, $entityManager));
        }

        $entityManager->flush();

        return $this->json($this->serialiserUser($user));
    }

    #[Route('/users/{id}/assignments', name: 'api_admin_users_assignments_replace', methods: ['PUT'])]
    public function remplacerAssignmentsUser(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $acteur,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('user.update');

        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }
        if (!$acteur->isSuperAdmin() && $user->getOrganisme() !== $acteur->getOrganisme()) {
            throw new AccessDeniedHttpException('Acces refuse pour cet utilisateur.');
        }

        $payload = $this->decoderJson($request);
        $assignments = $payload['assignments'] ?? null;
        if (!is_array($assignments)) {
            throw new UnprocessableEntityHttpException('Le champ "assignments" doit etre une liste.');
        }

        foreach ($user->getRoleAssignments()->toArray() as $assignment) {
            $user->removeRoleAssignment($assignment);
            $entityManager->remove($assignment);
        }

        foreach ($assignments as $index => $item) {
            if (!is_array($item)) {
                throw new UnprocessableEntityHttpException(sprintf('Assignment #%d invalide.', $index + 1));
            }

            $roleCode = $item['roleCode'] ?? null;
            $scope = $item['scope'] ?? null;
            $projetId = $item['projetId'] ?? null;

            if (!is_string($roleCode) || '' === trim($roleCode)) {
                throw new UnprocessableEntityHttpException(sprintf('Assignment #%d: roleCode obligatoire.', $index + 1));
            }
            if (!is_string($scope) || !in_array($scope, ['global', 'projet'], true)) {
                throw new UnprocessableEntityHttpException(sprintf('Assignment #%d: scope doit etre global ou projet.', $index + 1));
            }

            $role = $entityManager->getRepository(Role::class)->findOneBy(['code' => strtoupper(trim($roleCode))]);
            if (!$role instanceof Role) {
                throw new UnprocessableEntityHttpException(sprintf('Assignment #%d: role inconnu.', $index + 1));
            }

            $projet = null;
            if ('projet' === $scope) {
                if (!is_int($projetId) || $projetId <= 0) {
                    throw new UnprocessableEntityHttpException(sprintf('Assignment #%d: projetId obligatoire.', $index + 1));
                }

                $projet = $entityManager->getRepository(\App\Entity\Projet::class)->find($projetId);
                if (!$projet instanceof \App\Entity\Projet) {
                    throw new UnprocessableEntityHttpException(sprintf('Assignment #%d: projet introuvable.', $index + 1));
                }
                if ($projet->getOrganisme() !== $user->getOrganisme()) {
                    throw new UnprocessableEntityHttpException(sprintf('Assignment #%d: projet hors organisme utilisateur.', $index + 1));
                }
            }

            $entityManager->persist(new UserRoleAssignment($user, $role, $projet));
        }

        $entityManager->flush();

        return $this->json($this->serialiserUser($user));
    }

    private function decoderJson(Request $request): array
    {
        try {
            $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UnprocessableEntityHttpException('Corps JSON invalide.');
        }

        if (!is_array($payload)) {
            throw new UnprocessableEntityHttpException('Le corps de la requete doit etre un objet JSON.');
        }

        return $payload;
    }

    private function extraireTexteObligatoire(array $payload, string $champ): string
    {
        $valeur = $payload[$champ] ?? null;

        if (!is_string($valeur) || '' === trim($valeur)) {
            throw new UnprocessableEntityHttpException(sprintf('Le champ "%s" est obligatoire.', $champ));
        }

        return trim($valeur);
    }

    private function extraireTexteOptionnel(array $payload, string $champ): ?string
    {
        $valeur = $payload[$champ] ?? null;
        if (null === $valeur || '' === $valeur) {
            return null;
        }

        if (!is_string($valeur)) {
            throw new UnprocessableEntityHttpException(sprintf('Le champ "%s" doit etre un texte.', $champ));
        }

        return trim($valeur);
    }

    private function extraireBooleen(array $payload, string $champ, bool $defaut): bool
    {
        if (!array_key_exists($champ, $payload)) {
            return $defaut;
        }

        if (!is_bool($payload[$champ])) {
            throw new UnprocessableEntityHttpException(sprintf('Le champ "%s" doit etre booleen.', $champ));
        }

        return $payload[$champ];
    }

    private function extraireEntierOptionnel(array $payload, string $champ): ?int
    {
        if (!array_key_exists($champ, $payload) || null === $payload[$champ]) {
            return null;
        }

        if (!is_int($payload[$champ]) || $payload[$champ] <= 0) {
            throw new UnprocessableEntityHttpException(sprintf('Le champ "%s" doit etre un entier positif.', $champ));
        }

        return $payload[$champ];
    }

    /**
     * @return list<string>
     */
    private function extraireTableauTexte(array $payload, string $champ): array
    {
        $valeur = $payload[$champ] ?? [];

        if (!is_array($valeur)) {
            throw new UnprocessableEntityHttpException(sprintf('Le champ "%s" doit etre une liste.', $champ));
        }

        $elements = [];
        foreach ($valeur as $item) {
            if (!is_string($item) || '' === trim($item)) {
                throw new UnprocessableEntityHttpException(sprintf('Le champ "%s" contient une valeur invalide.', $champ));
            }
            $elements[] = trim($item);
        }

        return array_values(array_unique($elements));
    }

    /**
     * @param list<string> $codes
     *
     * @return list<Role>
     */
    private function trouverRolesParCodes(array $codes, EntityManagerInterface $entityManager): array
    {
        if ([] === $codes) {
            return [];
        }

        $roles = $entityManager->getRepository(Role::class)->findBy(['code' => $codes]);
        $trouves = array_map(static fn (Role $role): string => $role->getCode(), $roles);
        $manquants = array_values(array_diff($codes, $trouves));

        if ([] !== $manquants) {
            throw new UnprocessableEntityHttpException('Roles inconnus: '.implode(', ', $manquants));
        }

        usort($roles, static fn (Role $a, Role $b): int => strcmp($a->getCode(), $b->getCode()));

        return $roles;
    }

    /**
     * @param list<string> $codes
     *
     * @return list<Permission>
     */
    private function trouverPermissionsParCodes(array $codes, EntityManagerInterface $entityManager): array
    {
        if ([] === $codes) {
            return [];
        }

        $permissions = $entityManager->getRepository(Permission::class)->findBy(['code' => $codes]);
        $trouves = array_map(static fn (Permission $permission): string => $permission->getCode(), $permissions);
        $manquants = array_values(array_diff($codes, $trouves));

        if ([] !== $manquants) {
            throw new UnprocessableEntityHttpException('Permissions inconnues: '.implode(', ', $manquants));
        }

        usort($permissions, static fn (Permission $a, Permission $b): int => strcmp($a->getCode(), $b->getCode()));

        return $permissions;
    }

    /**
     * @param list<Role> $roles
     */
    private function remplacerAffectationsGlobales(User $user, array $roles, EntityManagerInterface $entityManager): void
    {
        foreach ($user->getRoleAssignments()->toArray() as $assignment) {
            if ($assignment->estGlobale()) {
                $user->removeRoleAssignment($assignment);
                $entityManager->remove($assignment);
            }
        }

        foreach ($roles as $role) {
            $entityManager->persist(new UserRoleAssignment($user, $role));
        }
    }

    private function assertSuperAdmin(User $user): void
    {
        if (!$user->isSuperAdmin()) {
            throw new AccessDeniedHttpException('Action reservee au superadministrateur.');
        }
    }

    private function resoudreOrganismeCible(User $acteur, ?int $organismeId, EntityManagerInterface $entityManager): Organisme
    {
        if (!$acteur->isSuperAdmin()) {
            if (null !== $organismeId && $organismeId !== $acteur->getOrganisme()->getId()) {
                throw new AccessDeniedHttpException('Vous ne pouvez gerer que votre organisme.');
            }

            return $acteur->getOrganisme();
        }

        $id = $organismeId ?? $acteur->getOrganisme()->getId();
        $organisme = $entityManager->getRepository(Organisme::class)->find($id);
        if (!$organisme instanceof Organisme) {
            throw new NotFoundHttpException('Organisme introuvable.');
        }

        return $organisme;
    }

    private function serialiserOrganisme(Organisme $organisme): array
    {
        return [
            'id' => $organisme->getId(),
            'code' => $organisme->getCode(),
            'nom' => $organisme->getNom(),
            'metadata' => $organisme->getMetadata(),
        ];
    }

    private function serialiserRole(Role $role): array
    {
        $permissionCodes = [];
        foreach ($role->getPermissions() as $permission) {
            $permissionCodes[] = $permission->getCode();
        }
        sort($permissionCodes);

        return [
            'id' => $role->getId(),
            'code' => $role->getCode(),
            'libelle' => $role->getLibelle(),
            'description' => $role->getDescription(),
            'permissionCodes' => $permissionCodes,
        ];
    }

    private function serialiserUser(User $user): array
    {
        $roles = [];
        $assignments = [];

        foreach ($user->getRoleAssignments() as $assignment) {
            $code = $assignment->getRole()->getCode();
            if ($assignment->estGlobale()) {
                $roles[] = $code;
            }

            $projet = $assignment->getProjet();
            $assignments[] = [
                'roleCode' => $code,
                'roleLibelle' => $assignment->getRole()->getLibelle(),
                'scope' => $assignment->estGlobale() ? 'global' : 'projet',
                'projetId' => $projet?->getId(),
                'projetNumeroMarche' => $projet?->getNumeroMarche(),
            ];
        }

        sort($roles);

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'actif' => $user->isActif(),
            'organisme' => $this->serialiserOrganisme($user->getOrganisme()),
            'roles' => array_values(array_unique($roles)),
            'permissions' => $user->getPermissionCodesToutesAffectations(),
            'assignments' => $assignments,
        ];
    }
}
