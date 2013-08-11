<?php
class FluidCache_Apimenti_Translator_Translator_action_index_af9c1964334225e10c0ad8dd63e701975da80276 extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

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
            <div class="col-lg-12">
                <small><em><p class="text-muted">ToUke v0.1.0a</p></em></small>
                
                    <h2>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments1 = array();
$arguments1['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songTitle', $renderingContext);
$arguments1['keepQuotes'] = false;
$arguments1['encoding'] = 'UTF-8';
$arguments1['doubleEncode'] = true;
$renderChildrenClosure2 = function() use ($renderingContext, $self) {
return NULL;
};
$value3 = ($arguments1['value'] !== NULL ? $arguments1['value'] : $renderChildrenClosure2());

$output0 .= (!is_string($value3) ? $value3 : htmlspecialchars($value3, ($arguments1['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments1['encoding'], $arguments1['doubleEncode']));

$output0 .= ' <small>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments4 = array();
$arguments4['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.artistName', $renderingContext);
$arguments4['keepQuotes'] = false;
$arguments4['encoding'] = 'UTF-8';
$arguments4['doubleEncode'] = true;
$renderChildrenClosure5 = function() use ($renderingContext, $self) {
return NULL;
};
$value6 = ($arguments4['value'] !== NULL ? $arguments4['value'] : $renderChildrenClosure5());

$output0 .= (!is_string($value6) ? $value6 : htmlspecialchars($value6, ($arguments4['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments4['encoding'], $arguments4['doubleEncode']));

$output0 .= '</small></h2>
                
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments7 = array();
$arguments7['partial'] = 'ChordTable';
// Rendering Array
$array8 = array();
$array8['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments7['arguments'] = $array8;
$arguments7['section'] = NULL;
$arguments7['optional'] = false;
$renderChildrenClosure9 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper10 = $self->getViewHelper('$viewHelper10', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper10->setArguments($arguments7);
$viewHelper10->setRenderingContext($renderingContext);
$viewHelper10->setRenderChildrenClosure($renderChildrenClosure9);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output0 .= $viewHelper10->initializeArgumentsAndRender();

$output0 .= '
            </div>
            <div class="col-lg-10">
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
            <div class="col-lg-12">
                <small><em><p class="text-muted">ToUke v0.1.0a</p></em></small>
                
                    <h2>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments23 = array();
$arguments23['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.songTitle', $renderingContext);
$arguments23['keepQuotes'] = false;
$arguments23['encoding'] = 'UTF-8';
$arguments23['doubleEncode'] = true;
$renderChildrenClosure24 = function() use ($renderingContext, $self) {
return NULL;
};
$value25 = ($arguments23['value'] !== NULL ? $arguments23['value'] : $renderChildrenClosure24());

$output22 .= (!is_string($value25) ? $value25 : htmlspecialchars($value25, ($arguments23['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments23['encoding'], $arguments23['doubleEncode']));

$output22 .= ' <small>';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Format\HtmlspecialcharsViewHelper
$arguments26 = array();
$arguments26['value'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song.artistName', $renderingContext);
$arguments26['keepQuotes'] = false;
$arguments26['encoding'] = 'UTF-8';
$arguments26['doubleEncode'] = true;
$renderChildrenClosure27 = function() use ($renderingContext, $self) {
return NULL;
};
$value28 = ($arguments26['value'] !== NULL ? $arguments26['value'] : $renderChildrenClosure27());

$output22 .= (!is_string($value28) ? $value28 : htmlspecialchars($value28, ($arguments26['keepQuotes'] ? ENT_NOQUOTES : ENT_COMPAT), $arguments26['encoding'], $arguments26['doubleEncode']));

$output22 .= '</small></h2>
                
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-2">
                ';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper
$arguments29 = array();
$arguments29['partial'] = 'ChordTable';
// Rendering Array
$array30 = array();
$array30['song'] = \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::getPropertyPath($renderingContext->getTemplateVariableContainer(), 'song', $renderingContext);
$arguments29['arguments'] = $array30;
$arguments29['section'] = NULL;
$arguments29['optional'] = false;
$renderChildrenClosure31 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper32 = $self->getViewHelper('$viewHelper32', $renderingContext, 'TYPO3\Fluid\ViewHelpers\RenderViewHelper');
$viewHelper32->setArguments($arguments29);
$viewHelper32->setRenderingContext($renderingContext);
$viewHelper32->setRenderChildrenClosure($renderChildrenClosure31);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\RenderViewHelper

$output22 .= $viewHelper32->initializeArgumentsAndRender();

$output22 .= '
            </div>
            <div class="col-lg-10">
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
#0             10014     