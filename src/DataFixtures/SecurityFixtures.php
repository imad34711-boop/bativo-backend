<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Entity\Organisme;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRoleAssignment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Permissions, roles applicatifs et compte administrateur initial.
 */
class SecurityFixtures extends Fixture
{
    private const RESSOURCES = ['projet', 'depense', 'revenue', 'user', 'role', 'permission', 'organisme'];

    private const ACTIONS = ['create', 'read', 'update', 'delete'];

    /**
     * @var array<string, array{libelle: string, permissions: string[]|null}>
     */
    private const ROLES = [
        'ADMIN' => [
            'libelle' => 'Administrateur',
            'permissions' => null,
        ],
        'DIRECTEUR' => [
            'libelle' => 'Directeur d organisme',
            'permissions' => [
                'projet.create', 'projet.read', 'projet.update',
                'depense.read',
                'revenue.read',
                'user.read', 'user.create', 'user.update',
            ],
        ],
        'CHEF_PROJET' => [
            'libelle' => 'Chef de projet',
            'permissions' => [
                'projet.read', 'projet.update',
                'depense.create', 'depense.read', 'depense.update', 'depense.delete',
                'revenue.create', 'revenue.read', 'revenue.update', 'revenue.delete',
            ],
        ],
        'USER' => [
            'libelle' => 'Utilisateur',
            'permissions' => [
                'projet.read',
                'depense.create', 'depense.read', 'depense.update', 'depense.delete',
                'revenue.create', 'revenue.read', 'revenue.update', 'revenue.delete',
            ],
        ],
        'COMPTABLE' => [
            'libelle' => 'Comptable',
            'permissions' => [
                'projet.read',
                'depense.create', 'depense.read', 'depense.update',
                'revenue.create', 'revenue.read', 'revenue.update',
            ],
        ],
        'LECTEUR' => [
            'libelle' => 'Lecteur',
            'permissions' => ['projet.read', 'depense.read', 'revenue.read'],
        ],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        /** @var array<string, Permission> $permissions */
        $permissions = [];

        foreach (self::RESSOURCES as $ressource) {
            foreach (self::ACTIONS as $action) {
                $permission = new Permission($ressource, $action);
                $manager->persist($permission);
                $permissions[$permission->getCode()] = $permission;
            }
        }

        $roles = [];
        foreach (self::ROLES as $code => $definition) {
            $role = new Role($code, $definition['libelle']);

            $codesAccordes = $definition['permissions'] ?? array_keys($permissions);
            foreach ($codesAccordes as $codePermission) {
                $role->addPermission($permissions[$codePermission]);
            }

            $manager->persist($role);
            $roles[$code] = $role;
            $this->addReference('role_'.$code, $role);
        }

        $admin = new User();
        $organisme = new Organisme('BATIVO', 'Bativo Groupe');
        $manager->persist($organisme);

        $admin->setEmail('admin@bativo.local');
        $admin->setNom('Administrateur');
        $admin->setActif(true);
        $admin->setOrganisme($organisme);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $this->resoudreMotDePasseAdmin()));
        $manager->persist($admin);

        $manager->persist(new UserRoleAssignment($admin, $roles['ADMIN']));

        $manager->flush();
    }

    private function resoudreMotDePasseAdmin(): string
    {
        $motDePasse = $_SERVER['FIXTURES_ADMIN_PASSWORD'] ?? $_ENV['FIXTURES_ADMIN_PASSWORD'] ?? null;

        if (!\is_string($motDePasse) || '' === $motDePasse) {
            throw new \LogicException(
                'La variable d\'environnement FIXTURES_ADMIN_PASSWORD doit etre definie pour charger les fixtures.'
            );
        }

        return $motDePasse;
    }
}
