<?php
namespace Nethgui\Module;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

class ModuleLoader implements \Nethgui\Module\ModuleSetInterface, \Nethgui\Utility\PhpConsumerInterface, \Nethgui\Log\LogConsumerInterface
{

    /**
     * @var \ArrayObject
     */
    private $namespaceMap;

    /**
     *
     * @var ArrayObject
     */
    private $instanceCache;

    /**
     * @var \Nethgui\Log\LogInterface
     */
    private $log;

    /**
     *
     * @var GlobalFunctionWrapper
     */
    private $phpWrapper;

    /**
     *
     * @var array
     */
    private $onInstantiate = array();

    /**
     *
     * @param \ArrayObject $namespaceMap
     */
    public function __construct(\ArrayObject $namespaceMap)
    {
        $this->namespaceMap = $namespaceMap;
        $this->instanceCache = new \ArrayObject();
        $this->phpWrapper = new \Nethgui\Utility\PhpWrapper();
        $this->cacheIsFilled = FALSE;
        $this->onInstantiate;
    }

    public function getIterator()
    {
        if ($this->cacheIsFilled !== TRUE) {
            $this->fillCache();
        }
        return $this->instanceCache->getIterator();
    }

    private function fillCache()
    {
        foreach ($this->namespaceMap as $namespaceName => $namespaceRootPath) {
            // XXX skip Nethgui modules - must be explicitly picked by getModule() ???
            if ($namespaceName === 'Nethgui') {
                continue;
            }

            $path = $namespaceRootPath . '/' . $namespaceName . '/Module';

            $files = $this->phpWrapper->scandir($path);

            if ($files === FALSE) {
                throw new \UnexpectedValueException(sprintf("%s: `%s` is not a valid module directory!", get_class($this), $path), 1322649822);
            }

            foreach ($files as $fileName) {
                if (substr($fileName, -4) !== '.php') {
                    continue;
                }

                $moduleIdentifier = substr($fileName, 0, -4);

                if ( ! isset($this->instanceCache[$moduleIdentifier])) {
                    $className = $namespaceName . '\Module\\' . $moduleIdentifier;
                    $moduleInstance = new $className();
                    $this->getLog()->notice(sprintf('%s::fillCache(): Created "%s" instance', get_class($this), $className));
                    $this->notifyCallbacks($moduleInstance);
                    $this->instanceCache[$moduleIdentifier] = $moduleInstance;                    
                }
            }
        }

        $this->cacheIsFilled = TRUE;
    }

    public function getModule($moduleIdentifier)
    {

        // Module is already instantiated, return it:
        if (isset($this->instanceCache[$moduleIdentifier])) {
            return $this->instanceCache[$moduleIdentifier];
        }

        $namespaces = array_keys(iterator_to_array($this->namespaceMap));

        $warnings = array();

        set_error_handler(function ($errno, $errstr) use (&$warnings) {
                $warnings[] = array($errno, $errstr);
            }, E_WARNING);

        $moduleInstance = NULL;

        // Resolve module class namespaces LIFO
        while ($nsName = array_pop($namespaces)) {
            $className = $nsName . '\Module\\' . $moduleIdentifier;

            if ($this->phpWrapper->class_exists($className)) {
                $moduleInstance = new $className();
                $this->getLog()->notice(sprintf('%s::getModule(): Created "%s" instance', get_class($this), $className));
                $this->notifyCallbacks($moduleInstance);
                $this->instanceCache[$moduleIdentifier] = $moduleInstance;
                break;
            }
        }

        restore_error_handler();

        if ($moduleInstance === NULL) {
            throw new \RuntimeException(sprintf("%s: `%s` is an unknown module identifier", __CLASS__, $moduleIdentifier), 1322231262);
        }

        if (count($warnings) > 0) {
            $message = '';
            foreach ($warnings as $warning) {
                $message .= sprintf('%s %s; ', $warning[0], $warning[1]);
            }
            $this->getLog()->notice(sprintf("%s: %s", __CLASS__, $message));
        }

        return $moduleInstance;
    }

    public function setPhpWrapper(\Nethgui\Utility\PhpWrapper $object)
    {
        $this->phpWrapper = $object;
    }

    public function getLog()
    {
        if ( ! isset($this->log)) {
            $this->log = new \Nethgui\Log\Nullog();
        }
        return $this->log;
    }

    public function setLog(\Nethgui\Log\LogInterface $log)
    {
        $this->log = $log;
        return $this;
    }

    /**
     *
     * @param callable $callable
     * @return ModuleLoader
     */
    public function addInstantiateCallback($callable)
    {
        $this->onInstantiate[] = $callable;
        return $this;
    }

    private function notifyCallbacks(ModuleInterface $module)
    {
        foreach ($this->onInstantiate as $callback) {
            call_user_func($callback, $module);
        }
    }

}