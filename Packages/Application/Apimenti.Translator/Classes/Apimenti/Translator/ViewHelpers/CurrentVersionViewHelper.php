<?php

namespace Apimenti\Translator\ViewHelpers;

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