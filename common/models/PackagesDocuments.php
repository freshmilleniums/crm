<?php

namespace common\models;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

// Check the URL by which the model is accessed and change the path for downloading files
if (\Yii::$app->id == 'app-api') {
    Yii::$app->params['uploadPath'] = realpath(dirname(__FILE__) . '/../../') . '/backend/web/uploads/';
} else {
    Yii::$app->params['uploadPath'] = Yii::$app->basePath . '/web/uploads/';
}
Yii::$app->params['uploadUrl'] = Yii::$app->urlManager->baseUrl . '/uploads/';

/**
 * This is the model class for table "packages_documents".
 *
 * @property int $id
 * @property int $package_id
 * @property string $path
 *
 * @property Packages $package
 */
class PackagesDocuments extends \yii\db\ActiveRecord
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
        return 'packages_documents';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['package_id'], 'required'],
            [['package_id'], 'integer'],
            [['path'], 'string', 'max' => 255],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif, pdf, doc, docx, odt'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'package_id' => 'Package ID',
            'path' => 'Path',
            'file' => 'File',
        ];
    }

    /**
     * Gets query for the associated package.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPackage()
    {
        return $this->hasOne(Packages::class, ['id' => 'package_id']);
    }

    /**
     * Get file URL
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->path ? Yii::$app->params['uploadUrl'] . $this->path : null;
    }

    /**
     * Get full file path
     *
     * @return string|null
     */
    public function getFilePath()
    {
        return isset($this->path) ? Yii::$app->params['uploadPath'] . $this->path : null;
    }

    /**
     * Get file name from path
     *
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
     * Upload file and save path
     *
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
        $fileDir = 'packagesDocuments/' . $fileNewName[0] . '/' . $fileNewName[1] . $fileNewName[2] . '/';
        $path = Yii::$app->params['uploadPath'] . $fileDir;

        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }

        $this->path = $fileDir . time() . '_' . $fileNewName . '.' . $ext;

        return $file->saveAs($this->getFilePath());
    }

    /**
     * Check if file is image
     *
     * @return bool
     */
    public function isImage()
    {
        if (!$this->path) {
            return false;
        }

        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
    }

    /**
     * Check if file is PDF
     *
     * @return bool
     */
    public function isPdf()
    {
        if (!$this->path) {
            return false;
        }

        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        return $extension === 'pdf';
    }

    /**
     * Check if file is document (Word, ODT)
     *
     * @return bool
     */
    public function isDocument()
    {
        if (!$this->path) {
            return false;
        }

        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        return in_array($extension, ['doc', 'docx', 'odt']);
    }

    /**
     * Get file preview type
     *
     * @return string
     */
    public function getPreviewType()
    {
        if ($this->isImage()) {
            return 'image';
        } elseif ($this->isPdf()) {
            return 'pdf';
        } elseif ($this->isDocument()) {
            return 'document';
        }
        return 'other';
    }

    /**
     * Delete file from filesystem
     *
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