<?php

namespace Module\QueueDriver\Actions\Worker;

use Poirot\Events\Event\BuildEvent;
use Poirot\Queue\Queue\AggregateQueue;


// TODO Improve worker registry
class WorkerAction
{
    const CONF = 'worker';

    /** @var array */
    protected static $workers = [];

    /** @var \Poirot\Queue\Worker */
    protected $worker;
    /** @var AggregateQueue */
    protected $queue;


    function __invoke($worker_name)
    {
        if ( isset(static::$workers[$worker_name]) )
            return static::$workers[$worker_name];


        $conf = \Module\Foundation\Actions::config(\Module\QueueDriver\Module::CONF, self::CONF);


        ## Global Worker Events Setting
        #
        if ( isset($conf['events']) )
            $defEvents = $conf['events'];

        ## Worker Settings
        #
        $conf = $conf['workers'];
        if (! isset($conf[$worker_name]) )
            throw new \Exception(sprintf(
                'Worker With name (%s) not found.'
                , $worker_name
            ));

        $conf = $conf[$worker_name];


        # Build Queue Aggregate
        #
        $qAggregate = new AggregateQueue;

        /*
        'channels' => [
          'general' => [
            // Jobs in this queue will executed with DemonShutdown
            'queue_name' => 'memory', // Queue defined in Service Container
            'weight'     => 10,
           ],
        ],
        */
        foreach ($conf['channels'] as $cname => $cvalue) {
            $queue = \Module\QueueDriver\Services::Queues()->get($cvalue['queue_name']);
            $qAggregate->addQueue(
                $cname
                , $queue
                , ( isset($cvalue['weight']) ) ? $cvalue['weight'] : null
            );
        }


        # Attain Built-in Queue Services
        #
        if (   isset($conf['settings']) && is_array( $conf['settings'] )
            && isset($conf['settings']['built_in_queue'])
        ) {
            if ( is_string($conf['settings']['built_in_queue']) )
                // Retrieve Queue From Services
                $conf['settings']['built_in_queue'] = \Module\QueueDriver\Services::Queues()->get($conf['settings']['built_in_queue']);
        }


        $settings = (isset($conf['settings'])) ? $conf['settings'] : [];
        $settings = array_merge($settings, [

        ]);


        $n = clone $this;
        $n->queue = $qAggregate;
        $n->worker = new \Poirot\Queue\Worker(
            $worker_name
            , $qAggregate
            , $settings
        );


        if ( isset($defEvents) ) {
            $events = $n->worker->event();
            $builds = new BuildEvent([ 'events' => $defEvents ]);
            $builds->build($events);
        }

        if ( \array_key_exists('events', $conf) ) {
            $events = $n->worker->event();
            $builds = new BuildEvent([ 'events' => $conf['events'] ]);
            $builds->build($events);
        }

        static::$workers[$worker_name] = $n;
        return $n;
    }

    /**
     * Queues
     *
     * @return AggregateQueue
     */
    function queue()
    {
        return $this->queue;
    }

    /**
     * Run Current Queue Tasks
     *
     */
    function goUntilEmpty()
    {
        $this->worker->goUntilEmpty();
    }

    /**
     * Run Worker
     *
     */
    function goWait($maxExecution = null)
    {
        $this->worker->goWait($maxExecution);
    }


    // ..

    /**
     * Proxy Calls To Worker
     *
     * @return mixed
     */
    function __call($name, $arguments)
    {
        return call_user_func_array([$this->worker, $name], $arguments);
    }
}