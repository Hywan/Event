<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Event;

use Hoa\Core;

/**
 * Class \Hoa\Event\Event.
 *
 * Events are asynchronous at registration, anonymous at use (until we
 * receive a bucket) and useful to largely spread data through components
 * without any known connection between them.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Event
{
    /**
     * Static register of all observable objects, i.e. \Hoa\Event\Source
     * object, i.e. object that can send event.
     *
     * @var array
     */
    private static $_register = [];

    /**
     * Callables, i.e. observer objects.
     *
     * @var array
     */
    protected $_callable      = [];



    /**
     * Privatize the constructor.
     *
     * @return  void
     */
    private function __construct()
    {
        return;
    }

    /**
     * Manage multiton of events, with the principle of asynchronous
     * attachements.
     *
     * @param   string  $eventId    Event ID.
     * @return  \Hoa\Core\Event
     */
    public static function getEvent($eventId)
    {
        if (!isset(self::$_register[$eventId][0])) {
            self::$_register[$eventId] = [
                0 => new self(),
                1 => null
            ];
        }

        return self::$_register[$eventId][0];
    }

    /**
     * Declare a new object in the observable collection.
     * Note: Hoa's libraries use hoa://Event/AnID for their observable objects;
     *
     * @param   string             $eventId    Event ID.
     * @param   \Hoa\Event\Source  $source     Observable object.
     * @return  void
     * @throws  \Hoa\Event\Exception
     */
    public static function register($eventId, $source)
    {
        if (true === self::eventExists($eventId)) {
            throw new Exception(
                'Cannot redeclare an event with the same ID, i.e. the event ' .
                'ID %s already exists.',
                0,
                $eventId
            );
        }

        if (is_object($source) && !($source instanceof Source)) {
            throw new Exception(
                'The source must implement \Hoa\Core\Event\Source ' .
                'interface; given %s.',
                1,
                get_class($source)
            );
        } else {
            $reflection = new \ReflectionClass($source);

            if (false === $reflection->implementsInterface('\Hoa\Event\Source')) {
                throw new Exception(
                    'The source must implement \Hoa\Core\Event\Source ' .
                    'interface; given %s.',
                    2,
                    $source
                );
            }
        }

        if (!isset(self::$_register[$eventId][0])) {
            self::$_register[$eventId][0] = new self();
        }

        self::$_register[$eventId][1] = $source;

        return;
    }

    /**
     * Undeclare an object in the observable collection.
     *
     * @param   string  $eventId    Event ID.
     * @param   bool    $hard       If false, just delete the source, else,
     *                              delete source and attached callables.
     * @return  void
     */
    public static function unregister($eventId, $hard = false)
    {
        if (false !== $hard) {
            unset(self::$_register[$eventId]);
        } else {
            self::$_register[$eventId][1] = null;
        }

        return;
    }

    /**
     * Attach an object to an event.
     * It can be a callable or an accepted callable form (please, see the
     * \Hoa\Core\Consistency\Xcallable class).
     *
     * @param   mixed   $callable    Callable.
     * @return  \Hoa\Core\Event
     */
    public function attach($callable)
    {
        $callable                              = xcallable($callable);
        $this->_callable[$callable->getHash()] = $callable;

        return $this;
    }

    /**
     * Detach an object to an event.
     * Please see $this->attach() method.
     *
     * @param   mixed   $callable    Callable.
     * @return  \Hoa\Event\Event
     */
    public function detach($callable)
    {
        unset($this->_callable[xcallable($callable)->getHash()]);

        return $this;
    }

    /**
     * Check if at least one callable is attached to an event.
     *
     * @return  bool
     */
    public function isListened()
    {
        return !empty($this->_callable);
    }

    /**
     * Notify, i.e. send data to observers.
     *
     * @param   string             $eventId    Event ID.
     * @param   \Hoa\Event\Source  $source     Source.
     * @param   \Hoa\Event\Bucket  $data       Data.
     * @return  void
     * @throws  \Hoa\Event\Exception
     */
    public static function notify($eventId, Source $source, Bucket $data)
    {
        if (false === self::eventExists($eventId)) {
            throw new Exception(
                'Event ID %s does not exist, cannot send notification.',
                3,
                $eventId
            );
        }

        $data->setSource($source);
        $event = self::getEvent($eventId);

        foreach ($event->_callable as $callable) {
            $callable($data);
        }

        return;
    }

    /**
     * Check whether an event exists.
     *
     * @param   string  $eventId    Event ID.
     * @return  bool
     */
    public static function eventExists($eventId)
    {
        return
            array_key_exists($eventId, self::$_register) &&
            self::$_register[$eventId][1] !== null;
    }
}

/**
 * Flex entity.
 */
Core\Consistency::flexEntity('Hoa\Event\Event');
