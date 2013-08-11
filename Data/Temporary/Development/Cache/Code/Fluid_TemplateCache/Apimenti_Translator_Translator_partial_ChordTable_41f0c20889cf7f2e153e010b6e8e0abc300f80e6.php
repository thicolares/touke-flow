<?php
class FluidCache_Apimenti_Translator_Translator_partial_ChordTable_41f0c20889cf7f2e153e010b6e8e0abc300f80e6 extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

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
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\ForViewHelper
$arguments0 = array();
$arguments0['each'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songNormalizedChord', $renderingContext);
$arguments0['as'] = 'chord';
$arguments0['key'] = '';
$arguments0['reverse'] = false;
$arguments0['iteration'] = NULL;
$renderChildrenClosure1 = function() use ($renderingContext, $self) {
$output2 = '';

$output2 .= '
    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments3 = array();
$arguments3['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordRootNote', $renderingContext);
$arguments3['keepQuotes'] = false;
$arguments3['encoding'] = 'UTF-8';
$arguments3['doubleEncode'] = true;
$renderChildrenClosure4 = function() use ($renderingContext, $self) {
return NULL;
};
$value5 = ($arguments3['value'] !== NULL ? $arguments3['value'] : $renderChildrenClosure4());

$output2 .= (!is_string($value5) ? $value5 : htmlspecialchars($value5, ($arguments3['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments3['encoding'], $arguments3['doubleEncode']));
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments6 = array();
$arguments6['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordFormula', $renderingContext);
$arguments6['keepQuotes'] = false;
$arguments6['encoding'] = 'UTF-8';
$arguments6['doubleEncode'] = true;
$renderChildrenClosure7 = function() use ($renderingContext, $self) {
return NULL;
};
$value8 = ($arguments6['value'] !== NULL ? $arguments6['value'] : $renderChildrenClosure7());

$output2 .= (!is_string($value8) ? $value8 : htmlspecialchars($value8, ($arguments6['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments6['encoding'], $arguments6['doubleEncode']));

$output2 .= '<br>
    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\IfViewHelper
$arguments9 = array();
// Rendering Boolean node
$arguments9['condition'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::evaluateComparator('!=', 1, 1);
$arguments9['then'] = NULL;
$arguments9['else'] = NULL;
$renderChildrenClosure10 = function() use ($renderingContext, $self) {
$output11 = '';

$output11 .= '
        ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\ThenViewHelper
$arguments12 = array();
$renderChildrenClosure13 = function() use ($renderingContext, $self) {
return '
            bli
        ';
};
$viewHelper14 = $self->getViewHelper('$viewHelper14', $renderingContext, 'TYPO3\Fluid\ViewHelpers\ThenViewHelper');
$viewHelper14->setArguments($arguments12);
$viewHelper14->setRenderingContext($renderingContext);
$viewHelper14->setRenderChildrenClosure($renderChildrenClosure13);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\ThenViewHelper

$output11 .= $viewHelper14->initializeArgumentsAndRender();

$output11 .= '
    ';
return $output11;
};
$arguments9['__thenClosure'] = function() use ($renderingContext, $self) {
return '
            bli
        ';
};
$viewHelper15 = $self->getViewHelper('$viewHelper15', $renderingContext, 'TYPO3\Fluid\ViewHelpers\IfViewHelper');
$viewHelper15->setArguments($arguments9);
$viewHelper15->setRenderingContext($renderingContext);
$viewHelper15->setRenderChildrenClosure($renderChildrenClosure10);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\IfViewHelper

$output2 .= $viewHelper15->initializeArgumentsAndRender();

$output2 .= '
';
return $output2;
};

return TYPO3\Fluid\ViewHelpers\ForViewHelper::renderStatic($arguments0, $renderChildrenClosure1, $renderingContext);
}


}
#0             4375      