<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Document\Activity;
use App\Document\Entry;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/entries")
 */
class EntriesController extends AbstractFOSRestController
{
    private $logger;
    private $serializer;
    private $dm;

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer, DocumentManager $dm)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->dm = $dm;
    }

    private function verifyCode($code)
    {
        if ((strlen($code) !== 64) || (preg_match("/[0-9a-z]/", $code) !== 1)) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_BAD_REQUEST,
                    "Formato de código erróneo",
                    "Ocurrió un error"
                )
            );
        }
    }

    /**
     * Get results for an activity
     * @Rest\Get("/{code}", name="get_results")
     * 
     * @return Response
     */
    public function getResultsForActivity($code)
    {
        $this->verifyCode($code);
        $activity = $this->dm->getRepository(Activity::class)->findOneBy(["code" => $code]);
        //TODO: make voter
        if ($this->getUser()->getGoogleId() !== $activity->getAuthor()) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_FORBIDDEN,
                    "La actividad no pertenece al usuario",
                    "La actividad no pertenece al usuario"
                )
            );
        }
        $entries = $this->dm->getRepository(Entry::class)->findBy(["code" => $code]);
        return $this->handleView($this->view(["results" => $entries]));
    }
}
