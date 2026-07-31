<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use kartik\select2\Select2;
use kartik\file\FileInput;
use backend\widgets\dynamicForm\DynamicFormWidget;
use common\models\UserDocument;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $administrators array */
/* @var $documents common\models\UserDocument[] */
/* @var $isEmployee bool */
?>

    <div class="user-form">

        <?php $form = ActiveForm::begin([
            'id'      => 'user-update-form-' . $model->id,
            'action'  => Url::to(['users/update', 'id' => $model->id]),
            'options' => [
                'data-pjax'  => false,
                'enctype'    => 'multipart/form-data',
            ],
            'enableAjaxValidation'  => true,
            'enableClientValidation' => true,
            'validationUrl'         => Url::toRoute(['users/ajax-validation', 'id' => $model->id]),
        ]); ?>

        <?php if (Yii::$app->user->can('administrator') || Yii::$app->user->can('super-administrator')): ?>
            <?= $form->field($model, 'role')->dropdownList(
                $availableRoles,
                ['prompt' => 'Select a role']
            ) ?>
        <?php endif; ?>

        <?php if ($model->role === 'employee'): ?>
            <?= $form->field($model, 'administrator_id')->dropdownList(
                ArrayHelper::map($administrators, 'id', function($admin) {
                    return $admin['first_name'] . ' ' . $admin['last_name'];
                }),
                ['prompt' => 'Select Administrator']
            ) ?>

            <?php if (Yii::$app->user->can('super-administrator') || Yii::$app->user->can('administrator')): ?>
                <?= $form->field($model, 'call_center_operator_id')->dropdownList(
                    ArrayHelper::map($operators, 'id', function($op) {
                        return $op['first_name'] . ' ' . $op['last_name'];
                    }),
                    ['prompt' => 'Not assigned']
                ) ?>
            <?php endif; ?>

            <?= $form->field($model, 'substatus')->dropdownList(
                \backend\models\User::getSubstatusLabels(),
                ['prompt' => 'Select Status']
            ) ?>
        <?php endif; ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'home_phone')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'position_title')->textInput(['maxlength' => true, 'placeholder' => 'e.g., Investment Analyst']) ?>
        <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'state')->textInput(['maxlength' => true, 'placeholder' => 'e.g., CA, New York, Bavaria']) ?>

        <?= $form->field($model, 'country')->widget(Select2::class, [
            'data'          => \common\models\User::getCountries(),
            'options'       => ['placeholder' => 'Select country...'],
            'pluginOptions' => ['allowClear' => false],
        ]) ?>

        <?= $form->field($model, 'zip_code')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'hr_source')->textInput(['maxlength' => true, 'placeholder' => 'Where did they come from?']) ?>

        <?php if ($isEmployee): ?>

            <?php DynamicFormWidget::begin([
                'widgetContainer' => 'user_documents_wrapper',
                'widgetBody'      => '.user-documents-items',
                'widgetItem'      => '.user-document-item',
                'min'             => 0,
                'insertButton'    => '.add-user-document',
                'deleteButton'    => '.remove-user-document',
                'model'           => $documents[0],
                'formId'          => 'user-update-form-' . $model->id,
                'formFields'      => ['file', 'document_type'],
            ]); ?>

            <div class="panel panel-default">
                <div class="panel-heading" style="background-color:#f8f9fa;padding:10px;border:1px solid #dee2e6;border-bottom:none;">
                    <h5 class="panel-title mb-0 d-inline-block">Documents</h5>
                    <button type="button" class="btn btn-success btn-sm add-user-document float-right">
                        <i class="fa fa-plus"></i> Add Document
                    </button>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body user-documents-items" style="border:1px solid #dee2e6;padding:15px;margin-bottom:20px;">
                    <?php foreach ($documents as $index => $doc): ?>
                        <div class="user-document-item card mb-3 border">
                            <div class="card-header bg-white">
                                <span class="doc-title font-weight-bold">Document: <?= $index + 1 ?></span>
                                <button type="button" class="btn btn-danger btn-sm remove-user-document float-right">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <div class="clearfix"></div>
                            </div>
                            <div class="card-body">
                                <?php if (!$doc->isNewRecord): ?>
                                    <?= Html::activeHiddenInput($doc, "[{$index}]id") ?>
                                <?php endif; ?>

                                <?= $form->field($doc, "[{$index}]document_type")->dropDownList(
                                    UserDocument::getDocumentTypes(),
                                    ['prompt' => 'Select type...']
                                ) ?>

                                <?php
                                $initialPreview = [];
                                if ($doc->path) {
                                    $initialPreview[] = $doc->getUrl();
                                }
                                $isPdf = $doc->path && $doc->isPdf();

                                echo $form->field($doc, "[{$index}]file")->widget(FileInput::class, [
                                    'pluginOptions' => [
                                        'initialPreview'          => $initialPreview,
                                        'initialCaption'          => $doc->getFileName(),
                                        'showUpload'              => false,
                                        'initialPreviewFileType'  => $isPdf ? 'other' : 'image',
                                        'previewFileIcon'         => $isPdf ? '<i class="fa fa-file-pdf-o text-danger"></i>' : null,
                                        'initialPreviewAsData'    => true,
                                        'overwriteInitial'        => true,
                                        'initialPreviewShowDelete' => true,
                                        'allowedFileExtensions'   => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'rtf'],
                                        'fileActionSettings'      => ['showZoom' => true],
                                    ],
                                ]);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php DynamicFormWidget::end(); ?>

        <?php endif; ?>

        <div class="form-group">
            <?= Html::button('Save', [
                'class' => 'btn btn-success update-employee-send',
                'id'    => 'update-employee-send-' . $model->id,
            ]) ?>
            <?= Html::button('Cancel', ['class' => 'btn btn-secondary cancel-action']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

<?php
$js = <<<'EOD'
$(".user_documents_wrapper").on("afterInsert", function(e, item) {
    $(".user_documents_wrapper .doc-title").each(function(index) {
        $(this).html("Document: " + (index + 1));
    });
    $(item).find('.fileinput-remove').click();
});

$(".user_documents_wrapper").on("afterDelete", function(e, item) {
    $(".user_documents_wrapper .doc-title").each(function(index) {
        $(this).html("Document: " + (index + 1));
    });
});
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>