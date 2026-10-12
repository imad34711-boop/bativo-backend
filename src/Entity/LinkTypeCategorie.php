<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Couples Type/Categorie autorises pour classer une depense.
 */
#[ORM\Entity]
#[ORM\Table(name: 'link_type_categorie')]
#[ORM\UniqueConstraint(name: 'uniq_type_categorie', columns: ['type_id', 'categorie_id'])]
class LinkTypeCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TypeFrais::class, inversedBy: 'categoriesAutorisees')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TypeFrais $type;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'typesAutorises')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Categorie $categorie;

    public function __construct(TypeFrais $type, Categorie $categorie)
    {
        $this->type = $type;
        $this->categorie = $categorie;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): TypeFrais
    {
        return $this->type;
    }

    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }
}
