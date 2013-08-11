<?php
namespace Apimenti\Translator\Domain\Model;

/* *
 * This script belongs to the FLOW3 package "Apimenti.Account".           *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Chord, a stupid (but simple) implementation
 *
 * @author Thiago Colares <thiago@apimenti.com.br>
 */
class Chord_Original {

    /**
     * Root Notes
     * @var array
     */
    public $rootNotes =  array(
		"A" => "A",
		"B" => "B",
		"C" => "C",
		"D" => "D",
		"E" => "E",
		"F" => "F",
		"G" => "G",

		"A#" => "A_srp_",
		"B#" => "C",
		"C#" => "C_srp_",
		"D#" => "D_srp_",
		"E#" => "F",
		"F#" => "F_srp_",
		"G#" => "G_srp_",

		"Db" => "C_srp_", 
		"Eb" => "D_srp_", 
		"Gb" => "F_srp_", 
		"Ab" => "G_srp_", 
		"Bb" => "A_srp_",
	);

    /**
     * Commons known notations (as far as I know)
     * This is like a hand-made dictionary
     * 
     * @var array
     */
	public $notations = array(
		"m" => "m", "minor triad" => "m", "min" => "m", "mi" => "m", "-" => "m",

	    "aug" => "aug","Aug" => "aug", "augmented triad"  => "aug", "#5" => "aug", "+5" => "aug",

		"dim" => "dim","Dim" => "dim", "diminished triad" => "dim", "b5" => "dim", "-5" => "dim", "º" => "dim","°" => "dim",

		"5" => "5", "power chord" => "5","no 3rd", "sus5" => "5","Sus5" => "5",

		"-5" => "_mns_5", "(-5)" => "_mns_5", "b5" => "_mns_5","dim5" => "_mns_5","maj-5" => "_mns_5","majb5" => "_mns_5","majdim5" => "_mns_5","º5" => "_mns_5",

		"°5" => "_mns_5",

		"+5" => "_pls_5","#5" => "_pls_5",// "+" => "+5",?
			"maj#5" => "_pls_5","maj(add5)" => "_pls_5","maj+5" => "_pls_5","majadd5" => "_pls_5",
			"Maj#5" => "_pls_5","Maj(Add5)" => "_pls_5","Maj(add5)" => "_pls_5","maj(Add5)" => "_pls_5","Maj+5" => "_pls_5",
			"MajAdd5" => "_pls_5","majAdd5" => "_pls_5","Majadd5" => "_pls_5",

		"add4" => "add4","Add4" => "add4",
			"(add11)" => "add4","(add4)" => "add4","4th" => "add4","add11" => "add4",		

		"7sus4" => "7sus4", "7(sus4)" => "7sus4","7(4)" => "7sus4",
			"7Sus4" => "7sus4", "7(Sus4)" => "7Sus4","7(4)" => "7sus4",
			"sus4_7" => "7sus4", "7_4" => "7sus4","7_4" => "7sus4",
		"7add4" => "7add4", "7_11" => "7add4", "7_4" => "7add4", "7_add11" => "7add4", "7+4" => "7add4", "7add11" => "7add4", 
			"7_Add11" => "7add4", "7Add11" => "7add4", 	

		"sus4" => "sus4", "sus" => "sus4", "4" => "sus4", "5sus4" => "sus4",
			"Sus4" => "Sus4", "Sus" => "Sus4", "4" => "Sus4",

		"sus2" => "sus2", "2" => "sus2",
			"Sus2" => "sus2", "2" => "Sus2",

		"6" => "6", "major 6" => "6", "maj6" => "6", "ma6" => "6",	
			"add6" => "6","(add6)" => "6","Add6" => "6","(Add6)" => "6",
			"Maj6" => "6", "Ma6" => "6",

		"6sus4" => "6sus4", "6sus" => "6sus4",
			"6Sus4" => "6sus4", "6Sus" => "6sus4",

		"m6" => "m6", "minor 6" => "m6", "min6" => "m6", "mi6" => "m6",
			"-6" => "m6", "m(add6)" => "m6",

		"69" => "69", "6_9" => "69", "6(9)" => "69", "major 6_9" => "69", "add6_9" => "69",
			"6(add2)" => "69", "6(add9)" => "69", "6add2" => "69", "6add9" => "69", "maj6(add9)" => "69",	

		"m69" => "m69", "-6_9" => "m69", "m(add6,9)" => "m69", "m6_9" => "m69", "min6_9" => "m69", 	

		"maj7" => "maj7","Maj7" => "maj7", "major 7" => "maj7", "ma7" => "maj7", "M7" => "maj7", "7M" => "maj7", "7+" => "maj7",
			"Ma7" => "maj7", "major7" => "maj7",		

		"mMaj7" => "mMaj7","-(maj7)" => "mMaj7","m(maj7)" => "mMaj7","m_maj7" => "mMaj7","m+7" => "mMaj7",
			"M7b3" => "mMaj7","ma7b3" => "mMaj7","min(maj7)" => "mMaj7",

		"maj7b5" => "maj7b5", "maj7#11" => "maj7b5", "maj7(-5)" => "maj7b5", "maj7(add#11)" => "maj7b5", "maj7(b5)" => "maj7b5", "maj7-5" => "maj7b5", 

		"m7(11)" => "m7_opr_11_cpr_", "m11(no 9th)" => "m7_opr_11_cpr_",  "m7(add11)" => "m7_opr_11_cpr_", "m7(add4)" => "m7_opr_11_cpr_", "m7_4" => "m7_opr_11_cpr_", 
			"m7add11" => "m7_opr_11_cpr_", "m7add4" => "m7_opr_11_cpr_", "m7sus" => "m7_opr_11_cpr_", "m7sus4" =>"m7_opr_11_cpr_",
			"m7_11" => "m7_opr_11_cpr_", "m11" => "m7_opr_11_cpr_",

		"7" => "7",
		"m7" => "m7", "minor 7" => "m7", "min7" => "m7", "mi7" => "m7", "-7" => "m7",
		 "Min7" => "m7", "Mi7" => "m7",
		"m7M" => "m7M", "m7+" => "m7M",
		"9" => "9", "7_9" => "9", "7(9)" => "9",

		"7" => "7",
		"m7" => "m7",

		"m7M" => "m7M", "m7+" => "m7M","m(7M)" => "m7M",

		"7(9)" => "9", "7_9" => "9", //"7(9)" => "7(9)", "7/9" => "7(9)",	

		"7(#9)" => "7_opr__spr_9_cpr_", "7_9#" => "7_opr__spr_9_cpr_", "7(9+)" => "7_opr__spr_9_cpr_",

		"7(b9)" => "7_opr_b9_cpr_", "7_9-" => "7_opr_b9_cpr_", "7(-9)" => "7_opr_b9_cpr_",
			"7b9" => "7_opr_b9_cpr_", "7(addb9)" => "7_opr_b9_cpr_",  "7-9" => "7_opr_b9_cpr_",  "7dim9" => "7_opr_b9_cpr_",	

		"7(b5)" => "7_opr_b5_cpr_", "7_5b" => "7_opr_b5_cpr_", "7_-5" => "7_opr_b5_cpr_", "7(-5)" => "7_opr_b5_cpr_",

		"m7(b5)" => "m7_opr_b5_cpr_", "m7_5-" => "m7_opr_b5_cpr_", "min7(b5)" => "m7_opr_b5_cpr_", "m7(-5)" => "m7_opr_b5_cpr_", "-7(b5)" => "m7_opr_b5_cpr_", "Min7(b5)" => "m7_opr_b5_cpr_",

		"dim7" => "dim7", "º7" => "dim7", "°7" => "dim7", "Dim7" => "dim7", "07" => "dim7", "º(7)" => "dim7","°(7)" => "dim7","o7" => "dim7", 
		"m7b5" => "m7b5", "-7b5" => "m7b5", "m7(-5)" => "m7b5", "m7(b5)" => "m7b5", "m7-5" => "m7b5", "m7dim5" => "m7b5", 
			"m7o5" => "m7b5", "ø" => "m7b5", "ø7" => "m7b5",

		"7sus2" => "7sus2","7sus9" => "7sus2",	


		"7(+5)" => "7_opr__pls_5_cpr_", "7(#5)" => "7_opr__pls_5_cpr_", "7_5+" => "7_opr__pls_5_cpr_",
			"7#5" => "7_opr__pls_5_cpr_", "7+5" => "7_opr__pls_5_cpr_", "7aug5" => "7_opr__pls_5_cpr_", "aug7" => "7_opr__pls_5_cpr_", 
			"7Aug5" => "7_opr__pls_5_cpr_", "Aug7" => "7_opr__pls_5_cpr_",

	    "7(b9)" => "7_opr_b9_cpr_", "7(-9)" => "7_opr_b9_cpr_",

		"7(+9)" => "7_opr__pls_9_cpr_", "7(#9)" => "7_opr__pls_9_cpr_", "7#9" => "7_opr__pls_9_cpr_", "7_9#" => "7_opr__pls_9_cpr_",
			"7(b10)" => "7_opr__pls_9_cpr_", "7+9" => "7_opr__pls_9_cpr_", "7aug9" => "7_opr__pls_9_cpr_",


		//"7M/5-" => "7M/5-", "7M/5-" => "7M(#11)",
	    "add9" => "add9", "(add9)" => "add9", "add2" => "add9","Add9" => "add9", "Add2" => "add9", "M_9" => "add9",

		"madd9" => "madd9", "m add9" => "madd9", "m add2" => "madd9", "madd2" => "madd9",

	    "maj9" => "maj9","Maj9" => "maj9", "ma9" => "maj9", "M9" => "maj9",
			"7M(9)" => "maj9", "ma7(add9)" => "maj9", "ma7add9" => "maj9", "maj7(9)" => "maj9",
			"maj7(add9)" => "maj9", "maj7add9" => "maj9", 

		"mMaj9" => "mMaj9","-(maj9)" => "mMaj9","-maj9" => "mMaj9", "m+9" => "mMaj9","min(maj)9" => "mMaj9",
			"-(Maj9)" => "mMaj9","-Maj9" => "mMaj9", "m+9" => "mMaj9","min(Maj)9" => "mMaj9",

		"9" => "9",

		"9sus" => "9sus",  "9 sus4" => "9sus", "9sus4" => "9sus", "sus9" => "9sus", "sus2" => "9sus",

		"9Sus" => "9sus",  "9 Sus4" => "9sus", "9Sus4" => "9sus", "Sus9" => "9sus", "Sus2" => "9sus",

		// is 9sus = sus9?
		"9(+11)" => "9_opr__pls_11_cpr_", "9(#11)" => "9_opr__pls_11_cpr_",

		"m9" => "m9", "min9" => "m9", "mi9" => "m9", "-9" => "m9", "m7(9)" => "m9","m7_9" => "m9",

		"11" => "11", "9sus4" => "11",

		"(#11)" => "_opr__srp_11_cpr_", "(add#11)" => "_opr__srp_11_cpr_", "add#11" => "_opr__spr_11_cpr_",
			"(Add#11)" => "_opr__spr_11_cpr_", "Add#11" => "_opr__spr_11_cpr_",

		"m(add11)" => "m_opr_add11_cpr_","m(add4)" => "m_opr_add11_cpr_","madd11" => "m_opr_add11_cpr_","madd4" => "m_opr_add11_cpr_","msus4" => "m_opr_add11_cpr_",
			"m(Add11)" => "m_opr_add11_cpr_","m(Add4)" => "m_opr_add11_cpr_","mAdd11" => "m_opr_add11_cpr_","mAdd4" => "m_opr_add11_cpr_","mSus4" => "m_opr_add11_cpr_",

		"maj11" => "maj11", "M11" => "maj11", "Maj11" => "maj11",

	    "maj13" => "maj13","Maj13" => "maj13", "ma13"  => "maj13",  "Ma13"  => "maj13", "M13"  => "maj13",

	    "13" => "13","7(13)" => "13",

		"7(b13)" => "7_opr_b13_cpr_",

		"13sus4" => "13sus4", "67 sus4" => "13sus4", "67sus4" => "13sus4",  "6_7sus4" => "13sus4",
			"13Sus4" => "13sus4", "67 Sus4" => "13sus4", "67Sus4" => "13sus4",  "6_7Sus4" => "13sus4",

	    "m13" => "m13", "min13" => "m13", "mi13" => "m13", "m13" => "m13", "-13" => "m13",
			"-7(add13)" => "m13", "-7add13" => "m13", "m7(add13)" => "m13", "m7add13" => "m13" //, "min7" => "m13"??
		);

