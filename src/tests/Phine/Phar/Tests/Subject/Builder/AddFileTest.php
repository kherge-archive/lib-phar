<?php

namespace Phine\Phar\Tests\Subject\Builder;

use Phine\Phar\Builder;
use Phine\Phar\Tests\Subject\AbstractTestCase;

/**
 * Tests the methods in the {@link AddFile} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddFileTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::ADD_FILE;

    /**
     * Make sure that we can add a file.
     */
    public function testDoLastStep()
    {
        $this
            ->phar
            ->expects($this->at(0))
            ->method('addFile')
            ->with(
                $this->equalTo(__FILE__),
                $this->isNull()
            );

        $this
            ->phar
            ->expects($this->at(1))
            ->method('addFile')
            ->with(
                $this->equalTo(__FILE__),
                $this->equalTo('test.php')
            );

        $this->invokeSubject(
            array(
                'file' => __FILE__,
                'local' => null
            )
        );

        $this->invokeSubject(
            array(
                'file' => __FILE__,
                'local' => 'test.php'
            )
        );
    }
}
