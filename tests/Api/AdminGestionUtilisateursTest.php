<?php

namespace App\Tests\Api;

use Symfony\Component\HttpFoundation\Response;

class AdminGestionUtilisateursTest extends ApiTestCase
{
    public function testLeSuperAdminPeutListerRolesPermissionsEtUsers(): void
    {
        $permissions = $this->requete('GET', '/api/admin/permissions');
        $roles = $this->requete('GET', '/api/admin/roles');
        $users = $this->requete('GET', '/api/admin/users');

        self::assertNotEmpty($permissions);
        self::assertContains('projet.create', array_map(static fn (array $permission): string => $permission['code'], $permissions));
        self::assertNotEmpty($roles);
        self::assertSame('ADMIN', $roles[0]['code']);
        self::assertCount(1, $users);
        self::assertSame(self::ADMIN_EMAIL, $users[0]['email']);
    }

    public function testLeSuperAdminPeutCreerEtMettreAJourUnUtilisateur(): void
    {
        $cree = $this->requete('POST', '/api/admin/users', [
            'email' => 'chef@example.local',
            'nom' => 'Chef',
            'prenom' => 'Projet',
            'password' => 'Pass!2026',
            'actif' => true,
            'roleCodes' => ['CHEF_PROJET'],
        ], Response::HTTP_CREATED);

        self::assertSame('chef@example.local', $cree['email']);
        self::assertSame(['CHEF_PROJET'], $cree['roles']);

        $misAJour = $this->requete('PUT', '/api/admin/users/'.$cree['id'], [
            'nom' => 'Chef Modifie',
            'actif' => false,
            'roleCodes' => ['LECTEUR', 'COMPTABLE'],
        ]);

        self::assertSame('Chef Modifie', $misAJour['nom']);
        self::assertFalse($misAJour['actif']);
        self::assertSame(['COMPTABLE', 'LECTEUR'], $misAJour['roles']);
    }

    public function testLeSuperAdminPeutMettreAJourLesPermissionsDUnRole(): void
    {
        $roles = $this->requete('GET', '/api/admin/roles');
        $lecteur = current(array_values(array_filter($roles, static fn (array $role): bool => $role['code'] === 'LECTEUR')));

        self::assertIsArray($lecteur);

        $modifie = $this->requete('PUT', '/api/admin/roles/'.$lecteur['id'].'/permissions', [
            'permissionCodes' => ['projet.read', 'depense.read', 'revenue.read', 'user.read'],
        ]);

        self::assertContains('user.read', $modifie['permissionCodes']);
        self::assertCount(4, $modifie['permissionCodes']);
    }

    public function testLeSuperAdminPeutCreerEtModifierUnRole(): void
    {
        $role = $this->requete('POST', '/api/admin/roles', [
            'code' => 'CONTROLEUR_QHSE',
            'libelle' => 'Controleur QHSE',
            'description' => 'Acces qualite et suivi lecture',
            'permissionCodes' => ['projet.read', 'depense.read', 'revenue.read'],
        ], Response::HTTP_CREATED);

        self::assertSame('CONTROLEUR_QHSE', $role['code']);
        self::assertContains('depense.read', $role['permissionCodes']);

        $misAJour = $this->requete('PUT', '/api/admin/roles/'.$role['id'], [
            'libelle' => 'Controleur QSE',
            'permissionCodes' => ['projet.read', 'depense.read'],
        ]);

        self::assertSame('Controleur QSE', $misAJour['libelle']);
        self::assertCount(2, $misAJour['permissionCodes']);
    }

    public function testLeSuperAdminPeutGererLesOrganismesEtAffecterUnUtilisateur(): void
    {
        $organisme = $this->requete('POST', '/api/admin/organismes', [
            'code' => 'ALPHA',
            'nom' => 'Alpha Construction',
        ], Response::HTTP_CREATED);

        $organismes = $this->requete('GET', '/api/admin/organismes');
        self::assertContains('ALPHA', array_map(static fn (array $item): string => $item['code'], $organismes));

        $user = $this->requete('POST', '/api/admin/users', [
            'email' => 'directeur@alpha.local',
            'nom' => 'Directeur',
            'prenom' => 'Alpha',
            'password' => 'Pass!2026',
            'actif' => true,
            'organismeId' => $organisme['id'],
            'roleCodes' => ['DIRECTEUR'],
        ], Response::HTTP_CREATED);

        self::assertSame('ALPHA', $user['organisme']['code']);
        self::assertContains('DIRECTEUR', $user['roles']);
    }
}
