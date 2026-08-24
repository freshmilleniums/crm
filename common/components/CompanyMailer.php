<?php

namespace common\components;

use common\models\Companies;
use Yii;

class CompanyMailer extends \yii\symfonymailer\Mailer
{
    public function compose($view = null, array $params = [])
    {
        $this->configureCompanyTransport();
        return parent::compose($view, $params);
    }

    private function configureCompanyTransport(): void
    {
        try {
            $companyId = Yii::$app->params['company_id'] ?? null;
            if (!$companyId) {
                $this->applyFallback('company_id not set in params');
                return;
            }

            $company = Companies::findOne($companyId);
            if (!$company || !$company->hasSmtpSettings()) {
                $this->applyFallback('Company not found or SMTP not configured');
                return;
            }

            $password = $company->getDecryptedSmtpPassword();
            if (!$password) {
                $this->applyFallback('Failed to decrypt SMTP password');
                return;
            }

            $port = (int)($company->smtp_port ?: 587);
            $scheme = ($port === 465) ? 'smtps' : 'smtp';

            $dsn = sprintf(
                '%s://%s:%s@%s:%d',
                $scheme,
                urlencode($company->smtp_login),
                urlencode($password),
                $company->smtp_server,
                $port
            );

            $this->setTransport(
                \Symfony\Component\Mailer\Transport::fromDsn($dsn)
            );

        } catch (\Exception $e) {
            Yii::error('CompanyMailer: ' . $e->getMessage(), 'email');
            $this->applyFallback('Exception: ' . $e->getMessage());
        }
    }

    private function applyFallback(string $reason): void
    {
        Yii::warning('CompanyMailer: falling back to native transport. Reason: ' . $reason, 'email');
        try {
            $this->setTransport(
                \Symfony\Component\Mailer\Transport::fromDsn('native://default')
            );
        } catch (\Exception $e) {
            Yii::error('CompanyMailer: fallback also failed: ' . $e->getMessage(), 'email');
        }
    }
}