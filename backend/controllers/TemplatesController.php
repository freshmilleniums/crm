<?php

namespace backend\controllers;

use Yii;
use common\models\Template;
use common\models\TemplatesDocuments;
use backend\models\User;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use backend\models\MultipleModel;

/**
 * TemplatesController implements the CRUD actions for Template model.
 */
class TemplatesController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-document' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Template models with tabs by category.
     * @return mixed
     */
    public function actionIndex()
    {
        $tabsData = $this->getTabsDataForCategories();

        return $this->render('index', [
            'tabsData' => $tabsData,
        ]);
    }

    /**
     * Get tabs data for all categories
     * @return array
     */
    private function getTabsDataForCategories()
    {
        $categories = Template::getCategoryList();
        $tabs = [];
        $firstCategory = array_key_first($categories);

        foreach ($categories as $categoryId => $categoryName) {
            $dataProvider = $this->createDataProviderForCategory($categoryId);

            $tabs[] = [
                'id' => "tab-category-$categoryId",
                'category' => $categoryId,
                'label' => $categoryName,
                'dataProvider' => $dataProvider,
                'active' => $categoryId === $firstCategory,
            ];
        }

        return $tabs;
    }

    /**
     * Create data provider for specific category
     * @param int $categoryId
     * @return ActiveDataProvider
     */
    private function createDataProviderForCategory($categoryId)
    {
        $query = Template::find()
            ->with(['creator', 'documents'])
            ->where(['category' => $categoryId]);

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);
    }

    /**
     * Displays a single Template model (AJAX expandable row).
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            return json_encode([
                'tpl' => $this->renderPartial('_view_expanded', [
                    'model' => $model,
                ])
            ]);
        }

        throw new NotFoundHttpException('Invalid request');
    }

    /**
     * Creates a new Template model via AJAX.
     * @return mixed
     */
    public function actionCreateAjax()
    {
        $model = new Template();
        $model->created_by = Yii::$app->user->id;

        $documentsModels = [new TemplatesDocuments()];

        if (Yii::$app->request->isGet) {
            return json_encode([
                'tpl' => $this->renderAjax('_form_create', [
                    'model' => $model,
                    'documents' => $documentsModels,
                ])
            ]);
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $documentsModels = MultipleModel::createMultiple(TemplatesDocuments::class);
            MultipleModel::loadMultiple($documentsModels, Yii::$app->request->post());

            $transaction = Yii::$app->db->beginTransaction();
            $success = false;
            $message = '';

            try {
                $flag = $model->save();

                if ($flag) {
                    foreach ($documentsModels as $index => $document) {
                        $document->template_id = $model->id;
                        $document->file = UploadedFile::getInstance($document, "[{$index}]file");

                        if ($document->file) {
                            if ($document->upload()) {
                                if (!$document->save(false)) {
                                    $flag = false;
                                    break;
                                }
                            } else {
                                $flag = false;
                                break;
                            }
                        }
                    }
                }

                if ($flag) {
                    $transaction->commit();
                    \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_TEMPLATE);
                    $success = true;
                    $message = 'Template created successfully';
                } else {
                    $transaction->rollBack();
                    $message = 'Failed to create template';
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                $message = 'Error: ' . $e->getMessage();
            }

            if ($success) {
                return json_encode([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => $message,
                    'tpl' => $this->renderAjax('_form_create', [
                        'model' => $model,
                        'documents' => $documentsModels,
                    ])
                ]);
            }
        }
    }

    /**
     * Updates an existing Template model via AJAX.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $documentsModels = $model->documents ?: [new TemplatesDocuments()];

        if (Yii::$app->request->isGet) {
            return json_encode([
                'tpl' => $this->renderAjax('update', [
                    'model' => $model,
                    'documents' => $documentsModels,
                ])
            ]);
        }

        if ($model->load(Yii::$app->request->post())) {
            $oldDocuments = $model->documents;

            $documentsModels = MultipleModel::createMultiple(TemplatesDocuments::class, $oldDocuments);
            MultipleModel::loadMultiple($documentsModels, Yii::$app->request->post());

            $deletedDocumentIDs = array_diff(
                array_map(function($doc) { return $doc->id; }, $oldDocuments),
                array_filter(array_map(function($doc) { return $doc->id; }, $documentsModels))
            );

            $transaction = Yii::$app->db->beginTransaction();
            $success = false;
            $message = '';

            try {
                $logData = \common\services\ActionLogService::prepareEntityUpdate(
                    $model,
                    \common\models\ActionLog::ENTITY_TEMPLATE
                );
                $flag = $model->save();

                $deletedDocumentNames = [];

                if ($flag) {
                    if (!empty($deletedDocumentIDs)) {
                        $deletedDocs = TemplatesDocuments::find()
                            ->where(['id' => $deletedDocumentIDs])
                            ->all();

                        $deletedDocumentNames = array_map(function($doc) {
                            return $doc->getFileName();
                        }, $deletedDocs);

                        TemplatesDocuments::deleteAll(['id' => $deletedDocumentIDs]);
                    }
                }

                $addedDocumentNames = [];
                if ($flag) {
                    foreach ($documentsModels as $index => $document) {
                        $document->template_id = $model->id;
                        $document->file = UploadedFile::getInstance($document, "[{$index}]file");

                        if ($document->file) {
                            if ($document->upload()) {
                                if (!$document->save(false)) {
                                    $flag = false;
                                    break;
                                } else {
                                    $addedDocumentNames[] = $document->getFileName();
                                }
                            } else {
                                $flag = false;
                                break;
                            }
                        } else {
                            if ($document->isNewRecord) {
                                continue;
                            }
                        }
                    }
                }

                if ($flag) {
                    $additionalChanges = [];

                    if (!empty($addedDocumentNames)) {
                        $additionalChanges['documents_added'] = $addedDocumentNames;
                    }

                    if (!empty($deletedDocumentNames)) {
                        $additionalChanges['documents_deleted'] = $deletedDocumentNames;
                    }

                    \common\services\ActionLogService::commitEntityUpdate($logData, $additionalChanges);

                    $transaction->commit();
                    $success = true;
                    $message = 'Template updated successfully';
                } else {
                    $transaction->rollBack();
                    $message = 'Failed to update template';
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                $message = 'Error: ' . $e->getMessage();
            }

            if ($success) {
                return json_encode([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => $message,
                    'tpl' => $this->renderAjax('update', [
                        'model' => $model,
                        'documents' => $documentsModels,
                    ])
                ]);
            }
        }
    }

    /**
     * Deletes an existing Template model.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            \common\services\ActionLogService::logEntityDelete($model, \common\models\ActionLog::ENTITY_TEMPLATE);
            $model->delete();
            Yii::$app->session->setFlash('success', 'Template deleted successfully');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Failed to delete template: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Preview template with macros replaced.
     * @param integer $id
     * @param integer $userId
     * @return mixed
     */
    public function actionPreview($id, $userId)
    {
        $model = $this->findModel($id);
        $user = User::findOne($userId);

        if (!$user) {
            return json_encode([
                'success' => false,
                'message' => 'User not found',
            ]);
        }

        $replaced = $model->replaceMacros($userId);

        if (Yii::$app->request->isAjax) {
            return json_encode([
                'success' => true,
                'tpl' => $this->renderAjax('_preview', [
                    'model' => $model,
                    'user' => $user,
                    'subject' => $replaced['subject'],
                    'body' => $replaced['body'],
                ])
            ]);
        }

        throw new NotFoundHttpException('Invalid request');
    }

    /**
     * Send template to employee.
     * @return mixed
     */
    public function actionSendToEmployee()
    {
        if (Yii::$app->request->isPost) {
            $templateId = Yii::$app->request->post('template_id');
            $employeeId = Yii::$app->request->post('employee_id');

            $template = $this->findModel($templateId);
            $employee = User::findOne($employeeId);

            if (!$employee) {
                return json_encode([
                    'success' => false,
                    'message' => 'Employee not found',
                ]);
            }

            $service  = new \common\services\TemplateSendService();
            $result   = $service->send($template, $employeeId, Yii::$app->user->id);

            return json_encode($result);
        }

        throw new NotFoundHttpException('Invalid request');
    }

    /**
     * Show send template form.
     * @param integer $id
     * @return mixed
     */
    public function actionShowSendForm($id)
    {
        $model = $this->findModel($id);
        $employees = $this->getEmployeesList();

        if (Yii::$app->request->isAjax) {
            return json_encode([
                'tpl' => $this->renderAjax('_send_form', [
                    'model' => $model,
                    'employees' => $employees,
                ])
            ]);
        }

        throw new NotFoundHttpException('Invalid request');
    }

    /**
     * Download template document.
     * @param integer $id
     * @return mixed
     */
    public function actionDownloadDocument($id)
    {
        $document = TemplatesDocuments::findOne($id);

        if (!$document) {
            throw new NotFoundHttpException('Document not found');
        }

        $filePath = $document->getFilePath();

        if (file_exists($filePath)) {
            return Yii::$app->response->sendFile($filePath, $document->getFileName());
        }

        throw new NotFoundHttpException('File not found');
    }

    /**
     * Delete template document.
     * @param integer $id
     * @return mixed
     */
    public function actionDeleteDocument($id)
    {
        $document = TemplatesDocuments::findOne($id);

        if (!$document) {
            return json_encode([
                'success' => false,
                'message' => 'Document not found',
            ]);
        }

        try {
            $templateId = $document->template_id;
            $fileName = $document->getFileName();

            $document->delete();

            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_TEMPLATE,
                $templateId,
                'delete_document',
                ['document_name' => $fileName],
                null
            );
            return json_encode([
                'success' => true,
                'message' => 'Document deleted successfully',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get list of employees for current administrator.
     * @return array
     */
    protected function getEmployeesList()
    {
        $currentUser = Yii::$app->user->identity;

        if (Yii::$app->user->can('super-administrator')) {
            $employees = User::find()
                ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
                ->where(['auth_assignment.item_name' => 'employee'])
                ->select(['user.id', 'user.first_name', 'user.last_name', 'user.email'])
                ->all();
        } elseif (Yii::$app->user->can('administrator')) {
            $employees = User::find()
                ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
                ->where(['auth_assignment.item_name' => 'employee'])
                ->andWhere(['user.administrator_id' => $currentUser->id])
                ->select(['user.id', 'user.first_name', 'user.last_name', 'user.email'])
                ->all();
        } else {
            $employees = [];
        }

        return ArrayHelper::map($employees, 'id', function($user) {
            return trim($user->first_name . ' ' . $user->last_name) . ' (' . $user->email . ')';
        });
    }

    /**
     * Finds the Template model based on its primary key value.
     * @param integer $id
     * @return Template the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Template::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested template does not exist.');
    }
}