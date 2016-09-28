<?php
/*
 * Xibo - Digital Signage - http://www.xibo.org.uk
 * Copyright (C) 2006-2015 Daniel Garner
 *
 * This file is part of Xibo.
 *
 * Xibo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version. 
 *
 * Xibo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Xibo.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Xibo\Custom;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Respect\Validation\Validator as v;
use Xibo\Entity\Media;
use Xibo\Exception\NotFoundException;
use Xibo\Factory\ModuleFactory;
use Xibo\Widget\ModuleWidget;

class Slideshow extends ModuleWidget
{
    private $resourceFolder;
    protected $codeSchemaVersion = 2;

    /**
     * Slideshow constructor.
     */
    public function init()
    {
        $this->resourceFolder = PROJECT_ROOT . '/web/modules/slideshow';

        // Initialise extra validation rules
        v::with('Xibo\\Validation\\Rules\\');
    }

    /**
     * Install or Update this module
     * @param ModuleFactory $moduleFactory
     */
    public function installOrUpdate($moduleFactory)
    {
        if ($this->module == null) {
            // Install
            $module = $moduleFactory->createEmpty();
            $module->name = 'Slideshow';
            $module->type = 'slideshow';
            $module->class = 'Xibo\Custom\Slideshow';
            $module->description = 'Slideshow with jquery cycle plugin';
            $module->imageUri = 'forms/library.gif';
            $module->enabled = 1;
            $module->previewEnabled = 0;
            $module->assignable = 1;
            $module->regionSpecific = 1;
            $module->renderAs = 'html';
            $module->schemaVersion = $this->codeSchemaVersion;
            $module->settings = [];
            $module->defaultDuration = 60;
            $module->viewPath = '../custom/slideshow';
            
            $this->setModule($module);
            $this->installModule();
        }

        // Check we are all installed
        //$this->installFiles();
    }

    public function installFiles()
    {
        $this->mediaFactory->createModuleSystemFile(PROJECT_ROOT . '/web/modules/jquery-cycle-2.1.6.min.js')->save();
        $this->mediaFactory->createModuleSystemFile(PROJECT_ROOT . '/web/modules/xibo-layout-scaler.js')->save();
    }

    public function validate()
    {
        if ($this->getUseDuration() == 1 && $this->getDuration() == 0)
            throw new \InvalidArgumentException(__('Please enter a duration'));

    }

    /**
     * Add Media to the Database
     */
    public function add()
    {
        $this->setDuration($this->getSanitizer()->getInt('duration', $this->getDuration()));
        $this->setUseDuration($this->getSanitizer()->getCheckbox('useDuration'));
        $this->setOption('mediaList', $this->getSanitizer()->getString('mediaList'));
        
        // should we use the assign media ?
        $mediaList = explode(',' $this->getOption('mediaList', null));
        foreach($mediaList as $mediaId) {
            $this->assignMedia($mediaId);
        }
        // Save the widget
        $this->validate();
        $this->saveWidget();
    }

    /**
     * Edit Media in the Database
     */
    public function edit()
    {
        $this->setDuration($this->getSanitizer()->getInt('duration', $this->getDuration()));
        $this->setUseDuration($this->getSanitizer()->getCheckbox('useDuration'));
        $this->setOption('mediaList', $this->getSanitizer()->getString('mediaList'));
        
        // should we use clearMedia & assignMedia ?
        $mediaList = explode(',' $this->getOption('mediaList', null));
        $this->clearMedia();
        foreach($mediaList as $mediaId) {
            $this->assignMedia($mediaId);            
        }

        // Save the widget
        $this->validate();
        $this->saveWidget();
    }



    /**
     * Get Resource
     * @param int $displayId
     * @return mixed
     */
    public function getResource($displayId = 0)
    {
        $data = [];
        $isPreview = ($this->getSanitizer()->getCheckbox('preview') == 1);

        // Clear all linked media.
        $this->clearMedia();

        // Replace the View Port Width?
        $data['viewPortWidth'] = ($isPreview) ? $this->region->width : '[[ViewPortWidth]]';
        
        $duration = $this->getCalculatedDurationForGetResource();

        // Replace the head content
        $javaScriptContent = '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/jquery-1.11.1.min.js') . '"></script>';
        $javaScriptContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/jquery-cycle-2.1.6.min.js') . '"></script>';
        $javaScriptContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('xibo-layout-scaler.js') . '"></script>';
        
        // Update and save widget if we've changed our assignments.
        if ($this->hasMediaChanged())
            $this->widget->save(['saveWidgetOptions' => false, 'notifyDisplays' => true]);        

        $i = 0;
        $body = <<<'EOD'
<div class="cycle-slideshow" 
    data-cycle-fx=tileBlind
    data-cycle-timeout=4000
    data-cycle-pager="#adv-custom-pager"
    data-cycle-pager-template="<a href='#'><img src='{{src}}' width=20 height=20></a>"
    >
EOD;
        $mediaList = explode(',',$this->parseLibraryReferences($isPreview, $this->getOption('mediaList', null)));
        
        foreach($mediaList as $media) {
            $body .= '<img src="' . $media . '" style="width: 100%; height: auto;"/>';
        }
        $body .= '</div><div id=adv-custom-pager class="center external"></div>';
        
        $data['javaScript'] = $javaScriptContent;
        $data['body'] = $body;
        
        // Return that content.
        return $this->renderTemplate($data);
    }

    public function isValid()
    {
        // Using the information you have in your module calculate whether it is valid or not.
        // 0 = Invalid
        // 1 = Valid
        // 2 = Unknown
        return 1;
    }
}
