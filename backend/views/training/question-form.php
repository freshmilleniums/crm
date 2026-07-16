<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use backend\models\TrainingModuleQuestion;

/* @var $this yii\web\View */
/* @var $model backend\models\TrainingModuleQuestion */
/* @var $module backend\models\TrainingModule */
/* @var $form yii\bootstrap4\ActiveForm */

$this->title = $model->isNewRecord ? 'Create Question' : 'Update Question';
$this->params['breadcrumbs'][] = ['label' => 'Training Modules', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $module->title, 'url' => ['questions', 'moduleId' => $module->id]];
$this->params['breadcrumbs'][] = $this->title;

$existingOptions = [];
$correctAnswers = [];
if (!$model->isNewRecord && in_array($model->type, [TrainingModuleQuestion::TYPE_RADIO, TrainingModuleQuestion::TYPE_CHECKBOX])) {
    foreach ($model->options as $index => $option) {
        $existingOptions[] = $option->option_text;
        if ($option->is_correct) {
            $correctAnswers[] = $index;
        }
    }
}

$this->registerCss('
.option-row {
    margin-bottom: 10px;
}
.option-row .input-group-text {
    min-width: 100px;
}
.remove-option {
    padding: 0.375rem 0.75rem;
    text-decoration: none;
}
.remove-option:hover {
    text-decoration: underline;
}
.has-validation-error {
    border: 1px solid #dc3545;
    padding: 10px;
    border-radius: 4px;
    background: #fff5f5;
}
');
?>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="training-question-form">
                            <?php $form = ActiveForm::begin([
                                'id' => 'question-form',
                            ]); ?>

                            <?= $form->field($model, 'question_text')->textarea(['rows' => 3, 'maxlength' => true])->label('Question') ?>

                            <?= $form->field($model, 'type')->dropDownList(TrainingModuleQuestion::getTypesList(), [
                                'id' => 'question-type',
                                'prompt' => 'Select question type...',
                            ]) ?>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <?= Html::activeCheckbox($model, 'has_correct_answer', [
                                        'class' => 'custom-control-input',
                                        'id' => 'has-correct-answer',
                                        'label' => false,
                                    ]) ?>
                                    <label class="custom-control-label" for="has-correct-answer">
                                        Check answer correctness (enable to mark correct answers)
                                    </label>
                                </div>
                            </div>

                            <div id="options-container" style="display: none;">
                                <div class="form-group" id="options-group">
                                    <label>Answer Options <span class="text-danger">*</span></label>
                                    <div id="options-wrapper">
                                        <?php if (!empty($existingOptions)): ?>
                                            <?php foreach ($existingOptions as $index => $optionText): ?>
                                                <div class="option-row">
                                                    <div class="input-group">
                                                        <input type="text" name="options[]" class="form-control option-input" value="<?= Html::encode($optionText) ?>" placeholder="Option text">
                                                        <div class="input-group-append correct-checkbox-container" style="<?= $model->has_correct_answer ? '' : 'display: none;' ?>">
                                                        <span class="input-group-text">
                                                            <label style="margin: 0;">
                                                                <input type="checkbox" name="correct_answers[]" value="<?= $index ?>" class="correct-answer-checkbox" <?= in_array($index, $correctAnswers) ? 'checked' : '' ?>>
                                                                Correct
                                                            </label>
                                                        </span>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <a href="javascript:void(0);" class="btn btn-link text-primary remove-option" title="Remove">
                                                                <svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="option-row">
                                                <div class="input-group">
                                                    <input type="text" name="options[]" class="form-control option-input" placeholder="Option text">
                                                    <div class="input-group-append correct-checkbox-container" style="display: none;">
                                                    <span class="input-group-text">
                                                        <label style="margin: 0;">
                                                            <input type="checkbox" name="correct_answers[]" value="0" class="correct-answer-checkbox">
                                                            Correct
                                                        </label>
                                                    </span>
                                                    </div>
                                                    <div class="input-group-append">
                                                        <a href="javascript:void(0);" class="btn btn-link text-primary remove-option" title="Remove" style="display: none;">
                                                            <svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right mt-2">
                                        <button type="button" class="btn btn-success btn-sm" id="add-option">
                                            <i class="fa fa-plus"></i> Add Option
                                        </button>
                                    </div>
                                    <div class="text-danger mt-2" id="options-error" style="display: none; font-size: 14px;">
                                        <i class="fas fa-exclamation-circle"></i> Please add at least one answer option
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <?= Html::submitButton(($model->isNewRecord ? 'Create Question' : 'Save'), ['class' => 'btn btn-success']) ?>
                                <?= Html::a('Cancel', ['questions', 'moduleId' => $module->id], ['class' => 'btn btn-secondary ml-2']) ?>
                            </div>

                            <?php ActiveForm::end(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$typeText = TrainingModuleQuestion::TYPE_TEXT;
$typeRadio = TrainingModuleQuestion::TYPE_RADIO;
$typeCheckbox = TrainingModuleQuestion::TYPE_CHECKBOX;

$this->registerJs("
var optionIndex = " . max(count($existingOptions), 0) . ";
var formSubmitting = false;

function updateOptionsVisibility() {
    var type = parseInt($('#question-type').val());
    if (type == {$typeRadio} || type == {$typeCheckbox}) {
        $('#options-container').show();
    } else {
        $('#options-container').hide();
    }
}

function updateCorrectCheckboxesVisibility() {
    var hasCorrectAnswer = $('#has-correct-answer').is(':checked');
    
    if (hasCorrectAnswer) {
        $('.correct-checkbox-container').show();
    } else {
        $('.correct-checkbox-container').hide();
    }
}

function updateCorrectAnswerRestriction() {
    var type = parseInt($('#question-type').val());
    if (type == {$typeRadio}) {
        $('.correct-answer-checkbox').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.correct-answer-checkbox').not(this).prop('checked', false);
            }
        });
    } else {
        $('.correct-answer-checkbox').off('change');
    }
}

