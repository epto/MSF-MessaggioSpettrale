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

function charBmp(&$font,$ch) {	// Da carattere a relativa bitmap (Array MxN).
	global $fontHeight;
	$bp = $ch*$fontHeight;
	
	$bmp = substr($font,$bp,$fontHeight);
	$map = array_pad(array(),8,array_pad(array(),$fontHeight,0));
	for ($y = 0 ;$y<$fontHeight;$y++) {
		for ($x=0;$x<8;$x++) {
			$bit = ord($bmp[($fontHeight-1) - $y]) & 1<<(7^$x);
			$map[$x][$y] = $bit ? 1:0;
			}
		}
	return $map;
	}

function showChar($map,$ch) {	// Visualizza il carattere per --dump-font
	global $fontHeight;
	echo str_pad($ch.' '.dechex($ch),8,' ')."\n";
	for ($y=0;$y<$fontHeight;$y++) {
		for ($x=0;$x<8;$x++) {
			$b = $map[$x][($fontHeight-1)^$y];
			echo $b ? '█' : ' ';
			}
		echo "\n";
		}
	echo "\n";
	}

function addBmp(&$org,$map) {	// Aggiunge alla bitmap finale la bitmap di un carattere.
	$cx = count($org);
	$dx=$cx+8;
	$ex=0;
	for ($i=$cx;$i<$dx;$i++) $org[$i]=$map[$ex++];
	}

/////////// Sezione socillatori.

function createFreq($f,$sampleRate) {	// Inizializza un oscillatore.
	$pi = pi() * 2;
	$st = $sampleRate / $f;
	$st = $pi / $st;
	
	return array(
		'f'		=>	$f	,		//	Frequenza
		'st'	=>	$st	,		//	Incremento in radianti.
		'al'	=>	0	)		//	Posizione dell'onda in radianti.
		;	
	}

function oscillator(&$freq) {	// Implementa l'oscillatore.
	$pi = pi() * 2;
	$peak=intval(255*sin($freq['al']));
	$freq['al']+=$freq['st'];
	if ($freq['al']>$pi) $freq['al']-=$pi;
	return $peak;	// Ritorna un valore PCM da -255 a 255.
	}

////////// Altre funzioni.

function valu($x) { // Legge/verifica un valore in ingresso.
	if (!is_numeric($x)) die("Valore non valido.\n");
	$x=floatval($x);
	if ($x<=0) die("Valore troppo basso.\n");
	return $x;
	}

// Uso getopt, non è il metodo migliore. Usare con cura!
$par = getopt("f:t:r:b:s:p:ho:elR:bNOW",array('file:','dump-font'));

// Guida con -h -? oppure senza argomenti.
if ($par===false or isset($par['h']) or @$argv[1]=='-?' or count($argv)<2) {
	echo "Trasmette una stringa di testo come messaggio spettrale.\n\n";
	echo "text2wav -f <font> { -t \"text\" | --file <textFile> } -o <outFile>\n";
	echo "  [ -r <sampleRate> ] [ -b <baseFreq> ] [ -R <repeat> ] [ -e ] [ -l ]\n";
	echo "  [ -s <stepFreq> ] [ -p <pixelFreq> ] \n\n";
	echo "text2wav -f <font> --dump-font\n\n";
	echo "  -f\tImposta il font.\n";
	echo "  -t\tSpecifica il testo da trasmettere.\n";
	echo "  -i\tImposta il file wave di uscita.\n";
	echo "  -r\tImposta la frequenza di campionamento (default 44100).\n";
	echo "  -b\tImposta la puù bassa frequenza di partenza.\n";
	echo "  -R\tRipete il messaggio n volte.\n";
	echo "  -s\tImposta la distanza in hertz delle frequenze.\n";
	echo "  -p\tImposta la lunghezza (hertz o secondi) dei pixel.\n";
	echo "  -e\tInterpreta i backslash: \xNN \\t \\r \\n \\0 \\n \\\\\n";
	echo "  -l\tImposta l'unità di misura in secondi per -p\n";
	echo "  -N\tConverte CR, LF e TAB in semplici spazi ed elimina gli spazi mutipli.\n";
	echo "  -O\tInvia l'output sullo standrard output.\n";
	echo "  -W\tCrea un file RAW.\n";
	echo "\nAltri comandi:\n";
	echo "  --file\tPrende il testo da un file binario.\n";
	echo "  --dump-font\tStampa il font usando i caratteri grafici UTF-8.\n\n";
	exit;
	}

