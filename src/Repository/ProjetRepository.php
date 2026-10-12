<?php

namespace App\Repository;

use App\Entity\Projet;
use App\Entity\User;
use App\Entity\UserRoleAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projet>
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    /**
     * Projets visibles par l'utilisateur, pagines.
     *
     * Une permission globale `projet.read` donne acces a tous les projets,
     * sinon seules les affectations portant sur un projet sont retenues.
     *
     * @return array{items: list<Projet>, total: int}
     */
    public function paginerPourUtilisateur(User $user, int $page, int $taille): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.id', 'DESC');

        if (!$user->isSuperAdmin()) {
            $qb->andWhere('p.organisme = :organisme')->setParameter('organisme', $user->getOrganisme());
        }

        if (!$user->isSuperAdmin() && !$user->hasRoleCode('DIRECTEUR')) {
            $qb->innerJoin(UserRoleAssignment::class, 'a', 'WITH', 'a.projet = p')
                ->innerJoin('a.role', 'r')
                ->innerJoin('r.permissions', 'perm')
                ->andWhere('a.user = :user')
                ->andWhere('perm.code = :permission')
                ->setParameter('user', $user)
                ->setParameter('permission', 'projet.read')
                ->distinct();
        }

        $qb->setFirstResult(($page - 1) * $taille)->setMaxResults($taille);

        $paginator = new Paginator($qb->getQuery(), false);

        return [
            'items' => array_values(iterator_to_array($paginator)),
            'total' => count($paginator),
        ];
    }
}
