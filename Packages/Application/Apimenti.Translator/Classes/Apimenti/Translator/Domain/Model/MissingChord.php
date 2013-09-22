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
use Doctrine\ORM\Mapping as ORM;

/**
 * Simple entity for save missing chords
 *
 * @Flow\Entity
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
     * @ORM\Column(nullable=true)
     */
    protected $song;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $songURL;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $noticedIn;

    /**
     * @var \DateTime
     * @ORM\Column(nullable=true)
     */
    protected $addedIn;

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

    /**
     * @param \DateTime $addedIn
     */
    public function setAddedIn($addedIn) {
        $this->addedIn = $addedIn;
    }

    /**
     * @return \DateTime
     */
    public function getAddedIn() {
        return $this->addedIn;
    }

    /**
     * @param \DateTime $noticedIn
     */
    public function setNoticedIn($noticedIn) {
        $this->noticedIn = $noticedIn;
    }

    /**
     * @return \DateTime
     */
    public function getNoticedIn() {
        return $this->noticedIn;
    }

}

?>