		/*
		"dim7" "diminished 7, dim7, (circle) 7" [1 b3 b5 6 11]

		"5" => "5",	
		//"dim" => "dim", "dim" => "º(triade)", "dim" => "º",

		"m6" => "m6",

		"13" => "13", "13" => "7(13)",
		"Add9" => "Add9",
		"mAdd9" => "mAdd9", "mAdd9" => "m9",

		"dim" => "dim","º" => "dim",

		*/
/*
	    "." "major triad, no symbol (just a root note)" [1 3 5 11 55 111]
	    "m" "minor triad, min, mi, m, -" [1 b3 5 11 55 111]
	    "aug" "augmented triad, aug, #5, +5" [1 3 b6 11 111]
	    "dim" "diminished triad, dim, b5, -5" [1 b3 b5 11]	
	    "5" "power chord, 5" [1 55]	
	    "sus4" "sus4, sus" [1 4 5 11 55 111]
	    "sus2" "sus2, 2" [1 99 5 11]
	    "6" "major 6, maj6, ma6, 6" [1 3 5 6 11]	
	    "m6" "minor 6, min6, mi6, m6" [1 b3 5 6 11]	
	    "69" "major 6/9, 6/9, add6/9" [1 111 3 13 9]	
	    "maj7" "major 7, maj7, ma7, M7, (triangle) 7" [1 3 5 7 11 55]	
	    "7" "dominant 7, 7" [1 3 5 b7 11 55]
	    "m7" "minor 7, min7, mi7, m7, -7" [1 b3 5 b7 11 55]	
	    "m7(b5)" "half diminished, min7(b5), (circle w/ line), m7(-5), -7(b5)"
	        [1 b3 b5 b7 11]		
	    "dim7" "diminished 7, dim7, (circle) 7" [1 b3 b5 6 11]	
	    "7sus4" "dominant 7 sus4 (7sus4)" [1 4 5 b7 55 11]
	    "7sus2" "dominant 7 sus2 (7sus2)" [1 b7 99 5 11]	
	    "7(b5)" "dominant 7 flat 5, 7(b5), 7(-5)" [1 3 b5 b7 11]
	    "7(+5)" "augmented 7, 7(#5), 7(+5)" [1 3 b6 b7 11]
	    "7(b9)" "dominant 7 flat 9, 7(b9), 7(-9)" [1 3 5 b7 b9]
	    "7(+9)" "dominant 7 sharp 9, 7(#9), 7(+9), 7#9" [1 111 3 b77 b33]
	    "7(b5b9)" "dominant 7 b5 b9, 7(b5b9), 7(-5-9)" [1 3 b5 b7 b9]
	    "7(b5+9)" "dominant 7 b5 #9, 7(b5#9), 7(-5+9)" [1 3 b5 b7 b33]
	    "7(+5b9)" "augmented 7 flat 9, aug7(b9), 7(#5b9)" [1 3 b6 b7 b9]
	    "7(+5+9)" "augmented 7 sharp 9, aug7(#9), 7(#5#9)" [1 3 b6 b7 b11]
	    "add9" "add9, add2" [1 3 5 999 55 11]
	    "madd9" "minor add9, min add9, m add9, m add2" [1 b3 5 999 55 11]
	    "maj9" "major 9, maj9, ma9, M9, (triangle) 9" [1 3 5 7 9]
	    "maj9(+11)" "major 9 sharp 11, maj9(#11), M9(+11)" [1 3 7 9 b5]	
	    "9" "dominant 9, 9" [1 3 5 b7 9 55]
	    "9sus" "dominant 9 sus4, 9sus4, 9sus" [1 4 5 b7 9 55]
	    "9(+11)" "dominant 9 sharp 11, 9(#11), 9(+11)" [1 3 b7 9 b5]
	    "m9" "minor 9, min9, mi9, m9, -9" [1 b3 5 b7 9 55]
	    "11" "dominant 11, 11" [1 b7 99 44 11]
	    "maj13" "major 13, maj13, ma13, M13, (triangle) 13" [1 3 55 7 11 13]
	    "13" "dominant 13, 13" [1 3 55 b7 11 13]
	    "m13" "minor 13, min13, mi13, m13, -13" [1 b3 55 b7 11 13]
		*/

