# Sorgenti dei font.

In questa directory ci sono i codici sorgente dei font usati dal messaggio spettrale.

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
