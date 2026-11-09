<?php

use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $sourceTask common\models\Task|null */

$sourceTask = $sourceTask ?? null;
?>

    <div class="template-clone form-group" data-task-data-url="<?= Url::to(['tasks/get-task-data']) ?>">
        <label class="control-label">Copy from template</label>
        <?= Select2::widget([
            'name'          => 'source_task_id',
            'value'         => $sourceTask ? $sourceTask->id : '',
            'initValueText' => $sourceTask ? $sourceTask->title : '',
            'options'       => [
                'id'          => 'clone-template-select',
                'placeholder' => 'Search template to copy (optional)...',
            ],
            'pluginOptions' => [
                'allowClear'         => true,
                'minimumInputLength' => 0,
                'dropdownParent'     => new JsExpression('$("#clone-template-select").closest(".modal")'),
                'ajax'               => [
                    'url'      => Url::to(['tasks/search-tasks']),
                    'dataType' => 'json',
                    'delay'    => 250,
                    'data'     => new JsExpression('function(params) { return {q: params.term, is_template: 1}; }'),
                ],
                // Result row: title + created date
                'templateResult' => new JsExpression('function(item) {
                if (item.loading) {
                    return item.text;
                }
                var $row = $("<div></div>");
                $("<div class=\"font-weight-bold\"></div>").text(item.text).appendTo($row);               
                return $row;
            }'),
            ],
        ]) ?>
        <small class="form-text text-muted">Fields will be prefilled from the selected template.</small>
    </div>

<?php
$js = <<<'EOD'
// Isolated scope: this partial is rendered inside different forms
(function() {
    var taskDataUrl = $('.template-clone').data('task-data-url');

    // Icons for non-image documents preview (keep in sync with $documentIcons in _template_clone_documents.php)
    var documentIcons = {
        pdf:      'fas fa-file-pdf text-danger',
        document: 'fas fa-file text-secondary',
        other:    'fas fa-file text-secondary'
    };

    function buildDocumentPreview(taskDocument) {
        var $link = $('<a target="_blank" class="task-document-preview"></a>')
            .attr('href', taskDocument.url)
            .attr('title', taskDocument.name);

        if (taskDocument.type === 'image') {
            $('<img alt="">').attr('src', taskDocument.url).appendTo($link);
        } else {
            $('<i></i>').addClass(documentIcons[taskDocument.type] || documentIcons.other).appendTo($link);
        }

        return $link;
    }

    function renderSourceDocuments(taskDocuments) {
        var $block = $('#clone-source-documents');
        var $list  = $block.find('.clone-source-documents-list').empty();

        if (!taskDocuments.length) {
            $block.hide();
            return;
        }

        taskDocuments.forEach(function(taskDocument) {
            var inputId = 'source-document-' + taskDocument.id;
            var $tile   = $('<div class="task-document-tile"></div>');
            var $check  = $('<div class="form-check"></div>');

            $tile.append(buildDocumentPreview(taskDocument));

            $('<input type="checkbox" class="form-check-input" name="source_document_ids[]" checked>')
                .attr('id', inputId)
                .val(taskDocument.id)
                .appendTo($check);

            $('<label class="form-check-label"></label>')
                .attr('for', inputId)
                .attr('title', taskDocument.name)
                .text(taskDocument.name)
                .appendTo($check);

            $tile.append($check);
            $list.append($tile);
        });

        $block.show();
    }

    $('#clone-template-select').on('select2:select', function(e) {
        $.getJSON(taskDataUrl, {id: e.params.data.id, is_template: 1}, function(response) {
            if (!response.success) {
                toastr.error(response.message);
                return;
            }

            var task = response.task;

            $('#task-title').val(task.title);
            $('#task-subject').val(task.subject);
            $('#task-description').val(task.description);
            $('#task-priority').val(task.priority);
            $('#task-status').val(task.status);

            renderSourceDocuments(response.documents);
        }).fail(function() {
            toastr.error('Failed to load template data');
        });
    });

    $('#clone-template-select').on('select2:clear', function() {
        renderSourceDocuments([]);
    });
})();
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>