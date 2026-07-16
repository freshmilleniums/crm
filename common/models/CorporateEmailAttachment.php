<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * CorporateEmailAttachment model
 *
 * @property int $id
 * @property int $message_id
 * @property string $filename
 * @property string $content_type
 * @property int $size
 * @property string $file_path
 * @property int $created_at
 *
 * @property CorporateEmailMessage $message
 */
class CorporateEmailAttachment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%corporate_email_attachments}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['message_id', 'filename', 'file_path'], 'required'],
            [['message_id', 'size'], 'integer'],
            [['filename'], 'string', 'max' => 255],
            [['content_type'], 'string', 'max' => 100],
            [['file_path'], 'string', 'max' => 500],

            [['message_id'], 'exist', 'skipOnError' => true, 'targetClass' => CorporateEmailMessage::class, 'targetAttribute' => ['message_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_id' => 'Message',
            'filename' => 'Filename',
            'content_type' => 'Content Type',
            'size' => 'Size',
            'file_path' => 'File Path',
            'created_at' => 'Created At',
        ];
    }

    public function getFormattedSize()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getMessage()
    {
        return $this->hasOne(CorporateEmailMessage::class, ['id' => 'message_id']);
    }
}