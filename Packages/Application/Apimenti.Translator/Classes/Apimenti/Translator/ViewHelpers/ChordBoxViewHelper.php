<?php

namespace Apimenti\Translator\ViewHelpers;

/**
 * View Helper which creates a control group form field based on twitter bootstrap
 */
use TYPO3\Flow\Annotations as Flow;

class ChordBoxViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

    /**
     * @Flow\Inject
     * @var \Apimenti\Translator\Domain\Model\Chord
     */
    protected $chord;

    /**
     * Initialize Arguments
     */
    public function initializeArguments() {
        $this->registerArgument('chordOriginal', 'string', 'Chord Original Name');
        $this->registerArgument('chordRootNote', 'string', 'Chord Root Note');
        $this->registerArgument('chordFormula', 'string', 'Chord Formula');
    }

    /**
     *
     * @return string the rendered string
     */
    public function render() {
        return $this->buildChordBox($this->arguments['chordOriginal'], $this->arguments['chordRootNote'], $this->arguments['chordFormula']);
    }
    
    /**
     * Build Chord Box
     * @param type $chordRootNote
     * @param type $chordFormula
     * @return type 
     */
    private function buildChordBox($chordOriginal, $chordRootNote, $chordFormula) {
        $finalChord = $this->translateChord($chordRootNote, $chordFormula);
        $variations = $this->countVariations($finalChord);
        if($variations>1){
            return '<div class="thumbnail" style="text-align: center; width:75px; height: 100px; float: left; margin: 0px 10px 10px 0px" >
    <strong>'. $chordOriginal . 
'  </strong>  
      '. "<img src=\"_Resources/Static/Packages/Apimenti.Translator/img/chords/$finalChord~1.gif\" alt=\"Smiley face\">" . '
      
    </div>

     ';
            //return $this->chordBoxN($finalChord, $variations, $slideId, $chordRootNote, $chordFormula, $bassNote);
        //}
//        if($variations>1){
//            return $this->chordBoxN($finalChord, $variations, $slideId, $chordRootNote, $chordFormula, $bassNote);
//        }else if($variations == 1){
//            return $this->chordBox1($finalChord, $variations, $slideId, $chordRootNote, $chordFormula, $bassNote);
        }else{
                        return '<div class="thumbnail" style="text-align: center; width:75px; height: 100px; float: left; margin: 0px 10px 10px 0px" >
    <strong>'. $chordOriginal . 
'  </strong>  
      '. "<img src=\"_Resources/Static/Packages/Apimenti.Translator/img/sad-donkey.png\" alt=\"Esse eu não sei...\">" . '
      
    </div>

     ';
        }
    }

    /**
     * Count the number of existent variations of a given chord
     * @param int $finalChord 
     */
    private function countVariations($finalChord) {
			if(isset($this->chord->allChords[$finalChord])){
             return $this->chord->allChords[$finalChord]; 
			}
         return 0;
    }
    
    /**
     * Translate Chord to expetected format
     * 
     * @param string $chordRootNote
     * @param string $chordFormula
     * @return string
     */
    private function translateChord($chordRootNote, $chordFormula) {
        $translatedChordFormula = strtr($chordFormula, $this->chord->notations);
        // this is the final chord (with bass)
        // Db => C#, Eb => D# etc.
        //$finalChord = strtr($chordRootNote.$translatedChordFormula.$translatedBassNote, $this->rootNotes);
        // this is the final chord (without bass)
        return strtr($chordRootNote . $translatedChordFormula, $this->chord->rootNotes); // Db => C#, Eb => D# 
    }

}

?>