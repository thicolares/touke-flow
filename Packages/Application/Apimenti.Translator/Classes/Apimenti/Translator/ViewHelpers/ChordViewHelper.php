<?php
namespace Apimenti\Translator\ViewHelpers;

/**
 * View Helper which creates a control group form field based on twitter bootstrap
 */

class ChordViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper {
	/**
	 *
	 * @return string the rendered string
	 */
	public function render() {
		$out  = '<div class="form-actions">';
		$out .= '<div class="row">';
		$out .= '<div id="responseMsg"></div>';
		$out .= $this->renderChildren();
		$out .= '</div>';
		$out .= '</div>';
		return $out;
	}
   
   
	public function parseChords(){


	    
//	    $html = "";
//       	$javascript = "$(document).ready(function() {";
//		$chordLi =  "";

       	$slideId = 0;
   		foreach($this->songChords as $chord){
   				
       		$rootNote = "";

			// Grabing the root note. ex.: C, D#, Gb etc. 
       		if(preg_match($this->regexpRootNote, $chord, $rootNote)){
       			$chordRootNote = $rootNote[0]; //got the root note
       		}	
       		
       		// Remove the root note from the chord and get the chord notation formula
       		$chordFormula = preg_replace($this->regexpRootNote, '', $chord); 
   		
   		    /**
   		    * @todo treat bass note kind! They say "just remove it for ukulele", but it could be better!
   		    */
       		// for now, let's remove the bass note from chord
       		$regexpBass = '/\/[A-G]#?b?/s'; // 
       		$bassNote = "";	
       		if(preg_match($regexpBass, $chordFormula, $bass)){ //if exists		
       			$bassNote = $bass[0]; // without slash / 
       			$chordFormula = preg_replace($regexpBass, '', $chordFormula); //remove it from formula
       		}	
   		
       		// Slash (/) becomes underline (_)
       		$translatedChordFormula = str_replace("/", "_", $chordFormula);
       		// $translatedChordFormula = str_replace("#", "_srp_", $chordFormula);
       		// $translatedChordFormula = str_replace("(", "_opr_", $chordFormula);
       		// $translatedChordFormula = str_replace(")", "_cpr_", $chordFormula);
       		// $translatedChordFormula = str_replace("-", "_mns_", $chordFormula);
       		// $translatedChordFormula = str_replace("+", "_pls_", $chordFormula);
       		$translatedBassNote = str_replace("/", "_", $bassNote);
       		// translating to a known notation
       		$translatedChordFormula = strtr($translatedChordFormula, $this->notations);		
       		// this is the final chord (with bass)
       		// Db => C#, Eb => D# etc.
       		//$finalChord = strtr($chordRootNote.$translatedChordFormula.$translatedBassNote, $this->rootNotes);
   		
       		// this is the final chord (without bass)
       		$finalChord = strtr($chordRootNote . $translatedChordFormula, $this->rootNotes);// Db => C#, Eb => D# 
   
            
            
            
            
            
       		// number of variations
			if(isset($this->allChords[$finalChord])){
				$variations = $this->allChords[$finalChord]; 
			} else {
				$variations = 0;
				$this->missingChords[] = $finalChord; 
			}
       		
   		
       		// get chord box html
			$tmp[] = $finalChord;	

       		$chordLi .= '<li>' . $this->_htmlChordBox($finalChord, $variations, $slideId, $chordRootNote, $chordFormula, $bassNote) . '</li>';
       		$javascript .= $this->_jsChordBox($slideId);		
       		$slideId ++;
   		
   		
       		//$image_filename = "http://www.ukefy.com/chords/".urlencode(str_replace("/", "_", $chord))."~1.gif";
       		//$image_filename = "http://localhost/uke/chords/".$chordRootNote.$chordFormula.".gif";
       		//for($v=1;$v<=$variations;$v++){
       		//$chord_image_filename = "chords/".urlencode($finalChord)."~1.gif";		
       		//$html .= "<span class=\"chord\" style=\"background-image:url('".$chord_image_filename."');\">".$chordRootNote.$chordFormula."</span>";		
   	
       	}
		//debug($tmp);
		//debug($this->missingChords);
   		
       		/*
   		
       		$image_filename = "http://www.ukefy.com/chords/".urlencode(str_replace("/", "_", $chord)).".gif";					
       		@$url=getimagesize($image_filename);
       		if(is_array($url)){
       			$html .= "<img src='chords/".urlencode(str_replace("/", "_", $chord)).".gif'>";		
       		}else{
       			//$html .= "<a class='missing'  href='make_chord.php?missing_chord_name=".urlencode($chord)."'>Suggest the chord <br><strong>".$chord."</strong></a>";		
       			$html .= "<span class='missing'  href='make_chord.php?missing_chord_name=".urlencode($chord)."'>Ops! Dunno the <strong>".$chord."</strong>. I'm working on it.</strong></span>";		
       		}*/
   }

}
?>