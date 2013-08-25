<?php

	$node = new \TYPO3\Surf\Domain\Model\Node('touke');
	$node->setHostname('ssh2.eu1.frbit.com');
	$node->setOption('username', 'u-touke');
   

	$application = new \TYPO3\Surf\Application\TYPO3\Flow();
	$application->setDeploymentPath('/var/www/web/touke/htdocs');
	$application->setOption('repositoryUrl', 'https://github.com/colares/touke-flow.git');
	$application->addNode($node);

	$deployment->addApplication($application);

?>
