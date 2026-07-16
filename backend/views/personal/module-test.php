<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use backend\models\TrainingModuleQuestion;

/* @var $this yii\web\View */
/* @var $module backend\models\TrainingModule */
/* @var $questions backend\models\TrainingModuleQuestion[] */
/* @var $progress backend\models\UserTrainingProgress */

$this->title = 'Test: ' . $module->title;
$this->params['breadcrumbs'][] = ['label' => 'Training', 'url' => ['training']];
$this->params['breadcrumbs'][] = ['label' => $module->title, 'url' => ['module', 'id' => $module->id]];
$this->params['breadcrumbs'][] = 'Test';
?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
                    </div>
                    <div class="card-body">

                        <div class="alert alert-danger" id="validation-summary" style="display: none;">
                            <h5><i class="fas fa-exclamation-triangle"></i> Please answer all questions</h5>
                            <ul id="validation-list"></ul>
                        </div>

                        <?php $form = ActiveForm::begin([
                            'id' => 'module-test-form',
                            'action' => ['submit-module-test', 'id' => $module->id],
                        ]); ?>

                        <?php foreach ($questions as $index => $question): ?>
                            <div class="question-block" data-question-id="<?= $question->id ?>">
                                <div class="question-title">
                                    <span class="question-number"><?= $index + 1 ?></span>
                                    <?= Html::encode($question->question_text) ?>
                                    <span class="required-mark" style="color: red; display: none;">*</span>
                                </div>
                                <div class="required-message" style="color: red; display: none; margin-top: 5px;">
                                    This question is required
                                </div>

                                <div class="question-content" data-question-type="<?= $question->type ?>">
                                    <?php if ($question->type == TrainingModuleQuestion::TYPE_TEXT): ?>
                                        <textarea
                                                name="answers[<?= $question->id ?>]"
                                                id="answer_<?= $question->id ?>"
                                                class="form-control question-input"
                                                data-question-id="<?= $question->id ?>"
                                                rows="4"
                                                placeholder="Enter your answer here..."></textarea>

                                    <?php elseif ($question->type == TrainingModuleQuestion::TYPE_RADIO): ?>
                                        <?php foreach ($question->options as $optIndex => $option): ?>
                                            <div class="form-check">
                                                <input
                                                        type="radio"
                                                        name="answers[<?= $question->id ?>]"
                                                        id="answer_<?= $question->id ?>_<?= $optIndex ?>"
                                                        value="<?= $option->id ?>"
                                                        class="form-check-input question-input"
                                                        data-question-id="<?= $question->id ?>">
                                                <label class="form-check-label" for="answer_<?= $question->id ?>_<?= $optIndex ?>">
                                                    <?= Html::encode($option->option_text) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php elseif ($question->type == TrainingModuleQuestion::TYPE_CHECKBOX): ?>
                                        <?php foreach ($question->options as $optIndex => $option): ?>
                                            <div class="form-check">
                                                <input
                                                        type="checkbox"
                                                        name="answers[<?= $question->id ?>][]"
                                                        id="answer_<?= $question->id ?>_<?= $optIndex ?>"
                                                        value="<?= $option->id ?>"
                                                        class="form-check-input question-input"
                                                        data-question-id="<?= $question->id ?>">
                                                <label class="form-check-label" for="answer_<?= $question->id ?>_<?= $optIndex ?>">
                                                    <?= Html::encode($option->option_text) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-actions mt-4 pt-3 border-top">
                            <?= Html::a(
                                '<i class="fas fa-arrow-left"></i> Back to Module',
                                ['module', 'id' => $module->id],
                                ['class' => 'btn btn-default']
                            ) ?>
                            <?= Html::submitButton(
                                ' Submit Test',
                                ['class' => 'btn btn-success float-right', 'id' => 'submit-test-btn']
                            ) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal fade" id="results-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" id="modal-header">
                    <h5 class="modal-title" id="modal-title">Test Results</h5>
                </div>
                <div class="modal-body" id="modal-body">
                    <!-- Results will be inserted here -->
                </div>
                <div class="modal-footer" id="modal-footer">
                    <!-- Buttons will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <style>
        .question-block {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .question-block.has-error {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .question-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 15px;
            color: #333;
        }

        .question-number {
            display: inline-block;
            background: #6c757d;
            color: white;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            margin-right: 10px;
            font-size: 14px;
        }

        .question-content {
            margin-top: 15px;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-check {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            transition: background 0.2s ease;
        }

        .form-check:hover {
            background: #f8f9fa;
        }

        .form-check-input {
            margin-top: 0.4rem;
            cursor: pointer;
        }

        .form-check-label {
            cursor: pointer;
            margin-left: 5px;
        }

        .modal-header.bg-success {
            background-color: #28a745 !important;
            color: white;
        }

        .modal-header.bg-warning {
            background-color: #ffc107 !important;
            color: #212529;
        }

        @media (max-width: 768px) {
            .question-block {
                padding: 15px;
            }

            .question-title {
                font-size: 15px;
            }

            .question-number {
                width: 25px;
                height: 25px;
                line-height: 25px;
                font-size: 12px;
            }

            .form-actions .float-right {
                float: none !important;
                display: block;
                width: 100%;
                margin-top: 10px;
            }
        }
    </style>

<?php
$submitUrl = Url::to(['submit-module-test', 'id' => $module->id]);
$moduleUrl = Url::to(['module', 'id' => $module->id]);
$trainingUrl = Url::to(['training']);

$js = "
var validationActive = false;

function isQuestionAnswered(questionId) {
    var questionBlock = $('[data-question-id=\"' + questionId + '\"]');
    var questionType = questionBlock.find('[data-question-type]').data('question-type');
    
    switch(questionType) {
        case 1: // TEXT
            var textValue = $('#answer_' + questionId).val();
            return textValue && textValue.trim().length > 0;
            
        case 2: // RADIO
            return $('input[name=\"answers[' + questionId + ']\"]:checked').length > 0;
            
        case 3: // CHECKBOX
            return $('input[name=\"answers[' + questionId + '][]\"]:checked').length > 0;
            
        default:
            return false;
    }
}

function updateQuestionValidation(questionId) {
    var questionBlock = $('.question-block[data-question-id=\"' + questionId + '\"]');
    var isAnswered = isQuestionAnswered(questionId);    
   
    if (validationActive && !isAnswered) {
        questionBlock.addClass('has-error');
        questionBlock.find('.required-mark').show();
        questionBlock.find('.required-message').show();
    } else {
        questionBlock.removeClass('has-error');
        questionBlock.find('.required-mark').hide();
        questionBlock.find('.required-message').hide();
    }
}

function validateAllQuestions() {
    var errors = [];
    
    $('.question-block').each(function(index) {
        var questionId = $(this).data('question-id');
        var questionTitle = $(this).find('.question-title').clone();        
        questionTitle.find('.question-number, .required-mark').remove();
        var cleanTitle = questionTitle.text().trim();
        
        updateQuestionValidation(questionId);
        
        if (!isQuestionAnswered(questionId)) {
            errors.push('Question ' + (index + 1) + ': ' + cleanTitle);
        }
    });
    
    return errors;
}

function updateValidationSummary(errors, shouldScroll) {
    var summary = $('#validation-summary');
    var list = $('#validation-list');
    
    if (errors.length > 0 && validationActive) {
        list.empty();
        errors.forEach(function(error) {
            list.append('<li>' + error + '</li>');
        });
        summary.show();        
      
        if (shouldScroll === true) {
            var firstError = $('.question-block.has-error').first();
            if (firstError.length > 0) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
        }
    } else {
        summary.hide();
    }
}

$('#module-test-form').on('submit', function(e) {
    e.preventDefault();
    
    validationActive = true;
    var errors = validateAllQuestions();
    
    if (errors.length > 0) {
        updateValidationSummary(errors, true);
        return false;
    }
    
    var submitBtn = $('#submit-test-btn');
    submitBtn.prop('disabled', true);
    submitBtn.html('<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span> Submitting...');
    
    $.ajax({
        url: '{$submitUrl}',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.success) {
                var headerClass = response.passed ? 'bg-success' : 'bg-warning';
                var icon = response.passed ? 'fa-check-circle' : 'fa-exclamation-triangle';
                var title = response.passed ? 'Test Passed!' : 'Test Failed';
                
                var bodyHtml = '<div class=\"text-center\">';
                
                if (response.passed) {
                    bodyHtml += '<h4><i class=\"fas ' + icon + '\"></i> Congratulations!</h4>';
                    bodyHtml += '<p>You have successfully completed this module.</p>';
                    bodyHtml += '<p>You can now proceed to the next module.</p>';
                } else {
                    bodyHtml += '<h4><i class=\"fas ' + icon + '\"></i> Not Passed</h4>';
                    bodyHtml += '<p>You need to review the module materials and try again.</p>';                  
                }
                
                bodyHtml += '</div>';
                
                // Footer buttons
                var footerHtml = '';
                if (response.passed) {
                    footerHtml = '<a href=\"{$trainingUrl}\" class=\"btn btn-primary\">Back to Training</a>';
                } else {
                    footerHtml = '<a href=\"{$moduleUrl}\" class=\"btn btn-secondary ml-2\">Back to Training</a>';
                }
                
                $('#modal-header').removeClass().addClass('modal-header ' + headerClass);
                $('#modal-title').text(title);
                $('#modal-body').html(bodyHtml);
                $('#modal-footer').html(footerHtml);
                $('#results-modal').modal('show');
            } else {
                alert('Error: ' + (response.message || 'Unknown error occurred'));
                submitBtn.prop('disabled', false);
                submitBtn.html(' Submit Test');
            }
        },
        error: function() {
            alert('An error occurred while submitting the test. Please try again.');
            submitBtn.prop('disabled', false);
            submitBtn.html(' Submit Test');
        }
    });
});

$(document).on('input change', '.question-input', function() {          
    var questionId = $(this).data('question-id');
    var questionBlock = $('[data-question-id=\"' + questionId + '\"]');
    questionBlock.removeClass('has-error');
    questionBlock.find('.required-mark').hide();
    questionBlock.find('.required-message').hide();   
    $('#validation-summary').hide();
});

$(document).ready(function() {
    validationActive = false;
});
";

$this->registerJs($js, \yii\web\View::POS_READY);
?>