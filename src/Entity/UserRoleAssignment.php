<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Affectation d'un role a un utilisateur.
 *
 * `projet` null signifie une portee globale sur l'application.
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_role_assignment')]
#[ORM\UniqueConstraint(name: 'uniq_user_role_projet', columns: ['user_id', 'role_id', 'projet_id'])]
class UserRoleAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'roleAssignments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Role $role;

    #[ORM\ManyToOne(targetEntity: Projet::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Projet $projet = null;

    public function __construct(User $user, Role $role, ?Projet $projet = null)
    {
        $this->user = $user;
        $this->role = $role;
        $this->projet = $projet;
        $user->addRoleAssignment($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function estGlobale(): bool
    {
        return null === $this->projet;
    }

    /**
     * Vrai si l'affectation s'applique au projet demande.
     */
    public function couvre(?Projet $projet): bool
    {
        if ($this->estGlobale()) {
            return true;
        }

        return null !== $projet && $this->projet === $projet;
    }
}
