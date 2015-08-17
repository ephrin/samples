<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons;


use Acme\Component\Daemons\StateConditions\DaemonStateConditionInterface;
use Acme\Component\Daemons\Suppressor\SuppressionInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;

class AbstractDaemonConfiguration
{
    /**
     * @var ArrayCollection|DaemonStateConditionInterface[]
     */
    private $conditions;


    private $hasConditions = [];

    /**
     * @var SuppressionInterface
     */
    private $suppression;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $groupName;

    private $collectErrors = false;

    private $runnerIdentifier;


    function __construct()
    {
        $this->conditions = new ArrayCollection();
        $this->runnerIdentifier = (string)new \MongoId();
    }

    public function addTerminationCondition(DaemonStateConditionInterface $condition)
    {
        if (!$this->conditions->contains($condition)) {
            $this->conditions[] = $condition;
        }
    }

    public function hasCondition($conditionName)
    {
        if (array_key_exists($conditionName, $this->hasConditions)) {
            return $this->hasConditions[$conditionName];
        }

        foreach ($this->conditions as $condition) {
            if ($condition->hasCondition($conditionName)) {
                return $this->hasConditions[$conditionName] = true;
            }
        }

        return $this->hasConditions[$conditionName] = false;
    }

    public function hasSuppression()
    {
        return isset($this->suppression);
    }


    /**
     * @return DaemonStateConditionInterface[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }


    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return boolean
     */
    public function getCollectErrors()
    {
        return $this->collectErrors;
    }

    /**
     * @param boolean $collectErrors
     */
    public function setCollectErrors($collectErrors)
    {
        $this->collectErrors = $collectErrors;
    }

    /**
     * @return \Acme\Component\Daemons\Suppressor\SuppressionInterface
     */
    public function getSuppression()
    {
        return $this->suppression;
    }


    /**
     * @param SuppressionInterface $suppressionAdapter
     * @param $suppressionGroup
     */
    public function setSuppression(
        SuppressionInterface $suppressionAdapter,
        $suppressionGroup = SuppressionInterface::GROUP_DEFAULT
    ) {
        $this->suppression = $suppressionAdapter;
        $this->groupName = $suppressionGroup;
    }

    /**
     * @return mixed
     */
    public function getRunnerIdentifier()
    {
        return $this->runnerIdentifier;
    }

    /**
     * @return mixed
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

} 