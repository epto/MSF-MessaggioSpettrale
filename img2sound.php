<?
/*
 * img2sound
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
 * Questa è una modifica di img2wav pensata per eliminare l'effetto righe verticali dei battimenti di frequenza.
 * Anzi, quel'effetto è usato per rendere l'immagine.
 * Quindi consiglio di leggere prima i commenti di quei file.
 * Qui commenterò solo le differenze.
 * 
 * La procedura è la stessa di img2wav, tuttavia:
 * Ad ongi picco degli oscillatori la frequenza è impostata a caso con scarto diqualche hertz.
 * I canali non sono equidistanti. In questo modo i battimenti sono mitigati al meglio.
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

function createFreq($sampleRate,$fr,$nPeak,$rand) {	// Crea un oscillatore
	global $PIx2;
	$pi = $PIx2;

	return array(
		'sr'	=>	$sampleRate,	// Frequenza di campionamento
		'al'	=>	$pi*$nPeak,		// Radiante corrente.
		'pi'	=>	$pi*$nPeak,		// Pigreco (possono essere fatte più onde prima di cambiare la frequenza, tanto se superi il valore di 2*PI sin e cos non si scandalizzano.
		'st'	=>	$pi*$nPeak,		// Incremento in radianti.
		'fr'	=>  $fr,			// Frequenza.
		'r'		=>	$rand)			// Margine casuale in hertz.
		;
	}
	
function oscillator(&$osc) {		// Oscillatore.
	global $PIx2;
	
	if ($osc['al']>=$osc['pi']) {
		$f =$osc['fr'] + mt_rand(-$osc['r'],$osc['r']); 
	        $r = $osc['sr'] / $f;
	        $r = $PIx2 / $r;
		$osc['al'] = 0;
		$osc['st'] = $r;
		}
	
	$y= floor(30000 * sin($osc['al']));
	$osc['al']+=$osc['st'];
	return $y; // Ritorna direttamente un valore da -30000 a 30000.
	}	

function valu($x) {
	if (!is_numeric($x)) die("\nValore non valido.\n");
	$x=floatval($x);
	if ($x<1) die("\nValore troppo basso.\n");
	return $x;
	}

$PIx2 = 2*pi();	// Si è globale.
	
$par = getopt("f:i:r:b:s:p:ho:w:a:z:kBd:e:m",array('ppx:','auto'));

if ($par===false or isset($par['h']) or @$argv[1]=='-?' or count($argv)<2) {
	echo "Trasmette un'immagine come messaggio spettrale.\n(Questo è il migliore).\n\n";
	echo "img2sound -i <imageFile.png> [ -w <bandWidth> ] [ -z <n> ] [ -k ]\n";
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
	echo "  -d\tImposta il range di sotto/frequenze fruscii.\n";
	echo "  -e\tImposta il numero di picchi stabili.\n";
	echo "  -m\tNon normalizza il segnale.\n";
	echo "  --auto\tCalcola tutto automaticamente.\n";
	echo "  -a\tImposta l'offset di fase tra le frequenze vicine.\n\n";
	exit;
	}

if (!@$par['o']) die("Manca -o\n");
if (!@$par['i']) die("Manca -i\n");

// Parametri di defaut.
$randFreq=10;				// Margine di casualità in hertz.
$nPeak=10;					// Numero di picchi prima di cambiare frequenza.
$sampleRate=44100;			// Frequenza di campionamento.
$baseFreq=500;				// Frequenza più bassa (di partenza).
$stepFreq=500;				// Distanza tra i canali (circa).
$pixelFreq=200;				// Lunghezza dei pixel in hertz (lcock).
$zMax=1;					// Allargamento dei pixel.
$freq=array();				// Array di oscillatori.
$norm=!isset($par['m']);	// Normalizzazione automatica del file.

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
if (isset($par['d'])) $randFreq = valu($par['d']);
if (isset($par['e'])) $nPeak = valu($par['e']);

$fout=fopen($par['o'],'w') or die("\nErrore file di uscita.\n");
WaveHeader($fout,$sampleRate);

if ($baseFreq>($sampleRate/2)) die("Parametri di frequenza non validi: base > bandwidth\n");

if (isset($par['w'])) {
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
	$freq[$i] = createFreq($sampleRate,$f,$nPeak,$randFreq);
	}

$sndWidth= $imgWidth*$pixXx;
$sndHeight=$fontHeight*30000;

echo "Dim.: $sndWidth x $fontHeight x $sndHeight x $zMax\n";
$cpercm=-1;
$oldst='...';
echo $oldst;
$max=10;
for ($x = 0 ;$x<$sndWidth;$x++) {
	if (!isset($par['k'])) $q=0;
	for ($z = 0;$z<$zMax;$z++) {
		if (isset($par['k'])) $q=0;
		for ($y = 0;$y<$fontHeight;$y++) {
			$imgy=($fontHeight-1)-$y;
			$imgx=floor($x/$pixXx);
			
			$rgb = imagecolorat($im, $imgx, $imgy);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$amp = ($r+$g+$b)/765;
			/// questo è fine a se stesso: if (!isset($par['S'])) $ja = ($amp / 8); else $ja=8;
			$peak1=oscillator($freq[$y]);
			$peak1=intval($peak1 * $amp);
			$q+=$peak1;
			}
		
		$q=intval(($q/$sndHeight)*30000);	// Valore PCM grezzo.
		if ($norm) {	// Algoritmo di normalizzazione (prentende una riga verticale all'inizio dell'immagine, vedi sync.png).
		        $y=abs($q);
		        if ($y>$max) $max=$y;
		        $q=floor( ($q/$max)*30000);
			}
			
		fwrite($fout, pack('v',$q));
		}
		
	// Progressione:
	$cperc = round( ($x/$sndWidth)*100,2);
	list($cp0,$cp1)=explode('.',$cperc.'.');
	$cperc=str_pad($cp0,3,' ',STR_PAD_LEFT).'.'.str_pad($cp1,2,'0',STR_PAD_LEFT);
	if ($cpercm!=$cperc) {
		$cpercm=$cperc;
		echo str_pad('',strlen($oldst),chr(8));
		$oldst=" $cperc % ... ";
		echo $oldst;
		}
	}
WaveHeader($fout,$sampleRate);
fclose($fout);
imagedestroy($im);
echo str_pad('',strlen($oldst),chr(8));
echo " 100% Ok\t\n";
?>
