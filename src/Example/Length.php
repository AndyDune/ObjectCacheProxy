<?php
/**
 * Cache results after working any class instance.
 * It accumulates setter methods, uses it as key for cache. Store results in cache and gets it for the next execution.
 *
 * PHP version >= 5.6
 * About adapters:
 * https://www.php-fig.org/psr/psr-16/
 *
 * @package andydune/object-cache-proxy
 * @link  https://github.com/AndyDune/ObjectCacheProxy for the canonical source repository
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrey Ryzhov  <info@rznw.ru>
 * @copyright 2018 Andrey Ryzhov
 */


namespace AndyDune\ObjectCacheProxy\Example;


class Length
{
    private $length = 100;
    private $lengthDone = 0;

    public function setLength($value)
    {
        $this->length = $value;
    }

    public function execute()
    {
        $data = [];
        for ($i = 0; $i < $this->length; $i++) {
            $this->lengthDone++;
            $data[] = $i;
        }
        return $data;
    }

    public function getLengthDone()
    {
        return $this->lengthDone;
    }

    public function clean()
    {
        $this->lengthDone = 0;
    }

}