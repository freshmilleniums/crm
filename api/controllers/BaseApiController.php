<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Base API Controller
 *
 * Provides common functionality for API endpoints including
 * JSON response formatting and standardized error handling.
 */
class BaseApiController extends Controller
{
    /**
     * Execute before any action
     *
     * Sets JSON response format for all API endpoints
     *
     * @param \yii\base\Action $action the action to be executed
     * @return bool whether the action should continue to run
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Ensure JSON response format for all API endpoints
        Yii::$app->response->format = Response::FORMAT_JSON;

        return true;
    }

    /**
     * Create a standard success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return array Formatted response array
     */
    protected function successResponse($data = null, $message = 'Success', $statusCode = 200)
    {
        Yii::$app->response->statusCode = $statusCode;

        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => time(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Create a standard error response
     *
     * @param string $message Error message
     * @param array $errors Detailed error information
     * @param int $statusCode HTTP status code
     * @return array Formatted error response array
     */
    protected function errorResponse($message = 'Error', $errors = [], $statusCode = 400)
    {
        Yii::$app->response->statusCode = $statusCode;

        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => time(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Format validation errors for API response
     *
     * Converts Yii model validation errors into a flat array
     * suitable for API consumption
     *
     * @param array $errors Validation errors from model
     * @return array Formatted error array
     */
    protected function formatValidationErrors($errors)
    {
        $formatted = [];
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $formatted[$field] = implode('. ', $messages);
            } else {
                $formatted[$field] = $messages;
            }
        }
        return $formatted;
    }
}