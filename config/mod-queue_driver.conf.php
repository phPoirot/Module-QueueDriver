<?php
use Module\QueueDriver\Services\ServiceQueuesContainer;
use Module\QueueDriver\Services\ServiceStorage;

return [
    \Module\QueueDriver\Module::CONF => [

        'worker' => [

            'workers' => [
                'default_worker' => [
                    'max_trades' => 25,
                    'channels' => [
                        'general' => [
                            // Jobs in this queue will executed with DemonShutdown
                            'queue_name' => 'mongodb', // Queue defined in Service Container
                            'weight'     => 10,
                        ],
                    ],
                    'aggregate' => [
                        // Queue Aggregator Settings
                        'built_in_queue' => 'mongodb'
                    ],
                ],
            ],
        ],

        // Each Queue Can Be Retrieved With Given Name For Usage.
        ServiceQueuesContainer::CONF => [
            // It's a configuration of BuilderContainer [services=>]
            // @see BuildContainer::setServices
            'memory'  => new \Poirot\Queue\Queue\InMemoryQueue(),
            'mongodb' => [
                \Module\QueueDriver\Services\Queue\ServiceQueueMongodb::class,
                'db' => 'mydb', 'client' => 'master', 'collection' => 'queue.app',
            ]
        ],

        // Storage Used By Worker(s) while running jobs ....
        ServiceStorage::CONF => [
            'instance' => new \Poirot\Ioc\instance(
                \Module\QueueDriver\Services\Storage\ServiceStorageMongodb::class,
                [ 'db' => 'mydb', 'client' => 'master', 'collection' => 'queue.app.storage', ]
            ),
        ],
    ],
];
