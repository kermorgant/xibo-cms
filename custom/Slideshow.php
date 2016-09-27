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
namespace Xibo\Widget;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Respect\Validation\Validator as v;
use Xibo\Entity\Media;
use Xibo\Exception\NotFoundException;
use Xibo\Factory\ModuleFactory;

class Slideshow extends ModuleWidget
{
    private $resourceFolder;
    protected $codeSchemaVersion = 1;

    /**
     * ForecastIo constructor.
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
            $module->description = 'Slideshow attempt with jquery cycle plugin';
            $module->imageUri = 'forms/library.gif';
            $module->enabled = 1;
            $module->previewEnabled = 0;
            $module->assignable = 1;
            $module->regionSpecific = 1;
            $module->renderAs = 'html';
            $module->schemaVersion = $this->codeSchemaVersion;
            $module->settings = [];
            $module->defaultDuration = 60;

            $this->setModule($module);
            $this->installModule();
        }

        // Check we are all installed
        $this->installFiles();
    }

    public function installFiles()
    {
        $this->mediaFactory->createModuleSystemFile(PROJECT_ROOT . '/web/modules/jquery-cycle-2.1.6.min.js')->save();
        $this->mediaFactory->createModuleSystemFile(PROJECT_ROOT . '/web/modules/xibo-layout-scaler.js')->save();

        foreach ($this->mediaFactory->createModuleFileFromFolder($this->resourceFolder) as $media) {
            /* @var Media $media */
            $media->save();
        }
    }

    /**
     * Form for updating the module settings
     */
    public function settingsForm()
    {
        return 'slideshow-form-settings';
    }

    /**
     * Process any module settings
     */
    // public function settings()
    // {
    //     // Process any module settings you asked for.
    //     $apiKey = $this->getSanitizer()->getString('apiKey');

    //     if ($apiKey == '')
    //         throw new \InvalidArgumentException(__('Missing API Key'));

    //     $this->module->settings['apiKey'] = $apiKey;
    //     $this->module->settings['cachePeriod'] = $this->getSanitizer()->getInt('cachePeriod', 300);
    // }

    /**
     * Loads templates for this module
     */
    private function loadTemplates()
    {
        // Scan the folder for template files
        foreach (glob(PROJECT_ROOT . '/modules/forecastio/*.template.json') as $template) {
            // Read the contents, json_decode and add to the array
            $this->module->settings['templates'][] = json_decode(file_get_contents($template), true);
        }

        $this->getLog()->debug(count($this->module->settings['templates']));
    }

    /**
     * Templates available
     * @return array
     */
    public function templatesAvailable()
    {
        if (!isset($this->module->settings['templates']))
            $this->loadTemplates();

        return $this->module->settings['templates'];
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
        // Behave exactly like the client.
        if (!$foreCast = $this->getForecastData($displayId))
            return '';

        // Do we need to override the language?
        // TODO: I don't like this date fix, the library should really check the file exists?
        if ($this->getOption('lang', 'en') != 'en' && file_exists(PROJECT_ROOT . '/vendor/jenssegers/date/src/Lang/' . $this->getOption('lang') . '.php')) {
            $this->getDate()->setLocale($this->getOption('lang'));
        }

        $data = [];
        $isPreview = ($this->getSanitizer()->getCheckbox('preview') == 1);

        // Replace the View Port Width?
        $data['viewPortWidth'] = ($isPreview) ? $this->region->width : '[[ViewPortWidth]]';

        $headContent = '
            <link href="' . $this->getResourceUrl('forecastio/weather-icons.min.css') . '" rel="stylesheet" media="screen">
            <style type="text/css">
                .container { color: ' . $this->getOption('color', '000') . '; }
                ' . $this->parseLibraryReferences($isPreview, $this->getRawNode('styleSheet', null)) . '
            </style>
        ';

        // Add our fonts.css file
        $headContent .= '<link href="' . (($isPreview) ? $this->getApp()->urlFor('library.font.css') : 'fonts.css') . '" rel="stylesheet" media="screen">';
        $headContent .= '<style type="text/css">' . file_get_contents($this->getConfig()->uri('css/client.css', true)) . '</style>';

        // Replace any icon sets
        $data['head'] = str_replace('[[ICONS]]', $this->getResourceUrl('forecastio/' . $this->getOption('icons')), $headContent);

        // Make some body content
        $body = $this->parseLibraryReferences($isPreview, $this->getRawNode('currentTemplate', null));
        $dailyTemplate = $this->parseLibraryReferences($isPreview, $this->getRawNode('dailyTemplate', null));

        // Get the JavaScript node
        $javaScript = $this->parseLibraryReferences($isPreview, $this->getRawNode('javaScript', ''));

        // Do we need to scale the inner content? Size provided?
        $size = $this->getOption('size', 1);

        if ($size != 1) {
            $javaScript .= '
                <script type="text/javascript">
                    if ($("body").hasClass("ie7") || $("body").hasClass("ie8")) {
                        $("#content").css({
                            "filter": "progid:DXImageTransform.Microsoft.Matrix(M11=' . $size . ', M12=0, M21=0, M22=' . $size . ', SizingMethod=\'auto expand\'"
                        });
                    }
                    else {
                        $("#content").css({
                            "transform": "scale(' . $size . ')",
                            "transform-origin": "top center"
                        });
                    }
                </script>
            ';
        }

        // Handle the daily template (if its here)
        if (stripos($body, '[dailyForecast]')) {
            // Pull it out, and run substitute over it for each day
            $dailySubs = '';
            // Substitute for every day (i.e. 7 times).
            for ($i = 0; $i < 7; $i++) {
                $dailySubs .= $this->makeSubstitutions($foreCast['daily']['data'][$i], $dailyTemplate);
            }

            // Substitute the completed template
            $body = str_replace('[dailyForecast]', $dailySubs, $body);
        }

        // Run replace over the main template
        $data['body'] = $this->makeSubstitutions($foreCast['currently'], $body);


        // JavaScript to control the size (override the original width and height so that the widget gets blown up )
        $options = array(
            'previewWidth' => $this->getSanitizer()->getDouble('width', 0),
            'previewHeight' => $this->getSanitizer()->getDouble('height', 0),
            'originalWidth' => $this->region->width,
            'originalHeight' => $this->region->height,
            'scaleOverride' => $this->getSanitizer()->getDouble('scale_override', 0)
        );

        $javaScriptContent = '<script type="text/javascript" src="' . $this->getResourceUrl('vendor/jquery-1.11.1.min.js') . '"></script>';
        $javaScriptContent .= '<script type="text/javascript" src="' . $this->getResourceUrl('xibo-layout-scaler.js') . '"></script>';
        $javaScriptContent .= '<script>

            var options = ' . json_encode($options) . '

            $(document).ready(function() {
                $("body").xiboLayoutScaler(options);
            });
        </script>';
        $javaScriptContent .= $javaScript;

        // Replace the After body Content
        $data['javaScript'] = $javaScriptContent;

        // Update and save widget if we've changed our assignments.
        if ($this->hasMediaChanged())
            $this->widget->save(['saveWidgetOptions' => false, 'notifyDisplays' => true]);

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
