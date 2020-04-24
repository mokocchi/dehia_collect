<?php

namespace App\Controller\v1;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
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

    public function __construct(LoggerInterface $logger, SerializerInterface $serializer)
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
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

    private function getActivityColumns($code) {
        $client = new \GuzzleHttp\Client(
            [
                'base_uri' => $_ENV["DEFINE_BASE_URL"]
            ]
        );
        try {
            $response = $client->get(sprintf("/api/v1.0/public/actividades/%s/columns", $code));
            return json_decode((string) $response->getBody(), true)["results"];
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
                            "Ocurrió un error al buscar la actividad",
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

    /**
     * Store results
     * @Rest\Post(name="post_results")
     * 
     * @return Response
     */
    public function getResultsAction(Request $request = null)
    {
        $data = $this->getJsonData($request);
        $this->checkRequiredParameters(["code", "responses"], $data);
        $code = $data["code"];
        $code = strip_tags(stripslashes($code));
        $responses = $data["responses"];

        $columns = $this->getActivityColumns($code);

        $entry = [];
        foreach ($responses as $response) {
            $this->checkRequiredParameters(["code", "response"], $response);
            $taskCode = $response["code"];
            if (array_search($taskCode, $columns) !== false) {
                $entry[$taskCode] = $response["response"];
            }
        }
        return $this->handleView($this->view(["data" => $entry]));
    }
}
