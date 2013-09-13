<?php
if (FLOW_PATH_ROOT !== '/Library/WebServer/Documents/touke-flow/' || !file_exists('/Library/WebServer/Documents/touke-flow/Data/Temporary/Production/Configuration/ProductionConfigurations.php')) {
	unlink(__FILE__);
	return array();
}
return require '/Library/WebServer/Documents/touke-flow/Data/Temporary/Production/Configuration/ProductionConfigurations.php';
?>