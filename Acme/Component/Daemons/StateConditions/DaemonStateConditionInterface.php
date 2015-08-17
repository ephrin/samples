<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\StateConditions;


use Acme\Component\Daemons\DaemonInterface;

interface DaemonStateConditionInterface
{

    const CONDITION_ITERATION_COUNT = 'iterationCount';
    const CONDITION_ERROR_COUNT = 'errorsCount'; //callback has set an error message to JobRunner
    const CONDITION_FAILURE_COUNT = 'failuresCount'; //callback returns false
    const CONDITION_SUCCESS_COUNT = 'successCount'; //callback returns something not false
    const CONDITION_SECONDS_RUN_COUNT = 'runSecond';
    const CONDITION_PEAK_MEMORY_USAGE = 'memPeakUsage';
    const CONDITION_MEMORY_USAGE_DIFF = 'memDiffUsage';
    const CONDITION_MEMORY_LEAK = 'memLeak';
    const CONDITION_MEMORY_USAGE = 'memUsage';
    const CONDITION_CALLBACK_EXCEPTION = 'callbackException';
    const CONDITION_RETURN_VALUE = 'returnValue';


    const TYPE_OR = 'or';
    const TYPE_XOR = 'xor';
    const TYPE_AND = 'and';

    public function handle(DaemonInterface $runner, $exitOnCondition = false);

    public function handleCustom($conditionName, $argument, DaemonInterface $runner);

    public function getExitCode();


    public function setExitCode($SIG_CODE);

    /**
     * @return array
     */
    public function getLastMatched();

    /**
     * @param $conditionName
     * @return boolean
     */
    public function hasCondition($conditionName);

    public function getType();
} 