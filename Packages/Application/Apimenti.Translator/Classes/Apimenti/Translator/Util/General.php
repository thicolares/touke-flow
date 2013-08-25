<?php
namespace Apimenti\Translator\Util;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Description of General
 *
 * @author thiago
 */
class General {

    /**
     * Get Current Version
     * @return type 
     */
    static public function getCurrentVersion() {
        $json = file_get_contents(FLOW_PATH_PACKAGES . 'Application/Apimenti.Translator/composer.json');
        $composerManifest = json_decode($json);
        return $composerManifest->version;
    }
    
}
