<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\StateConditions;


use Acme\Component\Daemons\DaemonInterface;
use Byte\ByteConverter;

class ConfigurableConditions implements DaemonStateConditionInterface
{

    private $conditions;
    private $exitOnMatch = false;
    private $exitCode = SIGQUIT; //non restartable codes - 0,9,15
    private $initTime = 0;
    private $initMem = 0;

    private $prevMaxMemUsage = 0;

    const GTE = 1;
    const CALLBACK = 2;
    const GTE_OR_CALLBACK = 3;


    private $type = self::TYPE_OR;

    private $lastMatched = [];

    protected static $supportedConditions = [
        self::CONDITION_CALLBACK_EXCEPTION => self::CALLBACK,
        self::CONDITION_RETURN_VALUE => self::GTE_OR_CALLBACK,
        self::CONDITION_ERROR_COUNT => self::GTE_OR_CALLBACK,
        self::CONDITION_ITERATION_COUNT => self::GTE_OR_CALLBACK,
        self::CONDITION_SECONDS_RUN_COUNT => self::GTE_OR_CALLBACK,
        self::CONDITION_FAILURE_COUNT => self::GTE_OR_CALLBACK,
        self::CONDITION_MEMORY_USAGE => self::GTE,
        self::CONDITION_MEMORY_USAGE_DIFF => self::GTE,
        self::CONDITION_PEAK_MEMORY_USAGE => self::GTE,
        self::CONDITION_MEMORY_LEAK => self::CALLBACK,
    ];

    protected static $supportedTypes = [
        self::TYPE_AND,
        self::TYPE_OR,
        self::TYPE_XOR
    ];

    function __construct(array $conditions = null, $exitCode = null, $exitOnMatch = null)
    {
        if (null !== $conditions) {
            $this->setConditions($conditions);
        }

        if (null !== $exitCode) {
            $this->setExitCode($exitCode);
        }

        if ($exitOnMatch) {
            $this->setExitOnMatch(true);
        }

    }

    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        $rc = self::CONDITION_SECONDS_RUN_COUNT;
        if (isset($this->conditions[$rc])) {
            $this->initTime = time();
        }

        $convertBytes = [
            self::CONDITION_MEMORY_USAGE_DIFF,
            self::CONDITION_MEMORY_USAGE,
            self::CONDITION_PEAK_MEMORY_USAGE
        ];

        foreach ($convertBytes as $n) {
            if (isset($this->conditions[$n])) {
                if (is_scalar($this->conditions[$n])) {
                    //convert string representation of mem size
                    $this->conditions[$n] = (new ByteConverter())->getBytes($this->conditions[$n]);
                }
                if (!$this->initMem) {
                    $this->initMem = memory_get_usage(1);
                }
            }
        }


        $typeException = function ($name, $type, $got) {
            $type = (array)$type;
            throw new \InvalidArgumentException(
                sprintf(
                    'Condition %s must be %s"%s" type%s. Got "%s"',
                    $name,
                    count($type) > 1 ? 'one of ' : '',
                    implode('" or "', $type),
                    count($type) > 1 ? 's' : '',
                    gettype($got)
                )
            );
        };

