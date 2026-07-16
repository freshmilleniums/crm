<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\web\JqueryAsset;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $operators backend\models\User[] */
/* @var $distributionSettings common\models\CallCenterDistribution[] */
/* @var $scripts common\models\CallCenterScript[] */

$this->title = 'Call Center Settings';
$this->params['breadcrumbs'][] = $this->title;

JqueryAsset::register($this);
$this->registerJsFile(
    'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js',
    ['depends' => [JqueryAsset::class]]
);

$this->registerCss('
.operator-row td { vertical-align: middle; }
.percentage-input { width: 75px; display: inline-block; }
.effective-pct { font-weight: 500; color: #28a745; }
.sortable-ghost { opacity: 0.4; }
.drag-handle { cursor: move; color: #999; }
');

$saveDistributionUrl = Url::to(['/call-center-settings/save-distribution']);
$createScriptUrl     = Url::to(['/call-center-settings/create-script']);
$updateScriptUrl     = Url::to(['/call-center-settings/update-script']);
$viewScriptUrl       = Url::to(['/call-center-settings/view-script']);
$deleteScriptUrl     = Url::to(['/call-center-settings/delete-script']);
$updateSortUrl       = Url::to(['/call-center-settings/update-sort']);

$scriptsDataProvider = new ArrayDataProvider([
    'allModels' => $scripts,
    'pagination' => false,
]);

$script = "
function showCreateScriptModal(event) {
    event.preventDefault();
    event.stopPropagation();

    var modal = $('#scriptModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Create Script');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$createScriptUrl',
        type: 'GET',
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);
            }
        },
        error: function() {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>');
        }
    });
}

function showScriptDetails(event, scriptId) {
    event.preventDefault();
    event.stopPropagation();

    var \$clickedRow = $(event.target).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.script-details-row');

    if (existingDetailsRow.length) {
        existingDetailsRow.find('.script-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }

    $('.script-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.script-details').slideUp(700, function() {
            \$this.remove();
        });
    });

    var detailsRow = $('<tr class=\"script-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"script-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    \$clickedRow.after(detailsRow);

    $.ajax({
        url: '$viewScriptUrl',
        type: 'GET',
        data: { id: scriptId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.script-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load script details');
            detailsRow.find('.script-details').hide().slideDown(700);
        }
    });
}

function showScriptEditForm(event, scriptId) {
    event.preventDefault();
    event.stopPropagation();

    var \$clickedRow = $(event.target).closest('tr');

    if (!\$clickedRow.length) {
        \$clickedRow = $(event.target).closest('.script-details-row').prev('tr');
    }

    var existingDetailsRow = \$clickedRow.next('.script-details-row');

    if (existingDetailsRow.length) {
        existingDetailsRow.find('.script-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
    }

    $('.script-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.script-details').slideUp(700, function() {
            \$this.remove();
        });
    });

    var detailsRow = $('<tr class=\"script-details-row\"><td colspan=\"' + (\$clickedRow.find('td').length || 5) + '\"><div class=\"script-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    \$clickedRow.after(detailsRow);

    $.ajax({
        url: '$updateScriptUrl',
        type: 'GET',
        data: { id: scriptId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.script-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load edit form');
            detailsRow.find('.script-details').hide().slideDown(700);
        }
    });
}

$(document).on('submit', '#script-ajax-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var btn  = form.find('.script-form-submit');
    var url  = form.prop('action');

    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }

    btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin mr-1\"></i> Saving...');

    $.ajax({
        url: url,
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);

                    $('.script-details-row').find('.script-details').slideUp(700, function() {
                        $('.script-details-row').remove();
                    });

                    location.reload();
                } else {
                    if (typeof response.tpl != 'undefined') {
                        var detailsRow = $('.script-details-row');
                        detailsRow.find('.content').html(response.tpl);
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() { toastr.error('An error occurred while saving script'); },
        complete: function() {
            btn.prop('disabled', false).html('Save');
        }
    });
});

