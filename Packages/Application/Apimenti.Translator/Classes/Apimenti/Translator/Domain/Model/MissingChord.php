<?php

namespace Apimenti\Translator\Domain\Model;

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
 * @author Thiago Colares <thiago@apimenti.com.br>
 */
class MissingChord {

    /**
     * @var string
     */
    protected $notation;

    /**
     * @var string
     */
    protected $song;

    /**
     * @var string
     */
    protected $songURL;

    /**
     * @var \DateTime
     */
    protected $occurredIn;

    /**
     * @param string $notation
     */
    public function setNotation($notation) {
        $this->notation = $notation;
    }

    /**
     * @return string
     */
    public function getNotation() {
        return $this->notation;
    }

    /**
     * @param \DateTime $occurredIn
     */
    public function setOccurredIn($occurredIn) {
        $this->occurredIn = $occurredIn;
    }

    /**
     * @return \DateTime
     */
    public function getOccurredIn() {
        return $this->occurredIn;
    }

    /**
     * @param string $song
     */
    public function setSong($song) {
        $this->song = $song;
    }

    /**
     * @return string
     */
    public function getSong() {
        return $this->song;
    }

    /**
     * @param string $songURL
     */
    public function setSongURL($songURL) {
        $this->songURL = $songURL;
    }

    /**
     * @return string
     */
    public function getSongURL() {
        return $this->songURL;
    }




}

?>