        //checking conditions
        foreach ($this->conditions as $name => $condition) {
            if (isset(self::$supportedConditions[$name])) {
                $type = self::$supportedConditions[$name];
                switch ($type) {
                    case self::CALLBACK:
                        if (!$condition instanceof \Closure) {
                            $typeException($name, ['\Closure'], $condition);
                        }
                        break;
                    case self::GTE:
                        if (!is_numeric($condition)) {
                            $typeException($name, ['numeric'], $condition);
                        }
                        break;
                    case self::GTE_OR_CALLBACK:
                        if (!is_numeric($condition) && !($condition instanceof \Closure)) {
                            $typeException($name, ['numeric', '\Closure'], $condition);
                        }
                        break;
                    default:
                        throw new \RuntimeException('Unsupported condition argument type %s');
                }
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Condition with name %s is not supported', $name)
                );
            }
        }


        //internal - memory leak condition
        $this->conditions[self::CONDITION_MEMORY_LEAK] = function (
            $isCritical,
            DaemonInterface $runner /*,
            JobConditionInterface $terminationCondition*/
        ) {
            if ($isCritical) {
                $runner->getLogger()->debug('Next iteration will exhaust memory limit. Terminating.');
                $runner->terminate($this->getExitCode());
            }
        };


    }

    private function conditionArgument($conditionName, DaemonInterface $runner)
    {
        switch ($conditionName) {
            case self::CONDITION_CALLBACK_EXCEPTION:
                return $runner->getLastException();
            case self::CONDITION_MEMORY_USAGE:
                return memory_get_usage(1);
            case self::CONDITION_MEMORY_USAGE_DIFF:
                return $this->initMem - memory_get_usage(1);
            case self::CONDITION_SECONDS_RUN_COUNT:
                return time() - $this->initTime;
            case self::CONDITION_ITERATION_COUNT:
                return $runner->getIterationsCount();
            case self::CONDITION_ERROR_COUNT:
                return $runner->getExecutionErrorsCount();
            case self::CONDITION_FAILURE_COUNT:
                return $runner->getExecutionFailuresCount();
            case self::CONDITION_RETURN_VALUE:
                return $runner->getLastReturnValue();
            case self::CONDITION_PEAK_MEMORY_USAGE:
                return memory_get_peak_usage(1);
            case self::CONDITION_MEMORY_LEAK:
                return $this->memoryLeakCheck();
            default:
                return null;
        }
    }

    /**
     * @param $conditionName
     * @return boolean
     */
    public function hasCondition($conditionName)
    {
        return array_key_exists($conditionName, $this->conditions);
    }

    public function handleCustom($conditionName, $argument, DaemonInterface $runner)
    {
        if (isset($this->conditions[$conditionName])) {
            $condition = $this->conditions[$conditionName];
            $type = self::$supportedConditions[$conditionName];
            switch ($type) {
                case self::GTE_OR_CALLBACK:
                    if (is_callable($condition)) {
                        if (call_user_func_array($condition, [$argument, $runner, $this])) {
                            return true;
                        }
                        break;
                    }
                    if (is_numeric($condition)) {
                        if ($argument >= $condition) {
                            return true;
                        }
                        break;
                    }
                    break;
                case self::GTE:
                    if ($argument >= $condition) {
                        return true;
                    }
                    break;
                case self::CALLBACK:
                    if (call_user_func_array($condition, [$argument, $runner, $this])) {
                        return true;
                    }
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported type of condition');
            }
        }

        return false;
    }


    public function handle(DaemonInterface $runner, $exitOnCondition = null)
    {

        $this->lastMatched = [];

        if (null === $exitOnCondition && $this->exitOnMatch) {
            $exitOnCondition = true;
        }

        foreach ($this->conditions as $conditionName => $condition) {
            $argument = $this->conditionArgument($conditionName, $runner);
            if (null === $argument) {
                continue;
            }

            if ($this->handleCustom($conditionName, $argument, $runner)) {
                $this->lastMatched[] = $conditionName;
            }
        }

        if ($this->typeMatch($this->lastMatched)) {
            if ($exitOnCondition) {
                $runner->getLogger()->info('Exiting while condition match', $this->lastMatched);
                $runner->terminate($this->getExitCode());
            }
            return true;
        } else {
            return false;
        }

    }


    public function memoryLeakCheck()
    {
        $currentPeakUsage = memory_get_peak_usage(1);
        if (!$this->prevMaxMemUsage) {
            $this->prevMaxMemUsage = $currentPeakUsage;
            return false;
        } else {
            $allowed = (new ByteConverter())->getBytes(ini_get('memory_limit'));

            $diff = $currentPeakUsage - $this->prevMaxMemUsage;

            return ($currentPeakUsage + $diff) >= $allowed;

        }
    }

    private function typeMatch($matched)
    {
        switch ($this->getType()) {
            case self::TYPE_OR:
                return count($matched);
            case self::TYPE_AND:
                return count($matched) == count($this->conditions);
            case self::TYPE_XOR:
                $flag = false;
                foreach ($this->conditions as $conditionName => $v) {
                    $flag = ($flag xor (in_array($conditionName, $matched)));
                }
                return $flag;
            default:
                throw new \RuntimeException('Unsupported type of criteria statements set');
        }
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }


    /**
     * @param int $SIG_CODE
     */
    public function setExitCode($SIG_CODE)
    {
        $this->exitCode = $SIG_CODE;
    }

    /**
     * @param boolean $exitOnMatch
     */
    public function setExitOnMatch($exitOnMatch)
    {
        $this->exitOnMatch = (bool)$exitOnMatch;
    }

    /**
     * @return array
     */
    public function getLastMatched()
    {
        return $this->lastMatched;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @throws \InvalidArgumentException
     */
    public function setType($type)
    {
        if (in_array($type, self::$supportedTypes)) {
            $this->type = $type;
        } else {
            throw new \InvalidArgumentException('Unsupported type');
        }
    }


}