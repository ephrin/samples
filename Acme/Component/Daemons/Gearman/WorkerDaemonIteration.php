<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Gearman;


use Acme\Component\Daemons\AbstractDaemonIteration;

class WorkerDaemonIteration extends AbstractDaemonIteration
{

    private $job;


    /**
     * @return mixed
     */
    public function getJob()
    {
        return $this->job;
    }

    public function setJob(\GearmanJob $job)
    {
        $this->job = $job;
    }


}