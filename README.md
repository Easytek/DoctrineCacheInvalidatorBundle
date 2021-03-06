# DoctrineCacheInvalidationBundle #

Invalidate your doctrine cache easily.

This bundle is still a work in progress.

## ToDo ##
- Define the invalidations rules in the bundle configuration instead of a class ?
- Add some tests.
- Add Doctrine 2.3 wildcard invalidation syntax.

## Install ##

```
composer require "easytek/doctrine-cache-invalidator-bundle" "dev-master"
```

## Use ##

Create this service :

```php
<?php

namespace You\YourBundle\Cache;

use Easytek\DoctrineCacheInvalidatorBundle\Cache\CacheInvalidationInterface;

class CacheInvalidation implements CacheInvalidationInterface
{
    public function getClasses()
    {
        retun array();
    }
}
```

Then you add it in your services file configuration :

```yml
    you.yourbundle.cache_invalidation:
        class: You\YourBundle\Cache\CacheInvalidation
        tags:
            - { name: easytek.doctrine_cache_invalidation }
```

Then you have to fill the array returned by the getClasses method of your service.
The array contains a key for each doctrine entity class you want to work on.
This key is associated with an array of invalidation rules, each invalidation rule is also an array, containing the two following informations :

- The cache id pattern, which can contain {attribute}, the same way you do in Twig.
- The entity changes triggering the invalidation, it can be "insert", "update", "delete" or "*" wich regroup the first three.

Example :

```php
// ...
    public function getClasses()
    {
        return array(
            'You\YourBundle\Entity\Category' => array(
                array(
                    'pattern' => 'category_{id}',
                    'changes' => array('*')
                )
            )
        )
    }
```

This means, when a Category entity will be either inserted, updated or deleted, the cache id 'category_{id}' (where {id} is replaced by $category->getId()) will be cleared.
