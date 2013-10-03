<?php

namespace Phine\Phar\Tests\Builder\Subject;

use PharFileInfo;
use Phine\Phar\Builder;

/**
 * Tests the methods in the {@link AddFileTest} class.
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
        $this->invokeSubject(
            array(
                'file' => __FILE__,
                'local' => null
            )
        );

        /** @var PharFileInfo $file */
        $file = $this->phar[__FILE__];

        $this->assertEquals(
            file_get_contents(__FILE__),
            file_get_contents($file),
            'Make sure that the file is added.'
        );

        unset($this->phar[__FILE__]);

        $this->invokeSubject(
            array(
                'file' => __FILE__,
                'local' => 'test.php'
            )
        );

        /** @var PharFileInfo $file */
        $file = $this->phar['test.php'];

        $this->assertEquals(
            file_get_contents(__FILE__),
            file_get_contents($file),
            'Make sure that the file is added using a different local path.'
        );
    }
}
