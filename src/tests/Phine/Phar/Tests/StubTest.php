<?php

namespace Phine\Phar\Tests;

use Phar;
use Phine\Phar\Stub;
use Phine\Phar\Stub\Extract;
use Phine\Test\Property;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Performs unit tests on the `Stub` class.
 *
 * @see Stub
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class StubTest extends TestCase
{
    /**
     * The stub instance being tested.
     *
     * @var Stub
     */
    private $stub;

    /**
     * Make sure that we can add a required file.
     */
    public function testAddRequire()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->addRequire('test'),
            'The method should return its object.'
        );

        $this->assertEquals(
            array(
                array('test', true)
            ),
            Property::get($this->stub, 'require'),
            'The required file should be added.'
        );
    }

    /**
     * Make sure that we can add source code to embed.
     */
    public function testAddSource()
    {
        $source = 'echo "Hello, world!\n";';

        $this->assertSame(
            $this->stub,
            $this->stub->addSource($source),
            'The method should return its object.'
        );

        $this->assertEquals(
            array(
                array($source, true)
            ),
            Property::get($this->stub, 'source'),
            'The source code should be added.'
        );
    }

    /**
     * Make sure we can create a new instance of the class.
     */
    public function testCreate()
    {
        $this->assertInstanceOf(
            'Phine\\Phar\\Stub',
            Stub::create()
        );
    }

    /**
     * Make sure that we can generate the stub.
     */
    public function testGetStub()
    {
        // injects a banner
        Property::set(
            $this->stub,
            'banner',
            <<<BANNER
This is a multi-line banner comment.

It should break correctly,

    including the indentation.
BANNER
        );

        // inject a mapping alias
        Property::set($this->stub, 'mapPhar', 'test.phar');

        // inject web phar settings
        Property::set(
            $this->stub,
            'webPhar',
            array(
                'web.phar',
                'index.php',
                '404.php',
                array(
                    'phps' => Phar::PHPS
                ),
                'rewrite'
            )
        );

        // enable intercept
        Property::set($this->stub, 'interceptFileFuncs', true);

        // inject mung list
        Property::set($this->stub, 'mungServer', array('PATH_INFO'));

        // inject a list to mount
        Property::set(
            $this->stub,
            'mount',
            array(
                array('internal/file.php', '/external/file.php')
            )
        );

        // inject a list to load
        Property::set(
            $this->stub,
            'loadPhar',
            array(
                array('/path/to/file.phar', 'file.phar')
            )
        );

        // inject a list of required files
        Property::set(
            $this->stub,
            'require',
            array(
                array('path/to/require.php', true),
                array('/path/to/require.php', false)
            )
        );

        // inject a list of source code to embed
        Property::set(
            $this->stub,
            'source',
            array(
                array('echo "before\n";', false),
                array('echo "after\n";', true)
            )
        );

        // enable self extraction
        Property::set($this->stub, 'selfExtract', true);

        $code = trim(Extract::getSource());

        $this->assertEquals(
            <<<STUB
#!/usr/bin/env php
<?php

/*
 * This is a multi-line banner comment.
 *
 * It should break correctly,
 *
 *     including the indentation.
 */

if (class_exists('Phar')) {
\$include = 'phar://' . __FILE__;
Phar::mapPhar('test.phar');
Phar::webPhar('web.phar', 'index.php', '404.php', array (
  'phps' => 1,
), 'rewrite');
Phar::interceptFileFuncs();
Phar::mungServer(array (
  0 => 'PATH_INFO',
));
Phar::mount('internal/file.php', '/external/file.php');
Phar::loadPhar('/path/to/file.phar', 'file.phar');
} else {
\$include = Extract::from(__FILE__)->to();
set_include_path(\$include . PATH_SEPARATOR . get_include_path());
}

echo "before\\n";

require \$include . '/path/to/require.php';
require '/path/to/require.php';

echo "after\\n";

$code

__HALT_COMPILER();
STUB
            ,
            $this->stub->getStub(),
            'The full stub should be generated.'
        );
    }

    /**
     * Make sure that we still check if `Phar` exists if using advanced funcs.
     *
     * If the developer decided to make sure of `phar`-extension specific
     * functionality (e.g. `Phar::mount()`), we need to inform the end-user
     * that the extension is required.
     */
    public function testGetStubAdvanced()
    {
        // inject a mapping alias
        Property::set($this->stub, 'mapPhar', 'test.phar');

        $this->assertEquals(
            <<<STUB
#!/usr/bin/env php
<?php

if (class_exists('Phar')) {
\$include = 'phar://' . __FILE__;
Phar::mapPhar('test.phar');
} else {
throw new Exception('The "phar" extension is required to run this archive.');
}
__HALT_COMPILER();
STUB
            ,
            $this->stub->getStub(),
            'The full stub should be generated.'
        );
    }

    /**
     * Make sure we can get the intercept flag.
     */
    public function testInterceptFileFuncs()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->interceptFileFuncs(),
            'The method should return its object.'
        );

        $this->assertTrue(
            Property::get($this->stub, 'interceptFileFuncs'),
            'The intercept flag should be set to true by default.'
        );

        $this->stub->interceptFileFuncs(false);

        $this->assertFalse(
            Property::get($this->stub, 'interceptFileFuncs'),
            'The intercept flag should now be false.'
        );
    }

    /**
     * Make sure we can add a phar to load.
     */
    public function testLoadPhar()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->loadPhar('/path/to/file.phar', 'file.phar'),
            'The method should return its object.'
        );

        $this->assertEquals(
            array(
                array('/path/to/file.phar', 'file.phar')
            ),
            Property::get($this->stub, 'loadPhar'),
            'The phar to load should be added.'
        );
    }

    /**
     * Make sure we can map the phar.
     */
    public function testMapPhar()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->mapPhar('alias.phar'),
            'The method should return its object.'
        );

        $this->assertEquals(
            'alias.phar',
            Property::get($this->stub, 'mapPhar'),
            'The alias should be set.'
        );
    }

    /**
     * Make sure we can set the phars to mount.
     */
    public function testMount()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->mount('internal/path', '/external/path'),
            'The method should return its object.'
        );

        $this->assertEquals(
            array(
                array('internal/path', '/external/path')
            ),
            Property::get($this->stub, 'mount'),
            'The paths should be added.'
        );
    }

    /**
     * Make sure we can list the variables to mung.
     */
    public function testMungServer()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->mungServer(array('PATH_INFO')),
            'The method should return its object.'
        );

        $this->assertEquals(
            array('PATH_INFO'),
            Property::get($this->stub, 'mungServer'),
            'The variables should be set.'
        );
    }

    /**
     * Make sure we can set the self extract flag.
     */
    public function testSelfExtracting()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->selfExtracting(),
            'The method should return its object.'
        );

        $this->assertTrue(
            Property::get($this->stub, 'selfExtract'),
            'The self extract flag should be set to true by default.'
        );

        $this->stub->selfExtracting(false);

        $this->assertFalse(
            Property::get($this->stub, 'selfExtract'),
            'The self extract flag should now be false.'
        );
    }

    /**
     * Make sure we can set the banner comment.
     */
    public function testSetBanner()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->setBanner('test'),
            'The method should return its object.'
        );

        $this->assertEquals(
            'test',
            Property::get($this->stub, 'banner'),
            'The banner comment should be set.'
        );
    }

    /**
     * Make sure we can set the shebang line.
     */
    public function testSetShebang()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->setShebang('#!test'),
            'The method should return its object.'
        );

        $this->assertEquals(
            '#!test',
            Property::get($this->stub, 'shebang'),
            'The shebang line should be set.'
        );
    }

    /**
     * Make sure we can set the web phar settings.
     */
    public function testWebPhar()
    {
        $this->assertSame(
            $this->stub,
            $this->stub->webPhar(),
            'The method should return its object.'
        );

        $this->assertSame(
            array(
                null,
                'index.php',
                null,
                array(),
                null
            ),
            Property::get($this->stub, 'webPhar'),
            'The default web phar settings should be set.'
        );

        $this->stub->webPhar(
            'alias.phar',
            'main.php',
            '404.php',
            array('phps' => Phar::PHPS),
            'rewrite'
        );

        $this->assertEquals(
            array(
                'alias.phar',
                'main.php',
                '404.php',
                array('phps' => Phar::PHPS),
                'rewrite'
            ),
            Property::get($this->stub, 'webPhar'),
            'The web phar settings should be set.'
        );
    }

    /**
     * Creates a new instance of `Stub` for testing.
     */
    protected function setUp()
    {
        $this->stub = new Stub();
    }
}
