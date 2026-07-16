<?php

namespace backend\controllers;

use Yii;
use common\models\EmailAccount;
use backend\models\EmailAccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\User;
use common\models\EmailAccountUser;

class EmailAccountsController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'save-admins' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new EmailAccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            $assignedAdmins = \common\models\EmailAccountUser::find()
                ->alias('eau')
                ->innerJoin('user u', 'u.id = eau.user_id')
                ->where(['eau.email_account_id' => $id])
                ->select(['u.first_name', 'u.last_name', 'u.email'])
                ->asArray()
                ->all();

            return json_encode([
                'tpl' => $this->renderPartial('_view_expanded', [
                    'model'          => $model,
                    'assignedAdmins' => $assignedAdmins,
                ])
            ]);
        }

        throw new NotFoundHttpException('Invalid request');
    }

    public function actionCreateAjax()
    {
        $model = new EmailAccount();
        $model->scenario = 'create';

        if (Yii::$app->request->isGet) {
            return json_encode([
                'tpl' => $this->renderPartial('_form_create', [
                    'model' => $model,
                ])
            ]);
        }

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            if ($model->save()) {
                return json_encode([
                    'success' => true,
                    'message' => 'Email account created successfully',
                ]);
            }

            return json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'tpl' => $this->renderPartial('_form_create', [
                    'model' => $model,
                ])
            ]);
        }
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isGet) {
            return json_encode([
                'tpl' => $this->renderPartial('update', [
                    'model' => $model,
                ])
            ]);
        }

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            if ($model->save()) {
                return json_encode([
                    'success' => true,
                    'message' => 'Email account updated successfully',
                ]);
            }

            return json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'tpl' => $this->renderPartial('update', [
                    'model' => $model,
                ])
            ]);
        }
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Email account deleted successfully');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Failed to delete email account: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    public function actionTestConnection()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request');
        }

        $id = Yii::$app->request->post('id');
        $model = $this->findModel($id);

        $imapResult = $this->testImapConnection($model);
        $smtpResult = $this->testSmtpConnection($model);

        return json_encode([
            'success' => $imapResult['success'] && $smtpResult['success'],
            'imap' => $imapResult,
            'smtp' => $smtpResult,
        ]);
    }

    protected function testImapConnection($model)
    {
        $password = $model->getDecryptedPassword();

        if ($password === null) {
            return [
                'success' => false,
                'message' => 'Failed to decrypt password',
            ];
        }

        $encryption = $model->imap_encryption == EmailAccount::IMAP_ENCRYPTION_SSL ? 'ssl' : 'tls';
        $connectionString = '{' . $model->imap_host . ':' . $model->imap_port . '/imap/' . $encryption . '}INBOX';

        try {
            $imap = @imap_open($connectionString, $model->username, $password);

            if ($imap) {
                imap_close($imap);
                return [
                    'success' => true,
                    'message' => 'IMAP connection successful',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'IMAP connection failed: ' . imap_last_error(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'IMAP connection error: ' . $e->getMessage(),
            ];
        }
    }

    protected function testSmtpConnection($model)
    {
        $password = $model->getDecryptedPassword();

        if ($password === null) {
            return [
                'success' => false,
                'message' => 'Failed to decrypt password',
            ];
        }

        try {
            $errno = 0;
            $errstr = '';

            $socket = @fsockopen($model->smtp_host, $model->smtp_port, $errno, $errstr, 10);

            if ($socket) {
                fclose($socket);
                return [
                    'success' => true,
                    'message' => 'SMTP connection successful',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'SMTP connection failed: ' . $errstr,
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SMTP connection error: ' . $e->getMessage(),
            ];
        }
    }

    protected function findModel($id)
    {
        if (($model = EmailAccount::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested email account does not exist.');
    }

    public function actionAssignAdmins()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request');
        }

        $id    = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        $admins = User::find()
            ->alias('u')
            ->innerJoin('auth_assignment aa', 'aa.user_id = u.id')
            ->where(['aa.item_name' => ['administrator', 'super-administrator']])
            ->andWhere(['u.status' => User::STATUS_ACTIVE])
            ->select(['u.id', 'u.first_name', 'u.last_name'])
            ->asArray()
            ->all();

        $adminsList = [];
        foreach ($admins as $admin) {
            $adminsList[$admin['id']] = $admin['first_name'] . ' ' . $admin['last_name'];
        }

        $assignedIds = EmailAccountUser::find()
            ->select('user_id')
            ->where(['email_account_id' => $id])
            ->column();

        return json_encode([
            'tpl' => $this->renderAjax('_form_assign_admins', [
                'model'       => $model,
                'admins'      => $adminsList,
                'assignedIds' => $assignedIds,
            ])
        ]);
    }

    public function actionSaveAdmins()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request');
        }

        $id      = Yii::$app->request->post('id');
        $userIds = Yii::$app->request->post('user_ids', []);

        $model = $this->findModel($id);

        $validatedIds = User::find()
            ->alias('u')
            ->innerJoin('auth_assignment aa', 'aa.user_id = u.id')
            ->where(['u.id' => $userIds])
            ->andWhere(['aa.item_name' => ['administrator', 'super-administrator']])
            ->andWhere(['u.status' => User::STATUS_ACTIVE])
            ->select('u.id')
            ->column();

        $transaction = Yii::$app->db->beginTransaction();

        try {
            EmailAccountUser::deleteAll(['email_account_id' => $id]);

            if (!empty($validatedIds)) {
                $rows = array_map(fn($uid) => [$id, (int)$uid], $validatedIds);

                Yii::$app->db->createCommand()
                    ->batchInsert(
                        EmailAccountUser::tableName(),
                        ['email_account_id', 'user_id'],
                        $rows
                    )
                    ->execute();
            }

            $transaction->commit();

            return json_encode([
                'success' => true,
                'message' => 'Administrators assigned successfully',
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Failed to assign admins to email account ' . $id . ': ' . $e->getMessage(), 'email');

            return json_encode([
                'success' => false,
                'message' => 'Failed to assign administrators',
            ]);
        }
    }
}