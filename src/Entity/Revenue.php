<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'revenue')]
#[ORM\UniqueConstraint(name: 'uniq_revenue_projet_decompte', columns: ['projet_id', 'numero_decompte'])]
#[UniqueEntity(fields: ['projet', 'numeroDecompte'], message: 'Ce numero de decompte existe deja pour ce projet.', errorPath: 'numeroDecompte')]
class Revenue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'revenues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Projet $projet;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $date;

    #[ORM\Column]
    #[Assert\Positive]
    private int $numeroDecompte = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $montantHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $tauxTva = '20.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $tauxRetenueGarantie = '10.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * Historique des changements.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSONB, options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $saisiPar;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjet(): Projet
    {
        return $this->projet;
    }

    public function setProjet(Projet $projet): self
    {
        $this->projet = $projet;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getNumeroDecompte(): int
    {
        return $this->numeroDecompte;
    }

    public function setNumeroDecompte(int $numeroDecompte): self
    {
        $this->numeroDecompte = $numeroDecompte;

        return $this;
    }

    public function getMontantHt(): string
    {
        return $this->montantHt;
    }

    public function setMontantHt(string $montantHt): self
    {
        $this->montantHt = $montantHt;

        return $this;
    }

    public function getTauxTva(): string
    {
        return $this->tauxTva;
    }

    public function setTauxTva(string $tauxTva): self
    {
        $this->tauxTva = $tauxTva;

        return $this;
    }

    public function getTauxRetenueGarantie(): string
    {
        return $this->tauxRetenueGarantie;
    }

    public function setTauxRetenueGarantie(string $tauxRetenueGarantie): self
    {
        $this->tauxRetenueGarantie = $tauxRetenueGarantie;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed> $metadata */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getAnnee(): int
    {
        return (int) $this->date->format('Y');
    }

    public function getSaisiPar(): User
    {
        return $this->saisiPar;
    }

    public function setSaisiPar(User $saisiPar): self
    {
        $this->saisiPar = $saisiPar;

        return $this;
    }

    public function getMontantTva(): float
    {
        return round((float) $this->montantHt * (float) $this->tauxTva / 100, 2);
    }

    public function getMontantTtc(): float
    {
        return round((float) $this->montantHt + $this->getMontantTva(), 2);
    }

    /**
     * Retenue de garantie avant application du plafond projet.
     */
    public function getRetenueGarantieTheorique(): float
    {
        return round($this->getMontantTtc() * (float) $this->tauxRetenueGarantie / 100, 2);
    }

    /**
     * Retenue de garantie effective, ecretee au plafond restant du projet.
     */
    public function getRetenueGarantie(): float
    {
        $theorique = $this->getRetenueGarantieTheorique();

        if (!isset($this->projet)) {
            return $theorique;
        }

        $plafond = $this->projet->getPlafondRetenueGarantie();

        if (null === $plafond) {
            return $theorique;
        }

        $restant = max(0.0, $plafond - $this->projet->getRetenueGarantieCumuleeAvant($this));

        return round(min($theorique, $restant), 2);
    }

    public function getMontantFacture(): float
    {
        return round($this->getMontantTtc() - $this->getRetenueGarantie(), 2);
    }
}
