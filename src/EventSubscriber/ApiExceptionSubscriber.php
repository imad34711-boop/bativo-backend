<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Normalise les erreurs de l'API en JSON.
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{
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
        error_log(sprintf(
            'API exception: %s - %s | %s %s',
            $exception::class,
            $exception->getMessage(),
            $event->getRequest()->getMethod(),
            $event->getRequest()->getPathInfo()
        ));

        $previous = $exception->getPrevious();
        if (null !== $previous) {
            error_log(sprintf(
                'API exception previous: %s - %s',
                $previous::class,
                $previous->getMessage()
            ));
        }

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
