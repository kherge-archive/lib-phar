<?php

namespace Phine\Phar\Tests\Builder\Subject;

use PharFileInfo;
use Phine\Phar\Builder;

/**
 * Tests the methods in the {@link AddDirectoryTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class AddDirectoryTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::ADD_DIR;

    /**
     * Make sure that we can add an empty directory.
     */
    public function testDoLastStep()
    {
        $this->invokeSubject(
            array(
                'name' => 'test'
            )
        );

        /** @var PharFileInfo $dir */
        $dir = $this->phar['test'];

        $this->assertTrue(
            $dir->isDir(),
            'Make sure that the directory is added.'
        );
    }
}
