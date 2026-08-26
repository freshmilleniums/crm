<?php

namespace common\services;

use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;
use common\models\UserCorporateEmail;
use common\models\EmailAccount;
use common\models\CorporateEmailMessage;
use common\models\ExternalEmailMessage;
use common\models\CorporateEmailAttachment;
use common\models\ExternalEmailAttachment;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SmtpService
{
    /**
     * @param UserCorporateEmail|EmailAccount $account
     * @param string $accountType 'corporate' | 'external'
     * @param string $to
     * @param string $subject
     * @param string $body HTML body
     * @param string|null $cc
     * @param string|null $bcc
     * @param UploadedFile[]|null $attachments
     * @return string Message-ID
     * @throws Exception
     */
    public function send($account, $accountType, $to, $subject, $body, $cc = null, $bcc = null, $attachments = null)
    {
        if ($accountType == 'corporate') {
            return $this->sendCorporate($account, $to, $subject, $body, $cc, $bcc, $attachments);
        }

        return $this->sendExternal($account, $to, $subject, $body, $cc, $bcc, $attachments);
    }

    private function sendCorporate(UserCorporateEmail $account, $to, $subject, $body, $cc, $bcc, $attachments)
    {
        $companyId = Yii::$app->params['company_id'] ?? null;
        $company = $companyId ? \common\models\Companies::findOne($companyId) : null;

        if (!$company || !$company->hasSmtpSettings()) {
            throw new Exception('Company SMTP not configured.');
        }

        $smtpHost       = $company->smtp_server;
        $smtpPort       = $company->smtp_port ?: 587;
        $smtpEncryption = ($smtpPort == 465) ? 'ssl' : 'tls';
        $password       = $company->getDecryptedSmtpPassword();

        if (!$password) {
            throw new Exception('Failed to decrypt company SMTP password.');
        }

        $fromEmail = $account->email;
        $fromName = $this->resolveCorporateFromName($account);
        $messageId = $this->generateMessageId($fromEmail);

        $this->dispatch(
            $smtpHost, $smtpPort, $smtpEncryption,
            $company->smtp_login, $password,
            $fromEmail, $fromName,
            $to, $cc, $bcc, $subject, $body, $attachments, $messageId
        );

        $message = new CorporateEmailMessage();
        $message->corporate_email_id = $account->id;
        $message->message_id = $messageId;
        $message->direction = 'outgoing';
        $message->folder = 'Sent';
        $message->from_email = $fromEmail;
        $message->from_name = $fromName;
        $message->to_emails = json_encode($this->parseRecipients($to));
        $message->cc_emails = $cc ? json_encode($this->parseRecipients($cc)) : null;
        $message->bcc_emails = $bcc ? json_encode($this->parseRecipients($bcc)) : null;
        $message->subject = $subject;
        $message->body_html = $body;
        $message->body_text = strip_tags($body);
        $message->sent_at = time();
        $message->received_at = time();
        $message->is_read = 1;
        $message->has_attachments = !empty($attachments) ? 1 : 0;

        if (!$message->save()) {
            Yii::error('Failed to save outgoing corporate message: ' . json_encode($message->errors), 'email');
            throw new Exception('Email sent, but failed to save it to Sent folder.');
        }

        if (!empty($attachments)) {
            $this->saveOutgoingAttachments($message, $attachments, 'corporate');
        }

        return $messageId;
    }

    private function sendExternal(EmailAccount $account, $to, $subject, $body, $cc, $bcc, $attachments)
    {
        $encryptionMap = [
            'SSL' => 'ssl',
            'TLS' => 'tls',
            'STARTTLS' => 'tls',
        ];
        $smtpEncryption = $encryptionMap[$account->getSmtpEncryptionName()] ?? 'tls';

        $password = $account->getDecryptedPassword();

        if (!$password) {
            throw new Exception('Failed to decrypt external email password.');
        }

        $fromEmail = $account->email;
        $fromName = $account->label ?: $account->email;
        $messageId = $this->generateMessageId($fromEmail);

        $this->dispatch(
            $account->smtp_host, $account->smtp_port, $smtpEncryption,
            $account->username, $password,
            $fromEmail, $fromName,
            $to, $cc, $bcc, $subject, $body, $attachments, $messageId
        );

        $message = new ExternalEmailMessage();
        $message->email_account_id = $account->id;
        $message->message_id = $messageId;
        $message->direction = 'outgoing';
        $message->folder = 'Sent';
        $message->from_email = $fromEmail;
        $message->from_name = $fromName;
        $message->to_emails = json_encode($this->parseRecipients($to));
        $message->cc_emails = $cc ? json_encode($this->parseRecipients($cc)) : null;
        $message->bcc_emails = $bcc ? json_encode($this->parseRecipients($bcc)) : null;
        $message->subject = $subject;
        $message->body_html = $body;
        $message->body_text = strip_tags($body);
        $message->sent_at = time();
        $message->received_at = time();
        $message->has_attachments = !empty($attachments) ? 1 : 0;

        if (!$message->save()) {
            Yii::error('Failed to save outgoing external message: ' . json_encode($message->errors), 'email');
            throw new Exception('Email sent, but failed to save it to Sent folder.');
        }

        if (!empty($attachments)) {
            $this->saveOutgoingAttachments($message, $attachments, 'external');
        }

        return $messageId;
    }

    private function dispatch($host, $port, $encryption, $username, $password, $fromEmail, $fromName, $to, $cc, $bcc, $subject, $body, $attachments, $messageId)
    {
        $scheme = ($encryption === 'ssl' || $port == 465) ? 'smtps' : 'smtp';

        $dsn = sprintf(
            '%s://%s:%s@%s:%d',
            $scheme,
            urlencode($username),
            urlencode($password),
            $host,
            $port
        );

        try {
            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from(new Address($fromEmail, $fromName))
                ->returnPath($fromEmail)
                ->subject($subject)
                ->html($body)
                ->text(strip_tags($body));

            foreach ($this->parseRecipients($to) as $address) {
                $email->addTo($address);
            }

            if ($cc) {
                foreach ($this->parseRecipients($cc) as $address) {
                    $email->addCc($address);
                }
            }

            if ($bcc) {
                foreach ($this->parseRecipients($bcc) as $address) {
                    $email->addBcc($address);
                }
            }

            $email->getHeaders()->addIdHeader('Message-ID', trim($messageId, '<>'));

            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    if ($file instanceof UploadedFile && $file->tempName) {
                        $email->attachFromPath($file->tempName, $file->name, $file->type);
                    }
                }
            }

            $mailer->send($email);

        } catch (\Exception $e) {
            Yii::error("SMTP send failed ({$host}:{$port}): " . $e->getMessage(), 'email');
            throw new Exception('Failed to send email via SMTP: ' . $e->getMessage());
        }
    }

    private function resolveCorporateFromName(UserCorporateEmail $account)
    {
        if ($account->user) {
            $name = trim(($account->user->first_name ?? '') . ' ' . ($account->user->last_name ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return $account->email;
    }

    private function parseRecipients($value)
    {
        $list = is_array($value) ? $value : preg_split('/[,;]+/', (string)$value);

        $result = [];
        foreach ($list as $item) {
            $item = trim($item);
            if ($item !== '') {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function generateMessageId($fromEmail)
    {
        $domain = strpos($fromEmail, '@') !== false ? explode('@', $fromEmail)[1] : 'localhost';
        return '<' . uniqid('msg_', true) . '@' . $domain . '>';
    }

    private function saveOutgoingAttachments($message, array $attachments, $accountType)
    {
        $uploadDir = Yii::getAlias('@backend/web/uploads/email-attachments/' . date('Y/m'));

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($attachments as $file) {
            if (!($file instanceof UploadedFile)) {
                continue;
            }

            $filename = uniqid() . '_' . $file->name;
            $filePath = $uploadDir . '/' . $filename;

            if ($file->saveAs($filePath)) {
                $attachmentModel = $accountType == 'corporate'
                    ? new CorporateEmailAttachment()
                    : new ExternalEmailAttachment();

                $attachmentModel->message_id = $message->id;
                $attachmentModel->filename = $file->name;
                $attachmentModel->content_type = $file->type;
                $attachmentModel->size = filesize($filePath);
                $attachmentModel->file_path = $filePath;

                if (!$attachmentModel->save()) {
                    Yii::error('Failed to save outgoing attachment: ' . json_encode($attachmentModel->errors), 'email');
                }
            }
        }
    }
}