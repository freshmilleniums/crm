<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use common\models\ProjectsDocuments;
use kartik\file\FileInput;
use backend\widgets\dynamicForm\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
/* @var $documents ProjectsDocuments[] */
/* @var $employees array */
/* @var $form yii\bootstrap4\ActiveForm */
?>

<div class="projects-form">

    <?php $form = ActiveForm::begin([
        'id' => 'projects-form-ajax',
        'action' => ['projects/create-ajax'],
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'type')->dropDownList($model::getTypeList(), [
                'prompt' => 'Select type...'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'employee_id')->dropDownList($employees, [
                'prompt' => 'Select employee...'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'net_worth')->textInput(['type' => 'number']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'roi')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
    </div>

    <?= $form->field($model, 'comment')->textarea(['rows' => 4, 'maxlength' => 5000]) ?>

    <!-- Documents Section -->
    <?php DynamicFormWidget::begin([
        'widgetContainer' => 'documents_dynamic_form_wrapper',
        'widgetBody' => '.documents_container-items',
        'widgetItem' => '.documents_item',
        'min' => 0,
        'insertButton' => '.add-document',
        'deleteButton' => '.remove-document',
        'model' => $documents[0],
        'formId' => 'projects-form-ajax',
        'formFields' => ['file'],
    ]); ?>

    <div class="panel panel-default">
        <div class="panel-heading" style="background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-bottom: none;">
            <h5 class="panel-title mb-0 d-inline-block"><?= Yii::t('app', 'Documents') ?></h5>
            <button type="button" class="btn btn-success btn-sm add-document float-right">
                <i class="fa fa-plus"></i> <?= Yii::t('app', 'Add Document') ?>
            </button>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body documents_container-items" style="border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px;">
            <?php foreach ($documents as $index => $doc): ?>
                <div class="documents_item card mb-3 border">
                    <div class="card-header bg-white">
                        <span class="panel-title-document font-weight-bold"><?= Yii::t('app', 'Document') ?>: <?= ($index + 1) ?></span>
                        <button type="button" class="btn btn-danger btn-sm remove-document float-right">
                            <i class="fa fa-minus"></i>
                        </button>
                        <div class="clearfix"></div>
                    </div>
                    <div class="card-body">
                        <?php
                        $initialPreview = [];
                        if ($doc->path) {
                            $initialPreview[] = $doc->getUrl();
                        }

                        $ext = strtolower(pathinfo($doc->path, PATHINFO_EXTENSION));
                        $isPdf = $ext === 'pdf';

                        echo $form->field($doc, "[{$index}]file")->widget(FileInput::class, [
                            'pluginOptions' => [
                                'initialPreview' => $initialPreview,
                                'initialCaption' => $doc->getFileName(),
                                'showUpload' => false,
                                'initialPreviewFileType' => $isPdf ? 'other' : 'image',
                                'previewFileIcon' => $isPdf ? '<i class="fa fa-file-pdf-o text-danger"></i>' : null,
                                'initialPreviewAsData' => true,
                                'overwriteInitial' => true,
                                'initialPreviewShowDelete' => true,
                                'allowedFileExtensions' => ['jpg', 'gif', 'png', 'jpeg', 'pdf', 'doc', 'docx', 'odt'],
                                'fileActionSettings' => ['showZoom' => true]
                            ],
                        ]);
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php DynamicFormWidget::end(); ?>

    <div class="form-group">
        <?= Html::submitButton('Create Project', [
            'class' => 'btn btn-success project-submit-btn'
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$js = <<<'EOD'
$(".documents_dynamic_form_wrapper").on("afterInsert", function(e, item) {
    $(".documents_dynamic_form_wrapper .panel-title-document").each(function(index) {
        $(this).html("Document: " + (index + 1));
    });
    $(item).find('.fileinput-remove').click();
});

$(".documents_dynamic_form_wrapper").on("afterDelete", function(e, item) {
    $(".documents_dynamic_form_wrapper .panel-title-document").each(function(index) {
        $(this).html("Document: " + (index + 1));
    });
});

$('.kv-file-remove').on('click', function(e){
    var filePreview = $(this).closest('.file-input').find('.fileinput-remove');
    if(filePreview){
        filePreview[0].click();
    }
});
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>