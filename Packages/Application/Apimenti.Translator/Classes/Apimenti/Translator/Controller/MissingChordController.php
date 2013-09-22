<?php
namespace Apimenti\Translator\Controller;

/*                                                                            *
 * This script belongs to the ToUke TYPO3 Flow package "Apimenti.Translator". *
 *                                                                            *
 * It is free software; you can redistribute it and/or modify it under        *
 * the terms of the GNU Affero General Public License as published by         *
 * the Free Software Foundation; either version 3 of the License, or          *
 * (at your option) any later version.                                        *
 *                                                                            *
 *                                                                            */

use TYPO3\Flow\Annotations as Flow;

class MissingChordController extends \TYPO3\Flow\Mvc\Controller\ActionController {

    /**
     * @Flow\Inject
     * @var \Apimenti\Translator\Domain\Repository\MissingChordRepository
     */
    protected $missingChordRepository;

    /**
     *
     */
    public function indexAction() {
        $missingChords = $this->missingChordRepository->findAll();
        $this->view->assign('missingChords', $missingChords);
    }
}