<?php

namespace App\Tests\Api;

use Symfony\Component\HttpFoundation\Response;

/**
 * Test de bout en bout n1: cycle de vie complet d'un marche.
 *
 * Cree le projet, saisit les depenses et les decomptes jusqu'au solde,
 * puis controle les chiffres cles et l'ecretage de la retenue de garantie.
 */
class CycleDeVieProjetTest extends ApiTestCase
{
    private const URL_PROJETS = '/api/projets';
    private const DATE_DEPENSE = '2026-03-01';

    public function testCycleDeVieCompletDUnMarche(): void
    {
        $scenario = $this->creerScenarioMarche();
        $projet = $scenario['projet'];
        $projetId = $projet['id'];

        // 1. Le marche et son plafond de retenue de garantie.
        self::assertSame('AO-2026-014', $projet['numeroMarche']);
        self::assertSame('EN_COURS', $projet['statut']['code']);
        self::assertEqualsWithDelta(10000000.0, $projet['montantMarcheHt'], 0.01);
        self::assertEqualsWithDelta(2000000.0, $projet['montantMarcheTva'], 0.01);
        self::assertEqualsWithDelta(12000000.0, $projet['montantMarcheTtc'], 0.01);
        self::assertEqualsWithDelta(840000.0, $projet['plafondRetenueGarantie'], 0.01, 'Le plafond vaut 7% du montant TTC.');

        // 2. Les depenses, avec et sans categorie selon le type de frais.
        self::assertCount(5, $scenario['depenses']);
        self::assertSame('MATERIAUX', $scenario['depenses'][0]['categorie']['code']);
        self::assertNull($scenario['depenses'][3]['categorie'], 'Les frais de chantier n exigent pas de categorie.');
        self::assertEqualsWithDelta(3000000.0, $scenario['depenses'][0]['montantTtc'], 0.01);

        // 3. Les decomptes: retenue pleine, puis ecretee au plafond restant.
        [$decompte1, $decompte2, $decompte3] = $scenario['revenues'];

        self::assertEqualsWithDelta(360000.0, $decompte1['retenueGarantie'], 0.01);
        self::assertFalse($decompte1['retenueGarantiePlafonnee']);

        self::assertEqualsWithDelta(360000.0, $decompte2['retenueGarantie'], 0.01);
        self::assertFalse($decompte2['retenueGarantiePlafonnee']);

        self::assertEqualsWithDelta(480000.0, $decompte3['retenueGarantieTheorique'], 0.01);
        self::assertEqualsWithDelta(120000.0, $decompte3['retenueGarantie'], 0.01, 'Le solde de la retenue est limite au plafond restant.');
        self::assertTrue($decompte3['retenueGarantiePlafonnee']);
        self::assertEqualsWithDelta(4680000.0, $decompte3['montantFacture'], 0.01);

        // 4. Un decompte supplementaire ne genere plus aucune retenue.
        $decompte4 = $this->requete('POST', sprintf('/api/projets/%d/revenues', $projetId), [
            'numeroDecompte' => 4,
            'date' => '2027-01-31',
            'montantHt' => 100000.0,
            'tauxTva' => 20.0,
            'tauxRetenueGarantie' => 10.0,
        ], Response::HTTP_CREATED);

        self::assertEqualsWithDelta(0.0, $decompte4['retenueGarantie'], 0.01, 'Le plafond etant atteint, la retenue est nulle.');

        // On repart du marche solde pour la synthese.
        $this->requete('DELETE', sprintf('/api/revenues/%d', $decompte4['id']), null, Response::HTTP_NO_CONTENT);

        // 5. Les chiffres cles du marche.
        $synthese = $this->requete('GET', sprintf('/api/projets/%d/synthese', $projetId));

        self::assertEqualsWithDelta(6800000.0, $synthese['depenses']['montantHt'], 0.01);
        self::assertEqualsWithDelta(8160000.0, $synthese['depenses']['montantTtc'], 0.01);
        self::assertEqualsWithDelta(7200000.0, $synthese['depenses']['parType']['DIRECT'], 0.01);
        self::assertEqualsWithDelta(600000.0, $synthese['depenses']['parType']['FRAIS_CHANTIER'], 0.01);

        self::assertSame(3, $synthese['revenues']['nombre']);
        self::assertEqualsWithDelta(12000000.0, $synthese['revenues']['montantTtc'], 0.01);
        self::assertEqualsWithDelta(11160000.0, $synthese['revenues']['montantFacture'], 0.01);

        self::assertEqualsWithDelta(840000.0, $synthese['retenueGarantie']['cumul'], 0.01);
        self::assertEqualsWithDelta(0.0, $synthese['retenueGarantie']['restant'], 0.01);
        self::assertTrue($synthese['retenueGarantie']['plafondAtteint']);

        self::assertEqualsWithDelta(3840000.0, $synthese['resultat']['marge'], 0.01);
        self::assertEqualsWithDelta(32.0, $synthese['resultat']['tauxMarge'], 0.01);
        self::assertEqualsWithDelta(100.0, $synthese['resultat']['avancement'], 0.01);

        $this->afficherChiffresCles($synthese);
    }

