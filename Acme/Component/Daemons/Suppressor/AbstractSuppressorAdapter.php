<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Suppressor;


abstract class AbstractSuppressorAdapter implements SuppressionInterface
{
    abstract protected function getStatus($identifier);

    abstract protected function getGroup($identifier);

    abstract protected function getGroupIdentifiers($group = self::GROUP_DEFAULT);
}