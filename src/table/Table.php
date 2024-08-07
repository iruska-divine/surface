<?php
/*
 * Author: zsw iszsw@qq.com
 */

namespace surface\table;

use surface\Helper;
use surface\Surface;
use surface\Component;
use surface\table\traits;
use surface\table\components;


/**
 * Class Build
 *
 * @method components\Expand expand($prop, $label) static
 * @method components\Selection selection($prop) static
 * @method components\Column column($prop, $label) static
 * @method Component component($config) static     下拉
 *
 * scopedSlots自定义组件
 * @method components\Switcher switcher($prop, $label) static 开关
 * @method components\Writable writable($prop, $label) static 可编辑文本
 * @method components\Select select($prop, $label) static     下拉
 *
 * Handler 组件
 * @method components\Button button($handler, $icon) static
 *
 * @package surface\table
 * Author: zsw iszsw@qq.com
 */
class Table extends Surface
{

    use traits\Header;

    use traits\Pagination;

    protected static $components = [
        'expand' => components\Expand::class,
        'selection' => components\Selection::class,
        'writable'  => components\Writable::class,
        'switcher'  => components\Switcher::class,
        'select'    => components\Select::class,
        'column'    => components\Column::class,
        'Table'     => components\Form::class,
        'button'    => components\Button::class,
        'component' => Component::class,
    ];

    public function page(): string
    {
        $pagination = $this->getPagination();
        $header     = $this->getHeader();
        $pagination = $pagination ? Helper::props2json($pagination->format()) : 'null';
        $header     = $header ? Helper::props2json($header->format()) : 'null';
        $options    = Helper::props2json($this->getGlobals()->format());
        $columns    = Helper::props2json($this->getColumns());

        /**@var $search Surface*/
        $search        = $this->search;
        $searchOptions = '';
        $searchColumns = '';
        if ($search) {
            // 初始化
            $search->search(true);
            $search->options([
                                 'props' => [
                                     'inline' => true,
                                 ],
                                 'submitBtn' => [
                                     'props' => [
                                         'prop' => [
                                             'icon' => 'el-icon-search',
                                         ],
                                     ]
                                 ]
                             ]);
            $search->execute();
            // 同步样式 主题
            $this->addStyle($search->getStyle());
            $this->addScript($search->getScript());

            $searchOptions = Helper::props2json($search->getGlobals()->format());
            $searchColumns = Helper::props2json($search->getColumns());
        }

        ob_start();
        include dirname(__FILE__).DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'page.php';
        return ob_get_clean();
    }

}

