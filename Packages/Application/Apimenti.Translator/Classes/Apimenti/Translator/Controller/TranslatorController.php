<?php
namespace Apimenti\Translator\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Apimenti.Translator".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

class TranslatorController extends \TYPO3\Flow\Mvc\Controller\ActionController {

   /**
	 * @Flow\Inject
	 * @var \Apimenti\Translator\Service\SongParserService
	 */
	protected $songParserService;
   
    /**
     * 
     */
    public function indexAction() {
        $URL = 'http://www.cifraclub.com.br/legiao-urbana/que-pais-e-esse/';
        $res = $this->songParserService->parse($URL);
        if($res['success'] == true) {
            $this->view->assign('song', $res['song']);
        } else {
            die('Erro: '. $res['message']);
        }
    }
}
?>