<?php

namespace App\Tests\Api;

use Symfony\Component\HttpFoundation\Response;

class ScopeOrganismeEtProfilsTest extends ApiTestCase
{
    public function testVisibiliteParOrganismeEtParProfil(): void
    {
        $orgAlpha = $this->requete('POST', '/api/admin/organismes', [
            'code' => 'ALPHA',
            'nom' => 'Alpha Batiment',
        ], Response::HTTP_CREATED);

        $orgBeta = $this->requete('POST', '/api/admin/organismes', [
            'code' => 'BETA',
            'nom' => 'Beta Batiment',
        ], Response::HTTP_CREATED);

        $directeurAlpha = $this->requete('POST', '/api/admin/users', [
            'email' => 'directeur.alpha@local.test',
            'nom' => 'Directeur',
            'prenom' => 'Alpha',
            'password' => 'Pass!2026',
            'actif' => true,
            'organismeId' => $orgAlpha['id'],
            'roleCodes' => ['DIRECTEUR'],
        ], Response::HTTP_CREATED);

        $directeurBeta = $this->requete('POST', '/api/admin/users', [
            'email' => 'directeur.beta@local.test',
            'nom' => 'Directeur',
            'prenom' => 'Beta',
            'password' => 'Pass!2026',
            'actif' => true,
            'organismeId' => $orgBeta['id'],
            'roleCodes' => ['DIRECTEUR'],
        ], Response::HTTP_CREATED);

        $chefAlpha = $this->requete('POST', '/api/admin/users', [
            'email' => 'chef.alpha@local.test',
            'nom' => 'Chef',
            'prenom' => 'Alpha',
            'password' => 'Pass!2026',
            'actif' => true,
            'organismeId' => $orgAlpha['id'],
            'roleCodes' => [],
        ], Response::HTTP_CREATED);

        $userAlpha1 = $this->requete('POST', '/api/admin/users', [
            'email' => 'user.alpha1@local.test',
            'nom' => 'User',
            'prenom' => 'Alpha1',
            'password' => 'Pass!2026',
            'actif' => true,
            'organismeId' => $orgAlpha['id'],
            'roleCodes' => [],
        ], Response::HTTP_CREATED);

        $userAlpha2 = $this->requete('POST', '/api/admin/users', [
            'email' => 'user.alpha2@local.test',
            'nom' => 'User',
            'prenom' => 'Alpha2',
            'password' => 'Pass!2026',
            'actif' => true,
            'organismeId' => $orgAlpha['id'],
            'roleCodes' => [],
        ], Response::HTTP_CREATED);

        $this->authentifierComme('directeur.alpha@local.test', 'Pass!2026');
        $projetAlpha = $this->requete('POST', '/api/projets', [
            'numeroMarche' => 'ALPHA-001',
            'objet' => 'Projet Alpha',
            'montantMarcheHt' => 1000000,
            'tauxTva' => 20,
            'tauxRetenueGarantie' => 10,
            'tauxPlafondRetenueGarantie' => 7,
        ], Response::HTTP_CREATED);

        $this->authentifierComme('directeur.beta@local.test', 'Pass!2026');
        $projetBeta = $this->requete('POST', '/api/projets', [
            'numeroMarche' => 'BETA-001',
            'objet' => 'Projet Beta',
            'montantMarcheHt' => 900000,
            'tauxTva' => 20,
            'tauxRetenueGarantie' => 10,
            'tauxPlafondRetenueGarantie' => 7,
        ], Response::HTTP_CREATED);

        $this->authentifierComme(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);
        $this->requete('PUT', '/api/admin/users/'.$chefAlpha['id'].'/assignments', [
            'assignments' => [
                ['roleCode' => 'CHEF_PROJET', 'scope' => 'projet', 'projetId' => $projetAlpha['id']],
            ],
        ]);

        $this->requete('PUT', '/api/admin/users/'.$userAlpha1['id'].'/assignments', [
            'assignments' => [
                ['roleCode' => 'USER', 'scope' => 'projet', 'projetId' => $projetAlpha['id']],
            ],
        ]);

        $this->requete('PUT', '/api/admin/users/'.$userAlpha2['id'].'/assignments', [
            'assignments' => [
                ['roleCode' => 'USER', 'scope' => 'projet', 'projetId' => $projetAlpha['id']],
            ],
        ]);

        $this->authentifierComme('user.alpha1@local.test', 'Pass!2026');
        $depenseUser1 = $this->requete('POST', '/api/projets/'.$projetAlpha['id'].'/depenses', [
            'date' => '2026-06-01',
            'typeCode' => 'DIRECT',
            'categorieCode' => 'MATERIAUX',
            'montantHt' => 1000,
            'tauxTva' => 20,
        ], Response::HTTP_CREATED);

        $this->requete('POST', '/api/projets/'.$projetAlpha['id'].'/revenues', [
            'date' => '2026-06-15',
            'numeroDecompte' => 1,
            'montantHt' => 10000,
            'tauxTva' => 20,
            'tauxRetenueGarantie' => 10,
        ], Response::HTTP_CREATED);

        $this->authentifierComme('user.alpha2@local.test', 'Pass!2026');
        $depenseUser2 = $this->requete('POST', '/api/projets/'.$projetAlpha['id'].'/depenses', [
            'date' => '2026-06-02',
            'typeCode' => 'DIRECT',
            'categorieCode' => 'MAIN_OEUVRE',
            'montantHt' => 2000,
            'tauxTva' => 20,
        ], Response::HTTP_CREATED);

        $depensesUser2 = $this->requete('GET', '/api/projets/'.$projetAlpha['id'].'/depenses');
        self::assertCount(1, $depensesUser2);
        self::assertSame($depenseUser2['id'], $depensesUser2[0]['id']);

        $this->requete('GET', '/api/depenses/'.$depenseUser1['id'], null, Response::HTTP_FORBIDDEN);

        $this->authentifierComme('chef.alpha@local.test', 'Pass!2026');
        $depensesChef = $this->requete('GET', '/api/projets/'.$projetAlpha['id'].'/depenses');
        self::assertCount(2, $depensesChef);

        $this->authentifierComme('directeur.alpha@local.test', 'Pass!2026');
        $projetsDirecteurAlpha = $this->requete('GET', '/api/projets');
        $codesAlpha = array_map(static fn (array $item): string => $item['numeroMarche'], $projetsDirecteurAlpha['items']);

        self::assertContains('ALPHA-001', $codesAlpha);
        self::assertNotContains('BETA-001', $codesAlpha);
        self::assertSame('ALPHA', $projetAlpha['organisme']['code']);
        self::assertSame('BETA', $projetBeta['organisme']['code']);

        $depensesDirecteur = $this->requete('GET', '/api/projets/'.$projetAlpha['id'].'/depenses');
        self::assertCount(2, $depensesDirecteur);
    }
}
