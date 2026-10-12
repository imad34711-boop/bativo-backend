<?php

namespace App\Dto;

use App\Entity\Revenue;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Charge utile de creation et de mise a jour d'un revenue (decompte).
 */
final class RevenueInput
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        #[Assert\NotNull(message: 'La date est obligatoire.')]
        public ?\DateTimeImmutable $date = null,

        #[Assert\Positive(message: 'Le numero de decompte doit etre superieur a zero.')]
        public int $numeroDecompte = 1,

        #[Assert\PositiveOrZero(message: 'Le montant ne peut pas etre negatif.')]
        public float $montantHt = 0.0,

        #[Assert\Range(min: 0, max: 100)]
        public float $tauxTva = 20.0,

        #[Assert\Range(min: 0, max: 100)]
        public float $tauxRetenueGarantie = 10.0,

        public ?string $notes = null,

        public array $metadata = [],
    ) {
    }

    public function applyTo(Revenue $revenue): void
    {
        $revenue
            ->setDate($this->date ?? new \DateTimeImmutable('today'))
            ->setNumeroDecompte($this->numeroDecompte)
            ->setMontantHt(number_format($this->montantHt, 2, '.', ''))
            ->setTauxTva(number_format($this->tauxTva, 2, '.', ''))
            ->setTauxRetenueGarantie(number_format($this->tauxRetenueGarantie, 2, '.', ''))
            ->setNotes($this->notes)
            ->setMetadata($this->metadata);
    }
}
