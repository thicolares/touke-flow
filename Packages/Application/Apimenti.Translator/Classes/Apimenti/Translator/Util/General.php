<?php
namespace Apimenti\Translator\Util;
/*                                                                            *
 * This script belongs to the ToUke TYPO3 Flow package "Apimenti.Translator". *
 *                                                                            *
 * It is free software; you can redistribute it and/or modify it under        *
 * the terms of the GNU Affero General Public License as published by         *
 * the Free Software Foundation; either version 3 of the License, or          *
 * (at your option) any later version.                                        *
 *                                                                            *
 *                                                                            */

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
