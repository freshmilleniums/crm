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
 * This is the model class for table "projects_documents".
 *
 * @property int $id
 * @property int $project_id
 * @property string $path
 *
 * @property Project $project
 */
class ProjectsDocuments extends \yii\db\ActiveRecord
{
    /** @var UploadedFile */
    public $file;

    public static function tableName()
    {
        return 'projects_documents';
    }

    public function rules()
    {
        return [
            [['project_id'], 'required'],
            [['project_id'], 'integer'],
            [['path'], 'string', 'max' => 255],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif, pdf, doc, docx, odt'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'project_id' => 'Project ID',
            'path'       => 'Path',
            'file'       => 'File',
        ];
    }

    public function getProject()
    {
        return $this->hasOne(Project::class, ['id' => 'project_id']);
    }

    public function getUrl()
    {
        return $this->path ? Yii::$app->params['uploadUrl'] . $this->path : null;
    }

    public function getFilePath()
    {
        return $this->path ? Yii::$app->params['uploadPath'] . $this->path : null;
    }

    public function getFileName()
    {
        return $this->path ? basename($this->path) : null;
    }

    public function upload()
    {
        $file = $this->file;
        if (empty($file)) {
            return false;
        }

        $ext         = pathinfo($file->name, PATHINFO_EXTENSION);
        $fileNewName = md5($file->name . time());
        $fileDir     = 'projectsDocuments/' . $fileNewName[0] . '/' . $fileNewName[1] . $fileNewName[2] . '/';
        $path        = Yii::$app->params['uploadPath'] . $fileDir;

        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }

        $this->path = $fileDir . time() . '_' . $fileNewName . '.' . $ext;

        return $file->saveAs($this->getFilePath());
    }

    public function isImage()
    {
        if (!$this->path) return false;
        return in_array(strtolower(pathinfo($this->path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
    }

    public function isPdf()
    {
        if (!$this->path) return false;
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function isDocument()
    {
        if (!$this->path) return false;
        return in_array(strtolower(pathinfo($this->path, PATHINFO_EXTENSION)), ['doc', 'docx', 'odt']);
    }

    public static function deleteFile($path)
    {
        if (empty($path)) return true;
        $file = Yii::$app->params['uploadPath'] . $path;
        if (!file_exists($file) || !is_file($file)) return true;
        return (bool) unlink($file);
    }

    public function delete()
    {
        self::deleteFile($this->path);
        return parent::delete();
    }

    public function beforeSave($insert)
    {
        if (!$insert && $this->isAttributeChanged('path', false)) {
            self::deleteFile($this->getOldAttribute('path'));
        }
        return parent::beforeSave($insert);
    }
}