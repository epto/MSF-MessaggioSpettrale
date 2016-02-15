#!/usr/bin/php
<?php
/*
 * text2wav
 * Copyright (C) 2015 by EPTO
 * Questo file è parte del progetto "Messaggio Spettrale Fantasma".
 * 
 * This is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This source code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this source code; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/*
 * Questo file è codificato in UTF-8 senza BOM.
 * 
 * Meglio zittire i notice, non dovrebbero esserci, ma parliamo pur sempre di PHP!
 * Visto che negli ultimi anni ne hanno inventate di nuove ad ongi versione... non si sa mai!
 */

$CP437 = array(0=>0,1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12,13=>13,14=>14,15=>15,16=>16,17=>17,18=>18,19=>19,20=>20,21=>21,22=>22,23=>23,24=>24,25=>25,26=>26,27=>27,28=>28,29=>29,30=>30,31=>31,32=>32,33=>33,34=>34,35=>35,36=>36,37=>37,38=>38,39=>39,40=>40,41=>41,42=>42,43=>43,44=>44,45=>45,46=>46,47=>47,48=>48,49=>49,50=>50,51=>51,52=>52,53=>53,54=>54,55=>55,56=>56,57=>57,58=>58,59=>59,60=>60,61=>61,62=>62,63=>63,64=>64,65=>65,66=>66,67=>67,68=>68,69=>69,70=>70,71=>71,72=>72,73=>73,74=>74,75=>75,76=>76,77=>77,78=>78,79=>79,80=>80,81=>81,82=>82,83=>83,84=>84,85=>85,86=>86,87=>87,88=>88,89=>89,90=>90,91=>91,92=>92,93=>93,94=>94,95=>95,96=>96,97=>97,98=>98,99=>99,100=>100,101=>101,102=>102,103=>103,104=>104,105=>105,106=>106,107=>107,108=>108,109=>109,110=>110,111=>111,112=>112,113=>113,114=>114,115=>115,116=>116,117=>117,118=>118,119=>119,120=>120,121=>121,122=>122,123=>123,124=>124,125=>125,126=>126,127=>127,128=>199,129=>252,130=>233,131=>226,132=>228,133=>224,134=>229,135=>231,136=>234,137=>235,138=>232,139=>239,140=>238,141=>236,142=>196,143=>197,144=>201,145=>230,146=>198,147=>244,148=>246,149=>242,150=>251,151=>249,152=>255,153=>214,154=>220,155=>162,156=>163,157=>165,158=>8359,159=>402,160=>225,161=>237,162=>243,163=>250,164=>241,165=>209,166=>170,167=>186,168=>191,169=>8976,170=>172,171=>189,172=>188,173=>161,174=>171,175=>187,176=>9617,177=>9618,178=>9619,179=>9474,180=>9508,181=>9569,182=>9570,183=>9558,184=>9557,185=>9571,186=>9553,187=>9559,188=>9565,189=>9564,190=>9563,191=>9488,192=>9492,193=>9524,194=>9516,195=>9500,196=>9472,197=>9532,198=>9566,199=>9567,200=>9562,201=>9556,202=>9577,203=>9574,204=>9568,205=>9552,206=>9580,207=>9575,208=>9576,209=>9572,210=>9573,211=>9561,212=>9560,213=>9554,214=>9555,215=>9579,216=>9578,217=>9496,218=>9484,219=>9608,220=>9604,221=>9612,222=>9616,223=>9600,224=>945,225=>223,226=>915,227=>960,228=>931,229=>963,230=>181,231=>964,232=>934,233=>920,234=>937,235=>948,236=>8734,237=>966,238=>949,239=>8745,240=>8801,241=>177,242=>8805,243=>8804,244=>8992,245=>8993,246=>247,247=>8776,248=>176,249=>8729,250=>183,251=>8730,252=>8319,253=>178,254=>9632,255=>160);
$CP437 = array_flip($CP437);
 
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_USER_WARNING &~E_NOTICE);

