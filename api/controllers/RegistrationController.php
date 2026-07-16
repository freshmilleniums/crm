<?php

namespace api\controllers;

use common\models\SignupForm;
use common\models\Company;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\Cors;
use yii\web\UnauthorizedHttpException;
use common\services\NotificationService;
use backend\models\Chat;
use backend\models\User;

/**
 * Registration Controller
 *
 * Handles user registration requests via API.
 * Provides endpoints for employee registration with API key authentication.
 */
class RegistrationController extends BaseApiController
{
    /**
     * Configure controller behaviors
     *
     * Sets up CORS for cross-origin requests and verb filtering
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            // Enable CORS for external integrations
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['*'], // Configure specific origins in production
                    'Access-Control-Request-Method' => [
                        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
                    ],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => false,
                    'Access-Control-Max-Age' => 86400, // Cache preflight for 24 hours
                ],
            ],
            // Restrict HTTP methods for security
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST', 'OPTIONS'],
                ],
            ],
        ];
    }

    /**
     * Execute before any action
     *
     * Disables CSRF validation and sets JSON response format
     *
     * @param \yii\base\Action $action the action to be executed
     * @return bool whether the action should continue to run
     */
    public function beforeAction($action)
    {
        // Disable CSRF validation for API endpoints
        $this->enableCsrfValidation = false;

        // Set JSON response format
        Yii::$app->response->format = Response::FORMAT_JSON;

        return parent::beforeAction($action);
    }

    /**
     * Register new employee
     *
     * Creates a new employee account with the provided information.
     * Requires API key authentication via X-API-Key and X-Landing-URL headers.
     * Assigns employee role and sets up necessary related records.
     *
     * Expected Headers:
     * - X-API-Key: API key from company settings
     * - X-Landing-URL: Landing page URL for verification
     *
     * Expected POST fields:
     * - first_name
     * - last_name
     * - email
     * - password
     * - password_repeat
     * - address (optional)
     * - phone_number (optional)
     * - city (optional)
     * - state (optional)
     * - zip_code (optional)
     *
     * @return array JSON response with registration result
     */
    public function actionCreate()
    {
        try {
            // Authenticate request via API key and landing URL
            $company = $this->authenticateRequest();

            // Get request data from both POST and JSON body
            $data = $this->getRequestData();

            // Log incoming registration attempt (without sensitive data)
            $logData = array_diff_key($data, ['password' => '', 'password_repeat' => '']);
            Yii::info(
                "Registration attempt for company {$company->id} ({$company->name}): " . json_encode($logData),
                'api'
            );

            // Sanitize input data
            $data = $this->sanitizeInput($data);

            // Associate user with the authenticated company
            $data['company_id'] = $company->id;

            // Create and validate signup form
            $model = new SignupForm();
            $model->load($data, '');

            if (!$model->validate()) {
                return $this->errorResponse(
                    'Validation failed',
                    $this->formatValidationErrors($model->getErrors()),
                    422
                );
            }

            // Attempt to register the user
            $user = $model->signup();

            if (!$user) {
                return $this->errorResponse(
                    'Registration failed',
                    $this->formatValidationErrors($model->getErrors()),
                    422
                );
            }

            // Assign employee role to the new user
            $this->assignEmployeeRole($user);

            // Create support chat for the employee
            $this->createEmployeeChat($user);

            // Send welcome notification
            $this->sendWelcomeNotification($user);

            // Log successful registration
            Yii::info(
                "New employee registered: {$user->email} (ID: {$user->id}) for company {$company->id}",
                'api'
            );

            return $this->successResponse([
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'status' => 'pending_verification',
                'company_id' => $company->id
            ], 'User registered successfully. Please check email for verification.', 201);

        } catch (UnauthorizedHttpException $e) {
            // Log authentication failure
            Yii::warning('API Authentication failed: ' . $e->getMessage(), 'api');

            return $this->errorResponse(
                $e->getMessage(),
                ['authentication' => 'Invalid API credentials'],
                401
            );

        } catch (\Exception $e) {
            // Log the error for debugging
            Yii::error(
                'API Registration error: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString(),
                'api'
            );

            return $this->errorResponse(
                'Internal server error',
                ['system' => 'Registration failed due to server error'],
                500
            );
        }
    }

