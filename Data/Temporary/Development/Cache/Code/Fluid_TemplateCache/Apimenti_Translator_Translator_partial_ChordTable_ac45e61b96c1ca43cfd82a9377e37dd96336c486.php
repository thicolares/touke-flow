<?php
class FluidCache_Apimenti_Translator_Translator_partial_ChordTable_ac45e61b96c1ca43cfd82a9377e37dd96336c486 extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

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

$output0 .= '

';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\ForViewHelper
$arguments1 = array();
$arguments1['each'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songChords', $renderingContext);
$arguments1['as'] = 'chord';
$arguments1['key'] = '';
$arguments1['reverse'] = false;
$arguments1['iteration'] = NULL;
$renderChildrenClosure2 = function() use ($renderingContext, $self) {
$output3 = '';

$output3 .= '
    ';
// Rendering ViewHelper Apimenti\Translator\ViewHelpers\ChordBoxViewHelper
$arguments4 = array();
$arguments4['chordOriginal'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordOriginal', $renderingContext);
$arguments4['chordRootNote'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordRootNote', $renderingContext);
$arguments4['chordFormula'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'chord.chordFormula', $renderingContext);
$arguments4['then'] = NULL;
$arguments4['else'] = NULL;
$renderChildrenClosure5 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper6 = $self->getViewHelper('$viewHelper6', $renderingContext, 'Apimenti\Translator\ViewHelpers\ChordBoxViewHelper');
$viewHelper6->setArguments($arguments4);
$viewHelper6->setRenderingContext($renderingContext);
$viewHelper6->setRenderChildrenClosure($renderChildrenClosure5);
// End of ViewHelper Apimenti\Translator\ViewHelpers\ChordBoxViewHelper

$output3 .= $viewHelper6->initializeArgumentsAndRender();

$output3 .= '
';
return $output3;
};

$output0 .= TYPO3\Fluid\ViewHelpers\ForViewHelper::renderStatic($arguments1, $renderChildrenClosure2, $renderingContext);

return $output0;
}


}
#0             2573      