$(document).on('submit', '#script-create-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var btn  = form.find('.script-form-submit');

    if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
    }

    btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin mr-1\"></i> Saving...');

    $.ajax({
        url: form.prop('action'),
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#scriptModal').modal('hide');
                    location.reload();
                } else {
                    if (typeof response.tpl != 'undefined') {
                        $('#scriptModal .modal-body').html(response.tpl);
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() { toastr.error('An error occurred while creating script'); },
        complete: function() {
            btn.prop('disabled', false).html('Create Script');
        }
    });
});

$(document).on('click', '.cancel-action', function(e) {
    e.preventDefault();
    $(this).closest('.script-details-row').find('.script-details').slideUp(700, function() {
        $(this).closest('.script-details-row').remove();
    });
});

$(document).on('click', '.edit-script-btn', function(e) {
    e.preventDefault();
    var scriptId = $(this).data('script-id');
    showScriptEditForm(e, scriptId);
});

$('#scriptModal').on('hidden.bs.modal', function() {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove();
    }
});

// Operators distribution
function recalcEffective() {
    var customTotal = 0;
    var autoCount   = 0;

    $('.operator-row').each(function() {
        var isCustom = $(this).find('.custom-checkbox').is(':checked');
        var pct      = parseInt($(this).find('.pct-input').val()) || 0;
        if (isCustom && pct > 0) {
            customTotal += pct;
        } else {
            autoCount++;
        }
    });

    var remainder = 100 - customTotal;
    var autoPct   = autoCount > 0 ? Math.round(remainder / autoCount) : 0;

    $('.operator-row').each(function() {
        var opId     = $(this).data('operator-id');
        var isCustom = $(this).find('.custom-checkbox').is(':checked');
        var pct      = parseInt($(this).find('.pct-input').val()) || 0;
        var eff      = (isCustom && pct > 0) ? pct + '%' : (!isCustom && autoCount > 0 ? autoPct + '%' : '—');
        $('#eff-' + opId).text(eff);
    });

    if (customTotal > 100) {
        $('#total-pct-info').html('<span class=\"text-danger\"><i class=\"fas fa-exclamation-triangle\"></i> Custom total exceeds 100% (' + customTotal + '%)</span>');
        $('#btn-save-distribution').prop('disabled', true);
    } else {
        $('#total-pct-info').html('Custom total: <strong>' + customTotal + '%</strong>');
        $('#btn-save-distribution').prop('disabled', false);
    }
}

$(document).on('change', '.custom-checkbox', function() {
    var pctInput = $(this).closest('tr').find('.pct-input');
    pctInput.prop('disabled', !$(this).is(':checked'));
    if (!$(this).is(':checked')) pctInput.val('');
    recalcEffective();
});

$(document).on('input', '.pct-input', function() {
    recalcEffective();
});

$('#btn-distribute-equal').on('click', function() {
    $('.custom-checkbox').prop('checked', false);
    $('.pct-input').prop('disabled', true).val('');
    recalcEffective();
});

$('#btn-save-distribution').on('click', function() {
    var data  = {};
    var valid = true;

    $('.operator-row').each(function() {
        var opId     = $(this).data('operator-id');
        var isCustom = $(this).find('.custom-checkbox').is(':checked') ? 1 : 0;
        var pct      = parseInt($(this).find('.pct-input').val()) || 0;

        if (isCustom && (pct < 1 || pct > 100)) {
            toastr.error('Enter a valid percentage (1-100) for all custom operators');
            valid = false;
            return false;
        }

        data[opId] = { is_custom: isCustom, percentage: pct };
    });

    if (!valid) return;

    var btn = $(this);
    btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin mr-1\"></i> Saving...');

    $.ajax({
        url: '$saveDistributionUrl',
        type: 'POST',
        data: { distribution: data },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message || 'Save error');
            }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() {
            btn.prop('disabled', false).html('<i class=\"fas fa-save mr-1\"></i> Save distribution');
        }
    });
});

