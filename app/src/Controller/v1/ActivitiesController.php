<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Document\Activity;
use App\Document\Type;
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
     * @Rest\Post(name="post_activity")
     * 
     * @return Response
     */
    public function postActivityAction(Request $request = null)
    {
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters(["tasks", "code", "author"], $data);
        $this->verifyCode($data["code"]);
        $code = $data["code"];

        $activity = $this->dm->getRepository(Activity::class)->findBy(["code" => $code]);

        if ($activity) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_BAD_REQUEST,
                    "La actividad ya existe",
                    "La actividad ya existe"
                )
            );
        }

        $activity = new Activity();
        $activity->setCode($code);
        $activity->setAuthor($data["author"]);

        foreach ($data["tasks"] as $taskArray) {
            $this->checkRequiredParameters(["code", "type"], $taskArray);
            $this->verifyCode($taskArray["code"]);
            $task["code"] = $taskArray["code"];
            if (!in_array($taskArray["type"], Type::TYPES)) {
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_BAD_REQUEST,
                        "Tipo desconocido",
                        "Tipo desconocido"
                    )
                );
            }
            $task["type"] = $taskArray["type"];
            $activity->addTask($task);
        }

        $this->dm->persist($activity);
        $this->dm->flush();

        return $this->handleView($this->view($activity));
    }

    /**
     * Close an activity
     * @Rest\Post("/closed", name="close_activity")
     * 
     * @return Response
     */

    public function closeActivity(Request $request)
    {
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters(["activity"], $data);
        $this->verifyCode($data["activity"]);
        $code = $data["activity"];
        $activity = $this->dm->getRepository(Activity::class)->findOneBy(["code" => $code]);

        if (is_null($activity)) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_NOT_FOUND,
                    "No se encontró la actividad",
                    "No se encontró la actividad"
                )
            );
        }

        if ($this->getUser()->getGoogleId() !== $activity->getAuthor()) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_FORBIDDEN,
                    "La actividad no pertenece al usuario",
                    "La actividad no pertenece al usuario"
                )
            );
        }

        $activity->setClosed(true);

        $this->dm->persist($activity);
        $this->dm->flush();

        return $this->handleView($this->view($activity));
    }

    /**
     * Reopen an activity
     * @Rest\Post("/open", name="reopen_activity")
     * 
     * @return Response
     */

    public function reopenActivity(Request $request)
    {
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters(["activity"], $data);
        $this->verifyCode($data["activity"]);
        $code = $data["activity"];
        $activity = $this->dm->getRepository(Activity::class)->findOneBy(["code" => $code]);

        if (is_null($activity)) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_NOT_FOUND,
                    "No se encontró la actividad",
                    "No se encontró la actividad"
                )
            );
        }

        if ($this->getUser()->getGoogleId() !== $activity->getAuthor()) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_FORBIDDEN,
                    "La actividad no pertenece al usuario",
                    "La actividad no pertenece al usuario"
                )
            );
        }

        $activity->setClosed(false);

        $this->dm->persist($activity);
        $this->dm->flush();

        return $this->handleView($this->view($activity));
    }
}
