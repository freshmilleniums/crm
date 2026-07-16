<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user_sent_templates".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $template_id
 * @property int|null $sent_by
 * @property string $template_name
 * @property string|null $subject
 * @property string|null $body
 * @property int $sent_at
 *
 * @property User $user
 * @property User $sender
 * @property Template $template
 */
class UserSentTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_sent_templates';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'template_id', 'sent_by'], 'integer'],
            [['template_name'], 'required'],
            [['template_name', 'subject'], 'string', 'max' => 255],
            [['body'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'user_id'       => 'User',
            'template_id'   => 'Template',
            'sent_by'       => 'Sent By',
            'template_name' => 'Template Name',
            'subject'       => 'Subject',
            'body'          => 'Body',
            'sent_at'       => 'Sent At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            $this->sent_at = time();

            // Auto-fill template_name if empty
            if (empty($this->template_name)) {
                $this->template_name = 'Template #' . ($this->template_id ?? $this->sent_at);
            }
        }

        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSender()
    {
        return $this->hasOne(User::class, ['id' => 'sent_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTemplate()
    {
        return $this->hasOne(Template::class, ['id' => 'template_id']);
    }

    /**
     * @return string
     */
    public function getSentAtFormatted()
    {
        return $this->sent_at ? date('Y-m-d H:i', $this->sent_at) : '';
    }

    /**
     * Create record from template model
     * @param Template $template
     * @param int $userId
     * @param int $sentBy
     * @return static
     */
    public static function createFromTemplate($template, $userId, $sentBy)
    {
        $model                = new static();
        $model->user_id       = $userId;
        $model->template_id   = $template->id;
        $model->sent_by       = $sentBy;
        $model->template_name = $template->name ?? ('Template #' . $template->id);
        $model->subject       = $template->subject ?? null;
        $model->body          = $template->body ?? null;

        return $model;
    }
}