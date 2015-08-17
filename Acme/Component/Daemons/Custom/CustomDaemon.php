<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Custom;


use Acme\Component\Daemons\AbstractDaemon;
use Acme\Component\Daemons\StateConditions\DaemonStateConditionInterface;
use Psr\Log\LoggerAwareTrait;

class CustomDaemon extends AbstractDaemon
{
    use LoggerAwareTrait;

    const DEFAULT_USLEEP = 250;

    public function listen()
    {
        /** @var \Acme\Component\Daemons\Custom\CustomDaemonConfiguration $configuration */
        $configuration = $this->getConfiguration();

        $sleeper = $configuration->hasSleeper() ? $configuration->getSleeper() : $this->getDefaultSleeper();

        $listener = $configuration->hasListener() ? $configuration->getListener() : $this->getDefaultListener();

        if ($configuration->hasCallback()) {
            $resultCallback = $this->wrap($configuration->getCallback(), $configuration->getGroupName());
        } else {
            $resultCallback = $this->getDefaultCallback($configuration->getGroupName());
        }


        results:
        if ($listenerResult = $listener($this)) {

            $resultCallback($listenerResult);

            $this->suppressListen();

            $this->checkMatchedConditions();

            $sleeper($this);
            goto results;
        }

    }

    public function getDefaultSleeper($seconds = null, $microseconds = null)
    {
        $t = ((int)$seconds * 1000000) + (int)$microseconds;

        if ($t == 0) {
            $t = self::DEFAULT_USLEEP;
        }

        return function () use ($t) {
            usleep($t);
        };
    }

    public function getDefaultListener()
    {
        return function () {
            return true;
        };
    }

    private function wrap(callable $callback, $name)
    {
        return function ($listenerResult) use ($callback, $name) {

            $this->suppressListen();

            $function = function () use ($listenerResult, $callback) {
                return call_user_func_array($callback, [$listenerResult, $this]);
            };

            $this->setCurrentIteration(new CustomDaemonIteration($name, $function));

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
     * @param $name
     * @return callable
     */
    private function getDefaultCallback($name)
    {
        return $this->wrap(
            function () use ($name) {
                return 'void:' . $name;
            },
            $name
        );
    }

}