<?php

namespace Phine\Phar\Tests\Builder\Subject;

use PharFileInfo;
use Phine\Phar\Builder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests the methods in the {@link BuildIteratorTest} class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class BuildIteratorTest extends AbstractTestCase
{
    const SUBJECT_ID = Builder::BUILD_ITERATOR;

    /**
     * Make sure that we can build using an iterator.
     */
    public function testDoLastStep()
    {
        $path = realpath(__DIR__ . '/../../../../../../lib');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::KEY_AS_PATHNAME
                    | RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                    | RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $this->invokeSubject(
            array(
                'iterator' => $iterator,
                'base' => $path
            )
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
