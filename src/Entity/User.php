<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\ManyToOne(targetEntity: Organisme::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organisme $organisme;

    /**
     * Personnalisation des parametres propres a l'utilisateur.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSONB, options: ['default' => '{}'])]
    private array $preferences = [];

    /** @var Collection<int, UserRoleAssignment> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRoleAssignment::class, orphanRemoval: true)]
    private Collection $roleAssignments;

    public function __construct()
    {
        $this->roleAssignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Roles Symfony a portee globale uniquement.
     *
     * Les droits contextuels par projet sont evalues par ProjetVoter.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->roleAssignments as $assignment) {
            if ($assignment->estGlobale()) {
                $roles[] = 'ROLE_'.$assignment->getRole()->getCode();
            }
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
        // Aucune donnee sensible temporaire n'est stockee sur l'entite.
    }

    public function isActif(): bool
    {
        return $this->actif;
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

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPreferences(): array
    {
        return $this->preferences;
    }

    /** @param array<string, mixed> $preferences */
    public function setPreferences(array $preferences): self
    {
        $this->preferences = $preferences;

        return $this;
    }

    /** @return Collection<int, UserRoleAssignment> */
    public function getRoleAssignments(): Collection
    {
        return $this->roleAssignments;
    }

    public function addRoleAssignment(UserRoleAssignment $assignment): self
    {
        if (!$this->roleAssignments->contains($assignment)) {
            $this->roleAssignments->add($assignment);
        }

        return $this;
    }

    public function removeRoleAssignment(UserRoleAssignment $assignment): self
    {
        $this->roleAssignments->removeElement($assignment);

        return $this;
    }

    /**
     * Permissions effectives pour un projet donne.
     *
     * Une affectation sans projet vaut portee globale.
     *
     * @return list<string>
     */
    public function getPermissionCodes(?Projet $projet = null): array
    {
        $codes = [];

        foreach ($this->roleAssignments as $assignment) {
            if (!$assignment->couvre($projet)) {
                continue;
            }

            foreach ($assignment->getRole()->getPermissions() as $permission) {
                $codes[$permission->getCode()] = true;
            }
        }

        return array_keys($codes);
    }

    public function hasPermission(string $code, ?Projet $projet = null): bool
    {
        return in_array($code, $this->getPermissionCodes($projet), true);
    }

    /**
     * @return list<string>
     */
    public function getPermissionCodesToutesAffectations(): array
    {
        $codes = [];

        foreach ($this->roleAssignments as $assignment) {
            foreach ($assignment->getRole()->getPermissions() as $permission) {
                $codes[$permission->getCode()] = true;
            }
        }

        return array_keys($codes);
    }

    public function hasRoleCode(string $code, ?Projet $projet = null): bool
    {
        foreach ($this->roleAssignments as $assignment) {
            if ($assignment->getRole()->getCode() !== $code) {
                continue;
            }

            if ($assignment->couvre($projet)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRoleCode('ADMIN');
    }

    public function appartientAuMemeOrganismeQueProjet(Projet $projet): bool
    {
        return $this->organisme === $projet->getOrganisme();
    }

    public function peutVoirProjet(Projet $projet): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->appartientAuMemeOrganismeQueProjet($projet)) {
            return false;
        }

        if ($this->hasRoleCode('DIRECTEUR')) {
            return $this->hasPermission('projet.read');
        }

        return $this->hasPermission('projet.read', $projet);
    }

    public function peutVoirToutesEntreesDuProjet(Projet $projet): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->appartientAuMemeOrganismeQueProjet($projet)) {
            return false;
        }

        if ($this->hasRoleCode('DIRECTEUR')) {
            return true;
        }

        return $this->hasRoleCode('CHEF_PROJET', $projet) || $this->hasRoleCode('CHEF_PROJET');
    }

    public function hasPermissionSurProjetViaAffectation(string $code, Projet $projet): bool
    {
        foreach ($this->roleAssignments as $assignment) {
            if ($assignment->getProjet() !== $projet) {
                continue;
            }

            if ($assignment->getRole()->hasPermission($code)) {
                return true;
            }
        }

        return false;
    }
}
