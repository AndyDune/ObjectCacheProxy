# ObjectCacheProxy

[![Build Status](https://travis-ci.org/AndyDune/ObjectCacheProxy.svg?branch=master)](https://travis-ci.org/AndyDune/ObjectCacheProxy)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/andydune/object-cache-proxy.svg?style=flat-square)](https://packagist.org/packages/andydune/object-cache-proxy)
[![Total Downloads](https://img.shields.io/packagist/dt/andydune/object-cache-proxy.svg?style=flat-square)](https://packagist.org/packages/andydune/object-cache-proxy)


Allow cache results for execution object method. Transparent to working code.

Installation
------------

Installation using composer:

```
composer require andydune/object-cache-proxy
```
Or if composer didn't install globally:
```
php composer.phar require andydune/object-cache-proxy
```
Or edit your `composer.json`:
```
"require" : {
     "andydune/object-cache-proxy": "^1"
}

```
And execute command:
```
php composer.phar update
```

Problem
------------

Here is instance of any class. We use setters for inject data and finally execute the last method for retrieve data.
We do it often with narrow range of input data. How can we simple to change it for speedup?

```php
$instance = new Adventures(); // any class

// Use some setters 
$instance->setDirection('up');
$instance->setSpeed('low');
$instance->setLimit(13);

// Next is results
$ways = $instance->get(); 
```

We can use `ObjectCacheProxy` to cache it with minimum work code change.   

```php
use AndyDune\ObjectCacheProxy\ObjectCacheProxy;
use Symfony\Component\Cache\Simple\FilesystemCache;

$instanceOrigin = new \Adventures(); // any class

$instance = new ObjectCacheProxy(new FilesystemCache());

// inject object and method name witch results will be cached. 
$instance->setObject($instanceOrigin, 'get');

// Use some setters 
$instance->setDirection('up');
$instance->setSpeed('low');
$instance->setLimit(13);

// Next is results
$ways = $instance->get(); 
```

`ObjectCacheProxy` gets work class instance and intercept methods call to it. 
It accumulates setter methods and parameters as cache key and executes them at ones before execution method `get`. 
   
