<?php

namespace Phine\Phar\Tests\Subject\Builder;

use Phine\Phar\Builder;
use Phine\Phar\Tests\Subject\AbstractTestCase;

/**
 * Tests the methods in the {@link AddString} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddStringTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::ADD_STRING;

    /**
     * Make sure that we can add a file from a string.
     */
    public function testDoLastStep()
    {
        $contents = file_get_contents(__FILE__);

        $this
            ->phar
            ->expects($this->once())
            ->method('addFromString')
            ->with(
                $this->equalTo('test.php'),
                $this->equalTo($contents)
            );

        $this->invokeSubject(
            array(
                'local' => 'test.php',
                'contents' => $contents
            )
        );
    }
}
