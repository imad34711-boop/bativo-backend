<?php

namespace App\Controller;

use App\Dto\RevenueInput;
use App\Dto\RevenueOutput;
use App\Entity\Projet;
use App\Entity\Revenue;
use App\Entity\User;
use App\Service\EntityValidator;
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
class RevenueController extends AbstractController
{
    private const ROUTE_COLLECTION = '/projets/{projetId}/revenues';
    private const ROUTE_ITEM = '/revenues/{id}';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityValidator $validator,
    ) {
    }

    #[Route(self::ROUTE_COLLECTION, name: 'api_revenue_index', requirements: ['projetId' => '\d+'], methods: ['GET'])]
    public function index(#[MapEntity(id: 'projetId')] Projet $projet, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('revenue.read', $projet);

        $revenues = $projet->getRevenuesOrdonnees();
        if (!$user->peutVoirToutesEntreesDuProjet($projet)) {
            $revenues = array_values(array_filter(
                $revenues,
                static fn (Revenue $revenue): bool => $revenue->getSaisiPar() === $user
            ));
        }

        return $this->json(array_map(RevenueOutput::fromEntity(...), $revenues));
    }

    #[Route(self::ROUTE_COLLECTION, name: 'api_revenue_create', requirements: ['projetId' => '\d+'], methods: ['POST'])]
    public function create(
        #[MapEntity(id: 'projetId')] Projet $projet,
        #[MapRequestPayload] RevenueInput $input,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('revenue.create', $projet);

        $revenue = new Revenue();
        $projet->addRevenue($revenue);
        $revenue->setSaisiPar($user);
        $input->applyTo($revenue);

        $this->validator->assertValide($revenue);

        $this->entityManager->persist($revenue);
        $this->entityManager->flush();

        return $this->json(RevenueOutput::fromEntity($revenue), Response::HTTP_CREATED);
    }

    #[Route(self::ROUTE_ITEM, name: 'api_revenue_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Revenue $revenue, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('revenue.read', $revenue->getProjet());
        if (!$user->peutVoirToutesEntreesDuProjet($revenue->getProjet()) && $revenue->getSaisiPar() !== $user) {
            throw new AccessDeniedHttpException('Acces refuse a ce decompte.');
        }

        return $this->json(RevenueOutput::fromEntity($revenue));
    }

    #[Route(self::ROUTE_ITEM, name: 'api_revenue_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Revenue $revenue, #[MapRequestPayload] RevenueInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('revenue.update', $revenue->getProjet());
        if (!$user->peutVoirToutesEntreesDuProjet($revenue->getProjet()) && $revenue->getSaisiPar() !== $user) {
            throw new AccessDeniedHttpException('Acces refuse a ce decompte.');
        }

        $input->applyTo($revenue);

        $this->validator->assertValide($revenue);
        $this->entityManager->flush();

        return $this->json(RevenueOutput::fromEntity($revenue));
    }

    #[Route(self::ROUTE_ITEM, name: 'api_revenue_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Revenue $revenue, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('revenue.delete', $revenue->getProjet());
        if (!$user->peutVoirToutesEntreesDuProjet($revenue->getProjet()) && $revenue->getSaisiPar() !== $user) {
            throw new AccessDeniedHttpException('Acces refuse a ce decompte.');
        }

        $projet = $revenue->getProjet();
        $projet->removeRevenue($revenue);

        $this->entityManager->remove($revenue);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
