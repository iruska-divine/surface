<?php

namespace surface;

use surface\exception\SurfaceException;

/**
 *
 * surface 公共类
 *
 * Class Surface
 *
 * @package surface
 * Author: zsw iszsw@qq.com
 */
abstract class Surface
{
    use GlobalsTrait;
    use ColumnTrait;

    /**
     * 资源文件CDN
     */
    const CDN_DOMAIN = '//cdn.jsdelivr.net/gh/iszsw/surface-src@main';

    /**
     * 唯一标识
     *
     * @var string
     */
    protected $id;

    protected $script = [];

    protected $style = [];

    /**
     * 组件
     *
     * @var array
     */
    protected static $components = [];

    /**
     * 组件配置
     *
     * @var Config
     */
    protected $config;

    /**
     * 延迟执行 传入闭包时延迟执行
     * 只能通过继承覆盖
     * 如果需要立即执行设置false
     *
     * @var bool
     */
    protected $delay = true;

    /**
     * 待处理闭包
     *
     * @var \Closure|null
     */
    protected $closure;

    /**
     * 搜索表单
     *
     * @var string
     */
    protected $search = '';

    /**
     * 类型 下划线小写
     * @var string
     */
    protected $name;

    public function __construct(?\Closure $closure = null)
    {
        $name = explode('\\', get_called_class());
        $this->name = Helper::snake(end($name));

        $this->init();

        if (!is_null($closure))
        {
            $this->closure = $closure;
            $this->delay || $this->execute();
        }
    }

    /**
     * 立即执行
     *
     * @return $this
     * @throws SurfaceException
     */
    public function execute()
    {
        if ( ! is_null($this->closure))
        {
            static::dispose($this->closure, [$this]);
            $this->closure = null;
        }

        return $this;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::make($name, $arguments);
    }

    public function __call($name, $arguments)
    {
        return $this->make($name, $arguments);
    }

    public static function make($name, $arguments)
    {
        $component = static::$components[$name] ?? null;

        if ( !$component)
        {
            throw new SurfaceException("Component:{$name}  is not founded!");
        }

        return static::dispose($component, $arguments);
    }

    public static function getServers()
    {
        return static::$components;
    }

    protected static function dispose($server, $ages = [])
    {
        if ($server instanceof \Closure || is_array($server))
        {
            return call_user_func_array($server, $ages);
        } elseif (class_exists($server))
        {
            return (new \ReflectionClass($server))->newInstanceArgs($ages);
        } else
        {
            return $server;
        }
    }

    public static function bind($name, $call)
    {
        static::$components[$name] = $call;
    }

    public function addScript($script)
    {
        if (is_array($script))
        {
            foreach ($script as $v)
            {
                $this->addResources($v);
            }
        } else
        {
            $this->addResources($script);
        }

        return $this;
    }

    public function addStyle($style)
    {
        if (is_array($style))
        {
            foreach ($style as $v)
            {
                $this->addResources($v, 'style');
            }
        } else
        {
            $this->addResources($style, 'style');
        }

        return $this;
    }

    private function addResources($resource, $type = 'script')
    {
        if ($type === 'script')
        {
            if ( ! in_array($resource, $this->script))
            {
                $this->script[] = $resource;
            }
        } else
        {
            if ( ! in_array($resource, $this->style))
            {
                $this->style[] = $resource;
            }
        }

        return $this;
    }

    public function getId()
    {
        if (empty($this->id))
        {
            $this->id = uniqid('z');
        }

        return $this->id;
    }

    public function getStyle()
    {
        return $this->style;
    }

    public function getScript()
    {
        return $this->script;
    }

    /**
     * 搜索样式
     *
     * Table 中保存搜索HTML
     * Form  中保存搜索状态
     *
     * @param string|bool|null $search
     *
     * @return $this|bool
     */
    public function search( $search = null)
    {
        if (is_null($search)) {return $this->search;}
        $this->search = $search;
        return $this;
    }

    public function view(): string
    {
        return $this->execute()->page();
    }

    protected function init()
    {
        $this->globals(new Globals($this->name, []));

        $cdn = Factory::configure('cdn', '');

        $this->addScript(
            [
                '<script src="'. ($cdn ? : '//cdn.staticfile.org/') . '/vue/2.6.12/vue.min.js"></script>',
                '<script src="'. ($cdn ? : '//cdn.staticfile.org/') . '/axios/0.24.0/axios.min.js"></script>',
                '<script src="'. ($cdn ? : '//cdn.staticfile.org/') . '/element-ui/2.15.6/index.min.js"></script>',
                '<script src="'. ($cdn ? : self::CDN_DOMAIN) . '/' . $this->name.'.js"></script>',
            ]
        );

        $this->theme = [
            '<link href="'. ($cdn ? : self::CDN_DOMAIN) . '/element-ui/index.dark.css" rel="stylesheet">',
        ];

        $styles  = Factory::configure($this->name .'.style', []);
        $scripts = Factory::configure($this->name .'.script', []);

        count($styles) > 0 && $this->addStyle($styles);
        count($scripts) > 0 && $this->addScript($scripts);
    }

    protected $theme = [];

    /**
     *
     * 自定义主题设置
     *
     * @param string|array|null $theme 主题样式
     * @param bool         $cover 覆盖
     *
     * @return $this|string[]
     */
    public function theme($theme = null, $cover = true)
    {
        if (null === $theme) {
            return $this->theme;
        }
        if ($cover)
        {
            $this->theme = [];
        }
        array_map(
            function ($t)
            {
                $this->theme[] = $t;
            }, (array)$theme
        );

        return $this;
    }

    /**
     * 获取页面
     *
     * @return string
     */
    abstract protected function page(): string;

}
