<?php
class FluidCache_TYPO3_Flow_Mvc_Standard_action_index_61c1e77dc9b494db1c50cbbd519116966aca365e extends \TYPO3\Fluid\Core\Compiler\AbstractCompiledTemplate {

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

$output0 .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<title>TYPO3 Flow - Standard View</title>
		';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\BaseViewHelper
$arguments1 = array();
$renderChildrenClosure2 = function() use ($renderingContext, $self) {
return NULL;
};
$viewHelper3 = $self->getViewHelper('$viewHelper3', $renderingContext, 'TYPO3\Fluid\ViewHelpers\BaseViewHelper');
$viewHelper3->setArguments($arguments1);
$viewHelper3->setRenderingContext($renderingContext);
$viewHelper3->setRenderChildrenClosure($renderChildrenClosure2);
// End of ViewHelper TYPO3\Fluid\ViewHelpers\BaseViewHelper

$output0 .= $viewHelper3->initializeArgumentsAndRender();

$output0 .= '
		<style type="text/css">
			body {
				font-family: Helvetica, Arial, sans-serif;
				margin: 0;
			}

			h1 {
				font-size: 15px;
			}

			.TYPO3_WidgetLibrary_Widgets_ApplicationWindow {
				position: absolute;
				width: 100%;
				height: 100%;
				background-color: #515151;
				margin: 0;
				z-index:1000;
			}

			.TYPO3_WidgetLibrary_Widgets_FloatingWindow {
				width: 500px;
				height: 360px;
				background: none;
				background-image: url(';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper
$arguments4 = array();
$arguments4['path'] = 'Mvc/StandardView_FloatingWindow.png';
$arguments4['package'] = NULL;
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

$output0 .= ');

			}

			.TYPO3_WidgetLibrary_Widgets_FloatingWindow .TYPO3_WidgetLibrary_Widgets_Window_TitleBar {
				font-size: 13px;
				position: relative;
				padding: 25px 0 0 26px;
				width: 440px;
				text-align: center;
				color: #404040;
			}

			.TYPO3_WidgetLibrary_Widgets_FloatingWindow .TYPO3_WidgetLibrary_Widgets_Window_Body {
				font-size: 14px;
				position: relative;
				padding: 30px 0 0 50px;
				width: 400px;
				text-align: left;
				color: #202020;
				line-height: 18px;
			}

			.StandardView_Package {
				width: 70px;
				float: right;
				margin: 0 0 80px 10px;
			}

		</style>
	</head>
	<body>
		<div class="TYPO3_WidgetLibrary_Widgets_ApplicationWindow">
			<div class="TYPO3_WidgetLibrary_Widgets_FloatingWindow">
				<div class="TYPO3_WidgetLibrary_Widgets_Window_TitleBar">Flow - Standard View</div>
				<div class="TYPO3_WidgetLibrary_Widgets_Window_Body">
					<img src="';
// Rendering ViewHelper TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper
$arguments7 = array();
$arguments7['path'] = 'Mvc/StandardView_Package.png';
$arguments7['package'] = NULL;
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

$output0 .= '" class="StandardView_Package" alt="Nice packshot of imaginary TYPO3 Flow box" />
					<h1>Welcome to TYPO3 Flow!</h1>

					<p>This is the default view of the TYPO3 Flow MVC component. You see this message because no
					other view is available.</p>

					<p>Please refer to the <a href="http://flow.typo3.org/documentation/quickstart.html">
					Quickstart</a> for more information on how to create and configure one.</p>

					<p>Be aware of the fact that only in &quot;Development&quot; context
					caches are flushed automatically. See the sections <a href="http://flow.typo3.org/documentation/guide/partiii/bootstrapping.html">
					on the TYPO3 Flow bootstrap</a> and <a href="http://flow.typo3.org/documentation/guide/partiii/configuration.html">
					on configuration</a> for details.</p>

					<p><em>Have fun! The TYPO3 Flow Development Team</em></p>
				</div>
			</div>
		</div>
	</body>
</html>';

return $output0;
}


}
#0             5195      