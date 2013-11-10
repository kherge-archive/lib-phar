<?php

namespace Phine\Phar\Test;

/**
 * A decoy Phar class for testing.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Phar extends \Phar
{
    /**
     * The list of methods that were called and their arguments.
     *
     * @var array
     */
    public $calls = array();

    /**
     * @override
     */
    public function addEmptyDir($dirname = null)
    {
        $this->called(__FUNCTION__, func_get_args());
    }

    /**
     * @override
     */
    public function addFile($filename, $local = null)
    {
        $this->called(__FUNCTION__, func_get_args());
    }

    /**
     * @override
     */
    public function addFromString($localname, $contents = null)
    {
        $this->called(__FUNCTION__, func_get_args());
    }

    /**
     * @override
     */
    public function buildFromDirectory($base_dir, $regex = null)
    {
        $this->called(__FUNCTION__, func_get_args());
    }

    /**
     * @override
     */
    public function buildFromIterator($iterator, $base_directory = null)
    {
        $this->called(__FUNCTION__, func_get_args());
    }

    /**
     * @override
     */
    public function setStub($sub, $len = -1)
    {
        $this->called(__FUNCTION__, func_get_args());
    }

    /**
     * Tracks a method call.
     *
     * @param string $name The method name.
     * @param array  $args The method arguments.
     */
    public function called($name, array $args)
    {
        $this->calls[] = array_merge(array($name), $args);
    }
}
