# DenetmenBundle [![Build Status](https://travis-ci.org/mustafaileri/DenetmenBundle.svg?branch=master)](https://travis-ci.org/mustafaileri/DenetmenBundle) #

## About ##
Denetmen is a url testing tool for Symfony2 projects via [Guzzle](https://github.com/guzzle/guzzle)
* Test your get url.
* Mock your urls for testing.
* Apply response validators for your result.
* Get email notification when your pages are nor working correctly.

![Screenshot](http://i.imgur.com/wSqnr3S.png)


## Installation ##

### For Symfony >= 2.0 ###

Require the bundle in your composer.json file:

```php
{
    "require": {
        "mstfleri/denetmen-bundle": "dev-master"
    }
}
```

Install the bundle:

```
$ composer update mstfleri/denetmen-bundle
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new \Hezarfen\DenetmenBundle\HezarfenDenetmenBundle()
    );
}
```

That's all!


## Usage ##

Configure your routes in parameters section.

Add denetmen.yml into your config.yml
```
#app/config/config.yml
imports:
    - { resource: denetmen.yml }
    ...
```

### Configuration Parameters ###
Define excluded routings in excluded segment.

Define general parameters in router_configs.general segment.

Define parameter by routing key in router_configs.routing_key.

```
#app/config/denetmen.yml
parameters:
    denetmen:
        base_url: "http://localhost:8000/"
        excluded:
            - "_wdt"
            - "_profiler"
            - "_configurator"
            - "_acme_demo"
            - "_profiler_info"
        router_configs:
            general:
                id: 1
                name: "Mustafa"
                
            get_bin_number_routing_key:
                parameters:
                  cardNumber: 1122334455667788

            get_user_routing_key:
                parameters:
                    id: 1

```

For these routings:
```
get_bin_number_routing_key:
    pattern:  /check-bin/{cardNumber}
    defaults: { _controller: YourPaymentBundle:Default:getBinNumber }
    methods:  [GET]
    
get_user_routing_key:
    pattern:  /user/{id}
    defaults: { _controller: YourUserBundle:Default:getUser }
    methods:  [GET]

```

Url requests will be generated:
```
    [GET] http://localhost:8000/check-bin/1122334455667788
    [GET] http://localhost:8000//user/1
    
```

### Response Validators ###
You can define reponse validations

* **type:** You can define response type validator.
* **max_response_time:** You can define maximum response time.
* **filter > node_traversing:** You can define selector and value of selector.

Example:

```
#app/config/denetmen.yml
        ...
        router_configs:
            ...
            
            get_user_routing_key:
              parameters:
                id: 1
              response:
                type: "text/html; charset=UTF-8"
                filter: 
                  node_traversing: 
                    - ['body > header >  div > nav > ul > li', 'Content1']
                    - ['body > header > div.top > nav > ul:nth-child(1) > li:nth-child(2) > a', 'Content2']
                  max_response_time: 0.5
            ...
        ...    
```


#### How to run?
```
$app/console  denetmen:run:url-test
```

#### Or you can use regex for routers.

You can filter your routes 

Example: Filter by starting with "get_"
```
$app/console  denetmen:run:url-test --pattern='#^get_(.*)$#i'
```

#### Activate email notification:
You can activate email notification when one of your routers has any error.
```
$app/console  denetmen:run:url-test  --alert-email='youremail@yourdomain.com'
```


MIT License
-----------

License can be found [here](https://github.com/mustafaileri/DenetmenBundle/blob/master/LICENSE).

Authors
-------

The bundle was originally created by [Mustafa İleri](http://blog.mustafaileri.com).
See the list of [contributors](https://github.com/mustafaileri/DenetmenBundle/graphs/contributors).




