<?php
use yii\helpers\Html;
use common\models\Task;

/* @var $this yii\web\View */
/* @var $model common\models\Task */
?>

<div class="task-details-content p-3" style="background-color: #f8f9fa;">
    <div class="row">
        <div class="col-md-6">
            <p>
                <strong>Task Name:</strong> <?= Html::encode($model->title) ?>
                <?php if ($model->is_training): ?>
                    <span class="badge badge-warning ml-2">
                        <i class="fas fa-graduation-cap"></i> Training Task
                    </span>
                <?php endif; ?>
            </p>
            <p><strong>Subject:</strong> <?= Html::encode($model->subject) ?></p>
            <p><strong>Description:</strong></p>
            <div class="border rounded p-2 mb-2" style="background-color: white;">
                <?= nl2br(Html::encode($model->description)) ?>
            </div>
        </div>
        <div class="col-md-6">
            <p><strong>Status:</strong> <?= Html::encode($model->getStatusName()) ?></p>

            <p><strong>Priority:</strong> <?= Html::encode($model->getPriorityName()) ?></p>

            <p><strong>Deadline:</strong>
                <?php
                if (!empty($model->due_date)) {
                    $timestamp = is_numeric($model->due_date) ? $model->due_date : strtotime($model->due_date);
                    echo date('m/d/Y H:i', $timestamp);
                } else {
                    echo 'N/A';
                }
                ?>
            </p>
        </div>
    </div>

    <?php if ($model->documents): ?>
        <div class="mt-3">
            <h6><strong>Files:</strong></h6>
            <div class="row">
                <?php foreach ($model->documents as $document): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                        <div class="card shadow-sm" style="height: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 8px;">
                            <div class="card-body p-1 d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 150px;">
                                <?php if ($document->isImage()): ?>
                                    <a href="<?= $document->getUrl() ?>" target="_blank">
                                        <img src="<?= $document->getUrl() ?>"
                                             class="img-thumbnail mb-2"
                                             style="max-width: 100%; max-height: 100px;"
                                             alt="Document">
                                    </a>
                                <?php elseif ($document->isPdf()): ?>
                                    <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                    <div class="small text-truncate" style="max-width: 100%">
                                        <?= Html::encode($document->getFileName()) ?>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-file fa-3x text-secondary mb-2"></i>
                                    <div class="small text-truncate" style="max-width: 100%">
                                        <?= Html::encode($document->getFileName()) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-2">
                                    <?= Html::a(
                                        '<i class="fas fa-download"></i>',
                                        ['download-document', 'id' => $document->id],
                                        ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Download']
                                    ) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <?php if ($model->status == Task::STATUS_NEW): ?>
            <?= Html::button(
                '<i class="fas fa-play"></i> Start Task',
                [
                    'class' => 'btn btn-success',
                    'onclick' => 'startTask(event, ' . $model->id . ')'
                ]
            ) ?>
        <?php elseif ($model->status == Task::STATUS_IN_PROGRESS): ?>
            <?= Html::button(
                '<i class="fas fa-check"></i> Mark as Completed',
                [
                    'class' => 'btn btn-primary',
                    'onclick' => 'completeTask(event, ' . $model->id . ')'
                ]
            ) ?>
        <?php elseif ($model->status == Task::STATUS_COMPLETED): ?>
            <span class="text-success" style="font-size: 1.1em;">
                <i class="fas fa-check-circle"></i> Task Completed
            </span>
        <?php endif; ?>
    </div>
</div>