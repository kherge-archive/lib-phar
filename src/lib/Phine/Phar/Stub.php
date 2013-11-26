<?php

namespace Phine\Phar;

use Phine\Phar\Stub\Extract as Embed;

/**
 * Generates a new PHP archive stub.
 *
 * Summary
 * -------
 *
 * The `Stub` class provides a relatively simple method of generating a
 * potentially complex archive file stub. With the stub generator, you can:
 *
 * - load files (useful for loading a bootstrap file)
 * - embed source code
 * - enable self-extraction (run on servers without the `phar` extension)
 * - set a banner comment
 * - set the shebang line
 * - use `phar` extension functionality:
 *     - `Phar::interceptFileFuncs()`
 *     - `Phar::loadPhar()`
 *     - `Phar::mapPhar()`
 *     - `Phar::mount()`
 *     - `Phar::mungServer()`
 *     - `Phar::webPhar()`
 *
 * Starting
 * --------
 *
 * To start, you will need to create a new instance of `Stub`.
 *
 * You may create a stub generator in one of two ways:
 *
 *     use Phine\Phar\Stub;
 *
 *     $stub = new Stub();
 *
 * Or you may call the static `create()` method:
 *
 *     $stub = Stub::create();
 *
 * The latter example will allow you to skip a variable assignment altogether:
 *
 *     $builder->setStub(
 *         Stub::create()
 *             ->addRequire('bin/main')
 *             ->getStub()
 *     );
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @api
 */
class Stub
{
    /**
     * The banner comment.
     *
     * @var string
     */
    private $banner;

    /**
     * The interceptFileFuncs() flag.
     *
     * @var boolean
     */
    private $interceptFileFuncs = false;

    /**
     * The list of archives to load.
     *
     * @var array
     */
    private $loadPhar = array();

    /**
     * The stream alias to map to the archive.
     *
     * @var string
     */
    private $mapPhar;

    /**
     * The list of archives to mount.
     *
     * @var array
     */
    private $mount = array();

    /**
     * The list of server variables to modify.
     *
     * @var array
     */
    private $mungServer = array();

    /**
     * The list of files to require.
     *
     * @var array
     */
    private $require = array();

    /**
     * The "self extracting" flag.
     *
     * @var boolean
     */
    private $selfExtract = false;

    /**
     * The archive shebang line.
     *
     * @var string
     */
    private $shebang = '#!/usr/bin/env php';

    /**
     * The source code to embed.
     *
     * @var array
     */
    private $source = array();

    /**
     * The web archive settings.
     *
     * @var array
     */
    private $webPhar;

