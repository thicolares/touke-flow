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
    * Song 
    * @param \Apimenti\Translator\Domain\Dto\Song $song 
    */
    public function indexAction(\Apimenti\Translator\Domain\Dto\Song $song = null) {
        
        if($song != null) {
            $res = $this->songParserService->parse($song->getSongURL());
            if($res['success'] == true) {
                $this->view->assign('song', $res['song']);
            } else {
                $this->addFlashMessage($res['message'], 'danger');
            }
        }
        
    }
}
?>