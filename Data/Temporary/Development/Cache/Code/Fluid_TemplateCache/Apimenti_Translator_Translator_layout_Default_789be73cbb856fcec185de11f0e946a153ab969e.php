<?php
class FluidCache_Apimenti_Translator_Translator_layout_Default_789be73cbb856fcec185de11f0e946a153ab969e extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

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

$output0 .= '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments1 = array();
$arguments1['section'] = 'Title';
$arguments1['partial'] = NULL;
$arguments1['arguments'] = array (
);
$arguments1['optional'] = false;
$renderChildrenClosure2 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper3 = $self->getViewHelper('$viewHelper3', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper3->setArguments($arguments1);
$viewHelper3->setRenderingContext($renderingContext);
$viewHelper3->setRenderChildrenClosure($renderChildrenClosure2);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output0 .= $viewHelper3->initializeArgumentsAndRender();

$output0 .= '</title>
      
		<link rel="stylesheet" type="text/css" href="';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper
$arguments4 = array();
$arguments4['path'] = 'css/bootstrap.min.css';
$arguments4['package'] = 'Apimenti.Translator';
$arguments4['resource'] = NULL;
$arguments4['localize'] = true;
$renderChildrenClosure5 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper6 = $self->getViewHelper('$viewHelper6', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper');
$viewHelper6->setArguments($arguments4);
$viewHelper6->setRenderingContext($renderingContext);
$viewHelper6->setRenderChildrenClosure($renderChildrenClosure5);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper

$output0 .= $viewHelper6->initializeArgumentsAndRender();

$output0 .= '">
      <link rel="stylesheet" type="text/css" href="';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper
$arguments7 = array();
$arguments7['path'] = 'css/agilize.css';
$arguments7['package'] = 'Apimenti.Translator';
$arguments7['resource'] = NULL;
$arguments7['localize'] = true;
$renderChildrenClosure8 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper9 = $self->getViewHelper('$viewHelper9', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper');
$viewHelper9->setArguments($arguments7);
$viewHelper9->setRenderingContext($renderingContext);
$viewHelper9->setRenderChildrenClosure($renderChildrenClosure8);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper

$output0 .= $viewHelper9->initializeArgumentsAndRender();

$output0 .= '">

		<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
		<script type="text/javascript" src="';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper
$arguments10 = array();
$arguments10['path'] = 'js/bootstrap.min.js';
$arguments10['package'] = 'Apimenti.Translator';
$arguments10['resource'] = NULL;
$arguments10['localize'] = true;
$renderChildrenClosure11 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper12 = $self->getViewHelper('$viewHelper12', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper');
$viewHelper12->setArguments($arguments10);
$viewHelper12->setRenderingContext($renderingContext);
$viewHelper12->setRenderChildrenClosure($renderChildrenClosure11);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper

$output0 .= $viewHelper12->initializeArgumentsAndRender();

$output0 .= '"></script>      
		';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\BaseViewHelper
$arguments13 = array();
$renderChildrenClosure14 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper15 = $self->getViewHelper('$viewHelper15', $renderingContext, 'TYPO3\Fluid\ViewHelpers\BaseViewHelper');
$viewHelper15->setArguments($arguments13);
$viewHelper15->setRenderingContext($renderingContext);
$viewHelper15->setRenderChildrenClosure($renderChildrenClosure14);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\BaseViewHelper

$output0 .= $viewHelper15->initializeArgumentsAndRender();

$output0 .= '
	</head>
	<body>
		';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\FlashMessagesViewHelper
$arguments16 = array();
$arguments16['class'] = 'flashmessages';
$arguments16['additionalAttributes'] = NULL;
$arguments16['as'] = NULL;
$arguments16['severity'] = NULL;
$arguments16['dir'] = NULL;
$arguments16['id'] = NULL;
$arguments16['lang'] = NULL;
$arguments16['style'] = NULL;
$arguments16['title'] = NULL;
$arguments16['accesskey'] = NULL;
$arguments16['tabindex'] = NULL;
$arguments16['onclick'] = NULL;
$renderChildrenClosure17 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper18 = $self->getViewHelper('$viewHelper18', $renderingContext, 'TYPO3\Fluid\ViewHelpers\FlashMessagesViewHelper');
$viewHelper18->setArguments($arguments16);
$viewHelper18->setRenderingContext($renderingContext);
$viewHelper18->setRenderChildrenClosure($renderChildrenClosure17);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\FlashMessagesViewHelper

$output0 .= $viewHelper18->initializeArgumentsAndRender();

$output0 .= '

		<h1>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments19 = array();
$arguments19['section'] = 'Title';
$arguments19['partial'] = NULL;
$arguments19['arguments'] = array (
);
$arguments19['optional'] = false;
$renderChildrenClosure20 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper21 = $self->getViewHelper('$viewHelper21', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper21->setArguments($arguments19);
$viewHelper21->setRenderingContext($renderingContext);
$viewHelper21->setRenderChildrenClosure($renderChildrenClosure20);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output0 .= $viewHelper21->initializeArgumentsAndRender();

$output0 .= '</h1>
		';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments22 = array();
$arguments22['section'] = 'Content';
$arguments22['partial'] = NULL;
$arguments22['arguments'] = array (
);
$arguments22['optional'] = false;
$renderChildrenClosure23 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper24 = $self->getViewHelper('$viewHelper24', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper24->setArguments($arguments22);
$viewHelper24->setRenderingContext($renderingContext);
$viewHelper24->setRenderChildrenClosure($renderChildrenClosure23);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output0 .= $viewHelper24->initializeArgumentsAndRender();

$output0 .= '
	</body>
</html>';

return $output0;
}


}
#0             7135      