<?php

namespace App\Controller\v1\pub;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/health-check")
 */
class HealthController extends AbstractFOSRestController
{

    /**
     * Prueba de vida
     * @Rest\Get(name="health_check")
     * 
     * @return Response
     */
    public function getHealthCheck()
    {
        return $this->getViewHandler()->handle($this->view(["status" => "ok"]));
    }
}
