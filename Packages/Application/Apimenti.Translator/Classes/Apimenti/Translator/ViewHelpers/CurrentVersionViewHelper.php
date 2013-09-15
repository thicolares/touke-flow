<?php

namespace Apimenti\Translator\ViewHelpers;

/*                                                                            *
 * This script belongs to the ToUke TYPO3 Flow package "Apimenti.Translator". *
 *                                                                            *
 * It is free software; you can redistribute it and/or modify it under        *
 * the terms of the GNU Affero General Public License as published by         *
 * the Free Software Foundation; either version 3 of the License, or          *
 * (at your option) any later version.                                        *
 *                                                                            *
 *                                                                            */

/**
 * View Helper which creates a control group form field based on twitter bootstrap
 */
use TYPO3\Flow\Annotations as Flow;

class CurrentVersionViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

    /**
     * Return Current Version
     * @return string
     */
    public function render() {
        return \Apimenti\Translator\Util\General::getCurrentVersion();
    }
}
?>