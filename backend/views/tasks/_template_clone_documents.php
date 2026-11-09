<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $sourceTask common\models\Task|null */
/* @var $sourceDocumentIds int[] */

// Documents block for "Copy from template", filled by JS in _template_clone.php

$sourceTask        = $sourceTask ?? null;
$sourceDocumentIds = $sourceDocumentIds ?? [];
$sourceDocuments   = $sourceTask ? $sourceTask->documents : [];

$documentIcons = [
    'pdf'      => 'fas fa-file-pdf text-danger',
    'document' => 'fas fa-file text-secondary',
    'other'    => 'fas fa-file text-secondary',
];
?>

<!-- Documents copied from source template -->
<div id="clone-source-documents" class="card mb-3 border" style="<?= !empty($sourceDocuments) ? '' : 'display: none;' ?>">
    <div class="card-header bg-white font-weight-bold">Documents from template</div>
    <div class="card-body clone-source-documents-list task-document-tiles">
        <?php foreach ($sourceDocuments as $sourceDocument): ?>
            <?php $previewType = $sourceDocument->getPreviewType(); ?>
            <div class="task-document-tile">
                <a href="<?= Html::encode($sourceDocument->getUrl()) ?>"
                   target="_blank"
                   class="task-document-preview"
                   title="<?= Html::encode($sourceDocument->getFileName()) ?>">
                    <?php if ($previewType === 'image'): ?>
                        <img src="<?= Html::encode($sourceDocument->getUrl()) ?>" alt="">
                    <?php else: ?>
                        <i class="<?= $documentIcons[$previewType] ?? $documentIcons['other'] ?>"></i>
                    <?php endif; ?>
                </a>
                <div class="form-check">
                    <?= Html::checkbox(
                        'source_document_ids[]',
                        in_array((int) $sourceDocument->id, $sourceDocumentIds, true),
                        [
                            'value' => $sourceDocument->id,
                            'id'    => 'source-document-' . $sourceDocument->id,
                            'class' => 'form-check-input',
                        ]
                    ) ?>
                    <label class="form-check-label"
                           for="source-document-<?= $sourceDocument->id ?>"
                           title="<?= Html::encode($sourceDocument->getFileName()) ?>">
                        <?= Html::encode($sourceDocument->getFileName()) ?>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card-footer bg-white">
        <small class="text-muted">Checked documents will be copied to the new task.</small>
    </div>
</div>