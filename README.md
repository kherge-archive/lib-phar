Phar
====

[![Build Status][]](https://travis-ci.org/phine/lib-phar)
[![Coverage Status][]](https://coveralls.io/r/phine/lib-phar)
[![Latest Stable Version][]](https://packagist.org/packages/phine/phar)
[![Total Downloads][]](https://packagist.org/packages/phine/phar)

A PHP library for creating and reading (without the phar extension) PHP archives.

Example
-----

Building an archive using observers:

```php
use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;
use Phine\Phar\Builder;

class Replace implements ObserverInterface
{
    public function receiveUpdate(SubjectInterface $subject)
    {
        /** @var Builder\Arguments $args */
        $args = $subject->getArguments();

        $args['contents'] = str_replace(
            '{name}',
            'world',
            $args['contents']
        );
    }
}

$builder = Builder::create('example.phar');
$builder->observe(Builder::ADD_STRING, new Replace());
$builder->addFromString(
    'index.php',
    '<?php echo "Hello, {name}!\n";'
);
```

Verifying the signature of an archive without the `phar` extension:

```php
use Phine\Phar\Signature;

if (Signature::create('example.phar')->isValid()) {
    echo "Valid\n";
} else {
    echo "Invalid\n";
}
```

Extracting an archive without the `phar` extension:

```php
use Phine\Phar\Archive;
use Phine\Phar\File\Reader;
use Phine\Phar\Extract;

$archive = Archive::create('example.phar');
$extract = new Extract($archive);

$extract->extractTo('/my/dir');
```

Documentation
-------------

You can find the API [documentation here][]. You may also be able to find
tutorials, tips, and more on the [wiki][].

Requirement
-----------

- PHP >= 5.3.3
- [Phine Exception] >= 1.0.0
- [Phine Observer] >= 1.0.1
- [Phine Path] >= 1.0.0

Installation
------------

Via [Composer][]:

    $ composer require "phine/phar=~1.0"

License
-------

This library is available under the [MIT license](LICENSE).

[Build Status]: https://travis-ci.org/phine/lib-phar.png?branch=master
[Coverage Status]: https://coveralls.io/repos/phine/lib-phar/badge.png
[Latest Stable Version]: https://poser.pugx.org/phine/phar/v/stable.png
[Total Downloads]: https://poser.pugx.org/phine/phar/downloads.png
[documentation here]: http://phine.github.io/lib-phar
[wiki]: https://github.com/phine/lib-phar/wiki
[Phine Exception]: https://github.com/phine/lib-exception
[Phine Observer]: https://github.com/phine/lib-observer
[Phine Path]: https://github.com/phine/lib-path
[Composer]: http://getcomposer.org/
