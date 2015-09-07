# MSF-MessaggioSpettrale

Questi script trasformano scritte ed immagini in messaggi spettrali fantasma che possono essere visti con lo 
spettrografo.
La modalità è sibile al sistema usato da Aphex Twin.

Vedi questo per capire di che si tratta:
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

Se vuoi inviare i messaggi in FM stereo la banda consigliata è 15Kz (per non fare interferenza sulla sotto-portante stereo).

Per passare tramite aria/audio da microfono ad altoparlante scrauso, ti consiglio di non superare gli 8000 Hz.

Se usi apparati di buona qualità vai tranquillo sui 14000Hz.

Se trasmetti messaggi radio in alta frequenza puoi sbizzarirti come vuoi (se non c'è lo stereo FM di mezzo).

Puoi scegliere la frequenza più bassa con -b, puoi scegliere la distanza dei canali con -s, e stabilire la banda massima con -w. Su alcuni script (quelli per le immagini) puoi usare anche il parametro --auto che fa stare tutto nella frequenza di campionamento senza tener conto di alcuna raccomandazione sopracitata.

Si possono visionare i caratteri del font così come sono tramite i sorgenti dei font.
Tramite l'opzione -e puoi impostare di usare i caratteri speciali con l'escape su text2wave.php

Se vuoi fare un messaggio con audio correlato ti conviene prosegure così:
Ricampioni l'audio di sottofondo a frequenza di campionamento doppia rispetto alla frequenza di base della scritta (altezza).
Poi lo ricampioni successivamente a 44100Hz. Generi la scrita oppure l'immagine con -b. Sommi i file con Audacity ed il gioco è fatto.

Le immagini possono essere alte da 8 pixel al 128 pixel. (Consigliati 64px o 128px).

Le scritte e le immagini possono essere allargate, alzate, stirate e/o spostate di frequenza liberamente.
I canali degli oscillatori non devono essere più vicini di 200Hz per mitigare gli abbattimenti di frequenza. Questi sono script molto semplici che non calcolano i battimaneti.

Le immagini vanno riprocessate aumentando il contrasto e dennendole di luminosità chiara. Altrimenti su Audacity non si vedono bene.

## Ipotesi di protocollo

Si consiglia di mettere sempre sync.png all'inizio. 
In questo modo c'è una specie di monoscopio che consente di capire come deve essere impostato lo zoom per vedere bene il messaggio, se la dinamica è corretta ed anche le frequenze.

Per le scrite tramite il parametro -e si consiglia di iniziare e finire con:
\xdbO\xdb \xf3

e

\xf3 \xdbO\xdb

La lettera "O" fa da monoscopio, il \xdb è il carattere pieno così si vedono tutti i canali e \xf3 è il fantasmino.
(Se usate il font a1-8x8.font) altrimenti evitate \xf3.

Tutti i font sono in codifica CP437, quindi occhio alle lettere accentate!

### Un po di caratteri belli per il font a1-8x8.font

\x02 Occhio (cerchio per monoscopio con punto i mezzo).

\x08 Omino

\x09 Donnetta

\x0d Gatto

\x16 Stella

\x8c Pistola

\x8e Macchina

\x9b Smile

\x9d Smile nero

\xa2 Cestino della spazzatura

\xa4 Computer

\xa6 Orologio

\xa7 Divieto

\xa8 Pericolo

\xad Informazioni (I)

\xb7 Esclamazione

\xf2 Vampiro

\xf3 Fantasma


Buoni messaggi antanici a tutti e la supercazzola alla prematurata con scappellamento a sinistra.
