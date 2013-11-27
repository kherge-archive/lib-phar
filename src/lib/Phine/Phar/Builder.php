<?php

namespace Phine\Phar;

use Iterator;
use Phar;
use Phine\Observer\Collection;
use Phine\Observer\ObserverInterface;
use Phine\Observer\SubjectInterface;
use Phine\Phar\Subject\Builder as Subject;
use Phine\Phar\Subject\Arguments;
use Phine\Phar\Subject\AbstractSubject;

/**
 * Manages an event-driven process for building a PHP archive.
 *
 * Summary
 * -------
 *
 * The `Builder` class wraps specific `Phar` instance methods so that they
 * could be made into observable events. When one of these methods is called,
 * the arguments passed to it are made available to all of the observers of
 * that method. Each observer will have the opportunity to change the values
 * of the arguments, prevent the event from completing (the actual `Phar`
 * method would not be called), or cause some other action to occur.
 *
 * > You may want to read the documentation for the
 * > [phine/observer](https://github.com/phine/lib-observer) library in order
 * > to have a much better understanding of how events are managed.
 *
 * Starting
 * --------
 *
 * To start, you will need to create a new instance of `Builder`.
 *
 * You can use an existing `Phar` instance:
 *
 *     $builder = new Builder($phar);
 *
 * Or you can create your own:
 *
 *     $builder = Builder::create('example.phar');
 *
 * Events
 * ------
 *
 * The `Builder` class provides the following events:
 *
 * - `Builder::ADD_DIR` - For addEmptyDir().
 * - `Builder::ADD_FILE` - For addFile().
 * - `Builder::ADD_STRING` - For addFromString().
 * - `Builder::BUILD_DIR` - For buildFromDirectory().
 * - `Builder::BUILD_ITERATOR` - For buildFromIterator().
 * - `Builder::SET_STUB` - For setStub().
 *
 * ### Observing an Event
 *
 * To observe an event, you must create an observer class that implements the
 * interface, `Phine\Observer\ObserverInterface`. It is a part of the observer
 * library used to manage events.
 *
 *     use Phine\Observer\ObserverInterface;
 *     use Phine\Observer\SubjectInterface;
 *
 *     class MyObserver implements ObserverInterface
 *     {
 *         public function receiveUpdate(SubjectInterface $subject)
 *         {
 *         }
 *     }
 *
 * The `$subject` is an instance of a class that extends the `AbstractSubject`
 * class. This class provides you access to methods such as `getArguments()`,
 * which allows you to retrieve and modify the arguments that will be passed
 * to the `Phar` class method. For example, if the observer is registered to
 * the `Builder::ADD_STRING` event, it will have access to the local name and
 * contents of the file that will be added to the archive.
 *
 *     use Phine\Observer\ObserverInterface;
 *     use Phine\Observer\SubjectInterface;
 *
 *     class Replace implements ObserverInterface
 *     {
 *         public function receiveUpdate(SubjectInterface $subject)
 *         {
 *             $arguments = $subject->getArguments();
 *
 *             $arguments['contents'] = str_replace(
 *                 '{name}',
 *                 'world',
 *                 $arguments['contents']
 *             );
 *         }
 *     }
 *
 * The observer above will replace all occurrences of `{name}`, in any string
 * added, with `world`.
 *
 * > It is important to note that each event will make available its own list
 * > of method arguments, and accessing non-existent arguments will throw an
 * > exception.
 *
 * To register the observer, you will need to call the `observe()` method
 * with the appropriate event identifier and an instance of your observer:
 *
 *     $builder->observe(Builder::ADD_STRING, new Replace());
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Builder extends Collection
{
    /**
     * The event ID for adding an empty directory.
     *
     * @api
     */
    const ADD_DIR = 'add.directory';

    /**
     * The event ID for adding a file from disk.
     *
     * @api
     */
    const ADD_FILE = 'add.file';

    /**
     * The event ID for adding a file from a string.
     *
     * @api
     */
    const ADD_STRING = 'add.string';

    /**
     * The event ID for building from a directory.
     *
     * @api
     */
    const BUILD_DIR = 'build.directory';

    /**
     * The event ID for building using an iterator.
     *
     * @api
     */
    const BUILD_ITERATOR = 'build.iterator';

    /**
     * The event ID for settings the stub.
     *
     * @api
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
     * registered with this new builder by calling `registerDefaultSubjects()`,
     * a protected instance method. This method can be overridden to register
     * new or different events.
     *
     *     use Phine\Phar\Builder;
     *
     *     $phar = new Phar('example.phar');
     *
     *     $builder = new Builder($phar);
     *
     * @see Builder::registerDefaultSubjects
     *
     * @param Phar $phar The PHP archive.
     *
     * @api
     */
    public function __construct(Phar $phar)
    {
        $this->phar = $phar;

        $this->registerDefaultSubjects();
    }

    /**
     * Adds an empty directory to the archive.
     *
     * Triggers the `Builder::ADD_DIR` event.
     *
     *     $builder->addEmptyDir('example');
     *
     * @see Phar::addEmptyDir
     *
     * @param string $name The name of the directory.
     *
     * @api
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
     * Triggers the `Builder::ADD_FILE` event.
     *
     *     $builder->addFile('/path/to/example.php', 'example.php');
     *
     * @see Phar::addFile
     *
     * @param string $file             The path to the file.
     * @param string $local (optional) The path to the file in the archive.
     *
     * @api
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
     * Triggers the `Builder::ADD_STRING` event.
     *
     *     $builder->addFromString('example.php', '<?php echo "Hello!\n";');
     *
     * @see Phar::addFromString
     *
     * @param string $local    The path to the file in the archive.
     * @param string $contents The contents of the file.
     *
     * @api
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
     * Builds the archive using the files in a directory path.
     *
     * Triggers the `Builder::BUILD_DIR` event.
     *
     *     $builder->buildFromDirectory('/path/to/example', '/(src|lib)/');
     *
     * @param string $dir              The directory path.
     * @param string $regex (optional) The regular expression filter.
     *
     * @return array An array mapping internal paths to external files.
     *
     * @api
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
     * Triggers the `Builder::BUILD_ITERATOR` event.
     *
     *     $iterator = new RecursiveIteratorIterator(
     *         new RecursiveDirectoryIterator('/path/to/example')
     *     );
     *
     *     $builder->buildFromIterator($iterator, '/path/to/example');
     *
     * > Note that if the iterator returns instances of `SplFileInfo`,
     * > the `$base` argument becomes required.
     *
     * @param Iterator $iterator            An iterator.
     * @param string   $base     (optional) The base directory path.
     *
     * @return array An array mapping internal paths to external files.
     *
     * @api
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
     * Creates a new `Phar` and `Builder` instance.
     *
     * This method is a shorter version of first creating a new instance of
     * `Phar`, and then a new instance of `Builder`. The following example:
     *
     *     use Phine\Phar\Builder;
     *
     *     $phar = new Phar('example.phar');
     *
     *     $builder = new Builder($phar);
     *
     * Is the same as this:
     *
     *     use Phine\Phar\Builder;
     *
     *     $builder = Builder::create('example.phar');
     *
     * @param string $file The PHP archive file path.
     *
     * @return Builder The new builder instance.
     *
     * @api
     */
    public static function create($file)
    {
        return new Builder(new Phar($file));
    }

    /**
     * Returns the `Phar` instance being built with.
     *
     * This method will return the `Phar` instance that is being used to
     * build the archive file.
     *
     *     $phar = $builder->getPhar();
     *
     * @return Phar The `Phar` instance.
     *
     * @api
     */
    public function getPhar()
    {
        return $this->phar;
    }

    /**
     * Registers an event observer with a subject.
     *
     * This method is a shortcut to the builder subject's `registerObserver()`
     * method. Instead of doing the following example:
     *
     *     use Phine\Observer\SubjectInterface;
     *     use Phine\Phar\Builder;
     *
     *     $builder
     *         ->getSubject(Builder::ADD_STRING)
     *         ->registerObserver(
     *             new Observer(),
     *             SubjectInterface::FIRST_PRIORITY
     *         );
     *
     * You may instead do the following example:
     *
     *     use Phine\Observer\SubjectInterface;
     *     use Phine\Phar\Builder;
     *
     *     $builder->observe(
     *         Builder::ADD_STRING,
     *         new Observer(),
     *         SubjectInterface::FIRST_PRIORITY
     *     );
     *
     * @see SubjectInterface::registerObserver
     *
     * @param string            $id                  The event subject identifier.
     * @param ObserverInterface $observer            The event subject observer.
     * @param integer           $priority (optional) The priority of the observer.
     *
     * @api
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
     * Triggers the `Builder::SET_STUB` event.
     *
     *     $builder->setStub($stub);
     *
     * @param string $stub The archive stub.
     *
     * @api
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
     *
     * The following is a list of events registered by this method:
     *
     * - `Builder::ADD_DIR` &mdash; `Phine\Phar\Subject\Builder\AddDirectory`
     * - `Builder::ADD_FILE` &mdash; `Phine\Phar\Subject\Builder\AddFile`
     * - `Builder::ADD_STRING` &mdash; `Phine\Phar\Subject\Builder\AddString`
     * - `Builder::BUILD_DIR` &mdash; `Phine\Phar\Subject\Builder\BuildDirectory`
     * - `Builder::BUILD_ITERATOR` &mdash; `Phine\Phar\Subject\Builder\BuildIterator`
     * - `Builder::SET_STUB` &mdash; `Phine\Phar\Subject\Builder\SetStub`
     *
     * @api
     */
    protected function registerDefaultSubjects()
    {
        $this->registerSubject(self::ADD_DIR, new Subject\AddDirectory($this));
        $this->registerSubject(self::ADD_FILE, new Subject\AddFile($this));
        $this->registerSubject(self::ADD_STRING, new Subject\AddString($this));
        $this->registerSubject(self::BUILD_DIR, new Subject\BuildDirectory($this));
        $this->registerSubject(self::BUILD_ITERATOR, new Subject\BuildIterator($this));
        $this->registerSubject(self::SET_STUB, new Subject\SetStub($this));
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
