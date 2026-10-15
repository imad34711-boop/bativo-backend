<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015153000 extends AbstractMigration
{
    private const RESOURCES = ['projet', 'depense', 'revenue', 'user', 'role', 'permission', 'organisme'];

    private const ACTIONS = ['create', 'read', 'update', 'delete'];

    /**
     * @var array<string, array{libelle: string, permissions: list<string>|null}>
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

    /**
     * @var array<int, array{email: string, nom: string, prenom: string, password: string}>
     */
    private const USERS = [
        [
            'email' => 'imadelkadiri@gmail.com',
            'nom' => 'elkadiri',
            'prenom' => 'imad',
            'password' => '$2y$13$IxKbQr6SffJad4OMnH3qrefuo4guRXabUbEh3Bl8gIVGG.geBaFkq',
        ],
        [
            'email' => 'earl.zakaria.khatib@gmail.com',
            'nom' => 'KHATIB',
            'prenom' => 'zakaria',
            'password' => '$2y$13$FIyW7BTEFtZj6bol/pyE1OemDRr1gZ4txbSRkRQFtGHdr8u3TUmnu',
        ],
    ];

    public function getDescription(): string
    {
        return 'Seed permissions, roles and two application users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO organisme (code, nom, metadata) VALUES ('LEGACY', 'Legacy Organisation', '{}'::jsonb) ON CONFLICT (code) DO NOTHING");

        foreach ($this->permissionRows() as [$code, $ressource, $action]) {
            $this->addSql(sprintf(
                "INSERT INTO permission (code, ressource, action, libelle) VALUES ('%s', '%s', '%s', NULL) ON CONFLICT (code) DO UPDATE SET ressource = EXCLUDED.ressource, action = EXCLUDED.action, libelle = EXCLUDED.libelle",
                $code,
                $ressource,
                $action,
            ));
        }

        foreach (self::ROLES as $code => $definition) {
            $this->addSql(sprintf(
                "INSERT INTO app_role (code, libelle, description) VALUES ('%s', '%s', NULL) ON CONFLICT (code) DO UPDATE SET libelle = EXCLUDED.libelle, description = EXCLUDED.description",
                $code,
                $definition['libelle'],
            ));

            $permissionCodes = $definition['permissions'] ?? $this->permissionCodes();
            foreach ($permissionCodes as $permissionCode) {
                $this->addSql(sprintf(
                    "INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM app_role r INNER JOIN permission p ON r.code = '%s' AND p.code = '%s' ON CONFLICT DO NOTHING",
                    $code,
                    $permissionCode,
                ));
            }
        }

        foreach (self::USERS as $user) {
            $this->addSql(sprintf(
                "INSERT INTO app_user (email, nom, prenom, password, actif, organisme_id, preferences) VALUES ('%s', '%s', '%s', '%s', TRUE, (SELECT id FROM organisme WHERE code = 'LEGACY'), '{}'::jsonb) ON CONFLICT (email) DO UPDATE SET nom = EXCLUDED.nom, prenom = EXCLUDED.prenom, password = EXCLUDED.password, actif = EXCLUDED.actif, organisme_id = EXCLUDED.organisme_id, preferences = EXCLUDED.preferences",
                $user['email'],
                $user['nom'],
                $user['prenom'],
                $user['password'],
            ));

            $this->addSql(sprintf(
                "INSERT INTO user_role_assignment (user_id, role_id, projet_id) SELECT u.id, r.id, NULL FROM app_user u INNER JOIN app_role r ON u.email = '%s' AND r.code = 'USER' WHERE NOT EXISTS (SELECT 1 FROM user_role_assignment existing WHERE existing.user_id = u.id AND existing.role_id = r.id AND existing.projet_id IS NULL)",
                $user['email'],
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::USERS as $user) {
            $this->addSql(sprintf("DELETE FROM user_role_assignment WHERE user_id = (SELECT id FROM app_user WHERE email = '%s')", $user['email']));
            $this->addSql(sprintf("DELETE FROM app_user WHERE email = '%s'", $user['email']));
        }

        foreach (array_keys(self::ROLES) as $code) {
            $this->addSql(sprintf("DELETE FROM role_permissions WHERE role_id = (SELECT id FROM app_role WHERE code = '%s')", $code));
            $this->addSql(sprintf("DELETE FROM app_role WHERE code = '%s'", $code));
        }

        foreach ($this->permissionRows() as [$code]) {
            $this->addSql(sprintf("DELETE FROM permission WHERE code = '%s'", $code));
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function permissionRows(): array
    {
        $rows = [];

        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                $rows[] = [$resource.'.'.$action, $resource, $action];
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function permissionCodes(): array
    {
        $codes = [];

        foreach ($this->permissionRows() as [$code]) {
            $codes[] = $code;
        }

        return $codes;
    }
}