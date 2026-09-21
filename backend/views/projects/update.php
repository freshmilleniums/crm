<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
/* @var $documents common\models\ProjectsDocuments[] */
/* @var $employees array */
?>

<div class="card" style="border-left: 3px solid #6c757d;">
    <div class="card-body">
        <?= $this->render('_form', [
            'model' => $model,
            'documents' => $documents,
            'employees' => $employees
        ]) ?>
    </div>
</div>