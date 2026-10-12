<?php

namespace App\Dto;

use App\Entity\Categorie;
use App\Entity\Depense;
use App\Entity\TypeFrais;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Charge utile de creation et de mise a jour d'une depense.
 */
final class DepenseInput
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        #[Assert\NotNull(message: 'La date est obligatoire.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\NotBlank(message: 'Le type de frais est obligatoire.')]
        public string $typeCode = '',

        public ?string $categorieCode = null,

        #[Assert\Length(max: 150)]
        public ?string $sousCategorie = null,

        #[Assert\Length(max: 150)]
        public ?string $elementDepense = null,

        public ?string $description = null,

        #[Assert\PositiveOrZero(message: 'Le montant ne peut pas etre negatif.')]
        public float $montantHt = 0.0,

        #[Assert\Range(min: 0, max: 100)]
        public float $tauxTva = 20.0,

        #[Assert\Length(max: 200)]
        public ?string $fournisseur = null,

        public ?string $notes = null,

        public array $metadata = [],
    ) {
    }

    public function applyTo(Depense $depense, TypeFrais $type, ?Categorie $categorie): void
    {
        $depense
            ->setDate($this->date ?? new \DateTimeImmutable('today'))
            ->setType($type)
            ->setCategorie($categorie)
            ->setSousCategorie($this->sousCategorie)
            ->setElementDepense($this->elementDepense)
            ->setDescription($this->description)
            ->setMontantHt(number_format($this->montantHt, 2, '.', ''))
            ->setTauxTva(number_format($this->tauxTva, 2, '.', ''))
            ->setFournisseur($this->fournisseur)
            ->setNotes($this->notes)
            ->setMetadata($this->metadata);
    }
}
