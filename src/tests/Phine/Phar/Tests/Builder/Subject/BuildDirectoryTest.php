<?php

namespace Phine\Phar\Tests\Builder\Subject;

use PharFileInfo;
use Phine\Phar\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests the methods in the {@link BuildDirectoryTest} class.
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
        $path = realpath(__DIR__ . '/../../../../../../lib');

        $this->invokeSubject(
            array(
                'dir' => $path,
                'regex' => '/Add/'
            )
        );

        $this->assertCount(
            3,
            $this->phar,
            'Make sure only 3 files were added.'
        );

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        /** @var PharFileInfo $info */
        foreach ($iterator as $file => $info) {
            if (('.' === $info->getBasename())
                || ('..' === $info->getBasename())
                || !preg_match('/Add/', $file)) {
                continue;
            }

            $local = str_replace($path . DIRECTORY_SEPARATOR, '', $file);

            $this->assertTrue(
                isset($this->phar[$local]),
                'Make sure the file is in the archive: ' . $local
            );
        }
    }
}
