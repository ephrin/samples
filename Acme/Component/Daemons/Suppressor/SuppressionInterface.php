<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Suppressor;


interface SuppressionInterface
{
    const GROUP_DEFAULT = 'default';
    const RECORD_TYPE_KEYWORD = '_suppression_aware_record';
    const STATUS_SUPPRESSING = 'suppressing';
    const STATUS_SUPPRESSED = 'suppressed';
    const STATUS_OK = 'ok';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';

    function subscribe($identifier, $group = self::GROUP_DEFAULT);

    function isSubscribed($identifier);

    function suppress($identifier);

    function suppressGroup($group = self::GROUP_DEFAULT);

    function isGroupSuppressed($group = self::GROUP_DEFAULT);

    function isSuppressed($identifier);

    function setSuppressed($identifier);

    function isSuppressing($identifier);

    function remove($identifier);

} 