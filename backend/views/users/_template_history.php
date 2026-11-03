<?php
/* @var $employee \backend\models\User */
/* @var $sentTemplates \common\models\UserSentTemplate[] */

use yii\helpers\Html;
?>
<?php if (empty($sentTemplates)): ?>
    <p class="text-muted mb-0">No templates have been sent to this employee yet.</p>
<?php else: ?>
    <table class="table table-sm table-bordered mb-0">
        <thead class="thead-light">
        <tr>
            <th style="width:45px;">#</th>
            <th>Template</th>
            <th style="width:130px;">Sent at</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($sentTemplates as $i => $sentTemplate): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= Html::encode($sentTemplate->template->title ?? ('Template #' . $sentTemplate->template_id)) ?></td>
                <td><?= $sentTemplate->sent_at ? date('Y-m-d H:i', $sentTemplate->sent_at) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>