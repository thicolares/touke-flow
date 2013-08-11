<?php

namespace Apimenti\Translator\Service;

use TYPO3\Flow\Annotations as Flow;

class SongParserService {
	
    /**
     * Regular Expression to extract Title
     */
    const REG_EXP_TITLE             = '/\<h1 id\=[\"]ai_musica[\"].*?\>(.*?)\<\/h1\>/s';
    
    /**
     * Regular Expression to extract artist name
     */
    const REG_EXP_ARTIST            = '/\<h2 id\=[\"]ai_artista[\"].*?\>(.*?)\<\/h2\>/s';
    
    /**
     * Regular Expression to remove artist link
     */
    const REG_EXP_ARTIST_RM_LINK 	= '/\<a.*?\>(.*?)\<\/a\>/s';
    
    /**
     * Regular Expression to extract lyric content
     */
    const REG_EXP_LYRIC             = '/\<pre id\=[\"]ct_cifra[\"].*?\>(.*?)\<\/pre\>/s';
    
    /**
     * Regular Expression to extract all chords
     */
    const REG_EXP_CHORDS            = '/\<b.*?\>(.*?)\<\/b\>/s';
    
    /**
     * Regular Expression to extract clean chords
     */
    const REG_EXP_CLEAN_CHORDS      = '/(\<b\>|\<\/b\>)/s';

    /**
     * Regular Expression to extract the root note
     */
    const REG_EXP_ROOT_NOTE = '/^[A-G]#?b?/s'; 

    /**
     * Regular Expression to extract the bass note
     */
    const REG_EXP_BASS = '/\/[A-G]#?b?/s';
    
	// do not remove tabs! Lets hide by using css and transpose someday
	//$regexp_tab 			= '/\<span calss\=[\"]tablatura[\"].*?\>(.*?)\<\span\>/s';
	/**
    * @todo REMOVE TABS
    */
    
    
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
    * @var array
    */
	var $songChords = array();
   
   
   /**
    * Normalized Song Chords
    * @var array 
    */
   var $songNormalizedChords = array();
   
   /**
    * Serial Song Chords
    * @var string
    */
	var $serialSongChords;
   
    
   /**
	 * Get content from a given url
	 *
	 * @param string $url URL 
	 * @return string
	 * @author Thiago Colares
	 */
    private function getHTMLContentFromURL($URL) {
        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');

        // grab URL and pass it to the browser
        $HTML = utf8_encode(curl_exec($ch));

        // close cURL resource, and free up system resources
        curl_close($ch);
        
        return $HTML;
    }

    /**
     * Get Error Array
     * 
     * @param string $msg
     * @return array
     */
    private function getErrorArray($msg = 'Erro desconhecido.') {
        return array(
            'success' => true,
            'message' => $msg
        );
    }
    
    /**
     * Get Success Array
     * @return array
     */
    private function getSuccessArray() {
        return array('success' => true);
    }
    
    
    /**
     * Pull Song Title
     * @return array 
     */
    private function pullSongTitle(){
		// Song title
		if(preg_match(self::REG_EXP_TITLE, $this->songHTML, $this->songTitle)){
	  	    $this->songTitle = $this->songTitle[1];
	  	    if(!isset($this->songTitle)) 
	  	        return $this->getErrorArray();
			else
	  	        return $this->getSuccessArray();
	  	} else {
          $msg = '<strong>ÊTA!</strong> Tem certeza de que <em>\'' . $this->songURL . '\'</em> é o endereço completo de uma <strong>música cifrada</strong> do site <strong>www.cifraclub.com.br</strong>?';
          return $this->getErrorArray($msg);
	  	}
	}
   
   /**
    * Pull Artist Name
    * @return array
    */
   private function pullArtistName() {
		if(
	        preg_match(self::REG_EXP_ARTIST, $this->songHTML, $artistLink) &&
	        preg_match(self::REG_EXP_ARTIST_RM_LINK, $artistLink[0], $this->artistName)
	    ){
            $this->artistName = $this->artistName[1];
            return $this->getSuccessArray();
	    } else {
            return $this->getErrorArray();
	    }
   }


