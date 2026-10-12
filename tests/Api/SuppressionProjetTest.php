<?php

namespace App\Tests\Api;

use App\Entity\Projet;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test de bout en bout n2: suppression du marche du premier test et retour arriere.
 *
 * Verifie d'abord qu'une suppression annulee par un rollback laisse les donnees intactes,
 * puis qu'une suppression confirmee purge le projet et ses lignes rattachees.
 */
class SuppressionProjetTest extends ApiTestCase
{
    private const FORMAT_COMPTAGE = '%d / 0 / %d';

    public function testUneSuppressionAnnuleeParUnRollbackRestaureLeMarche(): void
    {
        $scenario = $this->creerScenarioMarche();
        $projetId = $scenario['projet']['id'];

        $avant = $this->compter($projetId);
        self::assertSame(['projets' => 1, 'depenses' => 5, 'revenues' => 3], $avant);

        $connexion = $this->entityManager->getConnection();
        $connexion->beginTransaction();

        try {
            $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
            self::assertNotNull($projet);

            $this->entityManager->remove($projet);
            $this->entityManager->flush();

            $pendant = $this->compter($projetId);
            self::assertSame(
                ['projets' => 0, 'depenses' => 0, 'revenues' => 0],
                $pendant,
                'Dans la transaction, la suppression cascade sur les depenses et les revenues.'
            );
        } finally {
            $connexion->rollBack();
            $this->entityManager->clear();
        }

        $apres = $this->compter($projetId);
        self::assertSame($avant, $apres, 'Apres rollback, toutes les lignes sont retablies.');

        // Le marche reste coherent et interrogeable par l'API.
        $synthese = $this->requete('GET', sprintf('/api/projets/%d/synthese', $projetId));
        self::assertEqualsWithDelta(840000.0, $synthese['retenueGarantie']['cumul'], 0.01);
        self::assertEqualsWithDelta(3840000.0, $synthese['resultat']['marge'], 0.01);

        $this->afficherTableau('Rollback de la suppression du marche '.$synthese['numeroMarche'], [
            'Projets avant / pendant / apres' => sprintf(self::FORMAT_COMPTAGE, $avant['projets'], $apres['projets']),
            'Depenses avant / pendant / apres' => sprintf(self::FORMAT_COMPTAGE, $avant['depenses'], $apres['depenses']),
            'Revenues avant / pendant / apres' => sprintf(self::FORMAT_COMPTAGE, $avant['revenues'], $apres['revenues']),
            'Retenue de garantie retablie' => self::formaterMontant($synthese['retenueGarantie']['cumul']),
            'Marge retablie' => self::formaterMontant($synthese['resultat']['marge']),
        ]);
    }

    public function testLaSuppressionConfirmeePurgeLeMarcheEtSesLignes(): void
    {
        $scenario = $this->creerScenarioMarche();
        $projetId = $scenario['projet']['id'];
        $depenseId = $scenario['depenses'][0]['id'];
        $revenueId = $scenario['revenues'][0]['id'];

        self::assertSame(['projets' => 1, 'depenses' => 5, 'revenues' => 3], $this->compter($projetId));

        $this->requete('DELETE', sprintf('/api/projets/%d', $projetId), null, Response::HTTP_NO_CONTENT);
        $this->entityManager->clear();

        self::assertSame(
            ['projets' => 0, 'depenses' => 0, 'revenues' => 0],
            $this->compter($projetId),
            'La suppression du projet retire ses depenses et ses revenues.'
        );

        $this->requete('GET', sprintf('/api/projets/%d', $projetId), null, Response::HTTP_NOT_FOUND);
        $this->requete('GET', sprintf('/api/depenses/%d', $depenseId), null, Response::HTTP_NOT_FOUND);
        $this->requete('GET', sprintf('/api/revenues/%d', $revenueId), null, Response::HTTP_NOT_FOUND);
    }

    public function testLaSuppressionDUnDecompteRecalculeLaRetenueDeGarantie(): void
    {
        $scenario = $this->creerScenarioMarche();
        $projetId = $scenario['projet']['id'];

        // Le decompte 2 portait une retenue pleine de 360 000.
        $this->requete('DELETE', sprintf('/api/revenues/%d', $scenario['revenues'][1]['id']), null, Response::HTTP_NO_CONTENT);
        $this->entityManager->clear();

        $synthese = $this->requete('GET', sprintf('/api/projets/%d/synthese', $projetId));

        // Il reste 3 000 000 et 4 000 000 HT: 360 000 + 480 000 = 840 000, soit exactement le plafond.
        self::assertSame(2, $synthese['revenues']['nombre']);
        self::assertEqualsWithDelta(840000.0, $synthese['retenueGarantie']['cumul'], 0.01);
        self::assertEqualsWithDelta(0.0, $synthese['retenueGarantie']['restant'], 0.01);
    }

    /**
     * @return array{projets: int, depenses: int, revenues: int}
     */
    private function compter(int $projetId): array
    {
        $connexion = $this->entityManager->getConnection();

        return [
            'projets' => (int) $connexion->fetchOne('SELECT COUNT(*) FROM projet WHERE id = ?', [$projetId]),
            'depenses' => (int) $connexion->fetchOne('SELECT COUNT(*) FROM depense WHERE projet_id = ?', [$projetId]),
            'revenues' => (int) $connexion->fetchOne('SELECT COUNT(*) FROM revenue WHERE projet_id = ?', [$projetId]),
        ];
    }
}
