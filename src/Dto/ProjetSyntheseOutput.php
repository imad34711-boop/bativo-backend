<?php

namespace App\Dto;

use App\Entity\Projet;

/**
 * Chiffres cles d'un projet: marche, depenses, revenues, retenue de garantie et resultat.
 */
final class ProjetSyntheseOutput
{
    /**
     * @param array{montantHt: float, montantTva: float, montantTtc: float}                                    $marche
     * @param array{nombre: int, montantHt: float, montantTva: float, montantTtc: float, parType: array<string, float>} $depenses
     * @param array{nombre: int, montantHt: float, montantTva: float, montantTtc: float, montantFacture: float} $revenues
     * @param array{taux: float, tauxPlafond: float, plafond: float|null, cumul: float, restant: float|null, plafondAtteint: bool} $retenueGarantie
     * @param array{marge: float, tauxMarge: float|null, avancement: float|null}                               $resultat
     */
    private function __construct(
        public readonly int $projetId,
        public readonly string $numeroMarche,
        public readonly string $objet,
        public readonly array $marche,
        public readonly array $depenses,
        public readonly array $revenues,
        public readonly array $retenueGarantie,
        public readonly array $resultat,
    ) {
    }

    public static function fromEntity(Projet $projet): self
    {
        $depensesHt = 0.0;
        $depensesTtc = 0.0;
        $parType = [];

        foreach ($projet->getDepenses() as $depense) {
            $depensesHt += (float) $depense->getMontantHt();
            $depensesTtc += $depense->getMontantTtc();

            $code = $depense->getType()->getCode();
            $parType[$code] = round(($parType[$code] ?? 0.0) + $depense->getMontantTtc(), 2);
        }

        $revenuesHt = 0.0;
        $revenuesTtc = 0.0;
        $montantFacture = 0.0;

        foreach ($projet->getRevenues() as $revenue) {
            $revenuesHt += (float) $revenue->getMontantHt();
            $revenuesTtc += $revenue->getMontantTtc();
            $montantFacture += $revenue->getMontantFacture();
        }

        $depensesHt = round($depensesHt, 2);
        $depensesTtc = round($depensesTtc, 2);
        $revenuesHt = round($revenuesHt, 2);
        $revenuesTtc = round($revenuesTtc, 2);
        $montantFacture = round($montantFacture, 2);

        $plafond = $projet->getPlafondRetenueGarantie();
        $cumulRg = $projet->getRetenueGarantieTotale();
        $marge = round($revenuesTtc - $depensesTtc, 2);
        $marcheHt = (float) $projet->getMontantMarcheHt();

        return new self(
            projetId: (int) $projet->getId(),
            numeroMarche: $projet->getNumeroMarche(),
            objet: $projet->getObjet(),
            marche: [
                'montantHt' => $marcheHt,
                'montantTva' => $projet->getMontantMarcheTva(),
                'montantTtc' => $projet->getMontantMarcheTtc(),
            ],
            depenses: [
                'nombre' => $projet->getDepenses()->count(),
                'montantHt' => $depensesHt,
                'montantTva' => round($depensesTtc - $depensesHt, 2),
                'montantTtc' => $depensesTtc,
                'parType' => $parType,
            ],
            revenues: [
                'nombre' => $projet->getRevenues()->count(),
                'montantHt' => $revenuesHt,
                'montantTva' => round($revenuesTtc - $revenuesHt, 2),
                'montantTtc' => $revenuesTtc,
                'montantFacture' => $montantFacture,
            ],
            retenueGarantie: [
                'taux' => (float) $projet->getTauxRetenueGarantie(),
                'tauxPlafond' => (float) $projet->getTauxPlafondRetenueGarantie(),
                'plafond' => $plafond,
                'cumul' => $cumulRg,
                'restant' => null === $plafond ? null : round(max(0.0, $plafond - $cumulRg), 2),
                'plafondAtteint' => null !== $plafond && $cumulRg >= $plafond,
            ],
            resultat: [
                'marge' => $marge,
                'tauxMarge' => $revenuesTtc > 0 ? round($marge / $revenuesTtc * 100, 2) : null,
                'avancement' => $marcheHt > 0 ? round($revenuesHt / $marcheHt * 100, 2) : null,
            ],
        );
    }
}