	/**
	 * Grab all lyrics and chords
	 *
	 * @return void
	 * @author Thiago Colares
	 */
	private function pullLyricsAndChords(){
		// Song entire lyric with chords
        if(preg_match(self::REG_EXP_LYRIC, $this->songHTML, $this->songLyric)){
            $this->songLyric = $this->songLyric[1];  
            
            // All chords
            if(preg_match_all(self::REG_EXP_CHORDS, $this->songLyric, $this->songChords)){
                // Removing repeated chords
                $this->songChords = array_unique($this->songChords[1]);
                // Reseting indexes
                $this->songChords = array_merge($this->songChords,array());
                // Serializing
                $this->serialSongChords = json_encode($this->songChords);
                
                return $this->getSuccessArray();
            } else {
                return $this->getErrorArray();
            }
        } else {
				return $this->getErrorArray();
        }
	}
   
   /**
    * Pull Normalized Chords
    * @return void 
    */
   private function pullNormalizedChords() {

        $rootNote = "";
        foreach ($this->songChords as $chord) {
            // Grabing the root note. ex.: C, D#, Gb etc. 
            if (preg_match(self::REG_EXP_ROOT_NOTE, $chord, $rootNote)) {
                $chordRootNote = $rootNote[0]; //got the root note
            }

            // Remove the root note from the chord and get the chord notation formula
            $chordFormula = preg_replace(self::REG_EXP_ROOT_NOTE, '', $chord);

            /**
             * @todo treat bass note kind! They say "just remove it for ukulele", but it could be better!
             * for now, let's remove the bass note from chord
             */
            $bassNote = "";
            if (preg_match(self::REG_EXP_BASS, $chordFormula, $bass)) { //if exists		
                $bassNote = $bass[0]; // without slash / 
                $chordFormula = preg_replace(self::REG_EXP_BASS, '', $chordFormula); //remove it from formula
            }

            // Slash (/) becomes underline (_)
            $chordFormula = str_replace("/", "_", $chordFormula);
            // $translatedChordFormula = str_replace("#", "_srp_", $chordFormula);
            // $translatedChordFormula = str_replace("(", "_opr_", $chordFormula);
            // $translatedChordFormula = str_replace(")", "_cpr_", $chordFormula);
            // $translatedChordFormula = str_replace("-", "_mns_", $chordFormula);
            // $translatedChordFormula = str_replace("+", "_pls_", $chordFormula);
            // $translatedBassNote = str_replace("/", "_", $bassNote);
            // translating to a known notation

            $this->songNormalizedChords[] = array(
                'chordRootNote' => $chordRootNote,
                'chordFormula' => $chordFormula
            );


//       		$translatedChordFormula = strtr($translatedChordFormula, $this->notations);		
//       		// this is the final chord (with bass)
//       		// Db => C#, Eb => D# etc.
//       		//$finalChord = strtr($chordRootNote.$translatedChordFormula.$translatedBassNote, $this->rootNotes);
//   		
//       		// this is the final chord (without bass)
//       		$finalChord = strtr($chordRootNote . $translatedChordFormula, $this->rootNotes);// Db => C#, Eb => D# 
        }
        
        return $this->getSuccessArray();
    }


   /**
    * Run Pullers
    * @return array 
    */
   private function runPullers() {
        // Set of pullers
        $pullers = array(
            'pullSongTitle',
            'pullArtistName',
            'pullLyricsAndChords',
            'pullNormalizedChords'
        );

        $res = $this->getErrorArray('Não foi possível extrair as informações da música');
        foreach ($pullers as $puller) {
            $res = $this->{$puller}();
            if ($res['success'] == false) {
                return $res;
            }
        }
        return $res;
    }
    
    /**
     *
     * @param string $URL
     */
    public function parse($URL) {
        $this->songHTML = $this->getHTMLContentFromURL($URL);
        $res = $this->runPullers();
        if($res['success'] == true) {
            return array(
                'success' => true,
                'song' => new \Apimenti\Translator\Domain\Dto\Song(
                    $this->songURL,
                    $this->songHTML,
                    $this->songTitle,
                    $this->artistName,
                    $this->songLyric,
                    $this->songChords,
                    $this->serialSongChords,
                    $this->songNormalizedChords
                )
            );
        } else {
            return $res;
        }
    }
    
} ?>