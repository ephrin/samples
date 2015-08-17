<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons;


abstract class AbstractDaemonIteration
{
    private $error = null;
    private $exception = null;
    private $returnValue = null;
    private $closure;
    private $name;

    /**
     * @param $name
     * @param callable $closure
     */
    function __construct($name, \Closure $closure)
    {
        $this->name = $name;
        $this->closure = $closure;
    }

    /**
     * @param bool $strict
     * @return bool
     */
    public function isSuccess($strict = false)
    {
        return (!$this->exception && !$this->error && ($strict && $this->returnValue || !$strict));
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    public function hasError()
    {
        return (bool)$this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }

    /**
     * @param mixed $returnValue
     */
    public function setReturnValue($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    /**
     * @return callable
     */
    public function getClosure()
    {
        return $this->closure;
    }

    public function execute()
    {
        try {
            $this->returnValue = call_user_func($this->closure);
        } catch (\Exception $e) {
            $this->setException($e);
            throw $e;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

} 