    /**
     * Adds a file to require.
     *
     * This method will add a file path to that will be `require()`'d in the
     * stub, after `phar` extension function calls and embedded source code.
     *
     *     $stub->addRequire('internal/path/to/file.php');
     *
     * You may also load an external file by passing `false` as the second
     * argument (`$internal`):
     *
     *     $stub->addRequire('/external/path/to/file.php', false);
     *
     * @param string  $path                The file path.
     * @param boolean $internal (optional) Is it an internal path?
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function addRequire($path, $internal = true)
    {
        $this->require[] = array($path, $internal);

        return $this;
    }

    /**
     * Adds source code to embed.
     *
     * This method will embed PHP source code into the stub.
     *
     *     $stub->addSource('my_function_call();');
     *
     * If `$after` is `true`, the source code will be embedded after all of
     * the `require`s have been added to the stub. If it is `false`, it will
     * be embedded before the `require`s.
     *
     * @param string  $source            The source code.
     * @param boolean $after  (optional) Embed after the require(s)?
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function addSource($source, $after = true)
    {
        $this->source[] = array($source, $after);

        return $this;
    }

    /**
     * Creates a new stub generator.
     *
     * This method will simply create a new instance of this class.
     *
     *     $stub = Stub::create();
     *
     * The above example is the same as:
     *
     *     $stub = new Stub();
     *
     * @return Stub The new stub generator.
     *
     * @api
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Generates a stub according to the current settings.
     *
     * This method will generate the new stub based on the configuration that
     * was set using the other instance methods of this class. If the stub has
     * not been configured, an "empty" stub is returned:
     *
     *     #!/usr/bin/env php
     *     <?php
     *
     *
     *     __HALT_COMPILER();
     *
     * @return string The generated stub.
     *
     * @api
     */
    public function getStub()
    {
        $stub = '';

        if ($this->shebang) {
            $stub = $this->shebang . "\n";
        }

        $stub .= "<?php\n\n";
        $export = function ($value) {
            return var_export($value, true);
        };

        if ($this->banner) {
            $stub .= sprintf(
                "/*\n * %s\n */\n\n",
                preg_replace(
                    '/\s+$/m',
                    '',
                    join("\n * ", explode("\n", $this->banner))
                )
            );
        }

        $extension = false;

        if ($this->mapPhar
            || $this->webPhar
            || $this->interceptFileFuncs
            || $this->mungServer
            || $this->mount) {
            $extension = true;
            $stub .= <<<STUB
if (class_exists('Phar')) {
\$include = 'phar://' . __FILE__;

STUB;
        }

        if ($this->mapPhar) {
            $stub .= sprintf(
                "Phar::mapPhar(%s);\n",
                $export($this->mapPhar)
            );
        }

        if ($this->webPhar) {
            $stub .= sprintf(
                "Phar::webPhar(%s, %s, %s, %s, %s);\n",
                $export($this->webPhar[0]),
                $export($this->webPhar[1]),
                $export($this->webPhar[2]),
                $export($this->webPhar[3]),
                $export($this->webPhar[4])
            );
        }

        if ($this->interceptFileFuncs) {
            $stub .= "Phar::interceptFileFuncs();\n";
        }

        if ($this->mungServer) {
            $stub .= sprintf(
                "Phar::mungServer(%s);\n",
                $export($this->mungServer)
            );
        }

        foreach ($this->mount as $paths) {
            $stub .= sprintf(
                "Phar::mount(%s, %s);\n",
                $export($paths[0]),
                $export($paths[1])
            );
        }

        foreach ($this->loadPhar as $phar) {
            $stub .= sprintf(
                "Phar::loadPhar(%s, %s);\n",
                $export($phar[0]),
                $export($phar[1])
            );
        }

        if ($this->selfExtract) {
            $stub .= <<<STUB
} else {
\$include = Extract::from(__FILE__)->to();
set_include_path(\$include . PATH_SEPARATOR . get_include_path());
}

STUB;
        } elseif ($extension) {
            $stub .= <<<STUB
} else {
throw new Exception('The "phar" extension is required to run this archive.');
}
STUB;
        }

        if ($this->source) {
            $stub .= "\n";
        }

        foreach ($this->source as $source) {
            if (!$source[1]) {
                $stub .= "{$source[0]}\n";
            }
        }

        if ($this->require
            && ($this->mapPhar
                || $this->webPhar
                || $this->interceptFileFuncs
                || $this->mungServer
                || $this->mount
                || $this->loadPhar)
        ) {
            $stub .= "\n";
        }

        foreach ($this->require as $file) {
            if ($file[1]) {
                if (!preg_match('/^[\\\\\/]/', $file[0])) {
                    $file[0] = '/' . $file[0];
                }

                $file = '$include . ' . $export($file[0]);
            } else {
                $file = $export($file[0]);
            }

            $stub .= "require $file;\n";
        }

        if ($this->source) {
            $stub .= "\n";
        }

        foreach ($this->source as $source) {
            if ($source[1]) {
                $stub .= "{$source[0]}\n";
            }
        }

        if ($this->selfExtract) {
            $stub .= "\n" . trim(Embed::getSource()) . "\n";
        }

        $stub .= "\n__HALT_COMPILER();";

