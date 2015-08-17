<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Gearman;


use Doctrine\Common\Collections\ArrayCollection;

interface WorkerAwareInterface
{
    public function getWorker();

    public function setWorker(\GearmanWorker $worker);

    public function addFunction($name, callable $callback);

    /** @return ArrayCollection */
    public function getFunctions();
} 