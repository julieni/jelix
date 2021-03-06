<?php
/**
* @package     jacl2
* @author      Laurent Jouanneau
* @contributor
* @copyright   2012 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class jacl2ModuleInstaller extends jInstallerModule {
    function install() {
        if ($this->firstConfExec()) {
            $conf = $this->getConfigIni();
            if (null == $conf->getValue('jacl2', 'coordplugins')) {
                $conf->setValue('jacl2', '1', 'coordplugins');
                if ($this->entryPoint->type != 'classic')
                    $onerror = 1;
                else
                    $onerror = 2;
                $conf->setValue('on_error', $onerror, 'coordplugin_jacl2');
                $conf->setValue('error_message', "jacl2~errors.action.right.needed", 'coordplugin_jacl2');
                $conf->setValue('on_error_action', "jelix~error:badright", 'coordplugin_jacl2');
            }
        }
    }
}
