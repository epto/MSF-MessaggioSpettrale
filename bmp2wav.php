#!/usr/bin/php
<?php
/*
 * bmp2wav
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
 * Molte funzioni sono copiate da text2wav.
 * Quindi consiglio di leggere prima i commenti di quel file.
 * Qui commenterò solo le differenze.
 * 
 * La procedura è la stessa di text2wav, tuttavia:
 * 1° Leggo con le librerie gd l'immagine.
 * 2° Assumo i pixel come monocromatici.
 * 3° Si screa un oscillatore per ogni riga orizzontale.
 *    Consigliate: 64 righe o 128 righe.
 * */


error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_USER_WARNING &~E_NOTICE);

function WaveChunk($name,$data) {
	return str_pad($name,4,' ',STR_PAD_RIGHT).pack('V',strlen($data)).$data;	
	}

function WaveHeader(&$f,$sampleRate) {
		
	$fmt = // Format:
		pack('v',1).				//  CODEC 1 = PCM Signed
		pack('v',1).				//  Channels 1 Mono
		pack('V',$sampleRate).		//  SampleRate
		pack('V',$sampleRate*2).	//  Bytes x Sec.
		pack('v',2).				//  Block align
		pack('v',16);				//  Bits x sample
		
	$head = WaveChunk('fmt',$fmt);
	$org = ftell($f);
	
	if ($org!=0) {
		$ptr = $org - 8; 
		fseek($f,0,SEEK_SET);
		}
	
	$head = "RIFF".pack('V',$ptr).'WAVE'. $head .'data'.pack('V', $org - 44);
	/*
	 * RIFF / WAVE {
	 * 		fmt  { format }
	 * 		data { PCM Wave audio }
	 * }
	 * 
	 * */
	fwrite($f,$head);
		
	}

function createFreq($f,$sampleRate) {
	$pi = pi() * 2;
	$st = $sampleRate / $f;
	$st = $pi / $st;
	
	return array(
		'f'		=>	$f	,
		'st'	=>	$st	,
		'al'	=>	0	)
		;	
	}

function oscillator(&$freq) {
	$pi = pi() * 2;
	$peak=intval(255*sin($freq['al']));
	$freq['al']+=$freq['st'];
	if ($freq['al']>$pi) $freq['al']-=$pi;
	return $peak;
	}

function valu($x) {
	if (!is_numeric($x)) die("Valore non valido.\n");
	$x=floatval($x);
	if ($x<1) die("Valore troppo basso.\n");
	return $x;
	}

$par = getopt("r:b:s:p:ho:li:WO",array('auto'));

if ($par===false or isset($par['h']) or @$argv[1]=='-?' or count($argv)<2) {
	echo "Trasmette un'immagine monoscromatica come messaggio spettrale.\n";
	echo "bmp2wav -i <image> -o <outFile> [ -r <sampleRate> ] [ -b <baseFreq> ]\n";
	echo "  [ -s <stepFreq> ] [ -p <pixelFreq> ] [-l] [ --auto ]\n\n";
	echo "  -i\tImposta l'immagine PNG da leggere.\n";
	echo "    \tImmagini supportate: PNG a 24 bit monocromatica (2 Colori).\n";
	echo "    \tL'altezza può andare da: 1 a 128 pixel.\n\n";
	echo "  -o\tImposta il file WAVE di uscita.\n";
	echo "  -r\tImposta la frequenza di campionamento (default 44100).\n";
	echo "  -b\tImposta la frequenza più bassa di partenza.\n";
	echo "  -s\tImposta la distanza delle frequenze in hertz.\n";
	echo "  -p\tImposta la lunghezza dei pixel in hertz o secondi.\n";
	echo "  -l\tImposta l'unità di misura in secondi per -p\n\n";
	echo "  -O\tInvia l'output sullo standrard output.\n";
	echo "  --auto\tCalcola tutto automaticamente.\n";
	echo "  -W\tCrea un file RAW.\n\n";
	exit;
	}

if (!@$par['i']) die("Manca -i\n");
if (!@$par['o'] and !isset($par['O'])) die("Manca -o\n");