    /**
     * Authenticate API request via headers
     *
     * Validates X-API-Key and X-Landing-URL headers against company from config.
     * This ensures that only authorized landing pages can register employees.
     *
     * @return Company Authenticated company instance
     * @throws UnauthorizedHttpException if authentication fails
     */
    protected function authenticateRequest()
    {
        $request = Yii::$app->request;

        // Get authentication headers
        $apiKey = $request->headers->get('X-API-Key');
        $landingUrl = $request->headers->get('X-Landing-URL');

        // Check if headers are present
        if (empty($apiKey)) {
            Yii::warning('Missing X-API-Key header in registration request', 'api');
            throw new UnauthorizedHttpException('Missing API key');
        }

        if (empty($landingUrl)) {
            Yii::warning('Missing X-Landing-URL header in registration request', 'api');
            throw new UnauthorizedHttpException('Missing landing URL');
        }

        // Get company ID from config
        $companyId = Yii::$app->params['company_id'] ?? null;

        if (empty($companyId)) {
            Yii::error('company_id not configured in params', 'api');
            throw new UnauthorizedHttpException('Server configuration error');
        }

        // Load company from database
        $company = Company::findOne($companyId);

        if (!$company) {
            Yii::error("Company not found: {$companyId}", 'api');
            throw new UnauthorizedHttpException('Company not found');
        }

        // Check if company is active
        if ($company->status != Company::STATUS_ACTIVE) {
            Yii::warning("Company {$companyId} is not active (status: {$company->status})", 'api');
            throw new UnauthorizedHttpException('Company is not active');
        }

        // Normalize landing URL (remove trailing slashes, convert to lowercase)
        $normalizedLandingUrl = rtrim(strtolower($landingUrl), '/');
        $normalizedCompanyUrl = rtrim(strtolower($company->landing_url), '/');

        // Verify API key matches
        if ($company->landing_api_key !== $apiKey) {
            Yii::warning(
                "API key mismatch for company {$companyId}",
                'api'
            );
            throw new UnauthorizedHttpException('Invalid API key');
        }

        // Verify landing URL matches
        if ($normalizedCompanyUrl !== $normalizedLandingUrl) {
            Yii::warning(
                "Landing URL mismatch for company {$companyId}. Expected: {$normalizedCompanyUrl}, Got: {$normalizedLandingUrl}",
                'api'
            );
            throw new UnauthorizedHttpException('Invalid landing URL');
        }

        // Log successful authentication
        Yii::info(
            "Request authenticated for company: {$company->id} ({$company->name})",
            'api'
        );

        return $company;
    }

    /**
     * Assign employee role to user
     *
     * @param User $user The user to assign role to
     * @return void
     */
    protected function assignEmployeeRole($user)
    {
        try {
            $auth = Yii::$app->authManager;
            $role = $auth->getRole('employee');

            if ($role) {
                $auth->assign($role, $user->id);
                Yii::info("employee role assigned to user {$user->id}", 'api');
            } else {
                Yii::warning("employee role not found in RBAC system", 'api');
            }
        } catch (\Exception $e) {
            Yii::error("Failed to assign employee role to user {$user->id}: " . $e->getMessage(), 'api');
        }
    }

    /**
     * Create employee support chat
     *
     * @param User $user The user to create chat for
     * @return Chat|null Created chat instance or null on failure
     */
    protected function createEmployeeChat($user)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $chat = new Chat([
                'type' => Chat::TYPE_EMPLOYEE,
                'title' => 'Support Chat - ' . trim($user->first_name . ' ' . $user->last_name),
                'employee_id' => $user->id,
            ]);

            if ($chat->save()) {
                $transaction->commit();
                Yii::info("Support chat created for user {$user->id}", 'api');
                return $chat;
            } else {
                $transaction->rollBack();
                Yii::error("Failed to save chat for user {$user->id}: " . json_encode($chat->getErrors()), 'api');
                return null;
            }

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Failed to create employee chat for user {$user->id}: " . $e->getMessage(), 'api');
            return null;
        }
    }

    /**
     * Send welcome notification to new user
     *
     * @param User $user The user to send notification to
     * @return void
     */
    protected function sendWelcomeNotification($user)
    {
        try {
            $notificationService = new NotificationService();
            $notificationService->sendWelcomeNotification($user->id);
            Yii::info("Welcome notification sent to user {$user->id}", 'api');
        } catch (\Exception $e) {
            Yii::error("Failed to send welcome notification to user {$user->id}: " . $e->getMessage(), 'api');
        }
    }

    /**
     * Get request data from both POST and JSON body
     *
     * @return array Combined request data
     */
    protected function getRequestData()
    {
        return array_merge(
            Yii::$app->request->post(),
            Yii::$app->request->getBodyParams()
        );
    }

    /**
     * Sanitize input data
     *
     * Removes potentially harmful content from user input
     *
     * @param array $data Input data
     * @return array Sanitized data
     */
    protected function sanitizeInput($data)
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace and remove potentially harmful tags
                $sanitized[$key] = trim(strip_tags($value));
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}