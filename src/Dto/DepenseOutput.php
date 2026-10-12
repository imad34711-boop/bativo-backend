<?php

namespace App\Dto;

use App\Entity\Depense;

/**
 * Representation HTTP d'une depense.
 */
final class DepenseOutput
{
    /**
     * @param array{code: string, libelle: string}|null $categorie
    * @param array{id: int, email: string, nom: string, prenom: string|null} $saisiPar
     * @param array<string, mixed>                      $metadata
     */
    private function __construct(
        public readonly int $id,
        public readonly int $projetId,
        public readonly string $date,
        public readonly int $annee,
        public readonly array $type,
        public readonly ?array $categorie,
        public readonly ?string $sousCategorie,
        public readonly ?string $elementDepense,
        public readonly ?string $description,
        public readonly float $montantHt,
        public readonly float $tauxTva,
        public readonly float $montantTva,
        public readonly float $montantTtc,
        public readonly ?string $fournisseur,
        public readonly ?string $notes,
        public readonly array $saisiPar,
        public readonly array $metadata,
    ) {
    }

    public static function fromEntity(Depense $depense): self
    {
        $type = $depense->getType();
        $categorie = $depense->getCategorie();

        return new self(
            id: (int) $depense->getId(),
            projetId: (int) $depense->getProjet()->getId(),
            date: $depense->getDate()->format('Y-m-d'),
            annee: $depense->getAnnee(),
            type: ['code' => $type->getCode(), 'libelle' => $type->getLibelle()],
            categorie: null === $categorie ? null : ['code' => $categorie->getCode(), 'libelle' => $categorie->getLibelle()],
            sousCategorie: $depense->getSousCategorie(),
            elementDepense: $depense->getElementDepense(),
            description: $depense->getDescription(),
            montantHt: (float) $depense->getMontantHt(),
            tauxTva: (float) $depense->getTauxTva(),
            montantTva: $depense->getMontantTva(),
            montantTtc: $depense->getMontantTtc(),
            fournisseur: $depense->getFournisseur(),
            notes: $depense->getNotes(),
            saisiPar: [
                'id' => (int) $depense->getSaisiPar()->getId(),
                'email' => $depense->getSaisiPar()->getEmail(),
                'nom' => $depense->getSaisiPar()->getNom(),
                'prenom' => $depense->getSaisiPar()->getPrenom(),
            ],
            metadata: $depense->getMetadata(),
        );
    }
}