/*
 * Ora PHP può funzionare in modo sufficentemente antanico.
 * 
 * L'operazione di conversione è svolta in diversi passaggi:
 * 1° Caricare un font 8x8 oppure 8x16.
 * 2° Convertire la stringa in un'immagine bitmap monocromatica (userò un array).
 * 3° Creare degli oscillatori, uno per riga orizzontale, su diverse frequenze.
 *    L'asse Y è ribaltato, l'asse X dei caratteri pure (altrimenti era troppo facile no?)
 * 
 * 4° Accendere e spegnere gli oscillatori analizzando l'immagine da sinistra a destra.
 */
 
/////////////// Sezione per formato wave.

function WaveChunk($name,$data) {	// Crea un elemento RIFF.
	return str_pad($name,4,' ',STR_PAD_RIGHT).pack('V',strlen($data)).$data;	
	}

function WaveHeader(&$f,$sampleRate) { // Inizializza e finalizza un file wave.
		
	$fmt = // Format:
		pack('v',1).				//  CODEC 1 = PCM Signed
		pack('v',1).				//  Un canale solo (mono).
		pack('V',$sampleRate).		//  SampleRate
		pack('V',$sampleRate*2).	//  Byte x Sec.
		pack('v',2).				//  Block align
		pack('v',16);				//  Bit x sample.
		
	$head = WaveChunk('fmt',$fmt);
	$org = ftell($f);
	
	if ($org!=0) {	// Se il puntatore del file non è a 0, lo porto a zero e chiudo gli elementi RIFF/WAVE, e data.
		$ptr = $org - 8; 
		fseek($f,0,SEEK_SET);
		}
	
	$head = "RIFF".pack('V',$ptr).'WAVE'. $head .'data'.pack('V', $org - 44);
	/*
	 * Struttura dei fiel wave:
	 * 
	 * RIFF / WAVE {
	 * 		fmt  { formato }
	 * 		data { PCM Wave audio }
	 * }
	 * 
	 * */
	fwrite($f,$head);
		
	}

////////////// Sezione font e caratteri.

/*
 * Il tag delle informazioni sui font è un sistema prodotto nel 1992
 * per salvare informazioni sui font bitmap.
 * Parte del supporto è stato rimosso (font più o meno larghi di 8 pixel).
 * */
 
$FONTINFOSTRUCT = array(		//	Struttura costante per le informazioni sui caratteri.
//			       Nome         Len  
	1	=>	array('charset'     ,0 ) ,
	2	=>	array('height'      ,1 ) ,
	3	=>	array('max'         ,2 ) ,
	4	=>	array('info'        ,0 ) ,
	5	=>	array('ver'         ,1 ) ,
	6	=>	array('name'        ,0 ) ,
	7	=>	array('map'         ,0 ) ,
	8	=>	array('mode'        ,1 ) ,
	9	=>	array('width'		,1 ) )
	;
	
function charBmp(&$font,$ch) {	// Da carattere a relativa bitmap (Array MxN).
	$bp = ($ch % $font['max'])*$font['height'];
	$FW = $font['width'] ? $font['width'] : 8;
	if ($FW>8) $FW=8;
	
	$bmp = substr($font['font'],$bp,$font['height']);
	$map = array_pad(array(),$FW,array_pad(array(),$font['height'],0));
	for ($y = 0 ;$y<$font['height'];$y++) {
		for ($x=0;$x<$FW;$x++) {
			$bit = ord($bmp[($font['height']-1) - $y]) & 1<<(7^$x);
			$map[$x][$y] = $bit ? 1:0;
			}
		}
	return $map;
	}

function addBmp(&$org,$map,$FW) {	// Aggiunge alla bitmap finale la bitmap di un carattere.
	$cx = count($org);
	$dx=$cx+$FW;
	$ex=0;
	for ($i=$cx;$i<$dx;$i++) $org[$i]=$map[$ex++];
	}

function data2Map($raw) {	//	Esporta un array nome => valore
	$map=array();
	$j = strlen($raw);
	$i = 0;
	for ($fi=0;$fi<$j;$fi++) {
		$cx=ord($raw[$i++]);
		if ($cx==0) break;
		$t0='';
		for ($si = 0; $si<$cx; $si++) {
			$t0.=$raw[$i++];
			}
		$cx=ord($raw[$i++]);
		$t1='';
		for ($si = 0; $si<$cx; $si++) {
			$t1.=$raw[$i++];
			}
		$map[$t0]=$t1;
		}
	return $map;
	}