	/**
	* @var Array with all available chords and number of its variations
*/
   /**
    * Auto-generated array
    * 
    * @var array
    */
	public $allChords = array(
		"C" => 12,
		"Cm" => 6,
		"Caug" => 6,
		"Cdim" => 4,
		"C5" => 4,
		"C_mns_5" => 2,
		"C_pls_5" => 6,
		"Cadd4" => 1,
		"Csus4" => 4,
		"Csus2" => 7,
		"C6" => 8,
		"C6sus4" => 4,
		"Cm6" => 6,
		"C69" => 4,
		"Cm69" => 3,
		"Cmaj7" => 3,
		"Cmaj7b5" => 4,
		"CmMaj7" => 3,
		"C7" => 3,
		"Cm7" => 2,
		"Cm7M" => 3,
		"Cm7_opr_b5_cpr_" => 2,
		"Cm7_opr__pls_5_cpr_" => 2,
		"Cm7_opr_11_cpr_" => 2,
		"Cdim7" => 4,
		"C7sus4" => 3,
		"C7_opr_b5_cpr_" => 2,
		"C7_opr__pls_5_cpr_" => 3,
		"C7_opr_b9_cpr_" => 3,
		"C7_opr__pls_9_cpr_" => 2,
		"Cm7b5" => 2,
		"Cadd9" => 2,
		"Cmadd9" => 3,
		"Cmaj9" => 3,
		"CmMaj9" => 2,
		"C9" => 2,
		"C7_9" => 2,
		"C9sus" => 3,
		"C9_opr__pls_11_cpr_" => 3,
		"Cm9" => 3,
		"C11" => 3,
		"C_opr__srp_11_cpr_" => 12,
		"Cm_opr__srp_11_cpr_" => 6,
		"Cm_opr_add11_cpr_" => 1,
		"Cmaj11" => 4,
		"Cmaj13" => 5,
		"C13" => 3,
		"C7_opr_b13_cpr_" => 3,
		"C13sus4" => 4,
		"Cm13" => 3,
		"C_srp_" => 3,
		"C_srp_m" => 3,
		"C_srp_aug" => 4,
		"C_srp_dim" => 3,
		"C_srp_5" => 1,
		"C_srp__mns_5" => 3,
		"C_srp__pls_5" => 4,
		"C_srp_add4" => 1,
		"C_srp_sus4" => 1,
		"C_srp_sus2" => 1,
		"C_srp_6" => 2,
		"C_srp_6sus4" => 1,
		"C_srp_m6" => 2,
		"C_srp_69" => 1,
		"C_srp_m69" => 1,
		"C_srp_maj7" => 3,
		"C_srp_maj7b5" => 3,
		"C_srp_mMaj7" => 3,
		"C_srp_7" => 2,
		"C_srp_m7" => 2,
		"C_srp_m7M" => 3,
		"C_srp_m7_opr_b5_cpr_" => 2,
		"C_srp_m7_opr__pls_5_cpr_" => 3,
		"C_srp_m7_opr_11_cpr_" => 1,
		"C_srp_dim7" => 2,
		"C_srp_7sus4" => 1,
		"C_srp_7_opr_b5_cpr_" => 2,
		"C_srp_7_opr__pls_5_cpr_" => 3,
		"C_srp_7_opr_b9_cpr_" => 2,
		"C_srp_7_opr__pls_9_cpr_" => 2,
		"C_srp_m7b5" => 2,
		"C_srp_add9" => 1,
		"C_srp_madd9" => 1,
		"C_srp_maj9" => 1,
		"C_srp_mMaj9" => 2,
		"C_srp_9" => 1,
		"C_srp_7_9" => 1,
		"C_srp_9sus" => 1,
		"C_srp_9_opr__pls_11_cpr_" => 1,
		"C_srp_m9" => 1,
		"C_srp_11" => 1,
		"C_srp__opr__srp_11_cpr_" => 3,
		"C_srp_m_opr__srp_11_cpr_" => 3,
		"C_srp_m_opr_add11_cpr_" => 1,
		"C_srp_maj11" => 3,
		"C_srp_maj13" => 3,
		"C_srp_13" => 2,
		"C_srp_7_opr_b13_cpr_" => 3,
		"C_srp_13sus4" => 2,
		"C_srp_m13" => 2,
		"D" => 4,
		"Dm" => 4,
		"Daug" => 2,
		"Ddim" => 3,
		"D5" => 2,
		"D_mns_5" => 1,
		"D_pls_5" => 2,
		"Dadd4" => 3,
		"Dsus4" => 5,
		"Dsus2" => 4,
		"D6" => 3,
		"D6sus4" => 3,
		"Dm6" => 3,
		"D69" => 2,
		"Dm69" => 2,
		"Dmaj7" => 2,
		"Dmaj7b5" => 2,
		"DmMaj7" => 2,
		"D7" => 4,
		"Dm7" => 4,
		"Dm7M" => 2,
		"Dm7_opr_b5_cpr_" => 3,
		"Dm7_opr__pls_5_cpr_" => 3,
		"Dm7_opr_11_cpr_" => 3,
		"Ddim7" => 2,
		"D7sus4" => 6,
		"D7sus2" => 4,
		"D7_opr_b5_cpr_" => 3,
		"D7_opr__pls_5_cpr_" => 3,
		"D7_opr_b9_cpr_" => 2,
		"D7_opr__pls_9_cpr_" => 1,
		"Dm7b5" => 3,
		"Dadd9" => 2,
		"Dmadd9" => 2,
		"DmMaj9" => 4,
		"D9" => 2,
		"D7_9" => 2,
		"D9sus" => 2,
		"Dm9" => 2,
		"D11" => 6,
		"D_opr__srp_11_cpr_" => 4,
		"Dm_opr__srp_11_cpr_" => 4,
		"Dm_opr_add11_cpr_" => 3,
		"Dmaj11" => 2,
		"Dmaj13" => 2,
		"D13" => 4,
		"D7_opr_b13_cpr_" => 3,
		"D13sus4" => 3,
		"Dm13" => 3,
		"D_srp_" => 2,
		"D_srp_m" => 1,
		"D_srp_aug" => 3,
		"D_srp_dim" => 4,
		"D_srp__mns_5" => 6,
		"D_srp__pls_5" => 3,
		"D_srp_add4" => 2,
		"D_srp_sus4" => 2,
		"D_srp_sus2" => 1,
		"D_srp_6" => 2,
		"D_srp_6sus4" => 2,
		"D_srp_m6" => 2,
		"D_srp_69" => 1,
		"D_srp_maj7" => 1,
		"D_srp_maj7b5" => 4,
		"D_srp_mMaj7" => 1,
		"D_srp_7" => 1,
		"D_srp_m7" => 1,
		"D_srp_m7M" => 1,
		"D_srp_m7_opr_b5_cpr_" => 1,
		"D_srp_m7_opr__pls_5_cpr_" => 1,
		"D_srp_m7_opr_11_cpr_" => 1,
		"D_srp_dim7" => 4,
		"D_srp_7sus4" => 1,
		"D_srp_7_opr_b5_cpr_" => 2,
		"D_srp_7_opr__pls_5_cpr_" => 1,
		"D_srp_7_opr_b9_cpr_" => 1,
		"D_srp_7_opr__pls_9_cpr_" => 1,
		"D_srp_m7b5" => 1,
		"D_srp_add9" => 1,
		"D_srp_maj9" => 1,
		"D_srp_mMaj9" => 1,
		"D_srp_9" => 1,
		"D_srp_7_9" => 1,
		"D_srp_9sus" => 1,
		"D_srp_9_opr__pls_11_cpr_" => 1,
		"D_srp_11" => 1,
		"D_srp__opr__srp_11_cpr_" => 2,
		"D_srp_m_opr__srp_11_cpr_" => 1,
		"D_srp_m_opr_add11_cpr_" => 1,
		"D_srp_maj11" => 4,
		"D_srp_maj13" => 3,
		"D_srp_7_opr_b13_cpr_" => 1,
		"D_srp_13sus4" => 1,
		"D_srp_m13" => 1,
		"E" => 3,
		"Em" => 3,
		"Eaug" => 6,
		"Edim" => 3,
		"E5" => 1,
		"E_mns_5" => 3,
		"E_pls_5" => 6,
		"Eadd4" => 3,
		"Esus4" => 5,
		"Esus2" => 2,
		"E6" => 2,
		"E6sus4" => 3,
		"Em6" => 2,
		"E69" => 1,
		"Em69" => 1,
		"Emaj7" => 2,
		"Emaj7b5" => 1,
		"EmMaj7" => 2,
		"E7" => 2,
		"Em7" => 2,
		"Em7M" => 2,
		"Em7_opr_b5_cpr_" => 2,
		"Em7_opr__pls_5_cpr_" => 2,
		"Em7_opr_11_cpr_" => 3,
		"Edim7" => 2,
		"E7sus4" => 3,
		"E7_opr_b5_cpr_" => 2,
		"E7_opr__pls_5_cpr_" => 3,
		"E7_opr_b9_cpr_" => 2,
		"E7_opr__pls_9_cpr_" => 2,
		"Em7b5" => 2,
		"Eadd9" => 1,
		"Emadd9" => 2,
		"EmMaj9" => 2,
		"E9" => 1,
		"E7_9" => 1,
		"E9sus" => 2,
		"E9_opr__pls_11_cpr_" => 1,
		"Em9" => 2,
		"E11" => 3,
		"E_opr__srp_11_cpr_" => 3,
		"Em_opr__srp_11_cpr_" => 3,
		"Em_opr_add11_cpr_" => 3,
		"Emaj11" => 1,
		"Emaj13" => 1,
		"E13" => 2,
		"E7_opr_b13_cpr_" => 3,
		"E13sus4" => 2,
		"Em13" => 2,
		"F" => 8,
		"Fm" => 4,
		"Faug" => 4,
		"Fdim" => 3,
		"F5" => 2,
		"F_mns_5" => 5,
		"F_pls_5" => 4,
		"Fadd4" => 4,
		"Fsus4" => 5,
		"Fsus2" => 4,
		"F6" => 4,
		"F6sus4" => 3,
		"Fm6" => 3,
		"F69" => 3,
		"Fm69" => 2,
		"Fmaj7" => 4,
		"Fmaj7b5" => 4,
		"FmMaj7" => 2,
		"F7" => 2,
		"Fm7" => 1,
		"Fm7M" => 2,
		"Fm7_opr_b5_cpr_" => 1,
		"Fm7_opr__pls_5_cpr_" => 1,
		"Fm7_opr_11_cpr_" => 1,
		"Fdim7" => 2,
		"F7sus4" => 2,
		"F7_opr_b5_cpr_" => 2,
		"F7_opr__pls_5_cpr_" => 1,
		"F7_opr__pls_9_cpr_" => 1,
		"Fm7b5" => 1,
		"Fadd9" => 4,
		"Fmadd9" => 2,
		"Fmaj9" => 2,
		"FmMaj9" => 1,
		"F9" => 4,
		"F7_9" => 4,
		"F9sus" => 1,
		"F9_opr__pls_11_cpr_" => 1,
		"Fm9" => 2,
		"F11" => 2,
		"F_opr__srp_11_cpr_" => 8,
		"Fm_opr__srp_11_cpr_" => 4,
		"Fm_opr_add11_cpr_" => 3,
		"Fmaj11" => 4,
		"Fmaj13" => 2,
		"F13" => 1,
		"F7_opr_b13_cpr_" => 1,
		"F13sus4" => 1,
		"Fm13" => 1,
		"F_srp_" => 2,
		"F_srp_m" => 2,
		"F_srp_aug" => 2,
		"F_srp_dim" => 8,
		"F_srp__mns_5" => 3,
		"F_srp__pls_5" => 2,
		"F_srp_add4" => 2,
		"F_srp_sus4" => 2,
		"F_srp_sus2" => 1,
		"F_srp_6" => 1,
		"F_srp_6sus4" => 1,
		"F_srp_m6" => 1,
		"F_srp_69" => 1,
		"F_srp_m69" => 2,
		"F_srp_maj7" => 1,
		"F_srp_maj7b5" => 2,
		"F_srp_mMaj7" => 1,
		"F_srp_7" => 1,
		"F_srp_m7" => 1,
		"F_srp_m7M" => 1,
		"F_srp_m7_opr_b5_cpr_" => 4,
		"F_srp_m7_opr__pls_5_cpr_" => 2,
		"F_srp_m7_opr_11_cpr_" => 4,
		"F_srp_dim7" => 4,
		"F_srp_7sus4" => 1,
		"F_srp_7_opr_b5_cpr_" => 2,
		"F_srp_7_opr__pls_5_cpr_" => 1,
		"F_srp_7_opr_b9_cpr_" => 1,
		"F_srp_7_opr__pls_9_cpr_" => 2,
		"F_srp_m7b5" => 4,
		"F_srp_add9" => 1,
		"F_srp_madd9" => 1,
		"F_srp_maj9" => 1,
		"F_srp_mMaj9" => 1,
		"F_srp_9" => 1,
		"F_srp_7_9" => 1,
		"F_srp_9sus" => 1,
		"F_srp_9_opr__pls_11_cpr_" => 1,
		"F_srp_m9" => 1,
		"F_srp_11" => 1,
		"F_srp__opr__srp_11_cpr_" => 2,
		"F_srp_m_opr__srp_11_cpr_" => 2,
		"F_srp_m_opr_add11_cpr_" => 2,
		"F_srp_maj11" => 2,
		"F_srp_7_opr_b13_cpr_" => 1,
		"F_srp_13sus4" => 2,
		"F_srp_m13" => 2,
		"G" => 3,
		"Gm" => 3,
		"Gaug" => 3,
		"Gdim" => 3,
		"G5" => 1,
		"G_mns_5" => 3,
		"G_pls_5" => 3,
		"Gadd4" => 3,
		"Gsus4" => 7,
		"Gsus2" => 5,
		"G6" => 2,
		"G6sus4" => 2,
		"Gm6" => 2,
		"G69" => 3,
		"Gm69" => 3,
		"Gmaj7" => 2,
		"Gmaj7b5" => 1,
		"GmMaj7" => 1,
		"G7" => 2,
		"Gm7" => 2,
		"Gm7M" => 1,
		"Gm7_opr_b5_cpr_" => 2,
		"Gm7_opr__pls_5_cpr_" => 1,
		"Gm7_opr_11_cpr_" => 3,
		"Gdim7" => 2,
		"G7sus4" => 3,
		"G7sus2" => 4,
		"G7_opr_b5_cpr_" => 2,
		"G7_opr__pls_5_cpr_" => 1,
		"G7_opr_b9_cpr_" => 2,
		"G7_opr__pls_9_cpr_" => 2,
		"Gm7b5" => 2,
		"Gadd9" => 3,
		"Gmadd9" => 3,
		"Gmaj9" => 3,
		"GmMaj9" => 2,
		"G9" => 3,
		"G7_9" => 3,
		"G9sus" => 4,
		"G9_opr__pls_11_cpr_" => 3,
		"Gm9" => 3,
		"G11" => 3,
		"G_opr__srp_11_cpr_" => 3,
		"Gm_opr__srp_11_cpr_" => 3,
		"Gm_opr_add11_cpr_" => 3,
		"Gmaj11" => 1,
		"Gmaj13" => 2,
		"G13" => 2,
		"G7_opr_b13_cpr_" => 1,
		"G13sus4" => 1,
		"Gm13" => 2,
		"G_srp_" => 4,
		"G_srp_m" => 3,
		"G_srp_aug" => 6,
		"G_srp_dim" => 3,
		"G_srp__mns_5" => 7,
		"G_srp__pls_5" => 6,
		"G_srp_add4" => 1,
		"G_srp_sus4" => 1,
		"G_srp_sus2" => 2,
		"G_srp_6" => 1,
		"G_srp_6sus4" => 1,
		"G_srp_m6" => 1,
		"G_srp_69" => 3,
		"G_srp_m69" => 2,
		"G_srp_maj7" => 4,
		"G_srp_maj7b5" => 4,
		"G_srp_mMaj7" => 2,
		"G_srp_7" => 2,
		"G_srp_m7" => 2,
		"G_srp_m7M" => 2,
		"G_srp_m7_opr_b5_cpr_" => 2,
		"G_srp_m7_opr__pls_5_cpr_" => 1,
		"G_srp_m7_opr_11_cpr_" => 1,
		"G_srp_dim7" => 2,
		"G_srp_7sus4" => 1,
		"G_srp_7_opr_b5_cpr_" => 3,
		"G_srp_7_opr__pls_5_cpr_" => 1,
		"G_srp_7_opr_b9_cpr_" => 3,
		"G_srp_7_opr__pls_9_cpr_" => 3,
		"G_srp_m7b5" => 2,
		"G_srp_add9" => 2,
		"G_srp_madd9" => 2,
		"G_srp_maj9" => 2,
		"G_srp_mMaj9" => 2,
		"G_srp_9" => 2,
		"G_srp_7_9" => 2,
		"G_srp_9sus" => 1,
		"G_srp_9_opr__pls_11_cpr_" => 1,
		"G_srp_m9" => 2,
		"G_srp_11" => 1,
		"G_srp__opr__srp_11_cpr_" => 4,
		"G_srp_m_opr__srp_11_cpr_" => 3,
		"G_srp_m_opr_add11_cpr_" => 1,
		"G_srp_maj11" => 4,
		"G_srp_maj13" => 2,
		"G_srp_7_opr_b13_cpr_" => 1,
		"G_srp_13sus4" => 1,
		"G_srp_m13" => 1,
		"A" => 4,
		"Am" => 13,
		"Aaug" => 4,
		"Adim" => 8,
		"A5" => 2,
		"A_mns_5" => 1,
		"A_pls_5" => 4,
		"Aadd4" => 2,
		"Asus4" => 4,
		"Asus2" => 5,
		"A6" => 1,
		"A6sus4" => 2,
		"Am6" => 4,
		"A69" => 2,
		"Am69" => 4,
		"Amaj7" => 3,
		"Amaj7b5" => 2,
		"AmMaj7" => 4,
		"A7" => 3,
		"Am7" => 8,
		"Am7M" => 4,
		"Am7_opr_b5_cpr_" => 6,
		"Am7_opr__pls_5_cpr_" => 4,
		"Am7_opr_11_cpr_" => 6,
		"Adim7" => 4,
		"A7sus4" => 3,
		"A7sus2" => 4,
		"A7_opr_b5_cpr_" => 2,
		"A7_opr__pls_5_cpr_" => 3,
		"A7_opr_b9_cpr_" => 3,
		"A7_opr__pls_9_cpr_" => 6,
		"Am7b5" => 6,
		"Aadd9" => 3,
		"Amadd9" => 5,
		"Amaj9" => 3,
		"AmMaj9" => 8,
		"A9" => 3,
		"A7_9" => 3,
		"A9sus" => 3,
		"A9_opr__pls_11_cpr_" => 3,
		"Am9" => 5,
		"A11" => 3,
		"A_opr__srp_11_cpr_" => 4,
		"Am_opr__srp_11_cpr_" => 13,
		"Am_opr_add11_cpr_" => 4,
		"Amaj11" => 2,
		"Amaj13" => 1,
		"A13" => 1,
		"A7_opr_b13_cpr_" => 3,
		"A13sus4" => 3,
		"Am13" => 4,
		"A_srp_" => 3,
		"A_srp_m" => 3,
		"A_srp_aug" => 2,
		"A_srp_dim" => 3,
		"A_srp_5" => 1,
		"A_srp__mns_5" => 3,
		"A_srp__pls_5" => 2,
		"A_srp_add4" => 1,
		"A_srp_sus4" => 1,
		"A_srp_sus2" => 5,
		"A_srp_6" => 2,
		"A_srp_6sus4" => 1,
		"A_srp_m6" => 2,
		"A_srp_69" => 3,
		"A_srp_m69" => 3,
		"A_srp_maj7" => 3,
		"A_srp_maj7b5" => 3,
		"A_srp_mMaj7" => 3,
		"A_srp_7" => 2,
		"A_srp_m7" => 2,
		"A_srp_m7M" => 3,
		"A_srp_m7_opr_b5_cpr_" => 2,
		"A_srp_m7_opr__pls_5_cpr_" => 1,
		"A_srp_m7_opr_11_cpr_" => 1,
		"A_srp_dim7" => 2,
		"A_srp_7sus4" => 1,
		"A_srp_7sus2" => 4,
		"A_srp_7_opr_b5_cpr_" => 2,
		"A_srp_7_opr__pls_5_cpr_" => 1,
		"A_srp_7_opr_b9_cpr_" => 2,
		"A_srp_7_opr__pls_9_cpr_" => 2,
		"A_srp_m7b5" => 2,
		"A_srp_add9" => 3,
		"A_srp_madd9" => 3,
		"A_srp_maj9" => 4,
		"A_srp_mMaj9" => 2,
		"A_srp_9" => 3,
		"A_srp_7_9" => 3,
		"A_srp_9sus" => 2,
		"A_srp_9_opr__pls_11_cpr_" => 3,
		"A_srp_m9" => 3,
		"A_srp_11" => 1,
		"A_srp__opr__srp_11_cpr_" => 3,
		"A_srp_m_opr__srp_11_cpr_" => 3,
		"A_srp_m_opr_add11_cpr_" => 1,
		"A_srp_maj11" => 3,
		"A_srp_maj13" => 3,
		"A_srp_13" => 2,
		"A_srp_7_opr_b13_cpr_" => 1,
		"A_srp_13sus4" => 2,
		"A_srp_m13" => 2,
		"B" => 3,
		"Bm" => 3,
		"Baug" => 3,
		"Bdim" => 3,
		"B5" => 1,
		"B_mns_5" => 1,
		"B_pls_5" => 3,
		"Badd4" => 2,
		"Bsus4" => 2,
		"Bsus2" => 2,
		"B6" => 2,
		"B6sus4" => 1,
		"Bm6" => 2,
		"B69" => 1,
		"Bm69" => 2,
		"Bmaj7" => 2,
		"Bmaj7b5" => 2,
		"BmMaj7" => 2,
		"B7" => 3,
		"Bm7" => 3,
		"Bm7M" => 2,
		"Bm7_opr_b5_cpr_" => 3,
		"Bm7_opr__pls_5_cpr_" => 3,
		"Bm7_opr_11_cpr_" => 3,
		"Bdim7" => 2,
		"B7sus4" => 4,
		"B7sus2" => 4,
		"B7_opr_b5_cpr_" => 2,
		"B7_opr__pls_5_cpr_" => 3,
		"B7_opr_b9_cpr_" => 4,
		"B7_opr__pls_9_cpr_" => 2,
		"Bm7b5" => 3,
		"Badd9" => 1,
		"Bmadd9" => 2,
		"BmMaj9" => 3,
		"B9" => 1,
		"B7_9" => 1,
		"B9sus" => 3,
		"B9_opr__pls_11_cpr_" => 1,
		"Bm9" => 2,
		"B11" => 4,
		"B_opr__srp_11_cpr_" => 3,
		"Bm_opr__srp_11_cpr_" => 3,
		"Bm_opr_add11_cpr_" => 2,
		"Bmaj11" => 2,
		"Bmaj13" => 2,
		"B13" => 2,
		"B7_opr_b13_cpr_" => 3,
		"B13sus4" => 3,
		"Bm13" => 3,


		"Bm_E" => 1,
		"C_B" => 1,	
		"C_E" => 1,
		"C_F" => 1,
		"C_G" => 1,
		"Dm_G" => 1,
		"Em_B" => 1,
		"Em_D" => 1,
		"Em_G" => 1,
		"F_C" => 1,
		"G_A" => 1,
		"G_B" => 1,
		"G_F" => 1,
		"G_F_srp_" => 1,
		"A_srp__G_srp_" => 1,
	);
}

