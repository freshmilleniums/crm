<?php
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

// Create and run the application with error handling
try {
    (new yii\web\Application($config))->run();
} catch (\Exception $e) {
    // Fallback error handling if something goes wrong during application creation
    // This ensures API always returns JSON, even for critical initialization errors

    http_response_code(500);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    $errorResponse = [
        'success' => false,
        'message' => 'Application initialization failed',
        'timestamp' => time(),
        'error_id' => 'init_' . uniqid(),
    ];

    // Add debug information only in development
    if (YII_DEBUG) {
        $errorResponse['debug'] = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'type' => get_class($e),
        ];
    }

    echo json_encode($errorResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Log the critical error using Yii logger
    try {
        if (class_exists('Yii') && isset(Yii::$app->log)) {
            Yii::error(sprintf(
                'API Application Init Error [%s]: %s in %s:%d',
                $errorResponse['error_id'],
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), 'api-init');
        }
    } catch (\Exception $logException) {
        // If logging fails, continue silently to avoid cascading errors
    }

    exit(1);
}