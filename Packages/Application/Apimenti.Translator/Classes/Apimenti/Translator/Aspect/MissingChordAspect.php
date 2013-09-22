<?php
namespace Apimenti\Translator\Aspect;
/*                                                                            *
 * This script belongs to the ToUke TYPO3 Flow package "Apimenti.Translator". *
 *                                                                            *
 * It is free software; you can redistribute it and/or modify it under        *
 * the terms of the GNU Affero General Public License as published by         *
 * the Free Software Foundation; either version 3 of the License, or          *
 * (at your option) any later version.                                        *
 *                                                                            *
 *                                                                            */
use Apimenti\Translator\Domain\Model\MissingChord;
use Apimenti\Translator\Util\General;
use Doctrine\Tests\DBAL\Functional\DataAccessTest;
use TYPO3\Flow\Annotations as Flow;

/**
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class MissingChordAspect {

    /**
     * @Flow\Inject
     * @var \Apimenti\Translator\Domain\Repository\MissingChordRepository
     */
    protected $missingChordRepository;

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * Restart Activity or Decision worker if it is down
     *
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
     * @Flow\After("method(Apimenti\Translator\ViewHelpers\ChordBoxViewHelper->countVariations())")
     */
    public function saveMissingChord(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
        $finalChord = $joinPoint->getMethodArgument('finalChord');
        $count = $joinPoint->getResult();

        if($count == 0) {
            $missingChord = new MissingChord();
            $missingChord->setNotation($finalChord);
            $missingChord->setNoticedIn(General::getToday());
            $this->missingChordRepository->add($missingChord);
            $this->persistenceManager->persistAll();
        }
    }

} ?>