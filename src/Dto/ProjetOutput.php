<?php

namespace App\Dto;

use App\Entity\Projet;

/**
 * Representation HTTP d'un projet, montants calcules inclus.
 */
final class ProjetOutput
{
    /**
     * @param array<string, mixed>      $metadata
    * @param array{id: int, code: string, nom: string} $organisme
     * @param array{code: string, libelle: string}|null $statut
     */
    private function __construct(
        public readonly int $id,
        public readonly ?int $numero,
        public readonly string $numeroMarche,
        public readonly string $objet,
        public readonly ?string $ville,
        public readonly float $montantMarcheHt,
        public readonly float $tauxTva,
        public readonly float $montantMarcheTva,
        public readonly float $montantMarcheTtc,
        public readonly ?float $cautionProvisoire,
        public readonly ?float $cautionDefinitive,
        public readonly float $tauxRetenueGarantie,
        public readonly float $tauxPlafondRetenueGarantie,
        public readonly ?float $plafondRetenueGarantie,
        public readonly ?string $maitreOuvrage,
        public readonly ?string $maitreOuvrageDelegue,
        public readonly ?string $architecte,
        public readonly ?string $bet,
        public readonly ?string $bureauControle,
        public readonly ?string $laboratoire,
        public readonly ?string $topographe,
        public readonly ?int $dureeMois,
        public readonly ?int $dureeJours,
        public readonly ?string $dateDebut,
        public readonly ?string $dateFin,
        public readonly ?int $joursRestants,
        public readonly array $organisme,
        public readonly ?array $statut,
        public readonly array $metadata,
    ) {
    }

    public static function fromEntity(Projet $projet): self
    {
        $statut = $projet->getStatut();

        return new self(
            id: (int) $projet->getId(),
            numero: $projet->getNumero(),
            numeroMarche: $projet->getNumeroMarche(),
            objet: $projet->getObjet(),
            ville: $projet->getVille(),
            montantMarcheHt: (float) $projet->getMontantMarcheHt(),
            tauxTva: (float) $projet->getTauxTva(),
            montantMarcheTva: $projet->getMontantMarcheTva(),
            montantMarcheTtc: $projet->getMontantMarcheTtc(),
            cautionProvisoire: null === $projet->getCautionProvisoire() ? null : (float) $projet->getCautionProvisoire(),
            cautionDefinitive: null === $projet->getCautionDefinitive() ? null : (float) $projet->getCautionDefinitive(),
            tauxRetenueGarantie: (float) $projet->getTauxRetenueGarantie(),
            tauxPlafondRetenueGarantie: (float) $projet->getTauxPlafondRetenueGarantie(),
            plafondRetenueGarantie: $projet->getPlafondRetenueGarantie(),
            maitreOuvrage: $projet->getMaitreOuvrage(),
            maitreOuvrageDelegue: $projet->getMaitreOuvrageDelegue(),
            architecte: $projet->getArchitecte(),
            bet: $projet->getBet(),
            bureauControle: $projet->getBureauControle(),
            laboratoire: $projet->getLaboratoire(),
            topographe: $projet->getTopographe(),
            dureeMois: $projet->getDureeMois(),
            dureeJours: $projet->getDureeJours(),
            dateDebut: $projet->getDateDebut()?->format('Y-m-d'),
            dateFin: $projet->getDateFin()?->format('Y-m-d'),
            joursRestants: $projet->getJoursRestants(),
            organisme: [
                'id' => (int) $projet->getOrganisme()->getId(),
                'code' => $projet->getOrganisme()->getCode(),
                'nom' => $projet->getOrganisme()->getNom(),
            ],
            statut: null === $statut ? null : ['code' => $statut->getCode(), 'libelle' => $statut->getLibelle()],
            metadata: $projet->getMetadata(),
        );
    }
}
