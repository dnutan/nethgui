<?php

namespace Nethgui\Module\Help;

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

/**
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class Common extends \Nethgui\Controller\AbstractController implements \Nethgui\Component\DependencyConsumer
{
    /**
     * Holds the included file list for {{{INCLUDE}}} directive processing
     * 
     * @see expandIncludes()
     * @var array
     */
    private $includes = array();

    /**
     *
     * @var \Nethgui\Module\ModuleInterface
     */
    private $module;

    /**
     *
     * @var callable
     */
    private $fileNameResolver;

    private $nsMap = array();

    /**
     *
     * @return \Nethgui\Module\ModuleSetInterface $moduleSet
     * @return Menu
     */
    public function getModuleSet()
    {
        return $this->getParent()->getModuleSet();
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);

        $fileName = \Nethgui\array_head($request->getPath());

        if (preg_match('/[a-z][a-z0-9]+(.rst)/i', $fileName) == 0 && preg_match('/[a-z][a-z0-9]+(.html)/i', $fileName) == 0) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1322148405);
        }

        // Now assuming a trailing ".rst" or ".html" suffix.
        if (substr($fileName, -3) == 'rst') {
            $this->module = $this->getModuleSet()->getModule(substr($fileName, 0, -4));
        } else { //html
            $this->module = $this->getModuleSet()->getModule(substr($fileName, 0, -5));
        }

        if (is_null($this->module)) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1322148406);
        }

        $this->module->setPlatform($this->getPlatform());
        if ( ! $this->module->isInitialized()) {
            $this->module->initialize();
        }
    }

    public function setFileNameResolver($fileNameResolver)
    {
        $this->fileNameResolver = $fileNameResolver;
        return $this;
    }

    /**
     * @return \Nethgui\Module\ModuleInterface
     */
    protected function getTargetModule()
    {
        return $this->module;
    }

    protected function getHelpDocumentPath(\Nethgui\Module\ModuleInterface $module)
    {
        $parts = explode('\\', get_class($module));

        $locale = str_replace('-', '_', $this->getRequest()->getLocale());
        $lang = substr($locale, 0, 2);
        $fileName = implode('_', $parts) . '.html';

        $nsList = array_keys($this->nsMap);
        foreach ($nsList as $ns) {
            $pathLocale = call_user_func($this->fileNameResolver, implode("\\", array($ns, 'Help', $locale, $fileName)));
            if($this->getPhpWrapper()->file_exists($pathLocale)) {
                return $pathLocale;
            }
            $pathLang = call_user_func($this->fileNameResolver, implode("\\", array($ns, 'Help', $lang, $fileName)));
            if($this->getPhpWrapper()->file_exists($pathLang)) {
                return $pathLang;
            }
        }

        # last resort: English
        return call_user_func($this->fileNameResolver, implode("\\", array($ns, 'Help', 'en', $fileName)));
    }

    protected function getFileNameResolver()
    {
        return $this->fileNameResolver;
    }

    /**
     * Extract the contents of the first div tag in the XHTML help document
     * 
     * @return string
     * @throws \Nethgui\Exception\HttpException 
     */
    protected function readHelpDocument($filePath)
    {

        $document = new \XMLReader();

        set_error_handler(function ($errno, $errstr) {
            
        }, E_WARNING | E_NOTICE);

        if ($document->open('file://' . $filePath, 'utf-8', LIBXML_NOENT) === TRUE) {
            // Advance to BODY tag:
            while ($document->name != 'body' && $document->read());
            while ($document->name != 'div' && $document->read());

            $content = $document->readInnerXml();
        } else {
            $content = 'Not found';
            throw new \Nethgui\Exception\HttpException(sprintf("%s: resource not found", __CLASS__), 404, 1333119424);
        }

        restore_error_handler();

        return $this->expandIncludes($content);
    }

    public function expandIncludes($contents)
    {
        $self = $this;
        return preg_replace_callback(
            '/{{{INCLUDE\s+([^}\s]+)}}}/', function($matches) use ($self, $contents) {
            return $self->readHelpDocumentsByPattern($matches[1], $contents);
        }, $contents);
    }

    public function readHelpDocumentsByPattern($pattern)
    {
        if (strstr($pattern, '/') !== FALSE) {
            throw new \UnexpectedValueException(sprintf('%s: Forbidden slash "/" character in INCLUDE pattern', __CLASS__), 1338288914);
        }

        $absolutePattern = dirname($this->getHelpDocumentPath($this->getTargetModule())) . '/' . $pattern;

        $expansion = '';

        foreach ($this->getPhpWrapper()->glob($absolutePattern) as $fileName) {
            if (substr($fileName, -4) !== '.rst' && substr($fileName, -5) !== '.html') {
                throw new \UnexpectedValueException(sprintf('%s: Forbidden file name extension in help document `%s`.', __CLASS__, basename($fileName)), 1338288817);
            }
            if (isset($this->includes[$fileName])) {
                throw new \RuntimeException(sprintf('%s: the file has already been included: `%s`.', __CLASS__, basename($fileName)), 1338289668);
            }
            $this->includes[$fileName] = TRUE;

            $expansion .= $this->readHelpDocument($fileName);
        }

        return $expansion;
    }

    public function setNamespaceMap($nsMap) {
        $this->nsMap = $nsMap;
        return $this;
    }

    public function getDependencySetters()
    {
        return array(
           'namespaceMap' => array($this, 'setNamespaceMap')
        );
    }

}