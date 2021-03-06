<?php
/**
* @package     jelix
* @subpackage  core-module
* @author      Laurent Jouanneau
* @copyright   2016 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class UrlEngineUpgrader {

    protected $config;

    protected $epId;

    protected $xmlMapEntryPoint;

    function __construct(\Jelix\IniFile\MultiIniModifier $config, $epId,
                         \Jelix\Routing\UrlMapping\XmlEntryPoint $xml) {
        $this->config = $config;
        $this->xmlMapEntryPoint = $xml;
        $this->epId = $epId;
    }

    function upgrade() {

        $engine = $this->config->getValue('engine', 'urlengine');
        switch($engine) {
            case 'simple':
                $this->migrateSimple();
                break;
            case 'significant':
                $this->migrateSignificant();
                break;
            case 'basic_significant':
            default:
                $this->migrateBasicSignificant();
        }

        $defaultEntryPoint =  $this->config->getValue('defaultEntrypoint', 'urlengine');
        if ($defaultEntryPoint == $this->epId) {
            $this->xmlMapEntryPoint->setOptions(array('default'=>true));
        }

        $this->migrateStartModuleAction();
        
        $epConfig = $this->config->getOverrider();
        $this->cleanConfig($epConfig);
    }

    public function cleanConfig($ini) {
        $ini->removeValue('startModule');
        $ini->removeValue('startAction');
        $ini->removeValue('defaultEntrypoint', 'urlengine');
        $ini->removeValue('engine', 'urlengine');
        $ini->removeValue('simple_urlengine_https', 'urlengine');
        $ini->removeValue(null, 'simple_urlengine_entrypoints');
        $ini->removeValue(null, 'basic_significant_urlengine_entrypoints');
    }

    protected $httpsSelectors;

    protected function migrateSimple() {
        $https = preg_split("/[\s,]+/", $this->config->getValue('simple_urlengine_https', 'urlengine'));
        $this->httpsSelectors = array_combine($https, array_fill(0, count($https), true));

        $entrypoints = $this->config->getValues('simple_urlengine_entrypoints');
        foreach($entrypoints as $entrypoint=>$selectors) {
            $entrypoint = str_replace('.php', '', $entrypoint);
            if ($entrypoint == $this->epId) {
                $selectors = preg_split("/[\s,]+/", $selectors);
                foreach($selectors as $sel2){
                    $this->storeUrl($sel2);
                }
                break;
            }
        }
    }

    protected function migrateBasicSignificant() {
        $this->migrateSimple();
        // read basic_significant_urlengine_entrypoints
        // if the entry point is not in this section, or value is off
        // add an attribute noentrypoint=true
        $addEntryPoints = $this->config->getValues('basic_significant_urlengine_entrypoints');
        if (!isset($addEntryPoints[$this->epId]) ||
            !$addEntryPoints[$this->epId]) {
            $this->xmlMapEntryPoint->setOptions(array('noentrypoint'=>true));
        }
    }

    protected function migrateSignificant() {
        // doing something ?
        // replace <*entrypoint> ?
    }

    protected function migrateStartModuleAction() {
        $startModule = $this->config->getValue('startModule');
        $startAction = $this->config->getValue('startAction');
        if ($startModule != $this->config->getMaster()->getValue('startModule') ||
            $startAction != $this->config->getMaster()->getValue('startAction')) {
            $this->xmlMapEntryPoint->addUrlAction("/", $startModule, $startAction, null, null, array('default'=>true));
            $this->xmlMapEntryPoint->addUrlModule('', $startModule);
        }
    }
    
    protected function storeUrl($selStr) {
        $https = false;
        $options = null;

        if (preg_match("/^@([a-zA-Z0-9_]+)$/", $selStr, $m)) {
            $requestType = $m[1];
            $https = isset($this->httpsSelectors[$selStr]);
            $this->xmlMapEntryPoint->setOptions(array('https'=>$https, 'default'=>true));
        }
        else if (preg_match("/^([a-zA-Z0-9_\.]+)~([a-zA-Z0-9_:]+)@([a-zA-Z0-9_]+)$/", $selStr, $m)) {
            // --> <url pathinfo="/$module/$controller/$method" module="$module" action="$action"/>
            $module = $m[1];
            $action = $m[2];
            if (strpos($action, ':') !== false) {
                list($ctrl, $method) = explode(':', $action);
            }
            else {
                $ctrl = 'default';
                $method = $action;
                $action = 'default:'.$action;
            }

            $requestType = $m[3];

            if (isset($this->httpsSelectors[$module.'~'.$action.'@'.$requestType])){
                $https = true;
            }elseif(isset($this->httpsSelectors[$module.'~*@'.$requestType])){
                $https = true;
            }elseif(isset($this->httpsSelectors['@'.$requestType])){
                $https = true;
            }

            if ($https) {
                $options = array('https'=>true);
            }

            $pathinfo = '/'.$module.'/'.$ctrl.'/'.$method;
            $this->xmlMapEntryPoint->addUrlAction($pathinfo, $module, $action, null, null, $options);
        }
        else if (preg_match("/^([a-zA-Z0-9_\\.]+)~([a-zA-Z0-9_]+):\\*@([a-zA-Z0-9_]+)$/", $selStr, $m)) {
            // --> <url pathinfo="/module/controller" module="$module" controller="$controller"/>
            $module = $m[1];
            $ctrl = $m[2];
            $requestType = $m[3];
            $pathinfo = '/'.$module.'/'.$ctrl;

            if (isset($this->httpsSelectors[$module.'~'.$ctrl.':*@'.$requestType])){
                $https = true;
            }elseif(isset($this->httpsSelectors[$module.'~*@'.$requestType])){
                $https = true;
            }elseif(isset($this->httpsSelectors['@'.$requestType])){
                $https = true;
            }

            if ($https) {
                $options = array('https'=>true);
            }

            $this->xmlMapEntryPoint->addUrlController($pathinfo, $module, $controller, $options);
        }
        else if (preg_match("/^([a-zA-Z0-9_\\.]+)~\\*@([a-zA-Z0-9_]+)$/", $selStr, $m)) {
            // --> <url module=""/>
            $module = $m[1];
            $requestType = $m[2];
            if(isset($this->httpsSelectors[$module.'~*@'.$requestType])){
                $https = true;
            }elseif(isset($this->httpsSelectors['@'.$requestType])){
                $https = true;
            }

            if ($https) {
                $options = array('https'=>true);
            }

            $this->xmlMapEntryPoint->addUrlModule("", $module, $options);
        }
    }

}