function getFontInfo(&$font) {	// Legge il tag delle informazioni sui caratteri.
	global $FONTINFOSTRUCT;
	$info['width'] = 8;
	$bp = strlen($font)-1;
	if ($bp<6) return false;
	$t0 = substr($font,$bp-5);
	if (strpos($t0,'INFO')===2) {
		$t0 = unpack('v',$t0);
		$t0 = $t0[1];
		$bp -=$t0;
		$bp-=4;
		if ($bp<5 or $bp>strlen($font)) return false; 
		$t0 = substr($font,$bp);
		$info=array();
		$j = strlen($t0);
		$i=0;
		for ($fi=0;$fi<$j;$fi++) {
			$ch=ord($t0[$i++]);
			if ($ch==0) break;
			if (isset($FONTINFOSTRUCT[$ch])) {
					$ji = $t0[$i++].$t0[$i++];
					$ji = unpack('v',$ji);
					$ji = $ji[1];
					
					$t1='';
					for ($ii=0;$i<$j && $ii<$ji;$ii++) {
						$t1.=$t0[$i++];
						}
					$ji = $FONTINFOSTRUCT[$ch][1];
					if ($ji>0) {
						$t1=str_pad($t1,2,chr(0),STR_PAD_RIGHT);
						$t1=unpack('v',$t1);
						$t1=$t1[1];
						}
					$info[ $FONTINFOSTRUCT[$ch][0] ] = $t1;
				} else {
					$ji = ord($t0[$i++]);
					$i+=$ji;
				}
			}
		$font = substr($font,0,$bp);
		return $info;
		} else return false;
	}

function loadFont($file) { // Carica un font
	$font = file_get_contents($file) or die("Errore nel file font.\n");
	$inf = getFontInfo($font);
	if (!is_array($inf)) $inf=array('ver' => 1);
	if (!isset($inf['max'])) $inf['max']=256;
	if (!isset($inf['charset'])) $inf['charset']='CP437';
	if (!isset($inf['height'])) $inf['height'] = strlen($font) >=3072 ? 16: 8; // 8x256 byte = font 8x8, 16x256 byte = font 8x16. Ho messo una via di mezzo perchè alcuni file hanno roba alla fine.
	if (isset($inf['map'])) $inf['map'] = data2Map($inf['map']);
	$inf['font'] = $font;
	$font=null;
	return $inf;
	}

function encode($text,$fontEnc,$enc) {
	global $CP437;
	
	if ($enc===false) {
		return mb_convert_encoding($text,'UNICODE','8bit');	
		}
		
	if ($fontEnc=='CP437') {
		$text=mb_convert_encoding($text,'UNICODE',$enc);
		$text=unpack('n*',$text);
		$out='';
		foreach($text as $ch) {
			if (isset($CP437[$ch])) $ch=$CP437[$ch];
			$out.=pack('n',$ch);
			}
		return $out;
		}

	if ($fontEnc!=$enc) $text = mb_convert_encoding($text,$fontEnc,$enc);
	return mb_convert_encoding($text,'UNICODE',$fontEnc == 'UTF-8' ? 'UTF-8' : '8bit');
	}

/////////// Sezione oscillatori.

function createFreq($f,$sampleRate) {	// Inizializza un oscillatore.
	$pi = pi() * 2;
	$st = $sampleRate / $f;
	$st = $pi / $st;
	
	return array(
		'f'		=>	$f	,		//	Frequenza
		'st'	=>	$st	,		//	Incremento in radianti.
		'sx'	=>	0	,		//	Incremento aggiuntivo.
		'al'	=>	0	)		//	Posizione dell'onda in radianti.
		;	
	}

function addFreqMode(&$freq,$amp,$f,$sampleRate) {
	$freq['amp']=$amp;
	if ($f) {
		$pi = pi() * 2;
		$st = $sampleRate / $f;
		$st = $pi / $st;
		$freq['sm'] = $st;
		$freq['sx']=mt_rand(0,$freq['sm']);
		}
	}

