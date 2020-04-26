<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Document\Activity;
use App\Document\Entry;
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
 * @Route("/results")
 */
class ResultsController extends AbstractFOSRestController
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
        if($activity = $this->dm->getRepository(Activity::class)->findBy(["code" => $code])){
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

            foreach ($data["results"] as $taskCode) {
                $activity->addTask($taskCode);
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

    private function getEntry($code, $responses, $columns)
    {
        $entry = new Entry();
        $entry->setCode($code);
        foreach ($responses as $response) {
            $this->checkRequiredParameters(["code", "result"], $response);
            $taskCode = $response["code"];
            $this->verifyCode($taskCode);
            if (array_search($taskCode, $columns) !== false) {
                $resp = [$taskCode => $response["result"]];
                $entry->addResponse($resp);
            }
        }
        return $entry;
    }

    private function verifyCode($code)
    {
        if ((strlen($code) !== 64)|| (preg_match("/[0-9a-z]/", $code) !== 1)) {
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
     * @Rest\Post(name="post_results")
     * 
     * @return Response
     */
    public function postResultsAction(Request $request = null)
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

    /**
     * Get results for an activity
     * @Rest\Get("/{code}", name="get_results")
     * 
     * @return Response
     */
    public function getResultsForActivity($code)
    {
        $this->verifyCode($code);
        $entries = $this->dm->getRepository(Entry::class)->findBy(["code" => $code]);
        return $this->handleView($this->view(["results" => $entries]));
    }
}
