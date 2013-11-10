<?php

namespace Phine\Phar\Tests\Builder\Subject;

use Phine\Phar\Builder;

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
            );

        $this->invokeSubject(
            array(
                'dir' => __DIR__,
                'regex' => '/Add/'
            )
        );
    }
}