function oscillator(&$freq) {	// Implementa l'oscillatore.
	$pi = pi() * 2;
	$peak=intval(255*sin($freq['al']));
	$freq['al']+=$freq['st'] + $freq['sx'];
	
	if ($freq['al']>$pi) {
		$freq['al']-=$pi;
		if (@$freq['sm']) $freq['sx']=mt_rand(0,$freq['sm']);
		}
	
	if (@$freq['amp']) $peak=intval($peak * $freq['amp']);
	return $peak;	// Ritorna un valore PCM.
	}

////////// Altre funzioni.

function valu($x) { // Legge/verifica un valore in ingresso.
	if (!is_numeric($x)) die("Valore non valido.\n");
	$x=floatval($x);
	if ($x<=0) die("Valore troppo basso.\n");
	return $x;
	}

function parser($txt,$Sx='\\(',$Dx=')') {	// Parsa una stringa in token
	$sxl=strlen($Sx);
	$dxl=strlen($Dx);
	$o=array();
	$j = strlen($txt);
	for ($i=0;$i<$j;$i++) {
		$p = strpos($txt,$Sx);
		if ($p!==false) {
		        $o[] = array(0,substr($txt,0,$p));
		        $txt = substr($txt,$p+$sxl);
		        $f = strpos($txt,$Dx);
		        if ($f!==false) {
			        $t = substr($txt,0,$f);
			        $txt=substr($txt,$f+$dxl);
			        $o[] = array(1,$t);
				}
			} else break; 
		}
	if (strlen($txt)>0) $o[] = array(0,$txt);
	return $o;
	}

// Uso getopt, non è il metodo migliore. Usare con cura!
$par = getopt("f:t:r:b:s:p:ho:elR:bNOWc:PBL:",array('file:','freq:','bw:','info:','no'));

// Guida con -h -? oppure senza argomenti.
if ($par===false or isset($par['h']) or @$argv[1]=='-?' or count($argv)<2) {
	echo "Trasmette una stringa di testo come messaggio spettrale.\n\n";
	echo "text2wav -f <font> { -t \"text\" | --file <textFile> } -o <outFile>\n";
	echo "  [ -r <sampleRate> ] [ -b <baseFreq> ] [ -R <repeat> ] [ -e ] [ -l ]\n";
	echo "  [ -s <stepFreq> ] [ -p <pixelFreq> ] \n\n";
	echo "text2wav -f <font> --dump-font\n\n";
	echo "  -f\tImposta il font (può essere omesso con --freq se preimpostato).\n";
	echo "  -t\tSpecifica il testo da trasmettere.\n";
	echo "  -o\tImposta il file wave di uscita.\n";
	echo "  -r\tImposta la frequenza di campionamento (default 44100).\n";
	echo "  -b\tImposta la puù bassa frequenza di partenza.\n";
	echo "  -R\tRipete il messaggio n volte.\n";
	echo "  -s\tImposta la distanza in hertz delle frequenze.\n";
	echo "  -p\tImposta la lunghezza (hertz o secondi) dei pixel.\n";
	echo "  -e\tInterpreta i backslash: \xNN \\t \\r \\n \\0 \\n \\\\\n";
	echo "    \tQuesta opzione interpreta anche i simboli con il nome:\n";
	echo "    \tEsempio simbolo: \\(quadrato)\n";
	echo "    \tEsempio unicode: \\(#00DB)\n";
	echo "    \t(Usare font-edit per vedere la lista dei simboli).\n\n";
	echo "  -l\tImposta l'unità di misura in secondi per -p\n";
	echo "  -N\tConverte CR, LF e TAB in semplici spazi ed elimina gli spazi mutipli.\n";
	echo "  -O\tInvia l'output sullo standrard output.\n";
	echo "  -P\tUsa il protocollo con \$START e \$STOP\n";
	echo "  -c\tImposta il charset per la conversione:\n";
	echo "  -W\tCrea un file RAW.\n";
	echo "  -B\tNon usa il buffer di uscita (invia una word PCM alla volta).\n";
	echo "  -bw\tImposta il limite controllato su una banda precisa: -bw <da-a>\n";
	echo "  -L\tImposta la distanza minima dei canali in herts. (default 200)\n";
	echo "  --no\tNon controlla se i canali sono sovrapposti.\n";
	echo "\nAltri comandi:\n";
	echo "  --file\tPrende il testo da un file binario.\n";
	echo "  --freq\tUsa un file con dentro le frequenze.\n";
	echo "  --info\tVisualizza le informazioni sul file frequenze e i calcoli.\n\n";
	exit;
	}

