<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Gearman;


use Acme\Component\Daemons\AbstractDaemonConfiguration;
use Doctrine\Common\Collections\ArrayCollection;

class WorkerDaemonConfiguration extends AbstractDaemonConfiguration implements WorkerAwareInterface
{

    /**
     * @var \GearmanWorker
     */
    private $worker;

    /**
     * @var ArrayCollection
     */
    private $functions;


    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @param \GearmanWorker $worker
     */
    public function setWorker(\GearmanWorker $worker)
    {
        $this->worker = $worker;
    }


    public function addFunction($name, callable $callback)
    {
        if (!$this->functions) {
            $this->functions = new ArrayCollection();
        }
        $this->functions[$name] = $callback;
    }


    /**
     * @return ArrayCollection
     */
    public function getFunctions()
    {
        return $this->functions;
    }

}