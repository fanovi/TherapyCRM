<?php

namespace common\helpers;

/**
 * Helper class for GridView configuration
 */
class GridViewHelper
{
    /**
     * Get default pagination configuration for GridView
     * 
     * @param int $pageSize Number of items per page (default: 20)
     * @return array Pagination configuration
     */
    public static function getPaginationConfig($pageSize = 20)
    {
        return [
            'class' => 'yii\widgets\LinkPager',
            'options' => ['class' => 'pagination-wrapper px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-center'],
            'linkContainerOptions' => ['class' => 'pagination'],
            'linkOptions' => ['class' => 'page-link'],
            'activePageCssClass' => 'page-item active',
            'disabledPageCssClass' => 'page-item disabled',
            'prevPageLabel' => '‹ Precedente',
            'nextPageLabel' => 'Successiva ›',
            'firstPageLabel' => '« Prima',
            'lastPageLabel' => 'Ultima »',
            'maxButtonCount' => 7,
        ];
    }

    /**
     * Get default summary configuration for GridView
     * 
     * @param string $itemName Name of items being displayed (e.g., "terapisti", "pazienti")
     * @return string Summary HTML
     */
    public static function getSummary($itemName = 'elementi')
    {
        return '<div class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">Mostrando <b>{begin}-{end}</b> di <b>{totalCount}</b> ' . $itemName . '</div>';
    }

    /**
     * Get complete GridView pagination and summary configuration
     * 
     * @param string $itemName Name of items being displayed
     * @param int $pageSize Number of items per page
     * @return array Configuration array with 'summary' and 'pager'
     */
    public static function getGridViewConfig($itemName = 'elementi', $pageSize = 20)
    {
        return [
            'summary' => self::getSummary($itemName),
            'pager' => self::getPaginationConfig($pageSize),
        ];
    }
}