if (@$par['info']) $par['freq']=$par['info'];

if (!isset($par['B'])) $par['B']=true; else $par['B']=false;

if (!@$par['o'] and !isset($par['O'])) die("Manca -o\n");

if (@$par['file']) {
	$text = @file_get_contents($par['file']) or die("\nErrore nel file di testo!\n");
	} else {
		if (!@$par['t']) die("Manca -t\n");
		$text=$par['t'];
	}

// Parametri di default.

$sampleRate=44100;		//	Frequenza di campionamento.
$baseFreq=500;			//	Frequenza più bassa (da dove si inizia).
$stepFreq=500;			//	Distanza dei canali in hertz.
$pixelFreq=200;			//	Lunghezza di un pixel in hertz (il clock).
$minOverlap=150;		//	Minima distanza delle frequenze in HZ.

$freq=array();			//	Questo conterrà gli oscillatori.

$correggiFont=true;
$maxBandWidth=false;
$listChannels=false;

if (isset($par['freq'])) { // Legge il file frequenze e parametri --freq
	$lst=@file($par['freq']) or die("Errore sul file `{$par['freq']}`\n");
	if (strpos($lst[0],'@Text2Wave.Freq')===false) die("Tipo di file non riconosciuto.\nDeve iniziare con: @Text2Wave.Freq\n");
	$t0=' baseFreq stepFreq pixelFreq sampleRate minOverlap ';
	$listChannels=true;
	foreach($lst as $numl => $li) {
		$li=trim($li,"\t\r\n ");
		list($li)=explode(';',$li,2);
		if ($li=='') continue;
		if ($li[0]!='@') continue;
		$li=substr($li,1);
		list($a,$b)=explode('=',$li.'=');
		$a=trim($a);
		$b=trim($b);
		
		if ($a=='Text2Wave.Freq') continue;
		if ($a=='info') {
			if (@$par['info']) {
				list($a,$b)=explode('=',$li,2);
				echo 'Info: '.trim($b,"\t ")."\n";
				}
			continue;
			}
		
		if ($a=='font') {
			$c=false;
			$b = array(
				$b,
				pathinfo($par['freq'],PATHINFO_DIRNAME)."/$b",
				pathinfo($par['freq'],PATHINFO_DIRNAME)."/fonts/$b",
				"fonts/$b")
				;
				
			foreach($b as $a) {
				if (file_exists($a) and is_file($a)) {
					$c=$a;
					break;
					}
				}
				
			if ($c===false) die("Font preimpostato non trovato `{$b[0]}`\n");
			if (!isset($par['f'])) $par['f']=$c;
			$correggiFont=false;
			if (@$par['info']) echo "Font preimpostato: $c\n";
			continue;
			}
		
		if ($a=='start') {
			$text=$b.$text;
			continue;
			}
		
		if ($a=='end') {
			$text.=$b;
			continue;
			}
		
		if ($a=='startEsc') {
			$text=stripcslashes($b).$text;
			continue;
			}
		
		if ($a=='endEsc') {
			$text.=stripcslashes($b);
			continue;
			}
			
		if ($a=='bandWidth') {
			list($a,$b)=explode('-',$b,2);
			$a=trim($a,"\t ");
			$b=trim($b,"\t ");
			if ($a=='' or $b=='') die("Errore parser: bandWidth Linea ".($numl+1)." $li\n");
			$maxBandWidth = array(
				valu($a),
				valu($b))
				;
				
			$baseFreq = $maxBandWidth[0];
			if (@$par['info']) echo "Banda controllata da: $a a $b\n";
			continue;
			}
		
		if ($a=='noList') {
			$listChannels=false;
			continue;
			}
		
		if ($a=='longMode') {
			$pixelFreq = 1 / $pixelFreq;
			if (@$par['info']) echo "Modalità longMode attivata.\n";
			continue;
			}
		
		if (
			$a=='' or 
			$b=='' or
			strpos($a,' ')!==false or
			strpos($t0," $a ")===false
			) die("Errore parser: Linea ".($numl+1)." $li\n");
			
		$$a=valu($b);
		}
	}

