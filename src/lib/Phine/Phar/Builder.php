<?php

namespace Phine\Phar;

use Iterator;
use Phar;
use Phine\Observer\Collection;
use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;
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
 * <h2>Summary</h2>
 *
 * The Builder class wraps specific Phar instance methods so that they could
 * be made into observable events. When one of these methods is called, the
 * arguments passed to it are made available to all of the observers of that
 * method. Each observer will have the opportunity to change the values of the
 * arguments, prevent the event from completing (the actual Phar method would
 * not be called), or cause some other action to occur.
 *
 * <blockquote>
 *   You may want to read the documentation for the
 *   <a href="https://github.com/phine/lib-observer">phine/observer</a>
 *   library in order to have a much better understanding of how events
 *   are managed.
 * </blockquote>
 *
 * <h2>Starting</h2>
 *
 * To start, you will need to create a new instance of Builder.
 *
 * You can use an existing Phar instance:
 *
 * <pre><code>$builder = new Builder($phar);</code></pre>
 *
 * Or you can create your own:
 *
 * <pre><code>$builder = Builder::create('example.phar');</code></pre>
 *
 * <h2>Events</h2>
 *
 * The Builder class provides the following events:
 *
 * <ul>
 *   <li><code>Builder::ADD_DIR</code> - For addEmptyDir().</li>
 *   <li><code>Builder::ADD_FILE</code> - For addFile().</li>
 *   <li><code>Builder::ADD_STRING</code> - For addFromString().</li>
 *   <li><code>Builder::BUILD_DIR</code> - For buildFromDirectory().</li>
 *   <li><code>Builder::BUILD_ITERATOR</code> - For buildFromIterator().</li>
 *   <li><code>Builder::SET_STUB</code> - For setStub().</li>
 * </ul>
 *
 * <h2>Observing an Event</h2>
 *
 * To observe an event, you must create an observer class that implements
 * the interface, Phine\\Observer\\ObserverInterface. It is a part of the
 * observer library used to manage events.
 *
 * <pre><code>
 * use Phine\\Observer\\ObserverInterface;
 * use Phine\\Observer\\SubjectInterface;
 *
 * class MyObserver implements ObserverInterface
 * {
 *     public function receiveUpdate(SubjectInterface $subject)
 *     {
 *     }
 * }
 * </code></pre>
 *
 * The $subject is an instance of a class that extends the AbstractSubject
 * class. This class provides you access to methods such as getArguments(),
 * which allows you to retrieve and modify the arguments that will be passed
 * to the Phar class method. For example, if the observer is registered to
 * the Builder::ADD_STRING event, it will have access to the local name and
 * contents of the file that will be added to the archive.
 *
 * <pre><code>
 * use Phine\\Observer\\ObserverInterface;
 * use Phine\\Observer\\SubjectInterface;
 *
 * class Replace implements ObserverInterface
 * {
 *     public function receiveUpdate(SubjectInterface $subject)
 *     {
 *         $arguments = $subject->getArguments();
 *
 *         $arguments['contents'] = str_replace(
 *             '{name}',
 *             'world',
 *             $arguments['contents']
 *         );
 *     }
 * }
 * </code></pre>
 *
 * The observer above will replace all occurrences of "{name}", in any string
 * added, with "world".
 *
 * <blockquote>
 * It is important to note that each event will make available its own list
 * of method arguments, and accessing non-existent arguments will throw an
 * exception.
 * </blockquote>
 *
 * To register the observer, you will need to call the observe() method with
 * the appropriate event identifier and an instance of your observer:
 *
 * <pre><code>$builder->observe(Builder::ADD_STRING, new Replace());</code></pre>
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
     * Sets the PHP archive instance to build with.
     *
     * Once the archive instance has been set, the default event subjects are
     * registered with the builder by calling `registerDefaultSubjects()`, a
     * protected instance method. This method can be overridden to register new
     * or different events.
     *
     * @see Builder::registerDefaultSubjects
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
     * Triggers the Builder::ADD_DIR event, making the arguments of this
     * method available to the observers.
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
     * Triggers the Builder::ADD_FILE event, making the arguments of this
     * method available to the observers.
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
     * Triggers the Builder::ADD_STRING event, making the arguments of this
     * method available to the observers.
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
     * Triggers the Builder::BUILD_DIR event, making the arguments of this
     * method available to the observers.
     *
     * @param string $dir   The directory path.
     * @param string $regex The regular expression filter.
     *
     * @return array An array mapping internal paths to external files.
     */
    public function buildFromDirectory($dir, $regex = null)
    {
        return $this->invokeEvent(
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
     * Triggers the Builder::BUILD_ITERATOR event, making the arguments of this
     * method available to the observers.
     *
     * @param Iterator $iterator An iterator.
     * @param string   $base     The base directory path.
     *
     * @return array An array mapping internal paths to external files.
     */
    public function buildFromIterator(Iterator $iterator, $base = null)
    {
        return $this->invokeEvent(
            self::BUILD_ITERATOR,
            array(
                'iterator' => $iterator,
                'base' => $base
            )
        );
    }

    /**
     * Creates a new PHP archive and Builder instance.
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
     * Registers an event observer with a subject.
     *
     * @param string            $id       The event subject identifier.
     * @param ObserverInterface $observer The event subject observer.
     * @param integer           $priority The priority of the observer.
     */
    public function observe(
        $id,
        ObserverInterface $observer,
        $priority = SubjectInterface::FIRST_PRIORITY
    ) {
        $this->getSubject($id)->registerObserver($observer, $priority);
    }

    /**
     * Sets the stub used to bootstrap the archive.
     *
     * Triggers the Builder::SET_STUB event, making the arguments of this
     * method available to the observers.
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

    /**
     * Invokes an event after setting new method argument values.
     *
     * @param string $id     The event subject identifier.
     * @param array  $values The new method argument values.
     *
     * @return mixed Any value, if available.
     */
    private function invokeEvent($id, array $values)
    {
        /** @var AbstractSubject $subject */
        $subject = $this->getSubject($id);
        $subject->setArguments(new Arguments($values));

        return $subject->notifyObservers();
    }
}
