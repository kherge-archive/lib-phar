<?php

namespace Phine\Phar;

use Iterator;
use Phar;
use Phine\Observer\Collection;
use Phine\Phar\Builder\Arguments;
use Phine\Phar\Builder\Subject\AbstractSubject;
use Phine\Phar\Builder\Subject\AddDirectory;
use Phine\Phar\Builder\Subject\AddFile;
use Phine\Phar\Builder\Subject\AddString;
use Phine\Phar\Builder\Subject\BuildDirectory;
use Phine\Phar\Builder\Subject\BuildIterator;
use Phine\Phar\Builder\Subject\SetStub;

/**
 * Manages an event-driven process for building a PHP archive.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Builder extends Collection
{
    /**
     * The event ID for adding an empty directory.
     */
    const ADD_DIR = 'add.directory';

    /**
     * The event ID for adding a file from disk.
     */
    const ADD_FILE = 'add.file';

    /**
     * The event ID for adding a file from a string.
     */
    const ADD_STRING = 'add.string';

    /**
     * The event ID for building from a directory.
     */
    const BUILD_DIR = 'build.directory';

    /**
     * The event ID for building using an iterator.
     */
    const BUILD_ITERATOR = 'build.iterator';

    /**
     * The event ID for settings the stub.
     */
    const SET_STUB = 'set.stub';

    /**
     * The PHP archive being built.
     *
     * @var Phar
     */
    private $phar;

    /**
     * Sets the PHP archive to be built.
     *
     * @param Phar $phar The PHP archive.
     */
    public function __construct(Phar $phar)
    {
        $this->phar = $phar;

        $this->registerDefaultSubjects();
    }

    /**
     * Adds an empty directory to the archive.
     *
     * @param string $name The name of the directory.
     */
    public function addEmptyDir($name)
    {
        $this->invokeEvent(
            self::ADD_DIR,
            array(
                'name' => $name
            )
        );
    }

    /**
     * Adds a file from the disk to the archive.
     *
     * @param string $file  The path to the file.
     * @param string $local The path to the file in the archive.
     */
    public function addFile($file, $local = null)
    {
        $this->invokeEvent(
            self::ADD_FILE,
            array(
                'file' => $file,
                'local' => $local
            )
        );
    }

    /**
     * Adds a file from a string to the archive.
     *
     * @param string $local    The path to the file in the archive.
     * @param string $contents The contents of the file.
     */
    public function addFromString($local, $contents)
    {
        $this->invokeEvent(
            self::ADD_STRING,
            array(
                'local' => $local,
                'contents' => $contents
            )
        );
    }

    /**
     * Builds the archive using a directory path.
     *
     * @param string $dir   The directory path.
     * @param string $regex The regular expression filter.
     */
    public function buildFromDirectory($dir, $regex = null)
    {
        $this->invokeEvent(
            self::BUILD_DIR,
            array(
                'dir' => $dir,
                'regex' => $regex
            )
        );
    }

    /**
     * Builds the archive using an iterator.
     *
     * @param Iterator $iterator An iterator.
     * @param string   $base     The base directory path.
     */
    public function buildFromIterator(Iterator $iterator, $base = null)
    {
        $this->invokeEvent(
            self::BUILD_ITERATOR,
            array(
                'iterator' => $iterator,
                'base' => $base
            )
        );
    }

    /**
     * Creates a new PHP archive and builder instance.
     *
     * @param string $file The PHP archive file path.
     *
     * @return Builder A builder instance.
     */
    public static function create($file)
    {
        return new Builder(new Phar($file));
    }

    /**
     * Returns the PHP archive being built.
     *
     * @return Phar The PHP archive.
     */
    public function getPhar()
    {
        return $this->phar;
    }

    /**
     * Sets the stub used to bootstrap the archive.
     *
     * @param string $stub The archive stub.
     */
    public function setStub($stub)
    {
        $this->invokeEvent(
            self::SET_STUB,
            array(
                'stub' => $stub
            )
        );
    }

    /**
     * Invokes an event after setting new method argument values.
     *
     * @param string $id     The event subject identifier.
     * @param array  $values The new method argument values.
     */
    protected function invokeEvent($id, array $values)
    {
        /** @var AbstractSubject $subject */
        $subject = $this->getSubject($id);
        $subject->setArguments(new Arguments($values));
        $subject->notifyObservers();
    }

    /**
     * Registers the default event subjects.
     */
    protected function registerDefaultSubjects()
    {
        $this->registerSubject(self::ADD_DIR, new AddDirectory($this));
        $this->registerSubject(self::ADD_FILE, new AddFile($this));
        $this->registerSubject(self::ADD_STRING, new AddString($this));
        $this->registerSubject(self::BUILD_DIR, new BuildDirectory($this));
        $this->registerSubject(self::BUILD_ITERATOR, new BuildIterator($this));
        $this->registerSubject(self::SET_STUB, new SetStub($this));
    }
}