// Lettura dei parametri e verifiche varie.
if (!@$par['f']) die("Manca -f\n");

$font = loadFont($par['f']) or die("\nErrore nel file del font!\n");
$fontHeight= $font['height'];
$fontWidth = $font['width'] ? $font['width'] : 8;

if ($correggiFont and $fontHeight==16) {	//	Aggiustamento per font 8x16.
	$stepFreq=400;
	$baseFreq=400;
	}
	
if (isset($par['r'])) $sampleRate = valu($par['r']);
if (isset($par['b'])) $baseFreq = valu($par['b']);
if (isset($par['s'])) $stepFreq = valu($par['s']);
if (isset($par['p'])) $pixelFreq = valu($par['p']);
if (isset($par['l'])) $pixelFreq = 1 / $pixelFreq;
if (isset($par['O'])) $par['W']=true;
if (isset($par['L'])) $minOverlap = valu($par['L']);

if (isset($par['bw'])) {
	list($a,$b)=explode('-',$par['bw'],2);
	if ($a=='' or $b=='') die("Errore sul parametro -bw <da-a>\n");
	$maxBandWidth = array( valu($a) , valu($b) ) ;
	}
	
if ($maxBandWidth and $maxBandWidth[1]<=$maxBandWidth[0]) die("Errore sul parametro -bw <da-a>\n");

if ($pixelFreq>$baseFreq) die("Parametri di frequenza non validi: pixel > base\n");
if ($baseFreq>($sampleRate/2)) die("Parametri di frequenza non validi: base > bandwidth\n");
if (($baseFreq + ($fontHeight*$stepFreq))>($sampleRate/2)) die("Parametri di frequenza non validi: siamo fuori banda.\n");

if (isset($par['N'])) { // Rimuove spazi, cr, lf, tab.
	$text=str_replace(array("\t","\r","\n"),' ',$text);
	while(strpos($text,'  ')!=='') $text=str_replace('  ',' ',$text);
	}

if (isset($par['O'])) $fout=STDOUT; else $fout=fopen($par['o'],'w') or die("\nErrore sul file di output!\n");
if (!isset($par['W'])) WaveHeader($fout,$sampleRate);	// Inizializza il file come wave.

$map=array();	// Questo array contiene la bitmap finale.

//Conversione codifica caratteri.
$encIn = false;
$encOut= 'UTF-8';
if (isset($font['charset'])) $encOut=$font['charset'];
if (isset($par['c'])) $encIn=$par['c'];

// Start e stop.
if (isset($par['P'])) {
	$par['e']=true;
	$text='\\($START)'.$text.'\\($STOP)';
	}

// Modalità speciali.
$fMod = isset($font['mode']) && $font['mode']&1 && isset($font['map']);	// Rimappatura caratteri
$fBas = isset($font['mode']) && $font['mode']&2 && isset($font['map']); // Offset caratteri.

if ($fBas) $font['map']['_'] = "\xff\xff\x00\x00\x00\x00"; // Offset di sistema.

if (isset($par['e'])) { // Elabora le sezioni \( ... )
	$t0 = parser($text);
	$text='';
	foreach($t0 as $tok) {
		if ($tok[0]) {
			
			if (@$tok[1][0]=='#') { // \(#xxxx) Carattere unicode
				$t0 = hexdec(substr($tok[1],1));
				$text.=pack('n',$t0 & 0xFFFF);
				continue;
				}
			
			if (
					@$tok[1][0]!='$' and (  // Rimappatura interna
						!isset($font['map']) or 
						!isset($font['map'][$tok[1]]
						)
					)
				) die("Stringa non definita: `{$tok[1]}`\n");
				
			$text.=$font['map'][$tok[1]];
			} else {
			$tok[1]=stripcslashes($tok[1]);		
			$text.=encode($tok[1],$encOut,$encIn);
			}
		}	
	} else $text= encode($text,$encOut,$encIn);
	
