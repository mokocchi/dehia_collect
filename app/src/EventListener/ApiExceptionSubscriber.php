<?php

namespace App\EventListener;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiProblemResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private $logger;
    private $apiResponseFactory;

    public function __construct(LoggerInterface $logger, ApiProblemResponseFactory $apiResponseFactory)
    {
        $this->logger = $logger;
        $this->apiResponseFactory = $apiResponseFactory;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();
        $this->logger->error($e->getMessage() . "\n" . $e->getLine() . "\n" . $e->getFile() . "\n" . $e->getTraceAsString());

        if ($e instanceof HttpException) {
            if ($e instanceof ApiProblemException) {
                $apiProblem = $e->getApiProblem();
                return $event->setResponse($this->apiResponseFactory->createResponse($apiProblem));
            } elseif ($e instanceof NotFoundHttpException) {
                $devMessage = "Recurso no encontrado";
                $usrMessage = "Recurso no encontrado";
                $apiProblem = new ApiProblem(
                    $e->getStatusCode(),
                    $devMessage,
                    $usrMessage
                );
            } elseif ($e instanceof AccessDeniedHttpException) {
                $devMessage = "No tenés los permisos suficientes para acceder al recurso";
                $usrMessage = "Acceso denegado";
                $apiProblem = new ApiProblem(
                    $e->getStatusCode(),
                    $devMessage,
                    $usrMessage
                );
            } elseif ($e instanceof UnauthorizedHttpException) {
                $devMessage = "No se recibió información de autorización";
                $usrMessage = "No autorizado";
                $apiProblem = new ApiProblem(
                    $e->getStatusCode(),
                    $devMessage,
                    $usrMessage
                );
            } elseif ($e instanceof BadRequestHttpException) {
                if ($e->getMessage() == "Invalid json message received") {
                    $devMessage = "JSON inválido";
                } else {
                    $devMessage = "Hubo un problema con el request";
                }
                $usrMessage = "Datos inválidos";
                $apiProblem = new ApiProblem(
                    $e->getStatusCode(),
                    $devMessage,
                    $usrMessage
                );
            } elseif ($e instanceof MethodNotAllowedHttpException) {
                $devMessage = "Método no permitido";
                $usrMessage = "Ocurrió un error";
                $apiProblem = new ApiProblem(
                    $e->getStatusCode(),
                    $devMessage,
                    $usrMessage
                );
            } elseif ($e instanceof HttpException) {
                if ($e->getStatusCode() == 401) {
                    $devMessage = "No se recibió información de autorización";
                    $usrMessage = "No autorizado";
                    $apiProblem = new ApiProblem(
                        $e->getStatusCode(),
                        $devMessage,
                        $usrMessage
                    );
                } else {
                    $devMessage = "Ocurrió un error";
                    $usrMessage = "Ocurrió un error";
                    $apiProblem = new ApiProblem(
                        $e->getStatusCode(),
                        $devMessage,
                        $usrMessage
                    );
                }
            } else {
                $devMessage = "Ocurrió un error";
                $usrMessage = "Ocurrió un error";
                $apiProblem = new ApiProblem(
                    $e->getStatusCode(),
                    $devMessage,
                    $usrMessage
                );
            }
            $response = $this->apiResponseFactory->createResponse($apiProblem);
        } else {
            $apiProblem = new ApiProblem(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Error interno del servidor",
                "Ocurrió un error"
            );
            $response = $this->apiResponseFactory->createResponse($apiProblem);
        }
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }
}
