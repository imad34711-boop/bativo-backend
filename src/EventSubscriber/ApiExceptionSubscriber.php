<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Psr\Log\LoggerInterface;

/**
 * Normalise les erreurs de l'API en JSON.
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 0]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $this->logger->error('API exception: '.$exception::class.' - '.$exception->getMessage(), [
            'exception' => $exception,
            'path' => $event->getRequest()->getPathInfo(),
            'method' => $event->getRequest()->getMethod(),
        ]);

        $statut = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $payload = ['status' => $statut, 'message' => $this->message($exception, $statut)];

        $violations = $this->violations($exception);
        if ([] !== $violations) {
            $payload['violations'] = $violations;
        }

        $entetes = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        $event->setResponse(new JsonResponse($payload, $statut, $entetes));
    }

    /**
     * Les details internes ne sont jamais exposes sur une erreur serveur.
     */
    private function message(\Throwable $exception, int $statut): string
    {
        if (Response::HTTP_INTERNAL_SERVER_ERROR === $statut) {
            return 'Une erreur interne est survenue.';
        }

        return $exception->getMessage();
    }

    /**
     * @return list<array{champ: string, message: string}>
     */
    private function violations(\Throwable $exception): array
    {
        $precedent = $exception->getPrevious();

        if (!$precedent instanceof ValidationFailedException) {
            return [];
        }

        $violations = [];
        foreach ($precedent->getViolations() as $violation) {
            $violations[] = [
                'champ' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $violations;
    }
}
