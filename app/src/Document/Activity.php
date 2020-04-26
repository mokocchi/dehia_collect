<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Activity {
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
    protected $tasks;

    public function __construct()
    {
        $this->tasks = [];
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

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function addTask(string $task): self
    {
        $this->tasks[] = $task;
        return $this;
    }
}