namespace Apimenti\Translator\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Chord, a stupid (but simple) implementation
 */
class Chord extends Chord_Original implements \TYPO3\Flow\Object\Proxy\ProxyInterface {


	/**
	 * Autogenerated Proxy Method
	 */
	 public function __wakeup() {

	if (property_exists($this, 'Flow_Persistence_RelatedEntities') && is_array($this->Flow_Persistence_RelatedEntities)) {
		$persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		foreach ($this->Flow_Persistence_RelatedEntities as $entityInformation) {
			$entity = $persistenceManager->getObjectByIdentifier($entityInformation['identifier'], $entityInformation['entityType'], TRUE);
			if (isset($entityInformation['entityPath'])) {
				$this->$entityInformation['propertyName'] = \TYPO3\Flow\Utility\Arrays::setValueByPath($this->$entityInformation['propertyName'], $entityInformation['entityPath'], $entity);
			} else {
				$this->$entityInformation['propertyName'] = $entity;
			}
		}
		unset($this->Flow_Persistence_RelatedEntities);
	}
			}

	/**
	 * Autogenerated Proxy Method
	 */
	 public function __sleep() {
		$result = NULL;
		$this->Flow_Object_PropertiesToSerialize = array();
	$reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
	$reflectedClass = new \ReflectionClass('Apimenti\Translator\Domain\Model\Chord');
	$allReflectedProperties = $reflectedClass->getProperties();
	foreach ($allReflectedProperties as $reflectionProperty) {
		$propertyName = $reflectionProperty->name;
		if (in_array($propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if ($reflectionService->isPropertyTaggedWith('Apimenti\Translator\Domain\Model\Chord', $propertyName, 'transient')) continue;
		if (is_array($this->$propertyName) || (is_object($this->$propertyName) && ($this->$propertyName instanceof \ArrayObject || $this->$propertyName instanceof \SplObjectStorage ||$this->$propertyName instanceof \Doctrine\Common\Collections\Collection))) {
			foreach ($this->$propertyName as $key => $value) {
				$this->searchForEntitiesAndStoreIdentifierArray((string)$key, $value, $propertyName);
			}
		}
		if (is_object($this->$propertyName) && !$this->$propertyName instanceof \Doctrine\Common\Collections\Collection) {
			if ($this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
				$className = get_parent_class($this->$propertyName);
			} else {
				$className = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($this->$propertyName));
			}
			if ($this->$propertyName instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface && !\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->isNewObject($this->$propertyName) || $this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
				if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
					$this->Flow_Persistence_RelatedEntities = array();
					$this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
				}
				$identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getIdentifierByObject($this->$propertyName);
				if (!$identifier && $this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
					$identifier = current(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->$propertyName, '_identifier', TRUE));
				}
				$this->Flow_Persistence_RelatedEntities[$propertyName] = array(
					'propertyName' => $propertyName,
					'entityType' => $className,
					'identifier' => $identifier
				);
				continue;
			}
			if ($className !== FALSE && (\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getScope($className) === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_SINGLETON || $className === 'TYPO3\Flow\Object\DependencyInjection\DependencyProxy')) {
				continue;
			}
		}
		$this->Flow_Object_PropertiesToSerialize[] = $propertyName;
	}
	$result = $this->Flow_Object_PropertiesToSerialize;
		return $result;
	}

	/**
	 * Autogenerated Proxy Method
	 */
	 private function searchForEntitiesAndStoreIdentifierArray($path, $propertyValue, $originalPropertyName) {

		if (is_array($propertyValue) || (is_object($propertyValue) && ($propertyValue instanceof \ArrayObject || $propertyValue instanceof \SplObjectStorage))) {
			foreach ($propertyValue as $key => $value) {
				$this->searchForEntitiesAndStoreIdentifierArray($path . '.' . $key, $value, $originalPropertyName);
			}
		} elseif ($propertyValue instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface && !\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->isNewObject($propertyValue) || $propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
			if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
				$this->Flow_Persistence_RelatedEntities = array();
				$this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
			}
			if ($propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
				$className = get_parent_class($propertyValue);
			} else {
				$className = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($propertyValue));
			}
			$identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getIdentifierByObject($propertyValue);
			if (!$identifier && $propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
				$identifier = current(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($propertyValue, '_identifier', TRUE));
			}
			$this->Flow_Persistence_RelatedEntities[$originalPropertyName . '.' . $path] = array(
				'propertyName' => $originalPropertyName,
				'entityType' => $className,
				'identifier' => $identifier,
				'entityPath' => $path
			);
			$this->$originalPropertyName = \TYPO3\Flow\Utility\Arrays::setValueByPath($this->$originalPropertyName, $path, NULL);
		}
			}
}
#