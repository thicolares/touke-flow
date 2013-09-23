<?php

	$node = new \TYPO3\Surf\Domain\Model\Node('touke');
	$node->setHostname('touke.dreamhosters.com');
	$node->setOption('username', 'thicolares');
   

	$application = new \TYPO3\Surf\Application\TYPO3\Flow();
	$application->setDeploymentPath('/home/thicolares/touke.dreamhosters.com');
	$application->setOption('repositoryUrl', 'https://github.com/colares/touke-flow.git');
   $application->setOption('composerCommandPath', '/home/thicolares/composer.phar');
   $application->setOption('keepReleases', 2);
   

   //   $application->setOption('composerCommandPath', 'php /var/www/vhosts/neos.typo3.org/home/composer.phar');
   
	$application->addNode($node);

	$deployment->addApplication($application);
   
   /**
    * renenabled it JUST after create a DB setup file
    */
   $workflow = new \TYPO3\Surf\Domain\Model\SimpleWorkflow();
	$deployment->setWorkflow($workflow);
	

   $deployment->onInitialize(function() use ($workflow, $application) {
       $workflow->addTask('typo3.surf:typo3:flow:migrate', 'migrate', $application);
   });

?>