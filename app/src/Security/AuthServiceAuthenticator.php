<?php

namespace App\Security;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiProblemResponseFactory;
use App\Document\Autor;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class AuthServiceAuthenticator extends AbstractGuardAuthenticator
{
    private $apiProblemResponseFactory;
    private $logger;
    private $client;

    public function __construct(ApiProblemResponseFactory $apiProblemResponseFactory, LoggerInterface $logger, Client $client = null)
    {
        $this->apiProblemResponseFactory = $apiProblemResponseFactory;
        $this->logger = $logger;
        $this->client = $client ?: new \GuzzleHttp\Client(
            [
                'base_uri' => $_ENV["AUTH_BASE_URL"]
            ]
        );
    }

    public function supports(Request $request)
    {
        return $request->headers->has('Authorization')
            && 0 === strpos($request->headers->get('Authorization'), 'Bearer ');
    }

    public function getCredentials(Request $request)
    {
        return [$request->headers->get('Authorization'), $request->headers->get('X-Authorization-OAuth')];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials[0]) {
            throw new ApiProblemException(
                new ApiProblem(
                    "400",
                    "No hay credenciales",
                    "Ocurrió un error"
                )
            );
        }

        try {
            if ($credentials[1]) {
                //OAuth flow
                $oauth = true;
                $response = $this->client->get("/api/validate", ["headers" => ["Authorization" => $credentials[0]]]);
            } else {
                //JWT only flow
                $oauth = false;
                $response = $this->client->get("/api/v1.0/users/me", ["headers" => ["Authorization" => $credentials[0]]]);
            }
            $data = json_decode((string) $response->getBody(), true);
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
                            "Ocurrió un error en la autenticación",
                            "Ocurrió un error"
                        )
                    );
                }
            } else {
                $this->logger->error($e->getMessage());
                throw new ApiProblemException(
                    new ApiProblem(
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        "Ocurrió un error en la autenticación",
                        "Ocurrió un error"
                    )
                );
            }
        }

        if (!in_array("ROLE_AUTOR", $data["roles"])) {
            throw new ApiProblemException(
                new ApiProblem(
                    Response::HTTP_FORBIDDEN,
                    "El token no pertenece a un autor",
                    "Ocurrió un error en la autenticación"
                )
            );
        }

        $autor = new Autor();
        $autor->setGoogleid($data["googleid"]);

        foreach ($data["roles"] as $role) {
            $autor->addRole($role);
        }
        $autor->setOauth($oauth);
        $autor->setToken($credentials[0]);

        return $autor;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->apiProblemResponseFactory->createResponse(new ApiProblem(
            "500",
            "Ocurrió un error en la autenticación",
            "Ocurrió un error"
        ));
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->apiProblemResponseFactory->createResponse(new ApiProblem(
            "401",
            "Se requiere autenticación",
            "No autorizado"
        ));
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