// Lettura del file immagine con gd.
$im = imagecreatefrompng($par['i']) or die("\nErrore file immagine.\n");
$width = imagesx($im);
$height = imagesy($im);

$fontHeight= $height; // Notare l'assegnazione diretta lasciata appositamente per capirsi.

// Parametri di default.
$sampleRate=44100;	
$baseFreq=500;
$stepFreq=500;
$pixelFreq=200;

if (isset($par['r'])) $sampleRate = valu($par['r']);

if (isset($par['auto'])) { // Calcola automaticamente
	$t0 = floor($sampleRate / 2.5); // Un po meno della frequenza max. (Vedi Nyquist).
	$t0 -= 200; // Tolgo 200 HZ.
	$par['b'] = 200; // Perchè parto da 200 Hz.
	$par['s'] = floor($t0 / $fontHeight); // Imposto la distanza in hertz.
	if ($par['s']<150) echo "Attenzione: Troppe linee verticali, rischio di distorsioni.\n";
	$par['p'] = 50; // Clock 50 Hz.
	}

if (isset($par['b'])) $baseFreq = valu($par['b']);
if (isset($par['s'])) $stepFreq = valu($par['s']);
if (isset($par['p'])) $pixelFreq = valu($par['p']);
if (isset($par['l'])) $pixelFreq = 1 / $pixelFreq;

if ($pixelFreq>$baseFreq) die("Parametri di frequenza non validi: pixel > base\n");
if ($baseFreq>($sampleRate/2)) die("Parametri di frequenza non validi: base > bandwidth\n");
$t0 = $baseFreq + ($fontHeight*$stepFreq);
if ($t0 >($sampleRate/2)) die("Parametri di frequenza non validi: siamo fuori banda. Richiesti: $t0 Hz\n");

if (isset($par['O'])) $fout=STDOUT; else $fout=fopen($par['o'],'w') or die("\nErrore file di uscita\n");
if (!isset($par['W'])) WaveHeader($fout,$sampleRate);

// Conversione dell'immagine in bitmap (array MxN).
$map = array_pad( array() , $width , array_pad(array(),$height , 0));

for ($y = 0 ;$y<$height; $y++) {
	for ($x = 0; $x<$width; $x++) {
		$rgb = imagecolorat($im, $x, $y);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		$h = $r>>4 | $g>>4 | $b>>4;
		if ($h) $map[$x][($height-1)-$y]=1;
		}
	}
imagedestroy($im); // L'immagine PNG non ci serve più.

$imgWidth=$width; // giusto per capirsi.

// Il resto è uguale.

for ($i = 0;$i<$fontHeight;$i++) {
	$f = $baseFreq+($stepFreq*$i);
	$freq[$i] = createFreq($f,$sampleRate);
	}

$pixXx = $sampleRate/$pixelFreq;

$sndWidth= $imgWidth*$pixXx;
$sndHeight=$height*128;  
$fftChk=array_pad(array(),$fontHeight,0);

for ($x = 0 ;$x<$sndWidth;$x++) {
	$q=0;
	
	for ($y = 0;$y<$fontHeight;$y++) {
		$bit = $map[$mapX][$y];
		if (!$bit) $freq[$y]['al']=0;
		$peak1=oscillator($freq[$y]);
		$mapX = floor($x / $pixXx);
		$q+=($bit ? $peak1 : 0);
		if ($bit and !$fftChk[$y]) $fftChk[$y]=true;
		}
	
	$q=intval(($q/$sndHeight)*20000);
	fwrite($fout, pack('v',$q));
	}

if (!isset($par['W'])) WaveHeader($fout,$sampleRate);
	
fclose($fout);

if (isset($par['O'])) exit;

$fmin=$sampleRate;
$fmax=1;
for($i = 0;$i<$fontHeight;$i++) {
	$f = $freq[$i]['f'];
	if ($fftChk[$i]) {
		if ($f<$fmin) $fmin=$f;
		if ($f>$fmax) $fmax=$f;
		}
	}

echo "Freq-Min: $fmin\n";
echo "Freq-Max: $fmax\n";
echo "Snd-Width: $sndWidth\n";

?>