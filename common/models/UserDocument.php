<?php

namespace common\models;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

if (\Yii::$app->id == 'app-api') {
    Yii::$app->params['uploadPath'] = realpath(dirname(__FILE__) . '/../../') . '/backend/web/uploads/';
} else {
    Yii::$app->params['uploadPath'] = Yii::$app->basePath . '/web/uploads/';
}
Yii::$app->params['uploadUrl'] = Yii::$app->urlManager->baseUrl . '/uploads/';

/**
 * This is the model class for table "user_documents".
 *
 * @property int $id
 * @property int $user_id
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property int $document_type
 * @property int|null $uploaded_by
 * @property int $created_at
 *
 * @property User $user
 * @property User $uploader
 */
class UserDocument extends \yii\db\ActiveRecord
{
    const TYPE_OTHER    = 0;
    const TYPE_CONTRACT = 1;
    const TYPE_IDENTITY = 2;

    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_documents';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'uploaded_by', 'file_size'], 'integer'],
            [['document_type'], 'integer'],
            [['document_type'], 'in', 'range' => array_keys(self::getDocumentTypes())],
            [['document_type'], 'default', 'value' => self::TYPE_OTHER],
            [['path', 'original_name', 'mime_type'], 'string', 'max' => 255],
            [['path'], 'string', 'max' => 500],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, pdf, doc, docx, txt, rtf'],
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
            'path'          => 'Path',
            'original_name' => 'File Name',
            'mime_type'     => 'Type',
            'file_size'     => 'Size',
            'document_type' => 'Document Type',
            'uploaded_by'   => 'Uploaded By',
            'created_at'    => 'Uploaded At',
        ];
    }

    /**
     * @return array
     */
    public static function getDocumentTypes()
    {
        return [
            self::TYPE_OTHER    => 'Other',
            self::TYPE_CONTRACT => 'Contract',
            self::TYPE_IDENTITY => 'Identity',
        ];
    }

    /**
     * @return string
     */
    public function getDocumentTypeName()
    {
        $types = self::getDocumentTypes();
        return $types[$this->document_type] ?? 'Other';
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
    public function getUploader()
    {
        return $this->hasOne(User::class, ['id' => 'uploaded_by']);
    }

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->path ? Yii::$app->params['uploadUrl'] . $this->path : null;
    }

    /**
     * @return string|null
     */
    public function getFilePath()
    {
        return $this->path ? Yii::$app->params['uploadPath'] . $this->path : null;
    }

    /**
     * @return string|null
     */
    public function getFileName()
    {
        return $this->original_name ?: ($this->path ? basename($this->path) : null);
    }

    /**
     * @return string
     */
    public function getFileSizeFormatted()
    {
        if (!$this->file_size) {
            return '';
        }

        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 2) . ' MB';
        }

        if ($this->file_size >= 1024) {
            return round($this->file_size / 1024, 1) . ' KB';
        }

        return $this->file_size . ' B';
    }

    /**
     * @return bool
     */
    public function upload()
    {
        $file = $this->file;
        if (empty($file)) {
            return false;
        }

        $pathInfo    = pathinfo($file->name);
        $ext         = strtolower($pathInfo['extension']);
        $fileNewName = md5($file->name . time());
        $fileDir     = 'userDocuments/' . $fileNewName[0] . '/' . $fileNewName[1] . $fileNewName[2] . '/';
        $path        = Yii::$app->params['uploadPath'] . $fileDir;

        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }

        $this->path          = $fileDir . time() . '_' . $fileNewName . '.' . $ext;
        $this->original_name = $this->sanitizeFileName($file->name);
        $this->mime_type     = $file->type;
        $this->file_size     = $file->size;

        return $file->saveAs($this->getFilePath());
    }

    /**
     * @param string $name
     * @return string
     */
    private function sanitizeFileName(string $name): string
    {
        $name = strip_tags($name);
        $name = str_replace("\0", '', $name);
        $name = preg_replace('/[^\w\s\-\.\(\)\[\]]/u', '_', $name);
        return trim($name);
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png']);
    }

    /**
     * @return bool
     */
    public function isPdf()
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION)) === 'pdf';
    }

    /**
     * @return bool
     */
    public function isDocument()
    {
        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        return in_array($extension, ['doc', 'docx', 'txt', 'rtf']);
    }

    /**
     * @return string
     */
    public function getPreviewType()
    {
        if ($this->isImage())    return 'image';
        if ($this->isPdf())      return 'pdf';
        if ($this->isDocument()) return 'document';
        return 'other';
    }

    /**
     * @return bool
     */
    public function isUploadedByAdmin()
    {
        return $this->uploaded_by !== null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function deleteFile($path)
    {
        if (empty($path)) {
            return true;
        }

        $file = Yii::$app->params['uploadPath'] . $path;
        if (!file_exists($file) || !is_file($file)) {
            return true;
        }

        return unlink($file);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        self::deleteFile($this->path);
        return parent::delete();
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
            $this->created_at = time();

            if ($this->document_type === null || $this->document_type === '') {
                $this->document_type = self::TYPE_OTHER;
            }
        }

        if (!$insert && $this->isAttributeChanged('path', false)) {
            self::deleteFile($this->getOldAttribute('path'));
        }

        return true;
    }
}