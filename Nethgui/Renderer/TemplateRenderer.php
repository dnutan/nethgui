<?php
namespace Nethgui\Renderer;

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
 * Renders through a template script or callback method
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 * @api
 */
abstract class TemplateRenderer extends AbstractRenderer implements \Nethgui\Core\GlobalFunctionConsumerInterface, WidgetFactoryInterface
{

    /**
     *
     * @var integer
     */
    private $inheritFlags = 0;

    /**
     *
     * @var callable
     */
    private $templateResolver;

    /**
     * @var \Nethgui\Core\GlobalFunctionWrapper
     */
    protected $globalFunctionWrapper;

    abstract protected function createWidget($widgetType, $attributes);

    public function __construct(\Nethgui\Core\ViewInterface $view, $templateResolver, $inheritFlags)
    {
        if ( ! is_callable($templateResolver)) {
            throw new \InvalidArgumentException(sprintf('%s: $templateResolver must be a valid callback function.', get_class($this)), 1322238847);
        }
        parent::__construct($view);
        $this->templateResolver = $templateResolver;
        $this->inheritFlags = $inheritFlags & NETHGUI_INHERITABLE_FLAGS;
        $this->globalFunctionWrapper = new \Nethgui\Core\GlobalFunctionWrapper();
    }

    protected function getTemplateResolver()
    {
        return $this->templateResolver;
    }

    protected function render()
    {
        return $this->renderView($this->getTemplate(), array('view' => $this));
    }

    /**
     * Renders a view passing $viewState as view parameters.
     *
     * If specified, this function sets the default language catalog used
     * by T() translation function.
     *
     * @param string|callable $view Full view name that follows class naming convention or function callback
     * @param array $viewState Array of view parameters.
     * @param string|array $languageCatalog Name of language strings catalog.
     * @return string
     */
    private function renderView($viewName, $viewState)
    {
        if ($viewName === FALSE) {
            return '';
        }

        if (is_callable($viewName)) {
            // Rendered by callback function
            $viewOutput = (string) call_user_func_array($viewName, $viewState);
        } else {
            $absoluteViewPath = call_user_func($this->getTemplateResolver(), $viewName);

            if ( ! $absoluteViewPath) {
                $this->getLog()->warning("Unable to load `{$viewName}`.");
                return '';
            }

            // Rendered by PHP script
            ob_start();
            $this->globalFunctionWrapper->phpInclude($absoluteViewPath, $viewState);
            $viewOutput = ob_get_contents();
            ob_end_clean();
        }

        return $viewOutput;
    }

    public function getDefaultFlags()
    {
        return $this->inheritFlags;
    }

    public function setDefaultFlags($flags)
    {
        $this->inheritFlags = $flags;
        return $this;
    }

    public function elementList($flags = 0)
    {
        $flags |= $this->inheritFlags;
        $widget = $this->createWidget(__FUNCTION__, array('flags' => $flags));

        if ($flags & self::BUTTONSET) {
            $widget->setAttribute('class', 'Buttonset')
                ->setAttribute('wrap', 'div/');
        }

        // Automatically add standard submit/reset/cancel buttons:
        if ($flags & (self::BUTTON_SUBMIT | self::BUTTON_RESET | self::BUTTON_CANCEL | self::BUTTON_HELP)) {
            if ( ! $widget->hasAttribute('class')) {
                $widget->setAttribute('class', 'Buttonlist')
                    ->setAttribute('wrap', 'div/');
            }

            if ($flags & self::BUTTON_SUBMIT) {
                $widget->insert($this->button('Submit', self::BUTTON_SUBMIT));
            }
            if ($flags & self::BUTTON_RESET) {
                $widget->insert($this->button('Reset', self::BUTTON_RESET));
            }
            if ($flags & self::BUTTON_CANCEL) {
                $widget->insert($this->button('Cancel', self::BUTTON_CANCEL));
            }
            if ($flags & self::BUTTON_HELP) {
                $widget->insert($this->button('Help', self::BUTTON_HELP));
            }
        }

        return $widget;
    }

