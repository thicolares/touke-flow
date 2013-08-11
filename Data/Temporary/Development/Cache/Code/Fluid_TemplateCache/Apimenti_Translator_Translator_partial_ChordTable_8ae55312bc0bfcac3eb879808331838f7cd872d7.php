<?php
class FluidCache_Apimenti_Translator_Translator_partial_ChordTable_8ae55312bc0bfcac3eb879808331838f7cd872d7 extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

public function getVariableContainer() {
	// TODO
	return new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer();
}
public function getLayoutName(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {

return NULL;
}
public function hasLayout() {
return FALSE;
}

/**
 * Main Render function
 */
public function render(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
$self = $this;
$output0 = '';

$output0 .= 'asdasd
';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\ForViewHelper
$arguments1 = array();
$arguments1['each'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songNormalizedChord', $renderingContext);
$arguments1['as'] = 'chord';
$arguments1['key'] = '';
$arguments1['reverse'] = false;
$arguments1['iteration'] = NULL;
$renderChildrenClosure2 = function() use ($renderingContext, $self) {
$output3 = '';

$output3 .= '
    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments4 = array();
$arguments4['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordRootNote', $renderingContext);
$arguments4['keepQuotes'] = false;
$arguments4['encoding'] = 'UTF-8';
$arguments4['doubleEncode'] = true;
$renderChildrenClosure5 = function() use ($renderingContext, $self) {
return NULL;
};
$value6 = ($arguments4['value'] !== NULL ? $arguments4['value'] : $renderChildrenClosure5());

$output3 .= (!is_string($value6) ? $value6 : htmlspecialchars($value6, ($arguments4['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments4['encoding'], $arguments4['doubleEncode']));
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments7 = array();
$arguments7['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordFormula', $renderingContext);
$arguments7['keepQuotes'] = false;
$arguments7['encoding'] = 'UTF-8';
$arguments7['doubleEncode'] = true;
$renderChildrenClosure8 = function() use ($renderingContext, $self) {
return NULL;
};
$value9 = ($arguments7['value'] !== NULL ? $arguments7['value'] : $renderChildrenClosure8());

$output3 .= (!is_string($value9) ? $value9 : htmlspecialchars($value9, ($arguments7['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments7['encoding'], $arguments7['doubleEncode']));

$output3 .= '<br>
    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\IfViewHelper
$arguments10 = array();
// Rendering Boolean node
$arguments10['condition'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::evaluateComparator('!=', 1, 1);
$arguments10['then'] = NULL;
$arguments10['else'] = NULL;
$renderChildrenClosure11 = function() use ($renderingContext, $self) {
$output12 = '';

$output12 .= '
        ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\ThenViewHelper
$arguments13 = array();
$renderChildrenClosure14 = function() use ($renderingContext, $self) {
return '
            bli
        ';
};
$viewHelper15 = $self->getViewHelper('$viewHelper15', $renderingContext, 'TYPO3\Fluid\ViewHelpers\ThenViewHelper');
$viewHelper15->setArguments($arguments13);
$viewHelper15->setRenderingContext($renderingContext);
$viewHelper15->setRenderChildrenClosure($renderChildrenClosure14);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\ThenViewHelper

$output12 .= $viewHelper15->initializeArgumentsAndRender();

$output12 .= '
    ';
return $output12;
};
$arguments10['__thenClosure'] = function() use ($renderingContext, $self) {
return '
            bli
        ';
};
$viewHelper16 = $self->getViewHelper('$viewHelper16', $renderingContext, 'TYPO3\Fluid\ViewHelpers\IfViewHelper');
$viewHelper16->setArguments($arguments10);
$viewHelper16->setRenderingContext($renderingContext);
$viewHelper16->setRenderChildrenClosure($renderChildrenClosure11);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\IfViewHelper

$output3 .= $viewHelper16->initializeArgumentsAndRender();

$output3 .= '
';
return $output3;
};

$output0 .= TYPO3\Fluid\ViewHelpers\ForViewHelper::renderStatic($arguments1, $renderChildrenClosure2, $renderingContext);

return $output0;
}


}
#0             4443      