    public function testLaClassificationDesDepensesEstControlee(): void
    {
        $projet = $this->requete('POST', self::URL_PROJETS, [
            'numeroMarche' => 'AO-2026-020',
            'objet' => 'Marche de controle de la classification',
            'montantMarcheHt' => 1000000.0,
        ], Response::HTTP_CREATED);

        $url = sprintf('/api/projets/%d/depenses', $projet['id']);

        $sansCategorie = $this->requete('POST', $url, [
            'date' => self::DATE_DEPENSE,
            'typeCode' => 'DIRECT',
            'montantHt' => 1000.0,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNotEmpty($sansCategorie['violations']);

        $categorieInterdite = $this->requete('POST', $url, [
            'date' => self::DATE_DEPENSE,
            'typeCode' => 'FRAIS_CHANTIER',
            'categorieCode' => 'MATERIAUX',
            'montantHt' => 1000.0,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNotEmpty($categorieInterdite['violations']);

        $this->requete('POST', $url, [
            'date' => self::DATE_DEPENSE,
            'typeCode' => 'INCONNU',
            'montantHt' => 1000.0,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUnNumeroDeMarcheEnDoubleEstRefuse(): void
    {
        $charge = [
            'numeroMarche' => 'AO-2026-030',
            'objet' => 'Premier enregistrement',
            'montantMarcheHt' => 500000.0,
        ];

        $this->requete('POST', self::URL_PROJETS, $charge, Response::HTTP_CREATED);
        $doublon = $this->requete('POST', self::URL_PROJETS, $charge, Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertSame('numeroMarche', $doublon['violations'][0]['champ']);
    }

    public function testAccesRefuseSansJetonValide(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');
        $this->client->jsonRequest('GET', self::URL_PROJETS);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testLesReferencesSontExposeesPourLeWeb(): void
    {
        $references = $this->requete('GET', '/api/references');

        self::assertCount(4, $references['typesFrais']);
        self::assertCount(4, $references['categories']);
        self::assertCount(7, $references['statutsProjet']);

        self::assertSame('DIRECT', $references['typesFrais'][0]['code']);
        self::assertTrue($references['typesFrais'][0]['requiertCategorie']);
        self::assertSame(
            ['MATERIELS', 'MATERIAUX', 'MAIN_OEUVRE', 'SOUS_TRAITANT'],
            $references['typesFrais'][0]['categoriesAutorisees']
        );

        self::assertSame('FRAIS_CHANTIER', $references['typesFrais'][2]['code']);
        self::assertFalse($references['typesFrais'][2]['requiertCategorie']);
        self::assertSame([], $references['typesFrais'][2]['categoriesAutorisees']);

        self::assertSame('EN_PREPARATION', $references['statutsProjet'][0]['code']);
        self::assertSame('ANNULE', $references['statutsProjet'][6]['code']);
    }

    /**
     * @param array<string, mixed> $synthese
     */
    private function afficherChiffresCles(array $synthese): void
    {
        $this->afficherTableau('Chiffres cles du marche '.$synthese['numeroMarche'], [
            'Montant marche HT' => self::formaterMontant($synthese['marche']['montantHt']),
            'Montant marche TTC' => self::formaterMontant($synthese['marche']['montantTtc']),
            'Depenses TTC' => self::formaterMontant($synthese['depenses']['montantTtc']),
            '  dont frais directs' => self::formaterMontant($synthese['depenses']['parType']['DIRECT']),
            '  dont frais chantier' => self::formaterMontant($synthese['depenses']['parType']['FRAIS_CHANTIER']),
            '  dont frais generaux' => self::formaterMontant($synthese['depenses']['parType']['FRAIS_GENERAL']),
            'Revenues TTC' => self::formaterMontant($synthese['revenues']['montantTtc']),
            'Retenue de garantie' => self::formaterMontant($synthese['retenueGarantie']['cumul']),
            '  plafond 7% TTC' => self::formaterMontant($synthese['retenueGarantie']['plafond']),
            '  solde disponible' => self::formaterMontant($synthese['retenueGarantie']['restant']),
            'Montant facture' => self::formaterMontant($synthese['revenues']['montantFacture']),
            'Marge' => self::formaterMontant($synthese['resultat']['marge']),
            'Taux de marge' => $synthese['resultat']['tauxMarge'].' %',
            'Avancement' => $synthese['resultat']['avancement'].' %',
        ]);
    }
}
