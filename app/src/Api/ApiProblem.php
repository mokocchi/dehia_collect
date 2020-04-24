<?php

namespace App\Api;

class ApiProblem
{
    private $status;

    private $developerMessage;

    private $userMessage;

    private $errorCode;

    private $moreInfo;

    public function __construct(string $status, string $developerMessage, string $userMessage, int $errorCode = 1)
    {
        $this->status = $status;
        $this->developerMessage = $developerMessage;
        $this->userMessage = $userMessage;
        $this->errorCode = $errorCode;
        $this->moreInfo = $_ENV["SITE_BASE_URL"] . '/api/doc';
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function getDeveloperMessage()
    {
        return $this->developerMessage;
    }

    public function getUserMessage()
    {
        return $this->userMessage;
    }

    public function toArray()
    {
        return [
            "status" => $this->status,
            "developer_message" => $this->developerMessage,
            "user_message" => $this->userMessage,
            "error_code" => $this->errorCode,
            "more_info" => $this->moreInfo
        ];
    }
}