var scriptsTbody = $('#scripts-grid-table tbody').get(0);
if (scriptsTbody) {
    new Sortable(scriptsTbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        filter: '.script-details-row',
        onEnd: function() {
            var ids = [];
            $('#scripts-grid-table tbody tr:not(.script-details-row)').each(function() {
                ids.push($(this).data('id'));
            });
            $.ajax({
                url: '$updateSortUrl',
                type: 'POST',
                data: { ids: ids },
                success: function(r) {
                    r = JSON.parse(r);
                    if (r.success) toastr.success('Sort order updated');
                }
            });
        }
    });
}

recalcEffective();
";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="card card-secondary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="cc-settings-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active"
                                       id="tab-scripts-tab"
                                       data-toggle="pill"
                                       href="#tab-scripts"
                                       role="tab"
                                       aria-controls="tab-scripts"
                                       aria-selected="true">
                                        Scripts
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link"
                                       id="tab-operators-tab"
                                       data-toggle="pill"
                                       href="#tab-operators"
                                       role="tab"
                                       aria-controls="tab-operators"
                                       aria-selected="false">
                                        Operators
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content" id="cc-settings-tabContent">

                                <!-- Scripts tab -->
                                <div class="tab-pane fade show active"
                                     id="tab-scripts"
                                     role="tabpanel"
                                     aria-labelledby="tab-scripts-tab">

                                    <div class="mb-3">
                                        <?= Html::a('Create Script', '#', [
                                            'class' => 'btn btn-success',
                                            'onclick' => 'showCreateScriptModal(event); return false;'
                                        ]) ?>
                                    </div>

                                    <?= GridView::widget([
                                        'dataProvider' => $scriptsDataProvider,
                                        'tableOptions' => [
                                            'class' => 'table table-striped table-bordered',
                                            'id'    => 'scripts-grid-table',
                                        ],
                                        'rowOptions' => function ($model) {
                                            return ['data-id' => $model->id];
                                        },
                                        'columns' => [
                                            [
                                                'label'  => '',
                                                'format' => 'raw',
                                                'value'  => function () {
                                                    return '<i class="fas fa-bars drag-handle"></i>';
                                                },
                                                'options' => ['style' => 'width:40px'],
                                                'contentOptions' => ['class' => 'text-center'],
                                            ],
                                            [
                                                'class' => 'yii\grid\SerialColumn',
                                                'options' => ['style' => 'width:40px'],
                                            ],
                                            [
                                                'attribute' => 'title',
                                                'format'    => 'raw',
                                                'value'     => function ($model) {
                                                    return Html::encode($model->title);
                                                },
                                            ],
                                            [
                                                'attribute'      => 'is_active',
                                                'label'          => 'Status',
                                                'format'         => 'raw',
                                                'value'          => function ($model) {
                                                    return $model->is_active
                                                        ? '<span class="badge badge-success">Active</span>'
                                                        : '<span class="badge badge-secondary">Inactive</span>';
                                                },
                                                'options'        => ['style' => 'width:150px'],
                                                'contentOptions' => ['class' => 'text-center'],
                                            ],
                                            [
                                                'attribute' => 'created_at',
                                                'format'    => 'raw',
                                                'value'     => function ($model) {
                                                    return date('m/d/Y H:i', $model->created_at);
                                                },
                                                'options'   => ['style' => 'width:160px'],
                                            ],
                                            [
                                                'class'    => 'yii\grid\ActionColumn',
                                                'template' => '{view} {update} {delete}',
                                                'options' => ['style' => 'width:140px'],
                                                'buttons'  => [
                                                    'view' => function ($url, $model, $key) {
                                                        return Html::a(
                                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                                            '#',
                                                            [
                                                                'title'      => Yii::t('app', 'View'),
                                                                'onclick'    => 'showScriptDetails(event, ' . $model->id . '); return false;',
                                                                'data-pjax'  => '0',
                                                            ]
                                                        );
                                                    },
                                                    'update' => function ($url, $model, $key) {
                                                        return Html::a(
                                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M498 142l-46 46c-5 5-13 5-17 0L324 77c-5-5-5-12 0-17l46-46c19-19 49-19 68 0l60 60c19 19 19 49 0 68zm-214-42L22 362 0 484c-3 16 12 30 28 28l122-22 262-262c5-5 5-13 0-17L301 100c-4-5-12-5-17 0zM124 340c-5-6-5-14 0-20l154-154c6-5 14-5 20 0s5 14 0 20L144 340c-6 5-14 5-20 0zm-36 84h48v36l-64 12-32-31 12-65h36v48z"></path></svg>',
                                                            '#',
                                                            [
                                                                'title'      => Yii::t('app', 'Update'),
                                                                'onclick'    => 'showScriptEditForm(event, ' . $model->id . '); return false;',
                                                                'data-pjax'  => '0',
                                                            ]
                                                        );
                                                    },
                                                    'delete' => function ($url, $model, $key) use ($deleteScriptUrl) {
                                                        $deleteUrl = $deleteScriptUrl . '?id=' . $model->id;
                                                        $confirmTitle = Html::encode(addslashes($model->title));
                                                        return Html::a(
                                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                                            '#',
                                                            [
                                                                'title'     => Yii::t('app', 'Delete'),
                                                                'onclick'   => "if (!confirm('Delete script \"{$confirmTitle}\"?')) return false; var btn = $(this); btn.prop('disabled', true); $.ajax({ url: '{$deleteUrl}', type: 'POST', success: function(r) { r = JSON.parse(r); if (r.success) { toastr.success(r.message); btn.closest('tr').fadeOut(400, function() { $(this).next('.script-details-row').remove(); $(this).remove(); }); } else { toastr.error(r.message || 'Delete error'); btn.prop('disabled', false); } }, error: function() { toastr.error('An error occurred'); btn.prop('disabled', false); } }); return false;",
                                                                'data-pjax' => '0',
                                                            ]
                                                        );
                                                    },
                                                ],
                                            ],
                                        ],
                                        'summaryOptions' => ['class' => 'summary mb-2'],
                                        'pager' => [
                                            'class' => 'yii\bootstrap4\LinkPager',
                                        ],
                                    ]); ?>

                                </div>

                                <!-- Operators tab -->
                                <div class="tab-pane fade"
                                     id="tab-operators"
                                     role="tabpanel"
                                     aria-labelledby="tab-operators-tab">

                                    <?php if (empty($operators)): ?>
                                        <div class="alert alert-secondary">
                                            <p class="mb-0">No phone operators found in the system.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3 d-flex align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-distribute-equal">
                                                Reset to auto
                                            </button>
                                            <span class="ml-3 text-muted small" id="total-pct-info"></span>
                                        </div>

                                        <table class="table table-striped table-bordered" id="operators-table">
                                            <thead>
                                            <tr>
                                                <th>Operator</th>
                                                <th>Email</th>
                                                <th style="width:120px" class="text-center">Custom %</th>
                                                <th style="width:120px" class="text-center">Percentage</th>
                                                <th style="width:110px" class="text-center">Effective %</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($operators as $operator):
                                                $setting    = $distributionSettings[$operator->id] ?? null;
                                                $isCustom   = $setting ? (int)$setting->is_custom : 0;
                                                $percentage = $setting ? $setting->percentage : null;
                                                ?>
                                                <tr class="operator-row" data-operator-id="<?= $operator->id ?>">
                                                    <td><?= Html::encode($operator->getFullName()) ?></td>
                                                    <td><?= Html::encode($operator->email) ?></td>
                                                    <td class="text-center">
                                                        <input type="checkbox"
                                                               class="custom-checkbox"
                                                               data-operator-id="<?= $operator->id ?>"
                                                            <?= $isCustom ? 'checked' : '' ?>>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number"
                                                               class="form-control form-control-sm percentage-input pct-input mx-auto"
                                                               data-operator-id="<?= $operator->id ?>"
                                                               value="<?= $percentage ?? '' ?>"
                                                               min="1" max="100"
                                                            <?= !$isCustom ? 'disabled' : '' ?>
                                                               placeholder="—">
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="effective-pct" id="eff-<?= $operator->id ?>">—</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <div class="mt-3">
                                            <button type="button" class="btn btn-success" id="btn-save-distribution">
                                                Save distribution
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script modal (create only) -->
<div class="modal fade script-modal" id="scriptModal" tabindex="-1" role="dialog" aria-labelledby="scriptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scriptModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>