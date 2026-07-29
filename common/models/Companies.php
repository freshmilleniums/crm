<?php

namespace common\models;

use Yii;
use backend\models\User;

/**
 * This is the model class for table "companies".
 *
 * @property int $id
 * @property string $name
 * @property string|null $url
 * @property int $status
 * @property int|null $administrator_id
 * @property string $landing_url
 * @property string $landing_api_key
 * @property string|null $wp_site_title
 * @property string|null $wp_admin_email
 * @property string|null $smtp_server
 * @property int|null $smtp_port
 * @property string|null $smtp_login
 * @property string|null $smtp_password
 * @property string|null $email_domain
 *
 * @property User $administrator
 */
class Companies extends \yii\db\ActiveRecord
{
    const STATUS_STOPPED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_DEPLOYING = 2;

    // Virtual attributes for creating new administrator
    public $admin_first_name;
    public $admin_last_name;
    public $admin_email;
    public $admin_password;
    public $admin_password_confirm;

    // Virtual attributes for WordPress setup
    public $wp_admin_username;
    public $wp_admin_password;
    public $wp_admin_email;
    public $wp_site_title;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'companies';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'landing_url', 'landing_api_key'], 'required'],
            [['status', 'administrator_id', 'smtp_port'], 'integer'],
            [['name', 'url', 'wp_site_title', 'wp_admin_email', 'smtp_server', 'smtp_login', 'smtp_password'], 'string', 'max' => 255],
            [['landing_url'], 'string', 'max' => 255],
            [['landing_url'], 'url'],
            [['landing_api_key'], 'string', 'min' => 6, 'max' => 64],
            [['landing_api_key'], 'unique'],
            [['status'], 'default', 'value' => self::STATUS_STOPPED],
            [['status'], 'in', 'range' => array_keys(self::getStatusList())],

            // Rules for new administrator creation (only on create scenario)
            [['admin_first_name', 'admin_last_name', 'admin_email', 'admin_password', 'admin_password_confirm'], 'required', 'on' => 'create'],
            [['admin_first_name', 'admin_last_name'], 'string', 'max' => 255, 'on' => 'create'],
            [['admin_email'], 'email', 'on' => 'create'],
            [['admin_email'], 'unique', 'targetClass' => User::class, 'targetAttribute' => 'email', 'message' => 'This email has already been taken.', 'on' => 'create'],
            [['admin_password'], 'string', 'min' => 6, 'on' => 'create'],
            [['admin_password_confirm'], 'compare', 'compareAttribute' => 'admin_password', 'message' => 'Passwords do not match.', 'on' => 'create'],

            // Rules for WordPress settings (only on create scenario)
            [['wp_admin_username', 'wp_admin_password', 'wp_admin_email', 'wp_site_title'], 'required', 'on' => 'create'],
            [['wp_admin_email'], 'email', 'on' => 'create'],
            [['wp_admin_username'], 'string', 'min' => 3, 'max' => 50, 'on' => 'create'],
            [['wp_admin_password'], 'string', 'min' => 6, 'on' => 'create'],
            [['wp_site_title'], 'string', 'max' => 255, 'on' => 'create'],

            // Rule for update scenario
            [['administrator_id'], 'required', 'on' => 'update'],
            // SMTP validation rules
            [['smtp_port'], 'integer', 'min' => 1, 'max' => 65535],
            [['distribution_position'], 'integer'],
            [['distribution_position'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Company Name',
            'url' => 'URL',
            'status' => 'Status',
            'administrator_id' => 'Administrator',
            'landing_url' => 'Landing URL (WordPress)',
            'landing_api_key' => 'Landing API Key',
            'admin_first_name' => 'Administrator First Name',
            'admin_last_name' => 'Administrator Last Name',
            'admin_email' => 'Administrator Email',
            'admin_password' => 'Administrator Password',
            'admin_password_confirm' => 'Confirm Password',
            'wp_admin_username' => 'WordPress Admin Username',
            'wp_admin_password' => 'WordPress Admin Password',
            'wp_admin_email' => 'WordPress Admin Email',
            'wp_site_title' => 'WordPress Site Title',
            'smtp_server' => 'SMTP Server',
            'smtp_port' => 'SMTP Port',
            'smtp_login' => 'SMTP Login',
            'smtp_password' => 'SMTP Password',
            'distribution_position' => 'Distribution Position',
        ];
    }

    /**
     * Get list of available statuses
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_STOPPED => 'Stopped',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_DEPLOYING => 'Deploying',
        ];
    }

    /**
     * Get human-readable status name
     * @return string
     */
    public function getStatusName()
    {
        $statusList = self::getStatusList();
        return isset($statusList[$this->status]) ? $statusList[$this->status] : 'Unknown';
    }



    /**
     * Gets query for [[Administrator]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdministrator()
    {
        return $this->hasOne(User::class, ['id' => 'administrator_id']);
    }

    /**
     * Get administrator full name
     * @return string|null
     */
    public function getAdministratorName()
    {
        if ($this->administrator_id && $this->administrator) {
            return trim($this->administrator->first_name . ' ' . $this->administrator->last_name);
        }
        return null;
    }

    /**
     * Find company by landing API key
     * @param string $apiKey
     * @return Companies|null
     */
    public static function findByLandingApiKey($apiKey)
    {
        return static::findOne(['landing_api_key' => $apiKey]);
    }

    /**
     * Extract domain from URL
     */
    private function getDomainFromUrl()
    {
        if ($this->landing_url) {
            $domain = preg_replace('/^https?:\/\//', '', $this->landing_url);
            $domain = preg_replace('/\/.*$/', '', $domain);
            return $domain;
        }
        return 'localhost';
    }

    /**
     * Get SMTP settings for email configuration
     * @return array
     */
    public function getSmtpSettings()
    {
        return [
            'server' => $this->smtp_server,
            'port' => $this->smtp_port,
            'login' => $this->smtp_login,
            'password' => $this->smtp_password,
        ];
    }

    /**
     * Check if SMTP is configured
     * @return bool
     */
    public function hasSmtpSettings()
    {
        return !empty($this->smtp_server) && !empty($this->smtp_login) && !empty($this->smtp_password);
    }

    /**
     * Get SMTP DSN string for Symfony Mailer
     * @return string|null
     */
    public function getSmtpDsn()
    {
        if (!$this->hasSmtpSettings()) {
            return null;
        }

        $scheme = $this->getSmtpScheme();
        $host = $this->smtp_server;
        $port = $this->smtp_port ?: 587;
        $username = urlencode($this->smtp_login);
        $password = urlencode($this->smtp_password);

        return "{$scheme}://{$username}:{$password}@{$host}:{$port}";
    }

    /**
     * Get SMTP scheme based on port
     * @return string
     */
    private function getSmtpScheme()
    {
        switch ($this->smtp_port) {
            case 465:
                return 'smtps'; // SSL
            case 587:
                return 'smtp';  // TLS/STARTTLS
            case 25:
                return 'smtp';  // Plain
            default:
                return 'smtp';
        }
    }
}