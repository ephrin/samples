<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Gearman;

use Acme\Component\Daemons\AbstractDaemon;
use Acme\Component\Daemons\StateConditions\DaemonStateConditionInterface;

class WorkerDaemon extends AbstractDaemon
{
    /**
     * @var \GearmanWorker
     */
    private $worker;


    public function initialize()
    {
        $this->worker = $this->getConfiguration()->getWorker();
    }


    private function wrap(callable $callback, $name)
    {
        return function (\GearmanJob $job) use ($callback, $name) {

            $this->suppressListen();

            $function = function () use ($job, $callback) {
                return call_user_func_array($callback, [$job, $this]);
            };

            $this->setCurrentIteration(new WorkerDaemonIteration($name, $function));

            try {

                $this->getCurrentIteration()->execute();

            } catch (\Exception $exception) {

                if (!$this->hasCondition(DaemonStateConditionInterface::CONDITION_CALLBACK_EXCEPTION)) {
                    $this->getLogger()->critical($exception->getMessage(), ['exception' => $exception]);
                    throw $exception;
                } else {
                    if (!$this->handleException($exception)) {
                        throw $exception;
                    }
                    $this->getCurrentIteration()->setException($exception);
                }
            }
        };
    }

    /**
     * @return \Acme\Component\Daemons\Gearman\WorkerDaemonConfiguration
     */
    public function getConfiguration()
    {
        return parent::getConfiguration();
    }


    function listen()
    {
        foreach ($this->getConfiguration()->getFunctions() as $functionName => $callable) {
            $this->worker->addFunction($functionName, $this->wrap($callable, $functionName));
        }

        $this->suppressListen();

        $started = time();

        while ($this->worker->work()) {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS) {
                $this->getLogger()->error('Gearman success fail with code:' . $this->worker->returnCode());
                $this->terminate(SIGTERM);
            }

            $this->suppressListen();

            $this->checkMatchedConditions();

            if (time() - $started < 1) {
                sleep(1);
            }
        }

    }


}
