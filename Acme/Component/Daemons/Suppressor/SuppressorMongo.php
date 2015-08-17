<?php
/*
 * This file is part of Hotels24.ua project (c) 2008-2014.
 */

namespace Acme\Component\Daemons\Suppressor;


class SuppressorMongo extends AbstractSuppressorAdapter
{

    const FIELD_IDENTIFIER = 'identifier';
    const FIELD_TYPE = 'type';
    const FIELD_STATUS = 'status';
    const FIELD_GROUP = 'group';


    /**
     * @var \MongoCollection
     */
    private $collection;

    function __construct(\MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    function subscribe($identifier, $group = self::GROUP_DEFAULT)
    {
        $status = $this->getStatus($identifier);

        if ($status == self::STATUS_UNSUBSCRIBED) {
            $this->collection->insert(
                [
                    self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                    self::FIELD_IDENTIFIER => $identifier,
                    self::FIELD_GROUP => $group,
                    self::FIELD_STATUS => self::STATUS_OK
                ]
            );
            return true;
        } elseif ($status == self::STATUS_SUPPRESSED) {
            $this->collection->update(
                [
                    self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                    self::FIELD_IDENTIFIER => $identifier
                ],
                [
                    '$set' => [
                        self::FIELD_STATUS => self::STATUS_OK
                    ]
                ],
                [
                    'multiple' => false,
                ]
            );
            return true;
        } elseif ($status == self::STATUS_SUPPRESSING) {
            throw new \RuntimeException('Could not subscribe on identifier that currently suppressing on.');
        } elseif ($status == self::STATUS_OK) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $identifier
     * @return int
     */
    function isSubscribed($identifier)
    {
        return in_array(
            $this->getStatus($identifier),
            [self::STATUS_OK, self::STATUS_SUPPRESSED, self::STATUS_SUPPRESSING]
        );
    }

    function isSuppressed($identifier)
    {
        return in_array($this->getStatus($identifier), [self::STATUS_SUPPRESSED, self::STATUS_UNSUBSCRIBED]);
    }

    protected function getStatus($identifier)
    {
        $res = $this->collection->findOne(
            [
                self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                self::FIELD_IDENTIFIER => $identifier
            ]
        );

        if ($res) {
            return $res[self::FIELD_STATUS];
        } else {
            return self::STATUS_UNSUBSCRIBED;
        }
    }

    protected function getGroup($identifier)
    {
        $res = $this->collection->findOne(
            [
                self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                self::FIELD_IDENTIFIER => $identifier
            ]
        );
        if ($res) {
            return $res[self::FIELD_GROUP];
        } else {
            throw new \RuntimeException('Could not get group name. Identifier is not subscribed');
        }
    }

    protected function getGroupIdentifiers($group = self::GROUP_DEFAULT)
    {
        $identifiers = [];
        $query = [
            self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
            self::FIELD_GROUP => $group
        ];
        foreach ($this->collection->find($query, [self::FIELD_IDENTIFIER]) as $inGroup) {
            $identifiers[] = $inGroup[self::FIELD_IDENTIFIER];
        }
        return $identifiers;
    }

    function isGroupSuppressed($group = self::GROUP_DEFAULT)
    {
        return !$this->collection->find(
            [
                self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                self::FIELD_GROUP => $group,
                self::FIELD_STATUS => ['$ne' => self::STATUS_SUPPRESSED],
            ]
        )->count();
    }

    function isSuppressing($identifier)
    {
        return self::STATUS_SUPPRESSING == $this->getStatus($identifier);
    }

    function setSuppressed($identifier)
    {
        $result = $this->collection->update(
            [
                self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                self::FIELD_IDENTIFIER => $identifier,
            ],
            [
                '$set' => [
                    self::FIELD_STATUS => self::STATUS_SUPPRESSED
                ]
            ],
            [
                'multiple' => false
            ]
        );

        if ($result['n'] == 1) {
            return true;
        } else {
            throw new \RuntimeException('Could not set suppressed. Record does not exists');
        }
    }

    function suppress($identifier)
    {
        $this->collection->update(
            [
                self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                self::FIELD_IDENTIFIER => $identifier,
                self::FIELD_STATUS => self::STATUS_OK
            ],
            [
                '$set' => [
                    self::FIELD_STATUS => self::STATUS_SUPPRESSING
                ]
            ],
            [
                'multiple' => false
            ]
        );


    }

    function suppressGroup($group = self::GROUP_DEFAULT)
    {
        $identifiers = $this->getGroupIdentifiers($group);

        foreach ($identifiers as $identifier) {
            $this->suppress($identifier);
        }
    }

    function remove($identifier)
    {
        $this->collection->remove(
            [
                self::FIELD_TYPE => self::RECORD_TYPE_KEYWORD,
                self::FIELD_IDENTIFIER => $identifier
            ]
        );
    }


}