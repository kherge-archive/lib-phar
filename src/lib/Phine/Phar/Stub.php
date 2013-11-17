<?php

namespace Phine\Phar;

use Phine\Phar\Stub\Extract as Embed;

/**
 * Generates a new PHP archive stub.
 *
 * @author Kevin Herrera <kevin@herrera.io>
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
     * @param string  $path     The file path.
     * @param boolean $internal Is it an internal path?
     *
     * @return Stub The stub generator.
     */
    public function addRequire($path, $internal = true)
    {
        $this->require[] = array($path, $internal);

        return $this;
    }

    /**
     * Adds source code to embed.
     *
     * If `$after` is `true`, the source code will be embedded after all of
     * the `require`s have been added to the stub. If it is `false`, it will
     * be embedded before the `require`s.
     *
     * @param string  $source The source code.
     * @param boolean $after  Embed after the require(s)?
     *
     * @return Stub The stub generator.
     */
    public function addSource($source, $after = true)
    {
        $this->source[] = array($source, $after);

        return $this;
    }

    /**
     * Creates a new stub generator.
     *
     * @return Stub The new stub generator.
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Generates a stub according to the current settings.
     *
     * @return string The generated stub.
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

        if ($this->selfExtract) {
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
     * @param boolean $use Call the function?
     *
     * @return Stub The stub generator.
     */
    public function interceptFileFuncs($use = true)
    {
        $this->interceptFileFuncs = $use;

        return $this;
    }

    /**
     * Adds an archive file to load.
     *
     * @param string $file  The file path to the phar.
     * @param string $alias The stream alias for the phar.
     *
     * @return Stub The stub generator.
     */
    public function loadPhar($file, $alias = null)
    {
        $this->loadPhar[] = array($file, $alias);

        return $this;
    }

    /**
     * Sets the stream alias to map the archive to.
     *
     * @param string $alias The stream alias.
     *
     * @return Stub The stub generator.
     */
    public function mapPhar($alias)
    {
        $this->mapPhar = $alias;

        return $this;
    }

    /**
     * Mounts an external path inside the archive.
     *
     * @param string $internal The internal path.
     * @param string $external The external path.
     *
     * @return Stub The stub generator.
     */
    public function mount($internal, $external)
    {
        $this->mount[] = array($internal, $external);

        return $this;
    }

    /**
     * Sets the list of server variables to modify.
     *
     * @param array $vars The list of variables.
     *
     * @return Stub The stub generator.
     */
    public function mungServer(array $vars)
    {
        $this->mungServer = $vars;

        return $this;
    }

    /**
     * Toggles making the archive self extracting.
     *
     * @param boolean $extract Make archive self extracting?
     *
     * @return Stub The stub generator.
     */
    public function selfExtracting($extract = true)
    {
        $this->selfExtract = $extract;

        return $this;
    }

    /**
     * Sets the banner comment.
     *
     * @param string $banner The comment.
     *
     * @return Stub The stub generator.
     */
    public function setBanner($banner)
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * Sets the shebang line.
     *
     * @param string $shebang The shebang line.
     *
     * @return Stub The stub generator.
     */
    public function setShebang($shebang)
    {
        $this->shebang = $shebang;

        return $this;
    }

    /**
     * Sets the settings for the `Phar::webPhar()` method.
     *
     * @param string $alias    The stream alias.
     * @param string $index    The index internal file path.
     * @param string $notFound The 404 internal file path.
     * @param array  $mime     The list of file extension to MIME types.
     * @param string $rewrite  The name of the rewrite function.
     *
     * @return Stub The stub generator.
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
