<?php

namespace App\Controller\v1\pub;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Document\Activity;
use App\Document\Entry;
use App\Document\Type;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use GuzzleHttp\Exception\RequestException;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/entries")
 */
class PublicEntriesController extends AbstractFOSRestController
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

    private function getActivityColumns($code)
    {
        if ($activity = $this->dm->getRepository(Activity::class)->findOneBy(["code" => $code])) {
            if ($activity->getClosed()) {
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_FORBIDDEN,
                        "La actividad está cerrada",
                        "La actividad está cerrada"
                    )
                );
            }
            return $activity;
        }

        $activity = new Activity();
        $activity->setCode($code);

        $client = new \GuzzleHttp\Client(
            [
                'base_uri' => $_ENV["DEFINE_BASE_URL"]
            ]
        );
        try {
            $response = $client->get(sprintf("/api/v1.0/public/actividades/%s/columns", $code));
            $data = json_decode((string) $response->getBody(), true);

            if (
                !array_key_exists("results", $data) || !is_array($data["results"])
                || !array_key_exists("author", $data) || is_null($data["author"])
            ) {
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        "No se pudo obtener la definición de la actividad",
                        "No se pudo guardar la respuesta"
                    )
                );
            }
            $activity->setAuthor($data["author"]);
            foreach ($data["results"] as $taskArray) {
                if (array_key_exists("code", $taskArray) && array_key_exists("type", $taskArray)) {
                    $activity->addTask(["code" => $taskArray["code"], "type" => $taskArray["type"]]);
                } else {
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_INTERNAL_SERVER_ERROR,
                            "No se pudo obtener la definición de la tarea",
                            "No se pudo guardar la respuesta"
                        )
                    );
                }
            }

            $this->dm->persist($activity);
            $this->dm->flush();
            return $activity;
        } catch (Exception $e) {
            if ($e instanceof RequestException) {
                $response = $e->getResponse();
                if (!is_null($response)) {
                    $data = json_decode((string) $response->getBody(), true);
                    throw new ApiProblemException(
                        new ApiProblem(
                            $response->getStatusCode(),
                            $data["developer_message"],
                            $data["user_message"]
                        )
                    );
                } else {
                    $this->logger->error($e->getMessage());
                    throw new ApiProblemException(
                        new ApiProblem(
                            Response::HTTP_INTERNAL_SERVER_ERROR,
                            "Ocurrió un error al buscar la activity",
                            "Ocurrió un error"
                        )
                    );
                }
            } else {
                $this->logger->error($e->getMessage());
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        "Ocurrió un error al buscar la actividad",
                        "Ocurrió un error"
                    )
                );
            }
        }
    }

    private function filterResult($result, $type)
    {
        switch ($type) {
            case Type::SIMPLE:
                if (($result === "true")) {
                    return $result;
                } else {
                    return null;
                }
            case Type::TEXT_INPUT:
            case Type::AUDIO_INPUT:
                if (!is_array($result)) {
                    return $result;
                } else {
                    return null;
                }
            case Type::NUMBER_INPUT:
                if (!is_array($result) && ($result == "0" || (intval($result) != 0))) {
                    return $result;
                } else {
                    return null;
                }
            case Type::CAMERA_INPUT:
                if (is_array($result)) {
                    return $result;
                } else {
                    return null;
                }
            case Type::SELECT:
                if (!is_array($result)) {
                    return $result;
                } else {
                    return null;
                }
            case Type::MULTIPLE:
            case Type::COLLECT:
            case Type::DEPOSIT:
            case Type::COUNTERS:
                if (is_array($result)) {
                    foreach ($result as $elem) {
                        if (is_array($elem)) {
                            return null;
                        }
                    }
                    return $result;
                } else {
                    return null;
                }
            case Type::GPS_INPUT:
                if (is_array($result) && array_key_exists("type", $result) && array_key_exists("data", $result)) {
                    return ["type" => $result["type"], "data" => $result["data"]];
                } else {
                    return null;
                }
            default:
                break;
        }
    }

    private function getEntry($code, $responses, $tasks)
    {
        $entry = new Entry();
        $entry->setCode($code);
        $columns = array_map(function ($elem) {
            return $elem["code"];
        }, $tasks);
        foreach ($responses as $response) {
            $this->checkRequiredParameters(["code", "result"], $response);
            $taskCode = $response["code"];
            $this->verifyCode($taskCode);
            if (($index = array_search($taskCode, $columns)) !== false) {
                $this->filterResult($response["result"], $tasks[$index]["type"]);
                $resp = [$taskCode => $response["result"]];
                if ($resp === null) {
                    continue;
                }
                $entry->addResponse($resp);
            }
        }
        return $entry;
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
     * Store results
     * @Rest\Post(name="post_entries")
     * 
     * @return Response
     */
    public function postEntryAction(Request $request = null)
    {
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters(["code", "responses"], $data);
        $code = $data["code"];

        $this->verifyCode($code);

        $responses = $data["responses"];

        $tasks = $this->getActivityColumns($code)->getTasks();

        $entry = $this->getEntry($code, $responses, $tasks);

        $this->dm->persist($entry);
        $this->dm->flush();

        return $this->handleView($this->view($entry));
    }
}
