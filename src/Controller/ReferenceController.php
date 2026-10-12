<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\StatutProjet;
use App\Entity\TypeFrais;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ReferenceController extends AbstractController
{
    /**
     * Retourne les listes de reference consommees par les formulaires front.
     */
    #[Route('/api/references', name: 'api_references', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $entityManager): JsonResponse
    {
        $categories = $entityManager->getRepository(Categorie::class)->findBy([], ['ordre' => 'ASC', 'id' => 'ASC']);
        $types = $entityManager->getRepository(TypeFrais::class)->findBy([], ['ordre' => 'ASC', 'id' => 'ASC']);
        $statuts = $entityManager->getRepository(StatutProjet::class)->findBy([], ['ordre' => 'ASC', 'id' => 'ASC']);

        $payload = [
            'typesFrais' => array_map(function (TypeFrais $type) use ($categories): array {
                $categoriesAutorisees = [];
                foreach ($categories as $categorie) {
                    if ($type->autoriseCategorie($categorie)) {
                        $categoriesAutorisees[] = $categorie->getCode();
                    }
                }

                return [
                    'code' => $type->getCode(),
                    'libelle' => $type->getLibelle(),
                    'requiertCategorie' => $type->requiertCategorie(),
                    'categoriesAutorisees' => $categoriesAutorisees,
                ];
            }, $types),
            'categories' => array_map(static fn (Categorie $categorie): array => [
                'code' => $categorie->getCode(),
                'libelle' => $categorie->getLibelle(),
            ], $categories),
            'statutsProjet' => array_map(static fn (StatutProjet $statut): array => [
                'code' => $statut->getCode(),
                'libelle' => $statut->getLibelle(),
            ], $statuts),
        ];

        return $this->json($payload);
    }
}
