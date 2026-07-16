<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use common\models\Template;
use common\models\TemplatesDocuments;
use kartik\file\FileInput;
use backend\widgets\dynamicForm\DynamicFormWidget;
use backend\widgets\tinymce\TinyMceWidget;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
/* @var $documents TemplatesDocuments[] */
/* @var $form yii\bootstrap4\ActiveForm */

if (count($documents) == 0) {
    $documents = [new TemplatesDocuments()];
}
?>

    <div class="templates-form">

        <?php $form = ActiveForm::begin([
            'id' => 'templates-form-update',
            'action' => Url::to(['update', 'id' => $model->id]),
            'options' => ['enctype' => 'multipart/form-data']
        ]); ?>

        <?= $form->field($model, 'category')->dropDownList(Template::getCategoryList(), [
            'prompt' => 'Select category...'
        ]) ?>

        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'body')->widget(TinyMceWidget::class, [
            'preset' => 'template',
            'clientOptions' => [
                'height' => 400,
                'toolbar_sticky' => true,
                'toolbar_mode' => 'sliding',
                'paste_as_text' => false,
                'paste_retain_style_properties' => 'color font-size font-weight text-align',
                'content_style' => 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
                'style_formats' => [
                    ['title' => 'Headers', 'items' => [
                        ['title' => 'Heading 1', 'format' => 'h1'],
                        ['title' => 'Heading 2', 'format' => 'h2'],
                        ['title' => 'Heading 3', 'format' => 'h3'],
                        ['title' => 'Heading 4', 'format' => 'h4']
                    ]],
                    ['title' => 'Alignment', 'items' => [
                        ['title' => 'Left align', 'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div', 'styles' => ['text-align' => 'left']],
                        ['title' => 'Center', 'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div', 'styles' => ['text-align' => 'center']],
                        ['title' => 'Right align', 'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div', 'styles' => ['text-align' => 'right']],
                        ['title' => 'Justify', 'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div', 'styles' => ['text-align' => 'justify']]
                    ]],
                ]
            ]
        ])->label('Body') ?>

        <div class="alert alert-secondary">
            <h5><strong>Available Macros:</strong></h5>
            <p><strong>How to use macros:</strong> Use macros in double curly braces for automatic replacement when sending the template.</p>
            <p><strong>Available variables:</strong></p>
            <div class="row">
                <div class="col-md-6">
                    <ul>
                        <li><strong>&#123;&#123;FullName&#125;&#125;</strong> – Employee full name</li>
                        <li><strong>&#123;&#123;FirstName&#125;&#125;</strong> – First name</li>
                        <li><strong>&#123;&#123;LastName&#125;&#125;</strong> – Last name</li>
                        <li><strong>&#123;&#123;Email&#125;&#125;</strong> – Email address</li>
                        <li><strong>&#123;&#123;Position&#125;&#125;</strong> – Position title</li>
                        <li><strong>&#123;&#123;PhoneNumber&#125;&#125;</strong> – Phone number</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li><strong>&#123;&#123;Address&#125;&#125;</strong> – Address</li>
                        <li><strong>&#123;&#123;City&#125;&#125;</strong> – City</li>
                        <li><strong>&#123;&#123;State&#125;&#125;</strong> – State/Region</li>
                        <li><strong>&#123;&#123;Country&#125;&#125;</strong> – Country</li>
                        <li><strong>&#123;&#123;Project&#125;&#125;</strong> – Current project name</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php DynamicFormWidget::begin([
            'widgetContainer' => 'documents_dynamic_form_wrapper',
            'widgetBody' => '.documents_container-items',
            'widgetItem' => '.documents_item',
            'min' => 0,
            'insertButton' => '.add-document',
            'deleteButton' => '.remove-document',
            'model' => $documents[0],
            'formId' => 'templates-form-update',
            'formFields' => [
                'file',
            ],
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
                            <?php if (!$doc->isNewRecord) {
                                echo Html::activeHiddenInput($doc, "[{$index}]id");
                            } ?>

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
                                    'allowedFileExtensions' => ['jpg', 'gif', 'png', 'jpeg', 'pdf', 'doc', 'docx', 'odt', 'txt', 'xlsx', 'xls', 'csv', 'zip'],
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
            <?= Html::button('Save', [
                'class' => 'btn btn-success template-update-btn'
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
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>