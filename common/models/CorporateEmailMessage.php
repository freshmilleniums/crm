<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * CorporateEmailMessage model
 *
 * @property int $id
 * @property int $corporate_email_id
 * @property string $message_id
 * @property string $message_uid
 * @property string $thread_id
 * @property string $in_reply_to
 * @property string $references
 * @property string $direction
 * @property string $folder
 * @property string $from_email
 * @property string $from_name
 * @property string $reply_to
 * @property string $to_emails
 * @property string $cc_emails
 * @property string $bcc_emails
 * @property string $subject
 * @property string $body_text
 * @property string $body_html
 * @property int $is_read
 * @property int $is_draft
 * @property int $has_attachments
 * @property int $sent_at
 * @property int $received_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property UserCorporateEmail $corporateEmail
 * @property CorporateEmailAttachment[] $attachments
 */
class CorporateEmailMessage extends ActiveRecord
{
    const DIRECTION_INCOMING = 'incoming';
    const DIRECTION_OUTGOING = 'outgoing';

    const FOLDER_INBOX = 'INBOX';
    const FOLDER_SENT = 'Sent';
    const FOLDER_DRAFTS = 'Drafts';
    const FOLDER_TRASH = 'Trash';

    public static function tableName()
    {
        return '{{%corporate_email_messages}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['corporate_email_id', 'direction', 'from_email', 'to_emails'], 'required'],

            [['corporate_email_id', 'is_read', 'is_draft', 'has_attachments', 'sent_at', 'received_at'], 'integer'],

            [['message_id', 'message_uid', 'thread_id', 'in_reply_to', 'from_email', 'from_name', 'reply_to'], 'string', 'max' => 255],
            [['subject'], 'string', 'max' => 500],
            [['folder'], 'string', 'max' => 50],
            [['references', 'to_emails', 'cc_emails', 'bcc_emails', 'body_text', 'body_html'], 'string'],

            [['direction'], 'in', 'range' => [self::DIRECTION_INCOMING, self::DIRECTION_OUTGOING]],
            [['folder'], 'default', 'value' => self::FOLDER_INBOX],

            [['is_read', 'is_draft', 'has_attachments'], 'boolean'],
            [['is_read', 'is_draft', 'has_attachments'], 'default', 'value' => 0],

            [['corporate_email_id'], 'exist', 'skipOnError' => true, 'targetClass' => UserCorporateEmail::class, 'targetAttribute' => ['corporate_email_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corporate_email_id' => 'Corporate Email',
            'message_id' => 'Message ID',
            'message_uid' => 'Message UID',
            'thread_id' => 'Thread ID',
            'in_reply_to' => 'In Reply To',
            'references' => 'References',
            'direction' => 'Direction',
            'folder' => 'Folder',
            'from_email' => 'From Email',
            'from_name' => 'From Name',
            'reply_to' => 'Reply To',
            'to_emails' => 'To',
            'cc_emails' => 'CC',
            'bcc_emails' => 'BCC',
            'subject' => 'Subject',
            'body_text' => 'Body Text',
            'body_html' => 'Body HTML',
            'is_read' => 'Read',
            'is_draft' => 'Draft',
            'has_attachments' => 'Has Attachments',
            'sent_at' => 'Sent At',
            'received_at' => 'Received At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && empty($this->message_id)) {
            $this->message_id = $this->generateMessageId();
        }

        if ($insert && empty($this->thread_id)) {
            $this->thread_id = $this->in_reply_to ? $this->findThreadId() : uniqid('thread_', true);
        }

        return true;
    }

    protected function generateMessageId()
    {
        $domain = Yii::$app->params['corporateEmailDomain'] ?? 'yourcompany.com';
        return '<' . uniqid('msg_', true) . '@' . $domain . '>';
    }

    protected function findThreadId()
    {
        $parent = self::find()
            ->where(['message_id' => $this->in_reply_to])
            ->one();

        return $parent ? $parent->thread_id : uniqid('thread_', true);
    }

    public function getToEmailsArray()
    {
        return json_decode($this->to_emails, true) ?? [];
    }

    public function getCcEmailsArray()
    {
        return json_decode($this->cc_emails, true) ?? [];
    }

    public function getBccEmailsArray()
    {
        return json_decode($this->bcc_emails, true) ?? [];
    }

    public function getCorporateEmail()
    {
        return $this->hasOne(UserCorporateEmail::class, ['id' => 'corporate_email_id']);
    }

    public function getAttachments()
    {
        return $this->hasMany(CorporateEmailAttachment::class, ['message_id' => 'id']);
    }
}