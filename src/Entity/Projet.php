<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
#[ORM\Table(name: 'projet')]
#[UniqueEntity(fields: ['numeroMarche'], message: 'Ce numero de marche est deja utilise.')]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $numero = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    private string $numeroMarche;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $objet;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $montantMarcheHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $tauxTva = '20.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $cautionProvisoire = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $cautionDefinitive = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $tauxRetenueGarantie = '10.00';

    /**
     * Plafond du cumul des retenues de garantie, en pourcentage du montant marche TTC.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $tauxPlafondRetenueGarantie = '7.00';

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $maitreOuvrage = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $maitreOuvrageDelegue = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $architecte = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $bet = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $bureauControle = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $laboratoire = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $topographe = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $dureeMois = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $dureeJours = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\ManyToOne(targetEntity: Organisme::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organisme $organisme;

    #[ORM\ManyToOne(targetEntity: StatutProjet::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?StatutProjet $statut = null;

    /**
     * Historique et parametres non structures.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSONB, options: ['default' => '{}'])]
    private array $metadata = [];

    /** @var Collection<int, Depense> */
    #[ORM\OneToMany(mappedBy: 'projet', targetEntity: Depense::class, orphanRemoval: true)]
    private Collection $depenses;

    /** @var Collection<int, Revenue> */
    #[ORM\OneToMany(mappedBy: 'projet', targetEntity: Revenue::class, orphanRemoval: true)]
    private Collection $revenues;

    public function __construct()
    {
        $this->depenses = new ArrayCollection();
        $this->revenues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getNumeroMarche(): string
    {
        return $this->numeroMarche;
    }

    public function setNumeroMarche(string $numeroMarche): self
    {
        $this->numeroMarche = $numeroMarche;

        return $this;
    }

    public function getObjet(): string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): self
    {
        $this->objet = $objet;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getMontantMarcheHt(): string
    {
        return $this->montantMarcheHt;
    }

    public function setMontantMarcheHt(string $montantMarcheHt): self
    {
        $this->montantMarcheHt = $montantMarcheHt;

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

    public function getCautionProvisoire(): ?string
    {
        return $this->cautionProvisoire;
    }

    public function setCautionProvisoire(?string $cautionProvisoire): self
    {
        $this->cautionProvisoire = $cautionProvisoire;

        return $this;
    }

    public function getCautionDefinitive(): ?string
    {
        return $this->cautionDefinitive;
    }

    public function setCautionDefinitive(?string $cautionDefinitive): self
    {
        $this->cautionDefinitive = $cautionDefinitive;

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

    public function getTauxPlafondRetenueGarantie(): string
    {
        return $this->tauxPlafondRetenueGarantie;
    }

    public function setTauxPlafondRetenueGarantie(string $tauxPlafondRetenueGarantie): self
    {
        $this->tauxPlafondRetenueGarantie = $tauxPlafondRetenueGarantie;

        return $this;
    }

    public function getMaitreOuvrage(): ?string
    {
        return $this->maitreOuvrage;
    }

    public function setMaitreOuvrage(?string $maitreOuvrage): self
    {
        $this->maitreOuvrage = $maitreOuvrage;

        return $this;
    }

    public function getMaitreOuvrageDelegue(): ?string
    {
        return $this->maitreOuvrageDelegue;
    }

    public function setMaitreOuvrageDelegue(?string $maitreOuvrageDelegue): self
    {
        $this->maitreOuvrageDelegue = $maitreOuvrageDelegue;

        return $this;
    }

    public function getArchitecte(): ?string
    {
        return $this->architecte;
    }

    public function setArchitecte(?string $architecte): self
    {
        $this->architecte = $architecte;

        return $this;
    }

    public function getBet(): ?string
    {
        return $this->bet;
    }

    public function setBet(?string $bet): self
    {
        $this->bet = $bet;

        return $this;
    }

    public function getBureauControle(): ?string
    {
        return $this->bureauControle;
    }

    public function setBureauControle(?string $bureauControle): self
    {
        $this->bureauControle = $bureauControle;

        return $this;
    }

    public function getLaboratoire(): ?string
    {
        return $this->laboratoire;
    }

    public function setLaboratoire(?string $laboratoire): self
    {
        $this->laboratoire = $laboratoire;

        return $this;
    }

    public function getTopographe(): ?string
    {
        return $this->topographe;
    }

    public function setTopographe(?string $topographe): self
    {
        $this->topographe = $topographe;

        return $this;
    }

    public function getDureeMois(): ?int
    {
        return $this->dureeMois;
    }

    public function setDureeMois(?int $dureeMois): self
    {
        $this->dureeMois = $dureeMois;

        return $this;
    }

    public function getDureeJours(): ?int
    {
        return $this->dureeJours;
    }

    public function setDureeJours(?int $dureeJours): self
    {
        $this->dureeJours = $dureeJours;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getOrganisme(): Organisme
    {
        return $this->organisme;
    }

    public function setOrganisme(Organisme $organisme): self
    {
        $this->organisme = $organisme;

        return $this;
    }

    public function getStatut(): ?StatutProjet
    {
        return $this->statut;
    }

    public function setStatut(?StatutProjet $statut): self
    {
        $this->statut = $statut;

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

    /** @return Collection<int, Depense> */
    public function getDepenses(): Collection
    {
        return $this->depenses;
    }

    public function addDepense(Depense $depense): self
    {
        if (!$this->depenses->contains($depense)) {
            $this->depenses->add($depense);
            $depense->setProjet($this);
        }

        return $this;
    }

    public function removeDepense(Depense $depense): self
    {
        $this->depenses->removeElement($depense);

        return $this;
    }

    /** @return Collection<int, Revenue> */
    public function getRevenues(): Collection
    {
        return $this->revenues;
    }

    public function addRevenue(Revenue $revenue): self
    {
        if (!$this->revenues->contains($revenue)) {
            $this->revenues->add($revenue);
            $revenue->setProjet($this);
        }

        return $this;
    }

    public function removeRevenue(Revenue $revenue): self
    {
        $this->revenues->removeElement($revenue);

        return $this;
    }

    public function getMontantMarcheTva(): float
    {
        return round((float) $this->montantMarcheHt * (float) $this->tauxTva / 100, 2);
    }

    public function getMontantMarcheTtc(): float
    {
        return round((float) $this->montantMarcheHt + $this->getMontantMarcheTva(), 2);
    }

    public function getJoursRestants(?\DateTimeImmutable $reference = null): ?int
    {
        if (null === $this->dateFin) {
            return null;
        }

        $reference ??= new \DateTimeImmutable('today');

        return (int) $reference->diff($this->dateFin)->format('%r%a');
    }

    public function getDepensesTotales(): float
    {
        $total = 0.0;
        foreach ($this->depenses as $depense) {
            $total += $depense->getMontantTtc();
        }

        return round($total, 2);
    }

    public function getRevenuesTotales(): float
    {
        $total = 0.0;
        foreach ($this->revenues as $revenue) {
            $total += $revenue->getMontantTtc();
        }

        return round($total, 2);
    }

    public function getResultat(): float
    {
        return round($this->getRevenuesTotales() - $this->getDepensesTotales(), 2);
    }

    /**
     * Plafond absolu du cumul des retenues de garantie.
     *
     * Retourne null quand le montant du marche n'est pas renseigne: aucun plafond ne s'applique.
     */
    public function getPlafondRetenueGarantie(): ?float
    {
        $montantMarcheTtc = $this->getMontantMarcheTtc();

        if ($montantMarcheTtc <= 0) {
            return null;
        }

        return round($montantMarcheTtc * (float) $this->tauxPlafondRetenueGarantie / 100, 2);
    }

    /**
     * Revenues tries selon l'ordre d'application de la retenue de garantie.
     *
     * @return list<Revenue>
     */
    public function getRevenuesOrdonnees(): array
    {
        $revenues = $this->revenues->toArray();

        usort($revenues, static function (Revenue $a, Revenue $b): int {
            return [$a->getNumeroDecompte(), $a->getId() ?? PHP_INT_MAX]
                <=> [$b->getNumeroDecompte(), $b->getId() ?? PHP_INT_MAX];
        });

        return array_values($revenues);
    }

    /**
     * Cumul des retenues de garantie appliquees avant le revenue fourni.
     */
    public function getRetenueGarantieCumuleeAvant(Revenue $revenue): float
    {
        $cumul = 0.0;

        foreach ($this->getRevenuesOrdonnees() as $item) {
            if ($item === $revenue) {
                break;
            }

            $cumul += $item->getRetenueGarantie();
        }

        return round($cumul, 2);
    }

    public function getRetenueGarantieTotale(): float
    {
        $total = 0.0;

        foreach ($this->revenues as $revenue) {
            $total += $revenue->getRetenueGarantie();
        }

        return round($total, 2);
    }
}