$text=unpack('n*',$text);

if ($fBas) { // Elabora la modalità offset con UFFFF
	$j=count($text)+1;	
	$out=array(0);
	$base=0;
	$chDa=-1;
	$chA=-1;
	for ($i=0;$i<$j;$i++) {
		$ch = $text[$i];
		if ($ch==65535) {
			$i++;
			$base = $text[$i++];
			$chA = $text[$i] & 0xFF;
			$chDa = $text[$i] >> 8;
			continue;
			}
			
		if ($base and $ch>=$chDa and $ch<=$chA) {
			$ch = ($ch-$chDa+$base) & 0xFFFF;
			}
			
		$out[] = $ch;
		}
	$text=$out;
	}
	
$j=count($text)+1;	// Tipico ciclo for per convertire la stringa.
$FW = $font['width'] ? $font['width'] : 8;

for ($i=1;$i<$j;$i++) {
	
		if ($fMod) {
			$id = '@'.str_pad(dechex($text[$i]),4,'0',STR_PAD_LEFT);
			if (isset($font['map'][$id])) {
				$t0=unpack('n',$map[$id]);
				$text[$i] = $t0[1];
				}
			}
			
		addBmp($map,charBmp($font,$text[$i]),$FW);	// Aggiungi ogni bitmap di ogni carattere alla bitmap finale.
	}

$imgWidth=count($map); // Trova la larghezza della bitmap.

if (@$par['freq'] and $listChannels) { // Ricarica le frequenze se c'è listChannel attivato.
	$lst = file($par['freq']) or die("Errore nel file delle frequenze.\n");
	$frx = array();
	foreach($lst as $num => $li) {
		$li=str_replace("\t",' ',$li);
		$li=trim($li,"\t\r\n ");
		list($li)=explode(';',$li,2);
		if ($li=='') continue;
		if ($li[0]=='@') continue;
		while(strpos($li,'  ')!==false) $li=str_replace('  ',' ',$li);
		$li=explode(' ',$li);
		if (count($li)!=3) die("Errore del parser: `{$par['freq']}` line: ".($num+1)."\n");
		$a=intval($li[0]);
		$frx[$a]=$li;
		}
	ksort($frx);
	$lst=null;
	foreach($frx as $ff) {
		$f = createFreq($ff[0],$sampleRate);
		addFreqMode($f,$ff[1],$ff[2],$sampleRate);
		$freq[]=$f;
		}
		
	if ($fontHeight>count($freq)) die("Troppo pochi canali di frequenze in questo file `{$par['freq']}`\n");
	} else {

	// Produzione dei vari oscillatori, uno per riga orizzontale.
	for ($i = 0;$i<$fontHeight;$i++) {
		$f = $baseFreq+($stepFreq*$i);
		$freq[$i] = createFreq($f,$sampleRate);
		}
}
// Verifica overlapping dei canali:
$test=array();
foreach($freq as $n => $f) { 
	$x = floor($f['f'] / $minOverlap);
	if (!isset($test[$x])) $test[$x]=array();
	$test[$x][] = array($n,$f['f']);
	}

$overlap=false;
foreach($test as $f) {
	if (count($f)>1) {
		foreach($f as $a) {
				$freq[$a[0]]['ovr']=true;
			}
			$overlap=true;
		}
	}

if (@$par['info']) { // Info:
	echo "\nFrequenze:\n";
	foreach($freq as $n=>$f) {
		echo "Canale ".($n+1)." {$f['f']} Hz ";
		if (isset($f['ovr'])) echo "Ov. "; else if ($overlap) echo "-- ";
		if ($maxBandWidth) {
			if ($f['f']<$maxBandWidth[0] or $f['f']>$maxBandWidth[1]) echo "Fuori banda\n"; else echo "Ok\n";
			} else echo "\n";
		}	
	if ($overlap) echo "Ov. = Frequenze quasi sovrapposte.\n";
	echo "\nAltezza font: $fontHeight (canali richiesti)\n";
	echo "Canali presenti: ".count($freq)."\n";
	$x = ($imgWidth * ($maxRept?$maxRept:1) * ($sampleRate/$pixelFreq));
	$x = intval($x / $sampleRate);
	$x = str_pad(floor($x/3600),2,'0',STR_PAD_LEFT).':'.str_pad(floor($x/60) % 60,2,'0',STR_PAD_LEFT).':'.str_pad($x%60,2,'0',STR_PAD_LEFT);
	echo "Lunghezza file: $x\n\n";
	exit;
	}

