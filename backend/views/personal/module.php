<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $module backend\models\TrainingModule */
/* @var $progress backend\models\UserTrainingProgress */

$this->title = $module->title;
$this->params['breadcrumbs'][] = ['label' => 'Training', 'url' => ['training']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($module->content)): ?>
                        <div class="alert alert-secondary">
                            <h4><i class="icon fas fa-secondary"></i> No Content Available</h4>
                            <p>The content for this module has not been added yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="module-content-wrapper">
                            <?= $module->content ?>
                        </div>
                    <?php endif; ?>

                    <div class="module-actions mt-4 pt-3 border-top">
                        <div class="row">
                            <div class="col-12">
                                <?= Html::a(
                                    '<i class="fas fa-arrow-left"></i> Back to Training',
                                    ['training'],
                                    ['class' => 'btn btn-default']
                                ) ?>

                                <?php if ($module->id == $progress->current_module_id): ?>
                                    <?php if ($module->is_final_task): ?>
                                        <span class="badge badge-warning float-right" style="font-size: 14px; padding: 8px 12px;">
                                            <i class="fas fa-tasks"></i> Final task — check your tasks section
                                        </span>
                                    <?php elseif ($module->getQuestionCount() > 0): ?>
                                        <?= Html::a(
                                            '<i class="fas fa-pencil-alt"></i> Take Test',
                                            ['module-test', 'id' => $module->id],
                                            ['class' => 'btn btn-primary float-right']
                                        ) ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .module-content-wrapper {
        line-height: 1.8;
        font-size: 15px;
    }

    .module-content-wrapper h1,
    .module-content-wrapper h2,
    .module-content-wrapper h3,
    .module-content-wrapper h4 {
        margin-top: 1.5em;
        margin-bottom: 0.8em;
        font-weight: 600;
    }

    .module-content-wrapper h1 {
        font-size: 2em;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.3em;
    }

    .module-content-wrapper h2 {
        font-size: 1.6em;
    }

    .module-content-wrapper h3 {
        font-size: 1.3em;
    }

    .module-content-wrapper ul,
    .module-content-wrapper ol {
        margin: 1em 0;
        padding-left: 2em;
    }

    .module-content-wrapper li {
        margin: 0.5em 0;
    }

    .module-content-wrapper p {
        margin: 1em 0;
    }

    .module-content-wrapper img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin: 1em 0;
    }

    @media (max-width: 768px) {
        .module-actions .float-right {
            float: none !important;
            display: block;
            margin-top: 10px;
        }
    }
</style>