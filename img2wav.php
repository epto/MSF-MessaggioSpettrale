#!/usr/bin/php
<?php
/*
 * img2wav
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
 * 2° Assumo i pixel come scala di grigi.
 * 3° Si screa un oscillatore per ogni riga orizzontale.
 *    Consigliate: 64 righe o 128 righe.
 * La differenza è che i canali sono pilotati dalla luminosità dei pixel.
 * */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_USER_WARNING &~E_NOTICE);

function WaveChunk($name,$data) {
	return str_pad($name,4,' ',STR_PAD_RIGHT).pack('V',strlen($data)).$data;	
	}

function WaveHeader(&$f,$sampleRate) {
		
	$fmt = 
		pack('v',1).
		pack('v',1).
		pack('V',$sampleRate).
		pack('V',$sampleRate*2).
		pack('v',2).
		pack('v',16);
		
	$head = WaveChunk('fmt',$fmt);
	$org = ftell($f);
	
	if ($org!=0) {
		$ptr = $org - 8; 
		fseek($f,0,SEEK_SET);
		}
	
	$head = "RIFF".pack('V',$ptr).'WAVE'. $head .'data'.pack('V', $org - 44);
	fwrite($f,$head);
		
	}

function createFreq($f,$sampleRate) {
	$pi = pi() * 2;
	$st = $sampleRate / $f;
	$st = $pi / $st;
	
	return array(
		'ph'	=>	0	,	// Fase del oscillatore.
		'st'	=>	$st	,
		'al'	=>	0	)
		;	
	}

function oscillator(&$freq) { // Questo oscillatore tiene in cosiderazione anche un alterazione della fase.
	$pi = pi() * 2;
	$peak=intval(255*sin($freq['al']+$freq['ph']));
	$freq['al']+=$freq['st'];
	if ($freq['al']>$pi) $freq['al']-=$pi;
	return $peak;
	}

function valu($x) {
	if (!is_numeric($x)) die("\nValore non valido.\n");
	$x=floatval($x);
	if ($x<1) die("\nValore troppo basso.\n");
	return $x;
	}

$par = getopt("f:i:r:b:s:p:ho:w:a:z:k",array('ppx:','auto'));

if ($par===false or isset($par['h']) or @$argv[1]=='-?' or count($argv)<2) {
	echo "Trasmette un'immagine come messaggio spettrale.\n\n";
	echo "img2wav -i <imageFile.png> [ -w <bandWidth> ] [ -z <n> ] [ -k ]\n";
	echo "  -o <outFile> [ -r <sampleRate> ] [ -b <baseFreq> ] [--ppx <ppx> ]\n";
	echo "  [ -s <stepFreq> ] [ -p <pixelFreq> ] [ -a <phaseGradXCannel> ]\n";
	echo "\n";
	echo "  -i\tImposta l'immagine PNG da leggere.\n";
	echo "    \tImmagini supportate: PNG a 24 bit in scala di grigi.\n";
	echo "    \tL'altezza può andare da: 1 a 128 pixel.\n\n";
	echo "  -w\tImposta l'ampliezza di banda in hertz.\n";
	echo "  -z\tImposta l'allargamento dei pixel in hertz.\n";
	echo "  -k\tImposta la modalità di accumulo dell'onda (per linea verticale).\n";
	echo "  -o\tImposta il file WAVE di uscita.\n";
	echo "  -r\tImposta la frequenza di campionamento (default 44100).\n";
	echo "  -b\tImposta la frequenza più bassa di partenza.\n";
	echo "  --ppx\tImposta il numero di cicli per pixel (anziche usare la frequenza).\n";
	echo "  -s\tImposta la distanza delle frequenze in hertz.\n";
	echo "  -p\tImposta la lunghezza dei pixel in hertz.\n";
	echo "  -a\tImposta l'offset di fase tra le frequenze vicine.\n\n";
	exit;
	}

if (!@$par['o']) die("Manca -o\n");
if (!@$par['i']) die("Manca -i\n");

// Parametri di default. L'uso di -w è sempre consigliato per impostare una larghezza di banda.
$sampleRate=44100;
$baseFreq=500;
$stepFreq=500;
$pixelFreq=200;
$zMax=1;
$freq=array();

if (isset($par['r'])) $sampleRate = valu($par['r']);

$im = imagecreatefrompng($par['i']) or die("\nErrore immagine.\n");
$fontHeight = imagesy($im);
$imgWidth = imagesx($im);

