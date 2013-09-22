<?php
/**
 * Created by JetBrains PhpStorm.
 * User: colares
 * Date: 22/09/13
 * Time: 18:30
 * To change this template use File | Settings | File Templates.
 */
namespace Apimenti\Translator\Domain\Repository;

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

/**
 * Simple entity for save missing chords
 *
 * @Flow\Scope("singleton")
 * @author Thiago Colares <thiago@apimenti.com.br>
 */
class MissingChordRepository extends \TYPO3\Flow\Persistence\Repository {
    /**
     * @var array
     */
    protected $defaultOrderings = array('notation' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);

}