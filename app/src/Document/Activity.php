<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Activity
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $code;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $author;

    /**
     * @MongoDB\Field(type="boolean")
     */
    protected $closed;

    /**
     * @MongoDB\Field(type="collection")
     */
    protected $tasks;

    public function __construct()
    {
        $this->tasks = [];
        $this->closed = false;
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

    public function addTask(array $task): self
    {
        $this->tasks[] = $task;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getClosed(): bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;
        return $this;
    }
}
