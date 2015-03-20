<?php

namespace SrUnit;

use SrUnit\Bootstrap\DirectoryFinder;
use Composer\Autoload\ClassLoader;
use RuntimeException;
use SrUnit\Bootstrap\Emulator\Oxid;
use SrUnit\Bootstrap\ModuleAutoloader;
use SrUnit\Bootstrap\OxidLoader;
use SrUnit\Bootstrap\SrUnitModule;

/**
 * Class Bootstrap
 *
 * @link http://www.superReal.de
 * @copyright (C) superReal GmbH | Create Commerce
 * @package superreal/srunit
 * @author Jens Wiese <j.wiese AT superreal.de>
 */
class Bootstrap
{
    /** @var DirectoryFinder */
    protected $directoryFinder;

    /** @var  ClassLoader */
    protected $composerClassLoader;

    /** @var bool */
    protected $isOXIDLoaded = false;

    /** @var bool */
    protected $isOXIDMandatory = false;

    /** @var bool  */
    protected $isOXIDBypassed = false;


    /**
     * Creates new Bootstrap object
     *
     * @param string $testDir
     * @return Bootstrap
     */
    public static function create($testDir = null)
    {
        return new self($testDir);
    }

    /**
     * @param string $testDir
     */
    private function __construct($testDir = null)
    {
        $this->directoryFinder = $this->getDirectoryFinder($testDir);
    }

    /**
     * Enable loading of OXID bootstrapping
     *
     * @return $this
     */
    public function loadOXID()
    {
        $this->isOXIDMandatory = true;

        return $this;
    }

    /**
     * Bootstraps Unit Test Environment
     *
     * @throws \RuntimeException
     */
    public function bootstrap()
    {
        $this->loadComposerAutoloader();
        $this->loadOXIDFramework();
        $this->registerModuleAutoloader();

        if ($this->isOXIDMandatory() && !$this->isOXIDLoaded()) {
            throw new RuntimeException('Could not bootstrap test environment, due to a not loaded OXID framework.');
        }
    }

    /**
     * Returns whether or not OXID framework is set mandatory
     *
     * @return bool
     */
    protected function isOXIDMandatory()
    {
        return $this->isOXIDMandatory;
    }

    /**
     * Returns whether or not OXID framework was bootrapped
     *
     * @return bool
     */
    protected function isOXIDLoaded()
    {
        return $this->isOXIDLoaded;
    }

    /**
     * Returns whether or not OXID framework is bypassed
     *
     * @return bool
     */
    protected function isOXIDBypassed()
    {
        return $this->isOXIDBypassed();
    }

    /**
     * Returns Composer ClassLoader
     *
     * @return ClassLoader
     */
    protected function getComposerClassLoader()
    {
        return $this->composerClassLoader;
    }

    /**
     * Bootstraps composer-autoloader
     */
    protected function loadComposerAutoloader()
    {
        $path = $this->directoryFinder->getVendorDir() . '/autoload.php';
        if (file_exists($path)) {
            $this->composerClassLoader = require $path;
        }
    }

    /**
     * Boostraps OXID
     */
    protected function loadOXIDFramework()
    {
        $loader = OxidLoader::getInstance()->setDirectoryFinder($this->directoryFinder);

        if ($this->isOXIDMandatory()) {
            $loader->load();
            $this->isOXIDLoaded = $loader->isLoaded();
        }
    }

    /**
     * Bootstraps all files defined in metadata.php
     * of current module / or of all under modules
     */
    protected function registerModuleAutoloader()
    {
        if ($this->directoryFinder->isCallFromShopBaseDir()) {
            $pathToModules = $this->directoryFinder->getShopBaseDir() . '/modules';
            $metadataFiles = glob($pathToModules . '/*/metadata.php');
        } else {
            $metadataFiles = array($this->directoryFinder->getModuleDir() . '/metadata.php');
        }

        $autoloader = new ModuleAutoloader($metadataFiles);

        spl_autoload_register(array($autoloader, 'load'));
    }

    /**
     * @param $testDir
     * @return DirectoryFinder
     */
    protected function getDirectoryFinder($testDir)
    {
        return new DirectoryFinder($testDir);
    }
}