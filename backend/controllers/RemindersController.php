<?php
namespace backend\controllers;

use Yii;
use common\models\Reminders;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\db\Expression;


/**
 * RemindersController implements the CRUD actions for Reminders model.
 */
class RemindersController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Reminders models.
     * @return mixed
     */
    public function actionIndex()
    {
        $query = Reminders::find()
            ->select(['*', new Expression("CAST(SUBSTRING(code, 4) AS UNSIGNED) AS code_numeric")]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['code_numeric' => SORT_ASC]),
            'pagination' => false,
            'sort' => [
                'attributes' => [
                    'code' => [
                        'asc' => ['code_numeric' => SORT_ASC],
                        'desc' => ['code_numeric' => SORT_DESC],
                        'default' => SORT_ASC,
                        'label' => 'Code',
                    ],
                    'text',
                ],
            ],
        ]);


        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Updates an existing Reminders model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Reminder updated successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the Reminders model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Reminders the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Reminders::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}