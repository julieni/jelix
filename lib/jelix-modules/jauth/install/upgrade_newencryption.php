<?php
/**
* @package     jelix
* @subpackage  jauth module
* @author      Laurent Jouanneau
* @copyright   2016 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class jauthModuleUpgrader_newencryption extends jInstallerModule {

    public $targetVersions = array('1.7.0-beta.1');
    public $date = '2016-05-22 14:34';

    protected static $key = null;

    function install() {

        if (self::$key === null) {
            $cryptokey = \Defuse\Crypto\Key::createNewRandomKey();
            self::$key = $cryptokey->saveToAsciiSafeString();
        }
        $conf = $this->getConfigIni()->getValue('auth', 'coordplugins');
        if ($conf == '1') {
            $this->getConfigIni()->removeValue('persistant_crypt_key', 'coordplugin_auth');
        }
        else if ($conf) {
            $conff = jApp::configPath($conf);
            if (file_exists($conff)) {
                $ini = new \Jelix\IniFile\IniModifier($conff);
                $ini->removeValue('persistant_crypt_key');
            }
        }
        $this->getLocalConfigIni()->setValue('persistant_encryption_key', self::$key, 'coordplugin_auth');
    }
}
