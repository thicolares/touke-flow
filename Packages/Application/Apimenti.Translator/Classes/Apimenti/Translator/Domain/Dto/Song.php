<?php
namespace Apimenti\Translator\Domain\Dto;

use TYPO3\Flow\Annotations as Flow;


/**
 * @Flow\Scope("prototype")
 */
class Song {
	    
   /**
    * Song URL
    * @var string
    */
	var $songURL;
   
   /**
    * Song HTML
    * @var string
    */
	var $songHTML;
   
   /**
    * Song TITLE
    * @var string
    */
	var $songTitle;
   
   /**
    * Artist Name
    * @var string
    */
	var $artistName;
   
   /**
    * Song Lyric
    * @var string
    */
	var $songLyric;
   
   /**
    * Song Chords
    * @var string
    */
	var $songChords = array();
   
   /**
    * Serial Song Chords
    * @var string
    */
	var $serialSongChords;
   
   
   function __construct($songURL, $songHTML, $songTitle, $artistName, $songLyric, $songChords, $serialSongChords) {
       $this->songURL = $songURL;
       $this->songHTML = $songHTML;
       $this->songTitle = $songTitle;
       $this->artistName = $artistName;
       $this->songLyric = $songLyric;
       $this->songChords = $songChords;
       $this->serialSongChords = $serialSongChords;
   }

   
   /**
    * @return string
    */
   public function getSongURL() {
       return $this->songURL;
   }

   public function setSongURL($songURL) {
       $this->songURL = $songURL;
   }

   /**
    * @return string
    */
   public function getSongHTML() {
       return $this->songHTML;
   }

   /**
    * @param string $songHTML 
    */
   public function setSongHTML($songHTML) {
       $this->songHTML = $songHTML;
   }

   /**
    * @return string
    */
   public function getSongTitle() {
       return $this->songTitle;
   }

   /**
    * @param type $songTitle 
    */
   public function setSongTitle($songTitle) {
       $this->songTitle = $songTitle;
   }

   /**
    * @return string
    */
   public function getArtistName() {
       return $this->artistName;
   }

   /**
    * @param type $artistName 
    */
   public function setArtistName($artistName) {
       $this->artistName = $artistName;
   }

   /**
    * @return string
    */
   public function getSongLyric() {
       return $this->songLyric;
   }

   /**
    * @param type $songLyric 
    */
   public function setSongLyric($songLyric) {
       $this->songLyric = $songLyric;
   }

   /**
    * @return string
    */
   public function getSongChords() {
       return $this->songChords;
   }

   /**
    * @param type $songChords 
    */
   public function setSongChords($songChords) {
       $this->songChords = $songChords;
   }

   /**
    * @return string
    */
   public function getSerialSongChords() {
       return $this->serialSongChords;
   }

    /**
    * @param type $serialSongChords 
    */
   public function setSerialSongChords($serialSongChords) {
       $this->serialSongChords = $serialSongChords;
   }

}
?>