if ($overlap and !isset($par['no'])) die("Ci sono delle frequenze troppo vicine.\n");
	
if ($maxBandWidth) { //verifica bandwidth
	foreach($freq as $n => $f) {
		if ($f['f']<$maxBandWidth[0] or $f['f']>$maxBandWidth[1]) die("Fuori banda: Canale ".($n+1)." frequenza {$f['f']} Hz\n");
		}
}

$pixXx = $sampleRate/$pixelFreq;

$sndWidth= $imgWidth*$pixXx;
$sndHeight=$fontHeight*256;  // Valore PCM massimo di una riga verticale.

$fftChk=array_pad(array(),$fontHeight,0);	// Questo array ci serve per fare un po di statistica fft.
$maxRept=1;

if (isset($par['R'])) $maxRept=abs(intval($par['R'])); // C'è la possibilità di ripetere la stringa.
$buffer='';
$bufWord=0;
for ($rept=0;$rept<$maxRept;$rept++) {	// Per ogni ripetizione, tipicamente 1.
	for ($x = 0 ;$x<$sndWidth;$x++) {	// Da sinistra a destra.
		$q=0; // Valore PCM corrente.
		
		for ($y = 0;$y<$fontHeight;$y++) {	// Si analizza la bitmap a righe verticali.
			$bit = $map[$mapX][$y];
			if (!$bit) $freq[$y]['al']=0;	// Se il bit è a 0, si resetta l'oscillatore.
			$peak1=oscillator($freq[$y]);	// Questo produce l'output dal oscillatore della riga orizzontale.
			$mapX = floor($x / $pixXx);		// Trova la coordinata X.
			$q+=($bit ? $peak1 : 0);		// Somma, oppure no, l'onda se il bit dell'immagine bitmap è a 1.
			if ($bit and !$fftChk[$y]) $fftChk[$y]=true; // Un po di statistica non guasta.
			}
		
		$q=intval(($q/$sndHeight)*20000);	// Normalizza l'output ad un valore PCM decente.
		
		// Scrive il valore PCM sul file wave.
		if ($par['B']) {
				$buffer.=pack('v',$q);
				if ($bufWord++>4096) {	// 8KB di buffer.
					$bufWord=0;
					fwrite($fout,$buffer);
					$buffer='';
					}
			} else fwrite($fout, pack('v',$q));
		}
}

if ($par['B'] and strlen($buffer)) {	// Se usi il buffer, scrive anche gli ultimi dati.
	fwrite($fout,$buffer);
	$buffer=null;
}

if (!isset($par['W'])) WaveHeader($fout,$sampleRate); // Completa il file wave chiudendo RIFF/WAVE e data.
	
fclose($fout);
if (isset($par['O'])) exit;

// Analizza le statistiche e tira fuori: (vedi seguito)
$fmin=$sampleRate;
$fmax=1;
for($i = 0;$i<$fontHeight;$i++) {
	$f = $freq[$i]['f'];
	if ($fftChk[$i]) {
		if ($f<$fmin) $fmin=$f;
		if ($f>$fmax) $fmax=$f;
		}
	}

echo "Freq-Min: $fmin\n";			// Frequenza più bassa.
echo "Freq-Max: $fmax\n";			// Frequenza più altra.
echo "Snd-Width: $sndWidth\n";		// "Larghezza" file wave.
$x = intval($sndWidth/$sampleRate);
$x = str_pad(floor($x/3600),2,'0',STR_PAD_LEFT).':'.str_pad(floor($x/60) % 60,2,'0',STR_PAD_LEFT).':'.str_pad($x%60,2,'0',STR_PAD_LEFT);
echo "Snd-Len: $x\n"; // Lunghezza file wave.
?> 