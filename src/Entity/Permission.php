<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Droit unitaire, nomme selon la convention `ressource.action`.
 */
#[ORM\Entity]
#[ORM\Table(name: 'permission')]
class Permission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $code;

    #[ORM\Column(length: 50)]
    private string $ressource;

    #[ORM\Column(length: 50)]
    private string $action;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $libelle = null;

    public function __construct(string $ressource, string $action, ?string $libelle = null)
    {
        $this->ressource = $ressource;
        $this->action = $action;
        $this->code = $ressource.'.'.$action;
        $this->libelle = $libelle;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRessource(): string
    {
        return $this->ressource;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }
}
