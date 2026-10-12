<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'depense')]
#[ORM\Index(name: 'idx_depense_projet_date', columns: ['projet_id', 'date'])]
class Depense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'depenses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Projet $projet;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $date;

    #[ORM\ManyToOne(targetEntity: TypeFrais::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private TypeFrais $type;

    #[ORM\ManyToOne(targetEntity: Categorie::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $sousCategorie = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $elementDepense = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $montantHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $tauxTva = '20.00';

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $fournisseur = null;

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

    public function getType(): TypeFrais
    {
        return $this->type;
    }

    public function setType(TypeFrais $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getSousCategorie(): ?string
    {
        return $this->sousCategorie;
    }

    public function setSousCategorie(?string $sousCategorie): self
    {
        $this->sousCategorie = $sousCategorie;

        return $this;
    }

    public function getElementDepense(): ?string
    {
        return $this->elementDepense;
    }

    public function setElementDepense(?string $elementDepense): self
    {
        $this->elementDepense = $elementDepense;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?string $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

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

    #[Assert\Callback]
    public function validerClassification(ExecutionContextInterface $context): void
    {
        if ($this->type->requiertCategorie() && null === $this->categorie) {
            $context->buildViolation('Le type "{{ type }}" exige une categorie.')
                ->setParameter('{{ type }}', $this->type->getLibelle())
                ->atPath('categorie')
                ->addViolation();

            return;
        }

        if (null !== $this->categorie && !$this->type->autoriseCategorie($this->categorie)) {
            $context->buildViolation('Le couple type/categorie n\'est pas autorise.')
                ->atPath('categorie')
                ->addViolation();
        }
    }
}
