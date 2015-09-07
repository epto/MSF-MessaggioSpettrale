#!/usr/bin/php
<?php
/*
 * font-edit
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
	echo "@CH $ch ; 0x".dechex($ch)."\n";
	for ($y=0;$y<$fontHeight;$y++) {
		for ($x=0;$x<8;$x++) {
			$b = $map[$x][($fontHeight-1)^$y];
			echo $b ? '█' : ' ';
			}
		echo "\n";
		}
	echo "\n";
	}

function conv($st) {
	global $fontHeight;
	$st=strtoupper($st);
	$x = trim($st,'C ');
	list($x,$y)=explode('.',$x.'.0');
	if (strpos($st,'C')!==false) {
		if (abs($y)>$fontHeight) die("Errore nel parametro `$st`: Dopo il punto può essere tra: -$fontHeight e $fontHeight\n");
		if ($x<0 or $x>255) die("Errore nel parametro `$st`: Il carattere può essere tra 0 e 255\n");
		$bp = ($x * $fontHeight + $y);
		if ($bp> $fontHeight*256) die("Errore nel parametro `$st`: fuori da un indirizzo valido.\n");
		return $bp;
		} else {
		$bp = intval($x);
		if ($bp> $fontHeight*256) die("Errore nel parametro `$st`: fuori da un indirizzo valido.\n");
		return $bp;
		}
	}

function fontProc(&$font,$par) {
	global $fontHeight;
	$mx = 256*$fontHeight;
	
	foreach (array('movsb','stosb','lodsb') as $k) {
		if (isset($par[$k]) and !is_array($par[$k])) $par[$k] = array($par[$k]);
		}

	if (isset($par['movsb'])) {
		foreach($par['movsb'] as $op) {
			list($a,$b,$c)=explode(',',$op.',0C,0C');
			$a = conv($a);
			$b = conv($b);
			$c = conv($c);
			for ($i = 0; $i<$c; $i++) {
				$font[$b++] = $font[$a++];
				if ($b>=$fontHeight or $a>=$fontHeight) die("Errore: Operazione uscita dal range valido: `$op`\n");
				}
			}
		}
		
	if (isset($par['stosb'])) {
		foreach($par['movsb'] as $op) {
			list($a,$b,$c)=explode(',',$op.',,');
			if ($b=='' or $c=='') die("Errore: Siuntassi su `$op`\n");
			$a = conv($a);
			$b = conv($b);
			$out='';
			for ($i = 0 ; $i<$b; $i++) {
				$out.=$font[$a++];
				if ($a>=$fontHeight) die("Errore: Operazione uscita dal range valido: `$op`\n");
				}
			}
		file_put_contents($c,$out) or die("Stosb: Errore sul file di output.\n");
		}
	
	if (isset($par['lodsb'])) {
		foreach($par['lodsb'] as $op) {
			list($a,$b)=explode(',',$op.',');
			if ($b=='' or $b=='') die("Errore: Siuntassi su `$op`\n");
			$a = conv($a);
			$in = file_get_contents($b) or die("Lodsb: Errore sul file di input.\n");
			$j = strlen($in);
			for ($i = 0 ; $i<$j; $i++) {
				$font[$a++]=$in[$i];
				if ($a>=$fontHeight) die("Errore: Operazione uscita dal range valido: `$op`\n");
				}
			}
		}
	
	}

// Uso getopt, non è il metodo migliore. Usare con cura!
$par = getopt("i:u:o:p:h:",array('dump:','update:','movsb:','lodsb:','stosb:'));

// Guida con -h -? oppure senza argomenti.
if ($par===false or isset($par['h']) or @$argv[1]=='-?' or count($argv)<2) {
	echo "Strumento di manipolazione dei font.\n\n";
	echo "font-edit  [ -h <fontH> ] { -d <font> | -u <textFont> -o <fontFile> }\n";
	echo "font-edit  [ -h <fontH> ] { --dump <font> | --update <font> }\n";
	echo "font-edit [ -h <fontH> ] -i <font> -o <font> [ --movsb <op> ] [ --lodsb <op> ]\n";
	echo "          [ --stosb <op> ]\n\n";
	echo "  -d --dump    Estrae il font come file TXT\n";
	echo "  -u --update  Converte un font di testo in file binario.\n";
	echo "  -o           Imposta il file di uscita.\n";
	echo "  -i           Carica un font per rielaborarlo.\n";
	echo "  -h { 8|16 }  Forza l'altezza dei caratteri (usare con le\n";
	echo "               seguenti opzioni.\n";
	echo "  --movsb      Sposta una stringa di byte nel font.\n";
	echo "     Parametri: da,a,lunghezza\n\n";
	echo "  --lodsb      Carica i caratteri da un file.\n";
	echo "     Parametri: da,file\n\n";
	echo "  --stosb      Esporta i caratteri su un file.\n";
	echo "     Parametri: da,lunghezza,file\n\n";
	echo "               Gli indirizzi sono espressi in byte, oppure con\n";
	echo "               l'aggiunta del carattere C possono essere espressi\n";
	echo "               in caratteri e byte dal carattere (separati dal punto.)\n";
	echo "     Esempio:\n";
	echo "     1 = Secondo byte.\n";
	echo "     1C = Carattere 0x01\n";
	echo "     1.4C = Carattere 0x01 + 4 byte\n";
	echo "     1.-4C = Carattere 0x01 - 4 byte\n";
	echo "     Ogni byte corrisponde ad una riga del carattere.\n";
	echo "     N.B.: I file di testo sono in formato: UTF-8 senza BOM, acapo = NL\n";
	echo "     N.B.: Puoi usare più istruzioni movsb, stosb, lodsb, ma l'ordine di\n";
	echo "           esecuzione è sempre: movsb, stosb, lodsb\n\n";
	exit;
	}

if (isset($par['d']) and isset($par['dump'])) die("C'è qualquadra che non cosa!\n");
if (isset($par['u']) and isset($par['update'])) die("C'è qualquadra che non cosa!\n");
if (isset($par['i']) and (isset($par['d']) or isset($par['u']) or isset($par['dump']) or isset($par['update']))) die("C'è qualquadra che non cosa!\n");

if (isset($par['update'])) $par['u'] = $par['update'];
if (isset($par['dump'])) $par['d'] = $par['dump'];
if (@!$par['o']) die("Manca -o\n");
if (isset($par['d']) and isset($par['u'])) die("C'è qualquadra che non cosa!\n");

if (isset($par['d'])) {
	$font = file_get_contents($par['d']) or die("\nErrore nel file del font!\n");
	$fontHeight= strlen($font) >=3072 ? 16: 8; // 8x256 byte = font 8x8, 16x256 byte = font 8x16. Ho messo una via di mezzo perchè alcuni file hanno roba alla fine.
	if (isset($par['h']) and $par['h']==8) $fontHeight=8;
	if (isset($par['h']) and $par['h']==16) $fontHeight=16;
	fontProc($font,$par);
	ob_start();
	echo "@FH $fontHeight\n";
	for ($a = 0;$a<256;$a++) {
		$map = charBmp($font,$a);
		showChar($map,$a);
		}
	file_put_contents($par['o'],ob_get_clean());
	exit;
	}

if (isset($par['u'])) {
	$txt = file($par['u']) or die("\nErrore nel file di testo!\n");
	$fontHeight = 8;
	if (isset($par['h']) and $par['h']==8) $fontHeight=8;
	if (isset($par['h']) and $par['h']==16) $fontHeight=16;
	$font = str_pad('',$fontHeight*256,chr(0));
	$curCh=0;
	$curBmp=str_pad('',$fontHeight,chr(0));
	$curY=0;
	$test0=false;
	$fase=0;
	foreach($txt as $lin => $li) {
		$line=$lin+1;
		$li=trim($li,"\t\r\n");
		$li=str_replace('█','*',$li);
		if (strlen($li)) {
			
			if ($li[0]=='@') {
				list($a,$b) = explode(' ',$li.' ');
				
				if ($a == '@FH') {
					if ($test0 or $fase!=0) die("Riga $line: Non era atteso @FH\n");
					$fontHeight = intval($b);
					if ($fontHeight!=8 and $fontHeight!=16) die("Riga $line: Altezza del font non supportata.\n");
					$font = str_pad('',$fontHeight*256,chr(0));
					}
					
				if ($a == '@CH') {
					if ($fase!=0) die("Riga $line: Non era atteso @CH\n");
					$b = intval($b) & 255;
					$test0=true;
					$curY=0;
					$fase=1;
					$curCh=$b;
					$curBmp=str_pad('',$fontHeight,chr(0));
					}
				continue;
				}
			
			if (strlen($li)!=8) die("Riga $line: Doveva essere lunga 8 caratteri.\n");
			if ($curY >= $fontHeight) die("Riga $line: Il carattere $curCh doveva finire qui.\n");
			if ($fase!=1) die("Riga $line: Non era atteso un carattere in questo punto.\n");
			
			$byte=0;
			for ($x = 0 ; $x<8;$x++) {
				$ch = $li[$x];
				if ($ch!=' ') $byte|= 1<<(7^$x);
				}
			$bp = ($curCh * $fontHeight) + $curY;
			$font[$bp] = chr($byte);
			$curY++;
			
			} else { //strlen = 0
			$fase=0;
			}
		}
		
	fontProc($font,$par);
	file_put_contents($par['o'],$font) or die("\nErrore sul file di output.\n");
	}

if (isset($par['i'])) {
	$font = file_get_contents($par['i']) or die("\nErrore nel file del font!\n");
	$fontHeight= strlen($font) >=3072 ? 16: 8; // 8x256 byte = font 8x8, 16x256 byte = font 8x16. Ho messo una via di mezzo perchè alcuni file hanno roba alla fine.
	if (isset($par['h']) and $par['h']==8) $fontHeight=8;
	if (isset($par['h']) and $par['h']==16) $fontHeight=16;
	fontProc($font,$par);
	file_put_contents($par['o']);
	}
?> 