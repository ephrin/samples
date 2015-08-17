<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons;


use Acme\Component\Daemons\Gearman\WorkerDaemonIteration;
use Acme\Component\Daemons\StateConditions\DaemonStateConditionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractDaemon implements DaemonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;


    private $identifier;

    /**
     * @var \Acme\Component\Daemons\Gearman\WorkerDaemonConfiguration
     */
    private $configuration;

    /**
     * @var \Acme\Component\Daemons\Suppressor\SuppressionInterface|null
     */
    private $suppression;


    /**
     * @var AbstractDaemonIteration[]
     */
    private $iterations = [];

    /**
     * @var WorkerDaemonIteration
     */
    private $currentIteration;

    function __construct(AbstractDaemonConfiguration $configuration)
    {
        $this->configuration = $configuration;


        if ($logger = $configuration->getLogger()) {
            $this->setLogger($logger);
        } else {
            $this->setLogger(new NullLogger());
        }

        $this->identifier = $this->configuration->getRunnerIdentifier();

        $this->initialize();
    }


    protected function setCurrentIteration(AbstractDaemonIteration $iteration)
    {
        $this->currentIteration = $iteration;
        $this->iterations[] = $iteration;
        return $this;
    }

    public function getCurrentIteration()
    {
        return $this->currentIteration;
    }


    public function getLastError()
    {
        if (!$this->currentIteration) {
            return null;
        }
        return $this->currentIteration->getError();
    }


    public function checkMatchedConditions()
    {
        foreach ($this->configuration->getConditions() as $conditionStatement) {
            if ($conditionStatement->handle($this)) {
                $this->getLogger()->info(
                    'Terminating because condition match occurs',
                    ['matched' => $conditionStatement->getLastMatched()]
                );
                $this->terminate($conditionStatement->getExitCode());
            }
        }
    }

    protected function suppressListen()
    {
        if (!$this->suppression) {
            if ($this->configuration->hasSuppression()) {
                $this->suppression = $this->configuration->getSuppression();
            } else {
                return;
            }
        }

        if ($this->suppression->isSuppressing($this->identifier)) {
            $this->getLogger()->alert('Terminated by outer suppression');
            $this->terminate(SIGTERM);
        }
    }

    public function hasCondition($conditionName)
    {
        return $this->configuration->hasCondition($conditionName);
    }


    public function getLastException()
    {
        if ($this->currentIteration) {
            return $this->currentIteration->getException();
        }
        return null;
    }

    public function getLastReturnValue()
    {
        if ($this->currentIteration) {
            return $this->currentIteration->getReturnValue();
        }
        return null;
    }

    /**
     * @param $message
     * @throws \RuntimeException
     */
    public function setError($message)
    {
        if (!$this->currentIteration) {
            throw new \RuntimeException('Could not setError while not running anything');
        }

        if (!$this->currentIteration->getError()) {
            $this->currentIteration->setError($message);
            $this->getLogger()->error('Error occurred while running:' . $message);
        } else {
            throw new \RuntimeException('JobRunner can not handle more than one error per call.
            You may use JobRunnerInterface $jobRunner->getLogger()->error($message) in callback instead.');
        }
    }


    /**
     * @return \Acme\Component\Daemons\AbstractDaemonConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return array
     */
    public function getIterations()
    {
        return $this->iterations;
    }


    public function getIterationsCount()
    {
        return count($this->iterations);
    }


    public function terminate($code = SIGTERM)
    {
        if ($this->suppression) {
            $this->suppression->remove($this->identifier);
        }
        exit($code);
    }

    /**
     * @return mixed
     */
    public function getExecutionFailuresCount()
    {
        return count(
            array_filter(
                $this->iterations,
                function (WorkerDaemonIteration $e) {
                    return $e->isSuccess();
                }
            )
        );
    }

    public function getExecutionErrorsCount()
    {
        return count(
            array_filter(
                $this->iterations,
                function (WorkerDaemonIteration $e) {
                    return $e->hasError();
                }
            )
        );
    }

    protected function handleException(\Exception $exception)
    {
        $handled = [];
        foreach ($this->configuration->getConditions() as $conditionSet) {
            $handled[] = $conditionSet->handleCustom(
                DaemonStateConditionInterface::CONDITION_CALLBACK_EXCEPTION,
                $exception,
                $this
            );
        }

        return !empty(array_filter($handled));
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }


    abstract public function listen();

    public function initialize()
    {
    }
} 