<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Document\Activity;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/activities")
 */
class ActivitiesController extends AbstractFOSRestController
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

    private function getJsonData(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if (is_null($data)) {
            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, "Faltan campos en el json", "Hubo un problema con la petición")
            );
        }
        return $data;
    }

    private function checkRequiredParameters(array $parameters, array $data)
    {
        foreach ($parameters as $parameter) {
            if (!array_key_exists($parameter, $data) || is_null($data[$parameter]) || $data[$parameter] == "") {
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_BAD_REQUEST, "Uno o más de los campos requeridos falta o es nulo: " . $parameter, "Faltan datos")
                );
            }
        }
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
     * Store activity tasks codes
     * @Rest\Put("/{code}", name="put_activity")
     * 
     * @return Response
     */
    public function putActivityAction(Request $request = null, $code)
    {
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters(["tasks"], $data);
        $this->verifyCode($code);

        $activity = $this->dm->getRepository(Activity::class)->findBy(["code" => $code]);

        $activity = $activity ?: new Activity();
        $activity->setCode($code);

        foreach ($data["tasks"] as $taskCode) {
            $this->verifyCode($taskCode);
            $activity->addTask($taskCode);
        }

        $this->dm->persist($activity);
        $this->dm->flush();

        return $this->handleView($this->view($activity));
    }
}
