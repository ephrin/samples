<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */
namespace Acme\Component\Daemons;

use Psr\Log\LoggerInterface;

interface DaemonInterface
{

    public function listen();

    public function getExecutionErrorsCount();

    public function setLogger(LoggerInterface $logger);

    /**
     * @return LoggerInterface
     */
    public function getLogger();

    /**
     * @return integer
     */
    public function getExecutionFailuresCount();

    /**
     * @return array
     */
    public function getIterations();

    /**
     * @return integer
     */
    public function getIterationsCount();

    public function terminate($code = SIGTERM);

    /**
     * @return \Acme\Component\Daemons\Gearman\WorkerDaemonConfiguration
     */
    public function getConfiguration();

    /**
     * @return mixed
     */
    public function getLastException();

    /**
     * @return mixed
     */
    public function getLastReturnValue();

    public function setError($message);

    public function getLastError();
}