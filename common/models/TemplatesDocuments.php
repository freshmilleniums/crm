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
 * This is the model class for table "templates_documents".
 *
 * @property int $id
 * @property int $template_id
 * @property string $path
 *
 * @property Template $template
 */
class TemplatesDocuments extends \yii\db\ActiveRecord
{
    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'templates_documents';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_id'], 'required'],
            [['template_id'], 'integer'],
            [['path'], 'string', 'max' => 255],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif, pdf, doc, docx, odt, txt, xlsx, xls, csv, zip'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_id' => 'Template ID',
            'path' => 'Path',
            'file' => 'File',
        ];
    }

    /**
     * Get template relation
     * @return \yii\db\ActiveQuery
     */
    public function getTemplate()
    {
        return $this->hasOne(Template::class, ['id' => 'template_id']);
    }

    /**
     * Get file URL
     * @return string|null
     */
    public function getUrl()
    {
        return $this->path ? Yii::$app->params['uploadUrl'] . $this->path : null;
    }

    /**
     * Get file system path
     * @return string|null
     */
    public function getFilePath()
    {
        return isset($this->path) ? Yii::$app->params['uploadPath'] . $this->path : null;
    }

    /**
     * Get file name
     * @return string|null
     */
    public function getFileName()
    {
        if ($this->path) {
            return basename($this->path);
        }
        return null;
    }

    /**
     * Get preview type based on file extension
     * @return string 'image'|'pdf'|'document'|'spreadsheet'|'archive'|'other'
     */
    public function getPreviewType()
    {
        if (!$this->path) {
            return 'other';
        }

        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return 'image';

            case 'pdf':
                return 'pdf';

            case 'doc':
            case 'docx':
            case 'odt':
            case 'txt':
                return 'document';

            case 'xlsx':
            case 'xls':
            case 'csv':
                return 'spreadsheet';

            case 'zip':
                return 'archive';

            default:
                return 'other';
        }
    }

    /**
     * Upload file
     * @return bool
     */
    public function upload()
    {
        $file = $this->file;
        if (empty($file)) {
            return false;
        }

        $pathInfo = pathinfo($file->name);
        $ext = $pathInfo['extension'];
        $fileNewName = md5($file->name . time());
        $fileDir = 'templatesDocuments/' . $fileNewName[0] . '/' . $fileNewName[1] . $fileNewName[2] . '/';
        $path = Yii::$app->params['uploadPath'] . $fileDir;

        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }

        $this->path = $fileDir . time() . '_' . $fileNewName . '.' . $ext;

        return $file->saveAs($this->getFilePath());
    }

    /**
     * Delete file from filesystem
     * @param string $path
     * @return bool
     */
    public static function deleteFile($path)
    {
        if (empty($path)) {
            return true;
        }

        $file = Yii::$app->params['uploadPath'] . $path;
        if (empty($file) || !file_exists($file) || !is_file($file)) {
            return true;
        }

        if (!unlink($file)) {
            return false;
        }

        return true;
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
        if (!$insert && $this->isAttributeChanged('path', false)) {
            self::deleteFile($this->getOldAttribute('path'));
        }

        return parent::beforeSave($insert);
    }
}