<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'type_frais')]
class TypeFrais
{
    public const DIRECT = 'DIRECT';
    public const INDIRECT = 'INDIRECT';
    public const FRAIS_CHANTIER = 'FRAIS_CHANTIER';
    public const FRAIS_GENERAL = 'FRAIS_GENERAL';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $libelle;

    /**
     * Vrai pour Direct et Indirect, qui exigent une categorie.
     */
    #[ORM\Column]
    private bool $requiertCategorie = false;

    #[ORM\Column]
    private int $ordre = 0;

    /** @var Collection<int, LinkTypeCategorie> */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: LinkTypeCategorie::class, orphanRemoval: true)]
    private Collection $categoriesAutorisees;

    public function __construct(string $code, string $libelle, bool $requiertCategorie = false, int $ordre = 0)
    {
        $this->code = $code;
        $this->libelle = $libelle;
        $this->requiertCategorie = $requiertCategorie;
        $this->ordre = $ordre;
        $this->categoriesAutorisees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function requiertCategorie(): bool
    {
        return $this->requiertCategorie;
    }

    public function setRequiertCategorie(bool $requiertCategorie): self
    {
        $this->requiertCategorie = $requiertCategorie;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    /** @return Collection<int, LinkTypeCategorie> */
    public function getCategoriesAutorisees(): Collection
    {
        return $this->categoriesAutorisees;
    }

    public function autoriseCategorie(Categorie $categorie): bool
    {
        foreach ($this->categoriesAutorisees as $link) {
            if ($link->getCategorie() === $categorie) {
                return true;
            }
        }

        return false;
    }
}
