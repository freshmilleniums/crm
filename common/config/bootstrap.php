<?php
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@api', dirname(dirname(__DIR__)) . '/api');
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

// Auto-configure SMTP based on company_id parameter
if (isset(Yii::$app->params['company_id']) && Yii::$app->params['company_id']) {
    $companyId = Yii::$app->params['company_id'];

    try {
        $company = \common\models\Companies::findOne($companyId);

        if ($company && $company->hasSmtpSettings()) {
            // Override mailer configuration
            Yii::$app->set('mailer', [
                'class' => \yii\symfonymailer\Mailer::class,
                'viewPath' => '@common/mail',
                'useFileTransport' => false,
                'transport' => [
                    'dsn' => $company->getSmtpDsn(),
                    /*'scheme' => $company->smtp_port == 465 ? 'smtps' : 'smtp',
                    'host' => $company->smtp_server,
                    'username' => $company->smtp_login,
                    'password' => $company->smtp_password,
                    'port' => $company->smtp_port ?: 587,*/
                ],
            ]);
        }
    } catch (\Exception $e) {
        Yii::error("Failed to configure SMTP for company ID {$companyId}: " . $e->getMessage());
    }
}
