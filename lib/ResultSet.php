<?php

namespace Fazland\MailUpRestClient;


class ResultSet
{
    /**
     * @var Result[]
     */
    private $results;

    /**
     * ResultSet constructor.
     */
    public function __construct()
    {
        $this->results = [];
    }

    public function addResult(Result $result)
    {
        $this->results[] = $result;
    }

    /**
     * @return Result[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param Result[] $results
     *
     * @return $this
     */
    public function setResults(array $results): ResultSet
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->results->map(function ($result) {
            return $result->hasError();
        });
    }

    /**
     * @return bool
     */
    public function hasErrors():bool
    {
        return count($this->getErrors());
    }

    /**
     * @return array
     */
    public function getValidResults(): array
    {
        return $this->results->map(function($result) {
           return ! $result->hasError();
        });
    }

}