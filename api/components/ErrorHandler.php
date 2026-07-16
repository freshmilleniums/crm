<?php

namespace api\components;

use Yii;
use yii\web\ErrorHandler as BaseErrorHandler;
use yii\web\Response;
use yii\web\HttpException;

/**
 * API Error Handler
 *
 * Custom error handler for API endpoints that formats all errors
 * as JSON responses with consistent structure.
 *
 * @author Your Name
 * @since 1.0
 */
class ErrorHandler extends BaseErrorHandler
{
    /**
     * Renders the exception as JSON response
     *
     * Overrides the default error rendering to provide JSON-formatted
     * error responses suitable for API consumption.
     *
     * @param \Exception|\Error $exception the exception to be rendered
     */
    protected function renderException($exception)
    {
        // Ensure we have a proper response component
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            // Reset response state to ensure clean output
            $response->isSent = false;
            $response->stream = null;
            $response->content = null;
            $response->format = Response::FORMAT_JSON;
        } else {
            // Create new response if none exists
            $response = new Response();
            $response->format = Response::FORMAT_JSON;
            Yii::$app->set('response', $response);
        }

        // Set appropriate HTTP status code
        $response->setStatusCodeByException($exception);

        // Build JSON error response
        $response->data = [
            'success' => false,
            'message' => $this->getExceptionMessage($exception),
            'timestamp' => time(),
        ];

        // Add debug information in development mode only
        if (YII_DEBUG) {
            $response->data['debug'] = [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        $response->send();
    }

    /**
     * Gets appropriate exception message for API response
     *
     * Determines what error message to show based on exception type
     * and environment settings.
     *
     * @param \Exception|\Error $exception the exception
     * @return string formatted error message
     */
    protected function getExceptionMessage($exception)
    {
        // Handle HTTP exceptions with custom messages
        if ($exception instanceof HttpException) {
            $message = $exception->getMessage();

            // Return custom message if provided, otherwise use default
            if (!empty($message)) {
                return $message;
            }

            return $this->getHttpStatusMessage($exception->statusCode);
        }

        // In debug mode, show actual exception message
        if (YII_DEBUG) {
            return $exception->getMessage();
        }

        // In production, use generic message for security
        return 'Internal server error';
    }

    /**
     * Get standard HTTP status message
     *
     * @param int $statusCode HTTP status code
     * @return string status message
     */
    protected function getHttpStatusMessage($statusCode)
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        return $messages[$statusCode] ?? 'HTTP Error';
    }
}