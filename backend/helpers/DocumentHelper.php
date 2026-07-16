<?php

namespace backend\helpers;

use common\models\TemplatesDocuments;
use yii\helpers\Html;

/**
 * Helper class for document rendering
 */
class DocumentHelper
{
    /**
     * Get icon HTML for document type
     * @param TemplatesDocuments $document
     * @return string
     */
    public static function getIconHtml($document)
    {
        $icons = [
            'pdf' => '<i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>',
            'spreadsheet' => '<i class="fas fa-file-excel fa-3x text-success mb-2"></i>',
            'archive' => '<i class="fas fa-file-archive fa-3x text-warning mb-2"></i>',
            'document' => '<i class="fas fa-file-alt fa-3x text-info mb-2"></i>',
            'other' => '<i class="fas fa-file fa-3x text-secondary mb-2"></i>',
        ];

        $type = $document->getPreviewType();
        return $icons[$type] ?? $icons['other'];
    }

    /**
     * Render document preview
     * @param TemplatesDocuments $document
     * @return string
     */
    public static function renderPreview($document)
    {
        if ($document->getPreviewType() === 'image') {
            return '<a href="' . Html::encode($document->getUrl()) . '" target="_blank">
                        <img src="' . Html::encode($document->getUrl()) . '" 
                             class="img-thumbnail mb-2" 
                             style="max-width: 100%; max-height: 100px;" 
                             alt="Document">
                    </a>';
        }

        return self::getIconHtml($document) .
            '<div class="small text-truncate" style="max-width: 100%">' .
            Html::encode($document->getFileName()) .
            '</div>';
    }
}