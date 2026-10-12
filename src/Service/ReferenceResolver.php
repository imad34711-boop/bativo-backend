<?php

namespace App\Service;

use App\Entity\Categorie;
use App\Entity\StatutProjet;
use App\Entity\TypeFrais;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Resout les donnees de reference a partir de leur code fonctionnel.
 */
class ReferenceResolver
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function statutProjet(?string $code): ?StatutProjet
    {
        if (null === $code || '' === $code) {
            return null;
        }

        $statut = $this->entityManager->getRepository(StatutProjet::class)->findOneBy(['code' => $code]);

        if (null === $statut) {
            throw new UnprocessableEntityHttpException(sprintf('Statut projet inconnu: "%s".', $code));
        }

        return $statut;
    }

    public function typeFrais(string $code): TypeFrais
    {
        $type = $this->entityManager->getRepository(TypeFrais::class)->findOneBy(['code' => $code]);

        if (null === $type) {
            throw new UnprocessableEntityHttpException(sprintf('Type de frais inconnu: "%s".', $code));
        }

        return $type;
    }

    public function categorie(?string $code): ?Categorie
    {
        if (null === $code || '' === $code) {
            return null;
        }

        $categorie = $this->entityManager->getRepository(Categorie::class)->findOneBy(['code' => $code]);

        if (null === $categorie) {
            throw new UnprocessableEntityHttpException(sprintf('Categorie inconnue: "%s".', $code));
        }

        return $categorie;
    }
}
