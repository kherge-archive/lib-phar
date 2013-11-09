<?php

namespace Phine\Phar;

use Phine\Exception\Exception;
use Phine\Path\Path;
use Phine\Phar\Exception\FileException;
use Phine\Phar\File\Manifest;
use Phine\Phar\File\Reader;
use Phine\Phar\File\Writer;

/**
 * Extracts the contents of an archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Extract
{
    /**
     * The list of support compression algorithms.
     *
     * @var array
     */
    private $compression = array();

    /**
     * The manifest of the archive file.
     *
     * @var Manifest
     */
    private $manifest;

    /**
     * The archive file reader.
     *
     * @var Reader
     */
    private $reader;

    /**
     * Sets the archive manifest.
     *
     * @param Manifest $manifest The archive manifest.
     */
    public function __construct(Manifest $manifest)
    {
        $this->manifest = $manifest;
        $this->reader = $manifest->getReader();

        $this->compression['bzip2'] = function_exists('bzdecompress');
        $this->compression['gzip'] = function_exists('gzinflate');
    }

    /**
     * Extracts one or more files to an output directory.
     *
     * If a `$filter` callable is provided, it will be called with each file
     * found in the archive.  If `true` is returned by the filter, the file
     * will not be extracted. If any other value is returned, the file will
     * be extracted.
     *
     * @param string   $dir    The output directory path.
     * @param callable $filter The callable used to filter files.
     *
     * @return integer The number of files extracted.
     *
     * @throws Exception
     * @throws FileException If a file could not be extracted.
     */
    public function extractTo($dir, $filter = null)
    {
        $files = $this->manifest->getFileList();

        array_walk(
            $files,
            function (&$file) {
                $file['name']['data'] = Path::canonical($file['name']['data']);
            }
        );

        $count = 0;

        foreach ($files as $file) {
            if ($filter && (true === $filter($file))) {
                continue;
            }

            $path = "$dir/{$file['name']['data']}";
            $base = dirname($path);

            if (!is_dir($base)) {
                if (!@mkdir($base, 0755, true)) {
                    throw FileException::createUsingLastError();
                }
            }

            $writer = new Writer("$dir/{$file['name']['data']}");

            $writer->write($this->getFile($file));

            $count++;
        }

        return $count;
    }

    /**
     * Returns the contents of a file from the manifest.
     *
     * @param array $file The file information from the manifest.
     *
     * @return string The decompressed file contents.
     *
     * @throws Exception
     * @throws FileException If the file could not be decompressed.
     */
    public function getFile(array $file)
    {
        $this->reader->seek($file['offset']);

        $contents = $this->reader->read($file['size']['compressed']);

        if ($file['flags'] & Manifest::BZ2) {
            if (!$this->compression['bzip2']) {
                throw FileException::createUsingFormat(
                    'The "bz2" extension is required to decompress "%s".',
                    $file['name']['data']
                );
            }

            $contents = bzdecompress($contents);
        } elseif ($file['flags'] & Manifest::GZ) {
            if (!$this->compression['gzip']) {
                throw FileException::createUsingFormat(
                    'The "zlib" extension is required to decompress "%s".',
                    $file['name']['data']
                );
            }

            $contents = gzinflate($contents);
        }

        return $contents;
    }

    /**
     * Returns the manifest for the archive file.
     *
     * @return Manifest The manifest for the archive file.
     */
    public function getManifest()
    {
        return $this->manifest;
    }
}
