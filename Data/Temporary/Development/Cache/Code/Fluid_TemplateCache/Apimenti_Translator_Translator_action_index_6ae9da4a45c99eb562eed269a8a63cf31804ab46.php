<?php
class FluidCache_Apimenti_Translator_Translator_action_index_6ae9da4a45c99eb562eed269a8a63cf31804ab46 extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

public function getVariableContainer() {
	// TODO
	return new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer();
}
public function getLayoutName(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {

return 'Default';
}
public function hasLayout() {
return TRUE;
}

/**
 * section Title
 */
public function section_768e0c1c69573fb588f61f1308a015c11468e05f(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
$self = $this;

return '
    
';
}
/**
 * section Content
 */
public function section_4f9be057f0ea5d2ba72fd2c810e8d7b9aa98b469(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
$self = $this;
$output0 = '';

$output0 .= '
    <div class="container">
        

        
        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments1 = array();
$arguments1['partial'] = 'ChordTable';
// Rendering Array
$array2 = array();
$array2['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments1['arguments'] = $array2;
$arguments1['section'] = NULL;
$arguments1['optional'] = false;
$renderChildrenClosure3 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper4 = $self->getViewHelper('$viewHelper4', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper4->setArguments($arguments1);
$viewHelper4->setRenderingContext($renderingContext);
$viewHelper4->setRenderChildrenClosure($renderChildrenClosure3);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output0 .= $viewHelper4->initializeArgumentsAndRender();

$output0 .= '
            </div>
            <div class="col-lg-10">
                <h3>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments5 = array();
$arguments5['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songTitle', $renderingContext);
$arguments5['keepQuotes'] = false;
$arguments5['encoding'] = 'UTF-8';
$arguments5['doubleEncode'] = true;
$renderChildrenClosure6 = function() use ($renderingContext, $self) {
return NULL;
};
$value7 = ($arguments5['value'] !== NULL ? $arguments5['value'] : $renderChildrenClosure6());

$output0 .= (!is_string($value7) ? $value7 : htmlspecialchars($value7, ($arguments5['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments5['encoding'], $arguments5['doubleEncode']));

$output0 .= ' <small>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments8 = array();
$arguments8['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.artistName', $renderingContext);
$arguments8['keepQuotes'] = false;
$arguments8['encoding'] = 'UTF-8';
$arguments8['doubleEncode'] = true;
$renderChildrenClosure9 = function() use ($renderingContext, $self) {
return NULL;
};
$value10 = ($arguments8['value'] !== NULL ? $arguments8['value'] : $renderChildrenClosure9());

$output0 .= (!is_string($value10) ? $value10 : htmlspecialchars($value10, ($arguments8['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments8['encoding'], $arguments8['doubleEncode']));

$output0 .= '</small></h1>
                <pre>
                    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
$arguments11 = array();
$arguments11['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songLyric', $renderingContext);
$renderChildrenClosure12 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper13 = $self->getViewHelper('$viewHelper13', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Format\RawViewHelper');
$viewHelper13->setArguments($arguments11);
$viewHelper13->setRenderingContext($renderingContext);
$viewHelper13->setRenderChildrenClosure($renderChildrenClosure12);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper

$output0 .= $viewHelper13->initializeArgumentsAndRender();

$output0 .= '
                </pre>             
            </div>
        </div>
    </div>
    
	
   
';

return $output0;
}
/**
 * Main Render function
 */
public function render(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
$self = $this;
$output14 = '';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\LayoutViewHelper
$arguments15 = array();
$arguments15['name'] = 'Default';
$renderChildrenClosure16 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper17 = $self->getViewHelper('$viewHelper17', $renderingContext, 'TYPO3\Fluid\ViewHelpers\LayoutViewHelper');
$viewHelper17->setArguments($arguments15);
$viewHelper17->setRenderingContext($renderingContext);
$viewHelper17->setRenderChildrenClosure($renderChildrenClosure16);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\LayoutViewHelper

$output14 .= $viewHelper17->initializeArgumentsAndRender();

$output14 .= '

';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\SectionViewHelper
$arguments18 = array();
$arguments18['name'] = 'Title';
$renderChildrenClosure19 = function() use ($renderingContext, $self) {
return '
    
';
};

$output14 .= '';

$output14 .= '

';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\SectionViewHelper
$arguments20 = array();
$arguments20['name'] = 'Content';
$renderChildrenClosure21 = function() use ($renderingContext, $self) {
$output22 = '';

$output22 .= '
    <div class="container">
        

        
        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments23 = array();
$arguments23['partial'] = 'ChordTable';
// Rendering Array
$array24 = array();
$array24['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments23['arguments'] = $array24;
$arguments23['section'] = NULL;
$arguments23['optional'] = false;
$renderChildrenClosure25 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper26 = $self->getViewHelper('$viewHelper26', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper26->setArguments($arguments23);
$viewHelper26->setRenderingContext($renderingContext);
$viewHelper26->setRenderChildrenClosure($renderChildrenClosure25);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output22 .= $viewHelper26->initializeArgumentsAndRender();

$output22 .= '
            </div>
            <div class="col-lg-10">
                <h3>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments27 = array();
$arguments27['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songTitle', $renderingContext);
$arguments27['keepQuotes'] = false;
$arguments27['encoding'] = 'UTF-8';
$arguments27['doubleEncode'] = true;
$renderChildrenClosure28 = function() use ($renderingContext, $self) {
return NULL;
};
$value29 = ($arguments27['value'] !== NULL ? $arguments27['value'] : $renderChildrenClosure28());

$output22 .= (!is_string($value29) ? $value29 : htmlspecialchars($value29, ($arguments27['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments27['encoding'], $arguments27['doubleEncode']));

$output22 .= ' <small>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments30 = array();
$arguments30['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.artistName', $renderingContext);
$arguments30['keepQuotes'] = false;
$arguments30['encoding'] = 'UTF-8';
$arguments30['doubleEncode'] = true;
$renderChildrenClosure31 = function() use ($renderingContext, $self) {
return NULL;
};
$value32 = ($arguments30['value'] !== NULL ? $arguments30['value'] : $renderChildrenClosure31());

$output22 .= (!is_string($value32) ? $value32 : htmlspecialchars($value32, ($arguments30['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments30['encoding'], $arguments30['doubleEncode']));

$output22 .= '</small></h1>
                <pre>
                    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
$arguments33 = array();
$arguments33['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songLyric', $renderingContext);
$renderChildrenClosure34 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper35 = $self->getViewHelper('$viewHelper35', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Format\RawViewHelper');
$viewHelper35->setArguments($arguments33);
$viewHelper35->setRenderingContext($renderingContext);
$viewHelper35->setRenderChildrenClosure($renderChildrenClosure34);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper

$output22 .= $viewHelper35->initializeArgumentsAndRender();

$output22 .= '
                </pre>             
            </div>
        </div>
    </div>
    
	
   
';
return $output22;
};

$output14 .= '';

return $output14;
}


}
#0             9586      