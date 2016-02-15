# MSF-MessaggioSpettrale tools-font

Questi script modificano i set dei caratteri.

Questa directory è parte del progetto font92 e font97, progetti che usavo negli anni '90 per produrre e modificare i font 8x8 e 8x16.
All'epoca progettavo diverse GUI interattive (fin dal 1992) quindi avevo bisogno di disegnarmi i caratteri per un sistema operativo (ambiente di programmazione) che era fatto interamente in turbo assembler e Quick Basic 4.5.
In questa directory non è stato inserito alcun font editor per caratteri di larghezza superiore a 8 pixel perchè non è supportato dal Messaggio Spettrale.

I font possono essere creati e modificati tramite il loro codice sorgente, oppure possono essere convertiti in immagini. É utile usare la modalita con le bande di divisione che permettono di distinguere i margini dei caratteri.
Occorre sempre far attenzione alla larghezza dell'immagine.

Una buona idea per non sbagliare è produrre un'immagine di una riga sola. Di solito font2img produce un'immagine con una griglia 16x16 di caratteri.
Ogni comando è munito di un proprio help.

## font2img.php 

Trasforma un file font in un'immagine png modificabile.

Usando l'opzione -n saranno introdotte delle bande laterali di separazione dei singoli caratteri.
Usando l'opzione --mktpl sai può creare anche un template da impostare con il parametro --tpl su img2font.php per riconvertire il font.

## font-edit.php

Compila e decompila un font da file binario a codice sorgente.

## img2font.php

Trasforma un'immagine PNG a 24 bit in un file sorgente per i font.

N.B. Controllare il sorgente:

Per esempio 0x30 è il carattere "0", 0x40 è il carattere "@" e così via.
Se ci fossero problemi, prova a vedere se hai dimenticato il parametro --first oppure se la larghezza dell'immagine non è multipla della dimensione del carattere disegnato (con eventualmente una banda rosso scuro a destra).

É anche possibile indicare all'interno dei pixel dell'immagine dove andare a leggere tramite le opzioni --px e --py. Questo è utile usarlo in tutte quelle situazioni in cui i pixel stessi dei caratteri presentino un bordo.
L'immagine deve essere monocromatica (bianco o nero) a 24 bits o scala di grigi a 256 colori.
Usando l'opzione -n sarà considerato 1 il valore del colore superiore a 63. Questa opzione si usa anche per rimuovere la grilia dei caratteri.
Può anche essere che tra un carattere e l'altro ci siano degli spazi. É possibile specificare il taglio o questo spazio tramite le opzioni --icw e --ich.
```
Vedere font-images/example/lcd.png
```
In questo caso i pixel hanno un bord ed anche i caratteri hanno a loro volta un bordo. Specificando la dimensione dei caratteri nell'immagine, la dimensione dei pixel ed infine il punto da leggere relativo alla posizione del singolo pixel, si può ricreare il font senza dover rielaborare l'immagine.
Per qunato riguarda il margine interno dell'immagine invece, occorre tagliarla in modo adeguato altrimenti si scombina la sequenza dei caratteri.

## font-images

Questa directory contiene delle immagini dei font con i relativi teplate estratte con font2img.php

## font-sources

Questa direcotry tiene i codici sorgente dei font.

## Comandi per i file sorgente:

```
Commento @//
Informazione da mettere nel file @INF
CodePage oppure charset @CP
Larghezza caratteri @FW
ALtezza caratteri @FH
Versione modello @VER (1 o 2)
Modalità font @MOD
Nome del font @NAME
Numero massimo di caratteri @MAX
```

### Definire i caratteri con un nome:
```
Comando @CHR nome codice
```

Il codice è sempre nella forma U1234 dove 1234 è il numero del carattere nel font espresso in esadecimale e sempre in 4 cife.

Esempio:
```
@CHR pentola	U000F
```
### Definire le sequenze con un nome:

Comando @CHR nome sequenza

Il nome inizia per ($) se si tratta di $START e $STOP che sono sequenze di apertura e chiusura del messaggio se supportato dal font.
Solitamente per font conenenti immagini è utile mettere un quadrato pieno, un cerchio, e un carattere a scacchi per permettere all'utente di capire se il messaggio è letto correttamente e senza distorisioni.

La sequenza è un insieme di codici separati dal carattere "-". I codici iniziano per U e sono il numero del carattere nel font espresso come 4 cifre esadecimali.

Esempio:
```
@CHR $START    U00DB-U0002-U00DB-U0020-U00F3-U0020
@CHR $STOP     U0020-U00DB-U0002-U00DB
```

### Rimappare i caratteri:
```
Comando @CHS carattere codice
```

Il carattere è inserito direttamente, il codice è un numero esadecimale di 4 cifre (numero di carattere nel font).

### Rimappare gruppi di caratteri:
```
Comando @RMAP nome     codice inizio fine
```
Il nome è il nome del gruppo (inizia per _), il codice è il numero del primo carattere nel font, espresso come 4 cifre esadecimali.
I valori inizio e fine sono espressi in due cifre esadecimali. 

Quando sono attivati con \\(_gruppo) tutti i caratteri a partire da "inizio" fino a "fine" sono rimappati a partire da "codice".
```
Esempio: @RMAP _invaz    0450 61 7A
```

### Bitmap caratteri:

Inizio del carattere @CH (numero decimale)

I caratteri sono struttirati come insieme di righe lunghe 8 spazi.

Il bit 0 è lo spazio, il bit 1 è il carattere "*" oppure "█".

La fine del carattere è marcata con una riga vuota.

L'untima riga del file che è la fine dell'ultimo carattere è sempre una riga vuota.

Codifica: UTF-8 senza BOM.

## Esempio di intestazione di un font:

```
@CP CP850
@FH 8
@FW 8
@MAX 256
@VER 2
@NAME A1
@CHR ochio     U0002
@CHR $START    U00DB-U0002-U00DB-U0020-U00F3-U0020
@CHR $STOP     U0020-U00DB-U0002-U00DB
@INF (C) 1992 by EPTO (License GPL3)
@CH 0 ; 0x0
        
 ██████ 
 ██████ 
 ██████ 
 ██████ 
 ██████ 
 ██████ 
        
```