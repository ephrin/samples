<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Custom;

use Acme\Component\Daemons\AbstractDaemonConfiguration;

class CustomDaemonConfiguration extends AbstractDaemonConfiguration
{
    /** @var callable|null */
    private $listener = null;

    /** @var callable|null */
    private $sleeper = null;

    /** @var callable|null */
    private $callback = null;

    /**  @return callable */
    public function getListener()
    {
        return $this->listener;
    }

    /** @param callable $listener */
    public function setListener(callable $listener)
    {
        $this->listener = $listener;
    }

    /**  @return bool */
    public function hasListener()
    {
        return isset($this->listener);
    }

    /** @return callable */
    public function getSleeper()
    {
        return $this->sleeper;
    }

    /** @param callable $sleeper */
    public function setSleeper(callable $sleeper)
    {
        $this->sleeper = $sleeper;
    }

    /** @return bool */
    public function hasSleeper()
    {
        return isset($this->sleeper);
    }

    /** @return callable|null */
    public function getCallback()
    {
        return $this->callback;
    }

    /** @param callable|null $callback */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /** @return bool */
    public function hasCallback()
    {
        return isset($this->callback);
    }
}