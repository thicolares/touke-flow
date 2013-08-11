<?php
class FluidCache_Apimenti_Translator_Translator_action_index_f727f9482aff42cf0546b72458a755187cc034e8 extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

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
                <pre>
                    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
$arguments5 = array();
$arguments5['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songLyric', $renderingContext);
$renderChildrenClosure6 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper7 = $self->getViewHelper('$viewHelper7', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Format\RawViewHelper');
$viewHelper7->setArguments($arguments5);
$viewHelper7->setRenderingContext($renderingContext);
$viewHelper7->setRenderChildrenClosure($renderChildrenClosure6);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper

$output0 .= $viewHelper7->initializeArgumentsAndRender();

$output0 .= '
                </pre>             
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <small><em><p class="text-muted">ToUke v0.1.0a</p></em></small>
                
                    <h2>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments8 = array();
$arguments8['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songTitle', $renderingContext);
$arguments8['keepQuotes'] = false;
$arguments8['encoding'] = 'UTF-8';
$arguments8['doubleEncode'] = true;
$renderChildrenClosure9 = function() use ($renderingContext, $self) {
return NULL;
};
$value10 = ($arguments8['value'] !== NULL ? $arguments8['value'] : $renderChildrenClosure9());

$output0 .= (!is_string($value10) ? $value10 : htmlspecialchars($value10, ($arguments8['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments8['encoding'], $arguments8['doubleEncode']));

$output0 .= ' <small>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments11 = array();
$arguments11['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.artistName', $renderingContext);
$arguments11['keepQuotes'] = false;
$arguments11['encoding'] = 'UTF-8';
$arguments11['doubleEncode'] = true;
$renderChildrenClosure12 = function() use ($renderingContext, $self) {
return NULL;
};
$value13 = ($arguments11['value'] !== NULL ? $arguments11['value'] : $renderChildrenClosure12());

$output0 .= (!is_string($value13) ? $value13 : htmlspecialchars($value13, ($arguments11['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments11['encoding'], $arguments11['doubleEncode']));

$output0 .= '</small></h2>
                
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments14 = array();
$arguments14['partial'] = 'ChordTable';
// Rendering Array
$array15 = array();
$array15['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments14['arguments'] = $array15;
$arguments14['section'] = NULL;
$arguments14['optional'] = false;
$renderChildrenClosure16 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper17 = $self->getViewHelper('$viewHelper17', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper17->setArguments($arguments14);
$viewHelper17->setRenderingContext($renderingContext);
$viewHelper17->setRenderChildrenClosure($renderChildrenClosure16);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output0 .= $viewHelper17->initializeArgumentsAndRender();

$output0 .= '
            </div>
            <div class="col-lg-10">
                <pre>
                    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
$arguments18 = array();
$arguments18['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songLyric', $renderingContext);
$renderChildrenClosure19 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper20 = $self->getViewHelper('$viewHelper20', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Format\RawViewHelper');
$viewHelper20->setArguments($arguments18);
$viewHelper20->setRenderingContext($renderingContext);
$viewHelper20->setRenderChildrenClosure($renderChildrenClosure19);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper

$output0 .= $viewHelper20->initializeArgumentsAndRender();

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
$output21 = '';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\LayoutViewHelper
$arguments22 = array();
$arguments22['name'] = 'Default';
$renderChildrenClosure23 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper24 = $self->getViewHelper('$viewHelper24', $renderingContext, 'TYPO3\Fluid\ViewHelpers\LayoutViewHelper');
$viewHelper24->setArguments($arguments22);
$viewHelper24->setRenderingContext($renderingContext);
$viewHelper24->setRenderChildrenClosure($renderChildrenClosure23);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\LayoutViewHelper

$output21 .= $viewHelper24->initializeArgumentsAndRender();

$output21 .= '

';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\SectionViewHelper
$arguments25 = array();
$arguments25['name'] = 'Title';
$renderChildrenClosure26 = function() use ($renderingContext, $self) {
return '
    
';
};

$output21 .= '';

$output21 .= '

';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\SectionViewHelper
$arguments27 = array();
$arguments27['name'] = 'Content';
$renderChildrenClosure28 = function() use ($renderingContext, $self) {
$output29 = '';

$output29 .= '
    <div class="container">

        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments30 = array();
$arguments30['partial'] = 'ChordTable';
// Rendering Array
$array31 = array();
$array31['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments30['arguments'] = $array31;
$arguments30['section'] = NULL;
$arguments30['optional'] = false;
$renderChildrenClosure32 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper33 = $self->getViewHelper('$viewHelper33', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper33->setArguments($arguments30);
$viewHelper33->setRenderingContext($renderingContext);
$viewHelper33->setRenderChildrenClosure($renderChildrenClosure32);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output29 .= $viewHelper33->initializeArgumentsAndRender();

$output29 .= '
            </div>
            <div class="col-lg-10">
                <pre>
                    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
$arguments34 = array();
$arguments34['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songLyric', $renderingContext);
$renderChildrenClosure35 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper36 = $self->getViewHelper('$viewHelper36', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Format\RawViewHelper');
$viewHelper36->setArguments($arguments34);
$viewHelper36->setRenderingContext($renderingContext);
$viewHelper36->setRenderChildrenClosure($renderChildrenClosure35);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper

$output29 .= $viewHelper36->initializeArgumentsAndRender();

$output29 .= '
                </pre>             
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <small><em><p class="text-muted">ToUke v0.1.0a</p></em></small>
                
                    <h2>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments37 = array();
$arguments37['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songTitle', $renderingContext);
$arguments37['keepQuotes'] = false;
$arguments37['encoding'] = 'UTF-8';
$arguments37['doubleEncode'] = true;
$renderChildrenClosure38 = function() use ($renderingContext, $self) {
return NULL;
};
$value39 = ($arguments37['value'] !== NULL ? $arguments37['value'] : $renderChildrenClosure38());

$output29 .= (!is_string($value39) ? $value39 : htmlspecialchars($value39, ($arguments37['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments37['encoding'], $arguments37['doubleEncode']));

$output29 .= ' <small>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments40 = array();
$arguments40['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.artistName', $renderingContext);
$arguments40['keepQuotes'] = false;
$arguments40['encoding'] = 'UTF-8';
$arguments40['doubleEncode'] = true;
$renderChildrenClosure41 = function() use ($renderingContext, $self) {
return NULL;
};
$value42 = ($arguments40['value'] !== NULL ? $arguments40['value'] : $renderChildrenClosure41());

$output29 .= (!is_string($value42) ? $value42 : htmlspecialchars($value42, ($arguments40['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments40['encoding'], $arguments40['doubleEncode']));

$output29 .= '</small></h2>
                
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments43 = array();
$arguments43['partial'] = 'ChordTable';
// Rendering Array
$array44 = array();
$array44['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments43['arguments'] = $array44;
$arguments43['section'] = NULL;
$arguments43['optional'] = false;
$renderChildrenClosure45 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper46 = $self->getViewHelper('$viewHelper46', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper46->setArguments($arguments43);
$viewHelper46->setRenderingContext($renderingContext);
$viewHelper46->setRenderChildrenClosure($renderChildrenClosure45);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output29 .= $viewHelper46->initializeArgumentsAndRender();

$output29 .= '
            </div>
            <div class="col-lg-10">
                <pre>
                    ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
$arguments47 = array();
$arguments47['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songLyric', $renderingContext);
$renderChildrenClosure48 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper49 = $self->getViewHelper('$viewHelper49', $renderingContext, 'TYPO3\Fluid\ViewHelpers\Format\RawViewHelper');
$viewHelper49->setArguments($arguments47);
$viewHelper49->setRenderingContext($renderingContext);
$viewHelper49->setRenderChildrenClosure($renderChildrenClosure48);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\Format\RawViewHelper

$output29 .= $viewHelper49->initializeArgumentsAndRender();

$output29 .= '
                </pre>             
            </div>
        </div>
    </div>
    
	
   
';
return $output29;
};

$output21 .= '';

return $output21;
}


}
#0             14007     