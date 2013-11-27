<?php

namespace Phine\Phar\Tests\Subject\Builder;

use Phine\Phar\Builder;
use Phine\Phar\Tests\Subject\AbstractTestCase;

/**
 * Tests the methods in the {@link BuildDirectory} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildDirectoryTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::BUILD_DIR;

    /**
     * Make sure that we can build from a directory.
     */
    public function testDoLastStep()
    {
        $this
            ->phar
            ->expects($this->once())
            ->method('buildFromDirectory')
            ->with(
                $this->equalTo(__DIR__),
                $this->equalTo('/Add/')
            )
            ->will($this->returnValue('returned'));

        $this->assertEquals(
            'returned',
            $this->invokeSubject(
                array(
                    'dir' => __DIR__,
                    'regex' => '/Add/'
                )
            ),
            'The value should be returned.'
        );
    }
}
