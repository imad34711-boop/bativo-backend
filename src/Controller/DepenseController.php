<?php

namespace App\Controller;

use App\Dto\DepenseInput;
use App\Dto\DepenseOutput;
use App\Entity\Depense;
use App\Entity\Projet;
use App\Entity\User;
use App\Service\EntityValidator;
use App\Service\ReferenceResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class DepenseController extends AbstractController
{
    private const ROUTE_COLLECTION = '/projets/{projetId}/depenses';
    private const ROUTE_ITEM = '/depenses/{id}';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReferenceResolver $references,
        private readonly EntityValidator $validator,
    ) {
    }

    #[Route(self::ROUTE_COLLECTION, name: 'api_depense_index', requirements: ['projetId' => '\d+'], methods: ['GET'])]
    public function index(#[MapEntity(id: 'projetId')] Projet $projet, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('depense.read', $projet);

        $criteria = ['projet' => $projet];
        if (!$user->peutVoirToutesEntreesDuProjet($projet)) {
            $criteria['saisiPar'] = $user;
        }

        $depenses = $this->entityManager->getRepository(Depense::class)->findBy($criteria, ['date' => 'ASC', 'id' => 'ASC']);

        return $this->json(array_map(DepenseOutput::fromEntity(...), $depenses));
    }

    #[Route(self::ROUTE_COLLECTION, name: 'api_depense_create', requirements: ['projetId' => '\d+'], methods: ['POST'])]
    public function create(
        #[MapEntity(id: 'projetId')] Projet $projet,
        #[MapRequestPayload] DepenseInput $input,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('depense.create', $projet);

        $depense = new Depense();
        $projet->addDepense($depense);
        $depense->setSaisiPar($user);
        $input->applyTo(
            $depense,
            $this->references->typeFrais($input->typeCode),
            $this->references->categorie($input->categorieCode)
        );

        $this->validator->assertValide($depense);

        $this->entityManager->persist($depense);
        $this->entityManager->flush();

        return $this->json(DepenseOutput::fromEntity($depense), Response::HTTP_CREATED);
    }

    #[Route(self::ROUTE_ITEM, name: 'api_depense_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Depense $depense, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('depense.read', $depense->getProjet());
        if (!$user->peutVoirToutesEntreesDuProjet($depense->getProjet()) && $depense->getSaisiPar() !== $user) {
            throw new AccessDeniedHttpException('Acces refuse a cette depense.');
        }

        return $this->json(DepenseOutput::fromEntity($depense));
    }

    #[Route(self::ROUTE_ITEM, name: 'api_depense_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Depense $depense, #[MapRequestPayload] DepenseInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('depense.update', $depense->getProjet());
        if (!$user->peutVoirToutesEntreesDuProjet($depense->getProjet()) && $depense->getSaisiPar() !== $user) {
            throw new AccessDeniedHttpException('Acces refuse a cette depense.');
        }

        $input->applyTo(
            $depense,
            $this->references->typeFrais($input->typeCode),
            $this->references->categorie($input->categorieCode)
        );

        $this->validator->assertValide($depense);
        $this->entityManager->flush();

        return $this->json(DepenseOutput::fromEntity($depense));
    }

    #[Route(self::ROUTE_ITEM, name: 'api_depense_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Depense $depense, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('depense.delete', $depense->getProjet());
        if (!$user->peutVoirToutesEntreesDuProjet($depense->getProjet()) && $depense->getSaisiPar() !== $user) {
            throw new AccessDeniedHttpException('Acces refuse a cette depense.');
        }

        $this->entityManager->remove($depense);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
