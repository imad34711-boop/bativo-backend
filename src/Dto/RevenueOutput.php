<?php

namespace App\Dto;

use App\Entity\Revenue;

/**
 * Representation HTTP d'un revenue (decompte), retenue de garantie ecretee incluse.
 */
final class RevenueOutput
{
    /**
     * @param array<string, mixed> $metadata
    * @param array{id: int, email: string, nom: string, prenom: string|null} $saisiPar
     */
    private function __construct(
        public readonly int $id,
        public readonly int $projetId,
        public readonly string $date,
        public readonly int $annee,
        public readonly int $numeroDecompte,
        public readonly float $montantHt,
        public readonly float $tauxTva,
        public readonly float $montantTva,
        public readonly float $montantTtc,
        public readonly float $tauxRetenueGarantie,
        public readonly float $retenueGarantieTheorique,
        public readonly float $retenueGarantie,
        public readonly bool $retenueGarantiePlafonnee,
        public readonly float $montantFacture,
        public readonly ?string $notes,
        public readonly array $saisiPar,
        public readonly array $metadata,
    ) {
    }

    public static function fromEntity(Revenue $revenue): self
    {
        $theorique = $revenue->getRetenueGarantieTheorique();
        $effective = $revenue->getRetenueGarantie();

        return new self(
            id: (int) $revenue->getId(),
            projetId: (int) $revenue->getProjet()->getId(),
            date: $revenue->getDate()->format('Y-m-d'),
            annee: $revenue->getAnnee(),
            numeroDecompte: $revenue->getNumeroDecompte(),
            montantHt: (float) $revenue->getMontantHt(),
            tauxTva: (float) $revenue->getTauxTva(),
            montantTva: $revenue->getMontantTva(),
            montantTtc: $revenue->getMontantTtc(),
            tauxRetenueGarantie: (float) $revenue->getTauxRetenueGarantie(),
            retenueGarantieTheorique: $theorique,
            retenueGarantie: $effective,
            retenueGarantiePlafonnee: $effective < $theorique,
            montantFacture: $revenue->getMontantFacture(),
            notes: $revenue->getNotes(),
            saisiPar: [
                'id' => (int) $revenue->getSaisiPar()->getId(),
                'email' => $revenue->getSaisiPar()->getEmail(),
                'nom' => $revenue->getSaisiPar()->getNom(),
                'prenom' => $revenue->getSaisiPar()->getPrenom(),
            ],
            metadata: $revenue->getMetadata(),
        );
    }
}
