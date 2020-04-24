<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Entry {
    /**
     * @MongoDB\Id
     */
    protected $id;
    
    /**
     * @MongoDB\Field(type="string")
     */
    protected $code;

    /**
     * @MongoDB\Field(type="collection")
     */
    protected $responses;

    public function __construct()
    {
        $this->responses = [];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(String $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function addResponse(array $response): self
    {
        $this->responses[] = $response;
        return $this;
    }
}