    public function buttonList($flags = 0)
    {
        $flags |= $this->inheritFlags;
        $widget = $this->createWidget("elementList", array('flags' => $flags));

        $widget->setAttribute('class', 'Buttonlist')->setAttribute('wrap', 'div/');

        return $widget;
    }

    public function button($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function checkBox($name, $value, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'value' => $value, 'flags' => $flags));
    }

    public function dialog($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;

        $className = 'dialog';

        if ($flags & WidgetFactoryInterface::DIALOG_SUCCESS) {
            $className .= ' success';
        } elseif ($flags & WidgetFactoryInterface::DIALOG_WARNING) {
            $className .= ' warning';
        } elseif ($flags & WidgetFactoryInterface::DIALOG_ERROR) {
            $className .= ' error';
        }

        if ($flags & WidgetFactoryInterface::DIALOG_MODAL) {
            $className .= ' modal';
        }

        if ($flags & WidgetFactoryInterface::STATE_DISABLED) {
            $className .= ' disabled';
        }

        /*
         * Create a panel wrapped around the inset
         */

        $panel = $this->panel($flags)
            ->setAttribute('class', $className)
            ->setAttribute('name', $name);
        $inset = $this->createWidget('inset', array('name' => $name, 'flags' => $flags));

        $panel->insert($inset);

        return $panel;
    }

    public function fieldsetSwitch($name, $value, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'value' => $value, 'flags' => $flags));
    }

    public function form($flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('flags' => $flags));
    }

    public function hidden($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function inset($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function panel($flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('flags' => $flags));
    }

    public function radioButton($name, $value, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'value' => $value, 'flags' => $flags));
    }

    public function selector($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags, 'icon-before' => 'ui-icon-triangle-1-s'));
    }

    public function tabs($flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('flags' => $flags));
    }

    public function textInput($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function textLabel($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function fieldset($name = NULL, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        $widget = $this->createWidget(__FUNCTION__, array('flags' => $flags, 'icon-before' => 'ui-icon-triangle-1-s'));
        if ( ! is_null($name)) {
            $widget->setAttribute('name', $name);
        }
        return $widget;
    }

    public function header($name = NULL, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        $widget = $this->createWidget('textLabel', array('flags' => $flags, 'class' => 'header ui-widget-header ui-corner-all ui-helper-clearfix', 'tag' => 'h2'));
        if ( ! is_null($name)) {
            $widget->setAttribute('name', $name);
        }
        return $widget;
    }

    public function literal($data, $flags = 0)
    {
        return $this->createWidget(__FUNCTION__, array('data' => $data, 'flags' => $flags));
    }

    public function columns()
    {
        return $this->createWidget(__FUNCTION__, array());
    }

    public function progressBar($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function textArea($name, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags));
    }

    public function console($name, $flags = 0)
    {
        $flags |= self::STATE_READONLY;
        $flags |= self::LABEL_NONE;
        return $this->textArea($name, $flags)->setAttribute('appendOnly', TRUE)->setAttribute('class', 'console');
    }

    public function dateInput($name, $flags = 0)
    {
        /*
         * Set to "be" (Big Endian) date format. Supported also "le" and "me".
         * see http://en.wikipedia.org/wiki/Calendar_date
         */
        return $this->textInput('date')->setAttribute('class', 'Date be');
    }

    public function objectPicker($name = NULL, $flags = 0)
    {
        $flags |= $this->inheritFlags;
        return $this->createWidget(__FUNCTION__, array('name' => $name, 'flags' => $flags, 'icon-before' => 'ui-icon-triangle-1-s'));
    }

    public function setGlobalFunctionWrapper(\Nethgui\Core\GlobalFunctionWrapper $object)
    {
        $this->globalFunctionWrapper = $object;
    }

}