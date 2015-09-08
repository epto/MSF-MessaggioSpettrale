# MSF-MessaggioSpettrale

Questi script trasformano scritte ed immagini in messaggi spettrali fantasma che possono essere visti con lo 
spettrografo.
La modalità è simile al sistema usato da Aphex Twin.

Vedere questo per capire di che si tratta:
https://www.youtube.com/watch?v=M9xMuPWAZW8

I file sono salvati come wave, si possono produrre anche file di tipo raw a 16 bit.
Questi script funzionano tramite php a riga di comando e possono richiedere le librerie gd per l'elaborazione delle 
immagini.

Per leggere i messaggi si può usare anche Audacity con la visualizzazione "spettro".

Per scrivere testi sui file wave usare: text2wav.php

Per scrivere immagini nello spettro: bmp2wav.php (monocromatico), img2wav.php (grigi), img2sound.php (il migliore).

Sono inclusi alcuni set di caratteri disegnati da me tra il 1992 ed il 1994, fatene buon uso.

Possono essere modificati tramite il tool font-edit.php

Questi script sono stati usati per produrre i messaggi spettrali durante l'ESC:
http://endsummercamp.org

I messaggi possono essere sovrapposti, basta che la frequenza di partenza sia diversa. Quindi potete editare la figura finale come progetto Audacity multi traccia e poi esportarlo.

## Alcune considerazioni sul utilizzo

Se volete trasmettere il file via radio (LPD/PMR/CB) si consiglia di restare su una banda utilizzata di 
4000Hz (-w 4000). Stesso discorso per inviare messaggi per telefono.

Se volete inviare i messaggi in FM stereo la banda consigliata è 15Kz (per non fare interferenza sulla sotto-portante stereo).

Per passare tramite aria/audio da microfono ad altoparlante scrauso, consiglio di non superare gli 8000 Hz.

Se usi apparati di buona qualità vai tranquillo sui 14000Hz.

Se si trasmettono messaggi radio in alta frequenza ci si può sbizzarire (se non c'è lo stereo FM di mezzo).

Si può scegliere la frequenza più bassa con -b, si può scegliere la distanza dei canali con -s, e stabilire la banda massima con -w. Su alcuni script (quelli per le immagini) si può usare anche il parametro --auto che fa stare tutto nella frequenza di campionamento senza tener conto di alcuna raccomandazione sopracitata.

Si possono visionare i caratteri del font così come sono tramite i sorgenti dei font.
Tramite l'opzione -e si può impostare di usare i caratteri speciali con l'escape su text2wave.php

Per fare un messaggio con audio correlato conviene prosegure così:
Ricampionare l'audio di sottofondo a frequenza di campionamento doppia rispetto alla frequenza di base della scritta (altezza).
Poi ricampionare successivamente a 44100Hz. Generare la scrita oppure l'immagine con -b. Sommare i file con Audacity ed il gioco è fatto.

Le immagini possono essere alte da 8 pixel al 128 pixel. (Consigliati 64px o 128px).

Le scritte e le immagini possono essere allargate, alzate, stirate e/o spostate di frequenza liberamente.
I canali degli oscillatori non devono essere più vicini di 200Hz per mitigare gli abbattimenti di frequenza. Questi sono script molto semplici che non calcolano i battimenti.

Le immagini vanno riprocessate aumentando il contrasto e aumentandone la luminosità. Altrimenti su Audacity non si vedono bene.

## Ipotesi di protocollo

Si consiglia di mettere sempre sync.png all'inizio. 
In questo modo c'è una specie di monoscopio che consente di capire come deve essere impostato lo zoom per vedere bene il messaggio, se la dinamica è corretta ed anche le frequenze.

Con il parametro -P che implica -e si aggiungono i terminatori di protocollo (se supportati dai font).
Sono i simboli \\($START) e \\($STOP)

Tutti i font sono in codifica CP437, quindi occhio alle lettere accentate!

### Supporto unicode, simboli e caratteri.

É stato reimplementato il supporto font92. Si tratta di un tipo di font che avevo fatto nel 1992 quando programmavo le mie prime interfacce grafiche in turbo assembler e Quick Basic.

Adesso text2wav supporta anche le seguenti estensioni:

Usare il parametro -e

* Escape \\xNN per impostare un carattere.
* Escape \\(#NNNN) per i caratteri unicode.
* Escape \\(nomeSimbolo) per i siboli con il nome (se supportati dal font).
* Escape \\(_sottoFont) per impostare i sotto insiemi di caratteri (se supportati dal font).
* Supporto di font con più di 256 caratteri. (Il font RCUNI da 1628 caratteri).
* Rimappatura caratteri (per fon con codifiche strane).
* Importazione di font RAW specificando il puntatore.

Quest'ultima opzione ti permette di incorporare i font che desideri.

Se per esempio vuoi usare il font del Commodore 64, procurati la sua ROM. 

Una volta individuato il font puoi usare le opzioni:

font-edit.php -i ROMCODE.C64 -p 16384 --cp ISO-8859-1 -o C64.font -m 512 -h 8

Poi:

font-edit.php --dump C64.font -o C64.txt

Ricordati di rimappare i caratteri aggiungendo dei parametri al sorgente come mappa caratteri:

@MOD 3

@CHR #AAAA UBBBB

Dove AAAA è il carattere in ingresso su (ISO-8859-1) e BBBB è il carattere nel font.

Grazie all'opzione @MOD 3 puoi anche settare degli intervalli per rimappare serie di caratteri dandogli un nome.

Usa:

@CHR _nome UFFFF-UAAAA-UBBBB

Dove AAAA è il carattere di partenza, BBBB è il carattere che dovrebbe essere.

## Esempio per l'uso con i caratteri estesi:
```
php text2wav.php -e -f fonts/rcuni-8x16-1628.font -o test.wav -t "TEST \(_invAZ)TEST\(_inv)1234\(_invaz)test\(_) LOL \(fantasma)"
```

Buoni messaggi antanici a tutti e la supercazzola alla prematurata con scappellamento a sinistra.
