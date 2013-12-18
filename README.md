Phar
====

[![Build Status][]](https://travis-ci.org/phine/lib-phar)
[![Coverage Status][]](https://coveralls.io/r/phine/lib-phar)
[![Latest Stable Version][]](https://packagist.org/packages/phine/phar)
[![Total Downloads][]](https://packagist.org/packages/phine/phar)

A PHP library for creating and reading (without the phar extension) PHP archives.

Requirement
-----------

- PHP >= 5.3.3
- [Phine Exception] >= 1.0.0
- [Phine Observer] >= 2.0.0
- [Phine Path] >= 1.0.0

Installation
------------

Via [Composer][]:

    $ composer require "phine/phar=~1.0"

Usage
-----

### Building an Archive

To create a new archive, you will need to create a new `Builder` instance.
There are two ways of doing this: using an existing `Phar` instance, or by
creating a new one.

```php
use Phine\Phar\Builder;

// using an existing Phar instance
$builder = new Builder($phar);

// create a new Phar instance
$builder = Builder::create('/path/to/archive.phar');
```

With the new `Builder` instance, you will now have access to a few methods
that appear to be identical to that of the `Phar` class:

- `addEmptyDir()`
- `addFile()`
- `addFromString()`
- `buildFromDirectory()`
- `buildFromIterator()`
- `setStub()`

Truth be told, they have the exact same end result as their `Phar` counterparts.
The difference being that each method can be observed, and the arguments passed
to each method can be altered before the actual action (adding an empty directory,
adding a file from disk, etc) is performed. The simplest example is performing a
search and replace to all content added using the `addFromString()` method.

#### Observing an Action

The `Builder` class is based on the [phine/observer][] library, so it may
benefit you to read up on the documentation provided by that library. To
observe an action (aka "subject"), you will need to create your own
implementation of `Phine\Observer\ObserverInterface`.

```php
use Phine\Observer\SubjectInterface;
use Phine\Observer\ObserverInterface;

/**
 * Replaces occurrences of "{name}" with "world".
 */
class Replace implements ObserverInterface
{
    /**
     * {@inheritDoc}
     */
    public function receiveUpdate(SubjectInterface $subject)
    {
        // get the arguments for the addFromString() method
        $arguments = $subject->getArguments();

        // replace "{name}" with "world"
        $arguments['contents'] = str_replace(
            '{name}',
            'world',
            $arguments['contents']
        );
    }
}
```

Now that we have our observer, we will need to register an instance of it with
a builder event. In particular, we are interested in the `Builder::ADD_STRING`
event, which is the event used by the builder for the `addFromString()` method.

```php
// register our observer
$builder->observe(Builder::ADD_STRING, new Replace());
```

With the observer registered to the `addFromString()` method, whenever we call
it all occurrences of `{name}` will be replaced with the string `world`. So,
if we add the following:

```php
$builder->addFromString(
    'hello.php',
    <<<CODE
<?php

echo "Hello, {name}!\n";
CODE
);
```

The message `Hello, {name}!` will be replaced with `Hello, world!`.

##### Available Events

There is one event for each archive related method:

- `Builder::ADD_DIR` - For `addEmptyDir()`.
- `Builder::ADD_FILE` - For `addFile()`.
- `Builder::ADD_STRING` - For `addFromString()`.
- `Builder::BUILD_DIR` - For `buildFromDirectory()`.
- `Builder::BUILD_ITERATOR` - For `buildFromIterator()`.
- `Builder::SET_STUB` - For `setStub()`.

As demonstrated in the example observer above, you can retrieve the arguments
for each of these methods by calling the `getArguments()` method on the `$subject`
that is provided. The name of the arguments are the same as the parameter names
for the methods. You can find a complete list by viewing the API documentation.

#### Generating a Stub

The library provides a simple way of generating stubs for your archives. With
the stub generator, you can incorporate the functionality provided by the `Phar`
class, require files, or even embed some code to self-extract the archive if the
`phar` extension is not installed.

```php
use Phine\Phar\Stub;

$banner = <<<BANNER
This stub has been licensed under blah blah blah.

Copyright (c) 2199 Hulk Smash
BANNER
;

$builder->setStub(
    Stub::create()
        ->setBanner($banner)
        ->mapPhar('alias.phar')
        ->addRequire('src/hello.php')
        ->selfExtracting()
        ->getStub()
);
```

The example above sets a banner comment, will set the stream alias to
`alias.phar`, and will allow the archive to self-extract and run on machines
that do not have the `phar` extension installed.

```php
#!/usr/bin/env php
<?php

/*
 * This stub has been licensed under blah blah blah.
 *
 * Copyright (c) 2199 Hulk Smash
 */

if (class_exists('Phar')) {
$include = 'phar://' . __FILE__;
Phar::mapPhar('alias.phar');
} else {
$include = Extract::from(__FILE__)->to();
set_include_path($include . PATH_SEPARATOR . get_include_path());
}

require $include . '/src/hello.php';

final class Extract
{
    // ...snip...
}

__HALT_COMPILER();
```

You will want to read the API documentation to understand all of the features
that the stub generator provides.

### Extracting an Archive

If the `phar` extension is not installed and you need to extract the contents
of an archive, the `Extract` class will be your friend. It will use the archive
parser to read the manifest and extract its contents to the directory of your
choice.

```php
use Phine\Phar\Archive;
use Phine\Phar\Extract;

$archive = Archive::create('/path/to/archive.phar');

$extract = new Extract($archive);
$extract->extractTo('/path/to/output/dir');
```

Optionally, you may provide a callable as a second argument to `extractTo()`.
The callable will receive an instance of `Phine\Phar\Manifest\Entry`, and will
be used to determine if a file in the archive should be extracted.

```php
use Phine\Phar\Manifest\Entry;

$extract->extractTo(
    '/path/to/output/dir',
    function (Entry $entry) {
        if ('.php' !== substr($entry->getName(), -3, 3)) {
            return true; // skip this file
        }
    }
);
```

In the example above, the callable will check if each file ends in `.php`. If
the file does not end in `.php`, it will be skipped by returning `true`. Any
other value returned will be ignored by the `Extract` class.

### Verifying a Signature

If you need to verify the signature of an archive on a machine that does not
have the `phar` extension installed, you will want to use the `Signature` class.
If you need to verify archives that have been signed using a private key, you
will still need the `openssl` extension.

```php
use Phine\Phar\Signature;

if (Signature::create('/path/to/archive.phar')->verifySignature()) {
    // the signature was successfully verified
} else {
    // the verification failed!
}
```

Documentation
-------------

You can find the API [documentation here][]. You may also be able to find
tutorials, tips, and more on the [wiki][].

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
[phine/observer]: https://github.com/phine/lib-observer
[documentation here]: http://phine.github.io/lib-phar
[wiki]: https://github.com/phine/lib-phar/wiki
