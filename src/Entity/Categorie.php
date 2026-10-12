<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categorie')]
class Categorie
{
    public const MATERIELS = 'MATERIELS';
    public const MATERIAUX = 'MATERIAUX';
    public const MAIN_OEUVRE = 'MAIN_OEUVRE';
    public const SOUS_TRAITANT = 'SOUS_TRAITANT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $libelle;

    #[ORM\Column]
    private int $ordre = 0;

    /** @var Collection<int, LinkTypeCategorie> */
    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: LinkTypeCategorie::class, orphanRemoval: true)]
    private Collection $typesAutorises;

    public function __construct(string $code, string $libelle, int $ordre = 0)
    {
        $this->code = $code;
        $this->libelle = $libelle;
        $this->ordre = $ordre;
        $this->typesAutorises = new ArrayCollection();
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
    public function getTypesAutorises(): Collection
    {
        return $this->typesAutorises;
    }
}