function updateRemoveButtons() {
    var optionRows = $('#options-wrapper .option-row');
    var removeButtons = $('#options-wrapper .remove-option');
    
    if (optionRows.length > 1) {
        removeButtons.show();
    } else {
        removeButtons.hide();
    }
}

$('#question-type').change(function() {
    updateOptionsVisibility();
    
    var type = parseInt($(this).val());
    if (type == {$typeRadio} || type == {$typeCheckbox}) {
        if ($('#options-wrapper .option-row').length === 0) {
            $('#add-option').click();
        }
    }
    updateCorrectAnswerRestriction();
});

$('#has-correct-answer').change(function() {
    updateCorrectCheckboxesVisibility();
});

$('#add-option').click(function() {
    var hasCorrectAnswer = $('#has-correct-answer').is(':checked');
    
    var html = '<div class=\"option-row\">' +
        '<div class=\"input-group\">' +
        '<input type=\"text\" name=\"options[]\" class=\"form-control option-input\" placeholder=\"Option text\">' +
        '<div class=\"input-group-append correct-checkbox-container\" style=\"' + (hasCorrectAnswer ? '' : 'display: none;') + '\">' +
        '<span class=\"input-group-text\">' +
        '<label style=\"margin: 0;\">' +
        '<input type=\"checkbox\" name=\"correct_answers[]\" value=\"' + optionIndex + '\" class=\"correct-answer-checkbox\"> Correct' +
        '</label>' +
        '</span>' +
        '</div>' +
        '<div class=\"input-group-append\">' +
        '<a href=\"javascript:void(0);\" class=\"btn btn-link text-primary remove-option\" title=\"Remove\">' +
        '<svg aria-hidden=\"true\" style=\"display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 448 512\"><path fill=\"currentColor\" d=\"M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z\"></path></svg>' +
        '</a>' +
        '</div>' +
        '</div>' +
        '</div>';
    
    $('#options-wrapper').append(html);
    optionIndex++;
    updateRemoveButtons();
    updateCorrectAnswerRestriction();
});

$(document).on('click', '.remove-option', function() {
    $(this).closest('.option-row').remove();
    updateRemoveButtons();
});

// Form validation
$('#question-form').on('submit', function(e) {
    if (formSubmitting) {
        return true;
    }
    
    var type = parseInt($('#question-type').val());
    
    if (!type) {
        e.preventDefault();
        alert('Please select a question type');
        return false;
    }
    
    if (type == {$typeRadio} || type == {$typeCheckbox}) {
        var options = $('#options-wrapper .option-input');
        var hasValidOption = false;
        
        options.each(function() {
            if ($.trim($(this).val()) !== '') {
                hasValidOption = true;
                return false;
            }
        });
        
        if (!hasValidOption) {
            e.preventDefault();
            
            $('#options-group').addClass('has-validation-error');
            $('#options-error').show();
            
            $('html, body').animate({
                scrollTop: $('#options-group').offset().top - 100
            }, 500);
            
            return false;
        } else {
            $('#options-group').removeClass('has-validation-error');
            $('#options-error').hide();
        }
        
        // Check if correct answer is marked when validation is enabled
        var hasCorrectAnswer = $('#has-correct-answer').is(':checked');
        if (hasCorrectAnswer) {
            var hasCheckedCorrect = $('.correct-answer-checkbox:checked').length > 0;
            
            if (!hasCheckedCorrect) {
                e.preventDefault();
                
                $('#options-group').addClass('has-validation-error');
                $('#correct-answer-error').remove();
                
                var errorHtml = '<div class=\"text-danger mt-2\" id=\"correct-answer-error\">' +
                    '<i class=\"fas fa-exclamation-circle\"></i> Please mark at least one correct answer' +
                    '</div>';
                $('#options-error').after(errorHtml);
                
                $('html, body').animate({
                    scrollTop: $('#options-group').offset().top - 100
                }, 500);
                
                return false;
            } else {
                $('#correct-answer-error').remove();
            }
        }
    }
    
    formSubmitting = true;
    return true;
});

$(document).on('input', '#options-wrapper .option-input', function() {
    var hasValue = false;
    $('#options-wrapper .option-input').each(function() {
        if ($.trim($(this).val()) !== '') {
            hasValue = true;
            return false;
        }
    });
    
    if (hasValue) {
        $('#options-group').removeClass('has-validation-error');
        $('#options-error').hide();
    }
});

$(document).on('change', '.correct-answer-checkbox', function() {
    if ($('.correct-answer-checkbox:checked').length > 0) {
        $('#options-group').removeClass('has-validation-error');
        $('#correct-answer-error').remove();
    }
});

updateOptionsVisibility();
updateCorrectCheckboxesVisibility();
updateCorrectAnswerRestriction();
updateRemoveButtons();
");
?>