<?php
/**
 * Кеширование результатов работы произвольного объекта
 * Принимается объкт и имя запускаемого метода
 *
 * PHP version >= 7.1
 * About adapters:
 * https://www.php-fig.org/psr/psr-16/
 *
 * @package andydune/object-cache-proxy
 * @link  https://github.com/AndyDune/ObjectCacheProxy for the canonical source repository
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrey Ryzhov  <info@rznw.ru>
 * @copyright 2018 Andrey Ryzhov
 */


namespace AndyDune\ObjectCacheProxy;

class ObjectCacheProxy
{
    protected $object = null;
    protected $methodName = null;
    protected $className = null;

    protected $parameters = [];

    protected $adapter = null;

    protected $prepareMethods = [];

    protected $prepareMethodNames = [];

    protected $notAllow = false;


    /**
     *  Set into the object Simple Cache (PSR-16) adapter.
     *
     * ObjectCacheProxy constructor.
     * @param object $adapter
     */
    public function __construct($adapter = null)
    {
        $this->adapter = $adapter;
    }

    public function setObject($object, $methodName)
    {
        if (is_string($object)) {
            $this->className = $object;
            $this->object = new $object();
        } else {
            $this->object = $object;
            $this->className = get_class($object);
        }

        $this->methodName = $methodName;
        return $this;
    }

    /**
     * Set methods names witch will be used for preparation key for cache.
     *
     * @param mixed ...$params
     * @return ObjectCacheProxy
     */
    public function setCacheKeyMethods(...$params)
    {
        if (is_array($params[0])) {
            $this->prepareMethodNames = $params[0];
        }
        $this->prepareMethodNames = $params;
        return $this;
    }


    /**
     * It works as:
     * 1. Если вызванный метод равен указанному при созданнии объекта -
     *    происходит выборка данных. Из кеша или отрабатыается целевой объект.
     * 2. Сохраняется имя метода и его параметры для участия в формировании ключа
     *    кеша и для инициилизации целевого объекта.
     *
     * @param string $name method name
     * @param array $arguments arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($this->notAllow) {
            return call_user_func_array([$this->object, $name], $arguments);
        }
        // Вызван кешируемый метод
        if ($name == $this->methodName) {
            $this->setParams($arguments);
            return $this->get();
        }

        if ($this->prepareMethodNames and !in_array($name, $this->prepareMethodNames)) {
            return call_user_func_array([$this->object, $name], $arguments);
        }

        return $this->prepare($name, $arguments);
    }


    /**
     * Перед запуском основного метода запроса может понадобиться установка параметров.
     * Подготовка к запросу. Эти данные используются для формирования ключа дял кеша.
     *
     * @param string $methodName имя метода
     * @param array $params неасоц. массив параметров метода
     * @param boolean $spec параметры содержат массивы
     * @return ObjectCacheProxy
     */
    protected function prepare($methodName, $params = array())
    {
        $this->prepareMethods[] = array(
            'method' => $methodName,
            'params' => $params
        );
        return $this;
    }


    /**
     * Установка параметров для целевого метода.
     * Любое колличесво параметров.
     * В порядке, в каком они будут переданы методу.
     *
     * @return ObjectCacheProxy
     */

    protected function setParams($arguments)
    {
        $this->parameters = $arguments;
        return $this;
    }

    /**
     * Запрос результатов. Реальные либо из кеша.
     *
     * @return mixed
     */
    protected function get()
    {
        $key = $this->buildCacheKey();
        if ($this->adapter->has($key))
            return $this->adapter->get($key);

        $prepare = $this->prepareMethods;
        if (count($prepare)) {
            foreach ($prepare as $value) {
                call_user_func_array(array($this->object, $value['method']), $value['params']);
            }
        }

        $data = call_user_func_array(array($this->object, $this->methodName), $this->parameters);

        $this->prepareMethods = [];
        $this->adapter->set($key, $data);
        return $data;
    }

    /**
     * @return string
     */
    protected function buildCacheKey()
    {
        $name = '';
        $name = $this->className . '++' . $this->methodName;
        $name .= '++' . serialize($this->parameters);
        $name .= '++' . serialize($this->prepareMethods);


        return md5($name);
    }


    /**
     * @deprecated
     * @param $key
     * @return mixed
     */
    protected function checkCache($key)
    {
        return $this->adapter->get($key);
    }

    /**
     * @deprecated
     * @param $key
     * @param $data
     * @return $this
     */
    protected function storeCache($key, $data)
    {
        $cache = Cache::factory($this->cacheMode);
        $cache->save($data, $key);
        return $this;
    }

    public function setAllow($allow = true)
    {
        $this->notAllow = !$allow;
        return $this;
    }

}