if (isset($par['auto'])) { // Calcola automaticamente
	$t0 = floor($sampleRate / 2.5); // Un po meno della frequenza max. (Vedi Nyquist).
	$t0 -= 200; // Tolgo 200 HZ.
	$par['b'] = 200; // Perchè parto da 200 Hz.
	$par['s'] = floor($t0 / $fontHeight); // Imposto la distanza in hertz.
	if ($par['s']<150) echo "Attenzione: Troppe linee verticali, rischio di distorsioni.\n";
	$par['p'] = 50; // Clock 50 Hz.
	}

if (isset($par['z'])) $zMax = 1+ intval($sampleRate / valu($par['z']));
if (isset($par['b'])) $baseFreq = valu($par['b']);
if (isset($par['s'])) $stepFreq = valu($par['s']);
if (isset($par['p'])) $pixelFreq = valu($par['p']);

$fout=fopen($par['o'],'w') or die("\nErrore file di uscita.\n");
WaveHeader($fout,$sampleRate);

if ($baseFreq>($sampleRate/2)) die("Parametri di frequenza non validi: base > bandwidth\n");

if (isset($par['w'])) {		// Calcola i parametri autonomamanete tramite la larghezza di banda impostata.
	$bw = valu($par['w']);
	$bw = ($bw - $baseFreq);
	$stepFreq = floor($bw / $fontHeight);
	echo "StepFreq.: $bw / $fontHeight = $stepFreq\n";
	}

if ($pixelFreq>$baseFreq) die("Parametri di frequenza non validi: pixel > base\n");
if (($baseFreq + ($fontHeight*$stepFreq))>($sampleRate/2)) die("Parametri di frequenza non validi: siamo fuori banda.\n");

$pixXx = $sampleRate/$pixelFreq;

if (isset($par['ppx'])) $pixXx = valu($par['ppx']);


for ($i = 0;$i<$fontHeight;$i++) {
	$f = $baseFreq+($stepFreq*$i);
	$freq[$i] = createFreq($f,$sampleRate);
	}


if (isset($par['a'])) {	// Possiamo scegliere se sfalsare la fase degli oscillatori per mitigare i battimenti.
	$al = 0;
	$st = intval($par['a']) % 360;
	for ($i = 0;$i<$fontHeight;$i++) {
		$x = deg2rad($al);
		$al+=$st % 360;
		$freq[$i]['al'] = $x;
		}
	}

$sndWidth= $imgWidth*$pixXx;
$sndHeight=$fontHeight*256;

echo "Dim.: $sndWidth x $fontHeight x $sndHeight x $zMax\n";
$cpercm=-1;
$oldst='...';
echo $oldst;
for ($x = 0 ;$x<$sndWidth;$x++) {
	if (!isset($par['k'])) $q=0;
	for ($z = 0;$z<$zMax;$z++) {
		if (isset($par['k'])) $q=0;
		for ($y = 0;$y<$fontHeight;$y++) {
			$imgy=($fontHeight-1)-$y;
			$imgx=floor($x/$pixXx);
			
			// Prende un colore di un pixel.
			$rgb = imagecolorat($im, $imgx, $imgy);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			// Converte da rgb a scala di grigi.
			$amp = ($r+$g+$b)/765;
			
			$peak1=oscillator($freq[$y]); // Produce l'oscillazione.
			$peak1=intval($peak1 * $amp); // Quindi la regola con la luminosità del pixel.
			$q+=$peak1;
			}
		
		$q=intval(($q/$sndHeight)*30000); // Normalizza la riga verticale tramite il valore massimo.
		fwrite($fout, pack('v',$q)); // Srcrive il valore PCM sul file wave.
		}
		
	// Questa è la progressione in percentuale, può essere noioso aspettare senza vedere a che punto è.
	$cperc = round( ($x/$sndWidth)*100,2);
	if ($cpercm!=$cperc) {
		$cpercm=$cperc;
		echo str_pad('',strlen($oldst),chr(8));
		$oldst=" $cperc % ... ";
		echo $oldst;
		}
	}
WaveHeader($fout,$sampleRate); // Chuide tutto
fclose($fout);
imagedestroy($im);
echo str_pad('',strlen($oldst),chr(8));
echo " 100% Ok\t\n"; // Ehm... anche la progressione. Talvolta finisce al 99% per arrotondamento.  
		
?>
