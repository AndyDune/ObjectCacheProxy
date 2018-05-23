<?php
/*
 * Кеширование результатов работы произвольного объекта
 * Принимается объкт и имя запускаемого метода
 *
 *
 * About adapters:
 * https://www.php-fig.org/psr/psr-16/
 */
namespace AndyDune\ObjectCacheProxy;

class ObjectCacheProxy
{
    protected $object = null;
    protected $methodName = null;
    protected $className = null;

    protected $_parameters = array();
    
    protected $adapter = null;

    protected $prepareMethods = array();


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
        if (is_string($object))
        {
            $this->className  = $object;
            $this->object     = new $object();
        }
        else
        {
            $this->object     = $object;
            $this->className  = get_class($object);
        }

        $this->methodName = $methodName;
        return $this;
    }

    /**
     * Двойственная природа.
     * 1. Если вызванный метод равен указанному при созданнии объекта - 
     *    происходит выборка данных. Из кеша или отрабатыается целевой объект.
     * 2. Сохраняется имя метода и его параметры для участия в формировании ключа
     *    кеша и для инициилизации целевого объекта.
     * 
     * @param string $name имя вызываемого метода
     * @param array $arguments аргументы
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // Вызван кешируемый метод
        if ($name == $this->methodName)
        {
            $this->setParams($arguments);
            return $this->get();
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
        $this->_parameters = $arguments;
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
        if (count($prepare))
        {
            foreach($prepare as $value)
            {
                call_user_func_array(array($this->object, $value['method']), $value['params']);
            }
        }

        $data = call_user_func_array(array($this->object, $this->methodName), $this->parameters);

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



}
