<?php

	$node = new \TYPO3\Surf\Domain\Model\Node('touke');
	$node->setHostname('apimenti.com.br');
	$node->setOption('username', 'apimenti');
   $node->setOption('password', '1234');
   

	$application = new \TYPO3\Surf\Application\TYPO3\Flow();
	$application->setDeploymentPath('/home/apimenti/touke.apimenti.com.br');
	$application->setOption('repositoryUrl', 'git@github.com:colares/touke-flow.git');
	$application->addNode($node);

	$deployment->addApplication($application);

?>
