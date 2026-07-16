<?php
return [
    'name' => 'CRM Employers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'webSocket' => [
            'class' => 'common\components\WebSocketComponent',
            'serverUrl' => 'https://localhost:8901',
            'timeout' => 3,
            'retryCount' => 2,
            'enabled' => true
        ],
    ],
];