if (!@$par['f']) die("Manca -f\n");
$font = file_get_contents($par['f']) or die("\nErrore nel file del font!\n");
$fontHeight= strlen($font) >=3072 ? 16: 8; // 8x256 byte = font 8x8, 16x256 byte = font 8x16. Ho messo una via di mezzo perchè alcuni file hanno roba alla fine.

if (isset($par['dump-font'])) { // Visualizza tutto il set di caratteri.
	for ($a = 0;$a<255;$a++) {
		$map = charBmp($font,$a);
		showChar($map,$a);
		}
	exit;
	}

if (!@$par['o'] and !isset($par['O'])) die("Manca -o\n");

if (@$par['file']) {
	$text = file_get_contents($par['file']) or die("\nErrore nel file di testo!\n");
	} else {
		if (!@$par['t']) die("Manca -t\n");
		$text=$par['t'];
	}

if (isset($par['e'])) $text=stripcslashes($text);

// Parametri di default.

$sampleRate=44100;		//	Frequenza di campionamento.
$baseFreq=500;			//	Frequenza più bassa (da dove si inizia).
$stepFreq=500;			//	Distanza dei canali in hertz.
$pixelFreq=200;			//	Lunghezza di un pixel in hertz (il clock).

$freq=array();			//	Questo conterrà gli oscillatori.

if ($fontHeight==16) {	//	Aggiustamento per font 8x16.
	$stepFreq=400;
	$baseFreq=400;
	}

// Lettura dei parametri e veriifche varie.
if (isset($par['r'])) $sampleRate = valu($par['r']);
if (isset($par['b'])) $baseFreq = valu($par['b']);
if (isset($par['s'])) $stepFreq = valu($par['s']);
if (isset($par['p'])) $pixelFreq = valu($par['p']);
if (isset($par['l'])) $pixelFreq = 1 / $pixelFreq;

if ($pixelFreq>$baseFreq) die("Parametri di frequenza non validi: pixel > base\n");
if ($baseFreq>($sampleRate/2)) die("Parametri di frequenza non validi: base > bandwidth\n");
if (($baseFreq + ($fontHeight*$stepFreq))>($sampleRate/2)) die("Parametri di frequenza non validi: siamo fuori banda.\n");

if (isset($par['N'])) {
	$text=str_replace(array("\t","\r\n","\r","\n"),' ',$text);
	while(strpos($text,'  ')!=='') $text=str_replace('  ',' ',$text);
	}

if (isset($par['O'])) $fout=STDOUT; else $fout=fopen($par['o'],'w') or die("\nErrore sul file di output!\n");
if (!isset($par['W'])) WaveHeader($fout,$sampleRate);	// Inizializza il file come wave.

$map=array();	// Questo array contiene la bitmap finale.

$j=strlen($text);	// Tipo ciclo for per convertire la stringa.
for ($i=0;$i<$j;$i++) {
		addBmp($map,charBmp($font,ord($text[$i])));	// Aggiungi ogni bitmap di ogni carattere alla bitmap finale.
	}

$imgWidth=count($map); // Trova la larghezza della bitmap.

// Produzione dei vari oscillatori, uno per riga orizzontale.
for ($i = 0;$i<$fontHeight;$i++) {
	$f = $baseFreq+($stepFreq*$i);
	$freq[$i] = createFreq($f,$sampleRate);
	}

$pixXx = $sampleRate/$pixelFreq;

$sndWidth= $imgWidth*$pixXx;
$sndHeight=$fontHeight*256;  // Valore PCM massimo di una riga verticale.

$fftChk=array_pad(array(),$fontHeight,0);	// Questo array ci serve per fare un po di statistica fft.
$maxRept=1;

if (isset($par['R'])) $maxRept=abs(intval($par['R'])); // C'è la possibilità di ripetere la stringa.

for ($rept=0;$rept<$maxRept;$rept++) {	// Per ongi ripetizione, tipicamente 1.
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
		fwrite($fout, pack('v',$q));		// Scrive il valore PCM sul file wave.
		}
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

?> 