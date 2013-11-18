Phar
====

[![Build Status][]](https://travis-ci.org/phine/lib-phar)
[![Coverage Status][]](https://coveralls.io/r/phine/lib-phar)
[![Latest Stable Version][]](https://packagist.org/packages/phine/phar)
[![Total Downloads][]](https://packagist.org/packages/phine/phar)

A PHP library for accessing and creating PHP archives.

Usage
-----

```php
use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;
use Phine\Phar\Builder;
use Phine\Phar\Builder\Subject\AddString;
use Phine\Phar\Stub;

class Replace implements ObserverInterface
{
    public function receiveUpdate(SubjectInterface $subject)
    {
        /** @var AddString $subject */

        $arguments = $subject->getArguments();
        $arguments['contents'] = str_replace(
            '@name@',
            'world',
            $arguments['contents']
        );
    }
}

$phar = Builder::create('example.phar');

$phar
    ->getSubject(Builder::ADD_STRING)
    ->registerObserver(new Replace());

$phar->addFromString(
    'hello.php',
    '<?php echo "Hello, @name@!\n";'
);

$phar->setStub(
    Stub::create()
        ->addRequire('hello.php')
        ->getStub()
);

```

Requirement
-----------

- PHP >= 5.3.3
- [Phine Exception] >= 1.0.0
- [Phine Observer] >= 1.0.0
- [Phine Path] >= 1.0.0

Installation
------------

Via [Composer][]:

    $ composer require "phine/phar=~1.0"

Documentation
-------------

You can find the [documentation here][].

License
-------

This library is available under the [MIT license](LICENSE).

[Build Status]: https://travis-ci.org/phine/lib-phar.png?branch=master
[Coverage Status]: https://coveralls.io/repos/phine/lib-phar/badge.png
[Latest Stable Version]: https://poser.pugx.org/phine/phar/v/stable.png
[Total Downloads]: https://poser.pugx.org/phine/phar/downloads.png
[Phine Exception]: https://github.com/phine/lib-exception
[Phine Observer]: https://github.com/phine/lib-observer
[Phine Path]: https://github.com/phine/lib-path
[Composer]: http://getcomposer.org/
[documentation here]: https://phine.github.com/lib-phar