        return $stub;
    }

    /**
     * Toggles the use of the `Phar::interceptFileFuncs()` method.
     *
     * This method will toggle the use of the `Phar::interceptFileFuncs()`
     * method inside the stub. By default, the use of the method will be
     * enabled if `$use` is not provided.
     *
     *     $stub->interceptFileFuncs();
     *
     * @see Phar::interceptFileFuncs()
     *
     * @param boolean $use (optional) Call the function?
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function interceptFileFuncs($use = true)
    {
        $this->interceptFileFuncs = $use;

        return $this;
    }

    /**
     * Adds an archive file to load.
     *
     * This method will load an external archive file so that it can be used
     * inside the current archive. This is useful for loading assets or other
     * scripts.
     *
     *     $stub->loadPhar('/external/path/to/file.phar', 'myAlias.phar');
     *
     * @see Phar::loadPhar()
     *
     * @param string $file             The file path to the phar.
     * @param string $alias (optional) The stream alias for the phar.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function loadPhar($file, $alias = null)
    {
        $this->loadPhar[] = array($file, $alias);

        return $this;
    }

    /**
     * Sets the stream alias to map the archive to.
     *
     * This method will set the stream alias of the archive file.
     *
     *     $stub->mapPhar('myAlias.phar');
     *
     * > Note that caching may become an issue when using stream aliases
     * > for archives ([see here][]).
     *
     * [see here]: https://github.com/zendtech/ZendOptimizerPlus/issues/115#issuecomment-25612769
     *
     * @see Phar::mapPhar()
     *
     * @param string $alias The stream alias.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function mapPhar($alias)
    {
        $this->mapPhar = $alias;

        return $this;
    }

    /**
     * Mounts an external path inside the archive.
     *
     * This method will mount an external file path as if it were an internal
     * archive file path. This works for both files and directories, allowing
     * you to access items such as configuration files.
     *
     *     $stub->mount(
     *         'phar://internal/path/to/file.php',
     *         '/external/path/to/file.php'
     *     );
     *
     * @see Phar::mount()
     *
     * @param string $internal The internal path.
     * @param string $external The external path.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function mount($internal, $external)
    {
        $this->mount[] = array($internal, $external);

        return $this;
    }

    /**
     * Sets the list of server variables to modify.
     *
     * This method will set a list of `$_SERVER` variables that will need to
     * be modified for the archive. This functionality is intended to be used
     * in conjunction with the `Phar::webPhar()` method.
     *
     *     $stub->mungServer(
     *         array(
     *             'REQUEST_URI',
     *             'PHP_SELF',
     *             'SCRIPT_NAME',
     *             'SCRIPT_FILENAME'
     *         )
     *     );
     *
     * @see Phar::mungServer()
     *
     * @param array $vars The list of variables.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function mungServer(array $vars)
    {
        $this->mungServer = $vars;

        return $this;
    }

    /**
     * Toggles making the archive self extracting.
     *
     * This method will toggle embedding a self extraction class in the stub.
     * The class will allow the archive to still be executed in environments
     * that do not have the `phar` extension installed. Note that support is
     * limited, since not all functionality can be supported without the
     * extension.
     *
     *     $stub->selfExtracting();
     *
     * @param boolean $extract (optional) Make archive self extracting?
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function selfExtracting($extract = true)
    {
        $this->selfExtract = $extract;

        return $this;
    }

    /**
     * Sets the banner comment.
     *
     * This method will set the banner comment that will be presented at the
     * very beginning of the stub. This is useful for displaying information
     * such as licensing, copyright, and more.
     *
     *     $stub->setBanner(
     *         <<<BANNER
     *     This is my comment.
     *
     *     It can span multiple lines.
     *
     *         And event be indented.
     *     BANNER
     *     );
     *
     * > Note that the banner comment provided will be automatically wrapped
     * > as a multi-line comment (non-doc)block. Do not nest comments, as it
     * > will result in a PHP parsing error.
     *
     * @param string $banner The comment.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function setBanner($banner)
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * Sets the shebang line.
     *
     * This method will set the shebang line used to execute the archive.
     *
     *     $stub->setShebang('#!/usr/bin/env php5.5');
     *
     * By default, the shebang line is:
     *
     *     #!/usr/bin/env php
     *
     * You can remove this line by passing an empty string or `null`.
     *
     * @param string $shebang The shebang line.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function setShebang($shebang)
    {
        $this->shebang = $shebang;

        return $this;
    }

    /**
     * Sets the settings for the `Phar::webPhar()` method.
     *
     * This set the parameters that will be given to `Phar::webPhar()` when
     * it is called in the stub. If this method is not called, the `Phar`
     * method will not be called in the stub. You may call this method without
     * any arguments to use the default values.
     *
     *     $stub->webPhar();
     *
     * (alternatively)
     *
     *     $stub->webPhar(
     *         'myAlias.phar',
     *         'internal/path/to/index.php',
     *         'internal/path/to/404.php',
     *         array(
     *             // file extension => mime type
     *         ),
     *         'my_rewrite_function'
     *     );
     *
     * @param string $alias    (optional) The stream alias.
     * @param string $index    (optional) The index internal file path.
     * @param string $notFound (optional) The 404 internal file path.
     * @param array  $mime     (optional) The list of file extension to MIME types.
     * @param string $rewrite  (optional) The name of the rewrite function.
     *
     * @return Stub The stub generator.
     *
     * @api
     */
    public function webPhar(
        $alias = null,
        $index = 'index.php',
        $notFound = null,
        array $mime = array(),
        $rewrite = null
    ) {
        $this->webPhar = array($alias, $index, $notFound, $mime, $rewrite);

        return $this;
    }
}
