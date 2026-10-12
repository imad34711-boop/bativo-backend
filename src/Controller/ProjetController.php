<?php

namespace App\Controller;

use App\Dto\ProjetInput;
use App\Dto\ProjetOutput;
use App\Dto\ProjetSyntheseOutput;
use App\Entity\Projet;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRoleAssignment;
use App\Repository\ProjetRepository;
use App\Service\EntityValidator;
use App\Service\ReferenceResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/projets')]
class ProjetController extends AbstractController
{
    private const TAILLE_PAGE_MAX = 100;
    private const ROUTE_ITEM = '/{id}';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjetRepository $projets,
        private readonly ReferenceResolver $references,
        private readonly EntityValidator $validator,
    ) {
    }

    #[Route('', name: 'api_projet_index', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $taille = min(self::TAILLE_PAGE_MAX, max(1, $request->query->getInt('taille', 20)));

        $resultat = $this->projets->paginerPourUtilisateur($user, $page, $taille);

        return $this->json([
            'items' => array_map(ProjetOutput::fromEntity(...), $resultat['items']),
            'page' => $page,
            'taille' => $taille,
            'total' => $resultat['total'],
        ]);
    }

    #[Route('', name: 'api_projet_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ProjetInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('projet.create');

        $projet = new Projet();
        $projet->setOrganisme($user->getOrganisme());
        $input->applyTo($projet, $this->references->statutProjet($input->statutCode));

        $this->validator->assertValide($projet);

        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        // Créer une affectation automatique pour que l'utilisateur voie son propre projet
        if (!$user->hasRoleCode('DIRECTEUR') && !$user->isSuperAdmin()) {
            $roleRepo = $this->entityManager->getRepository(Role::class);
            $roleChef = $roleRepo->findOneBy(['code' => 'CHEF_PROJET']) ?? $roleRepo->findOneBy(['code' => 'USER']);
            
            if ($roleChef) {
                $this->entityManager->persist(new UserRoleAssignment($user, $roleChef, $projet));
                $this->entityManager->flush();
            }
        }

        return $this->json(ProjetOutput::fromEntity($projet), Response::HTTP_CREATED);
    }

    #[Route(self::ROUTE_ITEM, name: 'api_projet_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Projet $projet): JsonResponse
    {
        $this->denyAccessUnlessGranted('projet.read', $projet);

        return $this->json(ProjetOutput::fromEntity($projet));
    }

    #[Route('/{id}/synthese', name: 'api_projet_synthese', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function synthese(Projet $projet): JsonResponse
    {
        $this->denyAccessUnlessGranted('projet.read', $projet);

        return $this->json(ProjetSyntheseOutput::fromEntity($projet));
    }

    #[Route(self::ROUTE_ITEM, name: 'api_projet_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Projet $projet, #[MapRequestPayload] ProjetInput $input): JsonResponse
    {
        $this->denyAccessUnlessGranted('projet.update', $projet);

        $input->applyTo($projet, $this->references->statutProjet($input->statutCode));

        $this->validator->assertValide($projet);
        $this->entityManager->flush();

        return $this->json(ProjetOutput::fromEntity($projet));
    }

    #[Route(self::ROUTE_ITEM, name: 'api_projet_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Projet $projet): JsonResponse
    {
        $this->denyAccessUnlessGranted('projet.delete', $projet);

        $this->entityManager->remove($projet);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
