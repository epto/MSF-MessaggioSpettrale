# MSF-MessaggioSpettrale tools-font

Questi script modificano i set dei caratteri.

Questa directory è parte del progetto font92 e font97, progetti che usavo negli anni '90 per produrre e modificare i font 8x8 e 8x16.
All'epoca progettavo diverse GUI interattive (fin dal 1992) quindi avevo bisogno di disegnarmi i caratteri per un sistema operativo (ambiente di programmazione) che era fatto interamente in turbo assembler e Quick Basic 4.5.
In questa directory non è stato inserito alcun font editor per caratteri di larghezza superiore a 8 pixel perchè non è supportato dal Messaggio Spettrale.

I font possono essere creati e modificati tramite il loro codice sorgente, oppure possono essere convertiti in immagini. É utile usare la modalità con le bande di divisione che permettono di distinguere i margini dei caratteri.
Occorre sempre far attenzione alla larghezza dell'immagine.

Una buona idea per non sbagliare è di produrre un'immagine di una riga sola. Di solito font2img produce un'immagine con una griglia 16x16 di caratteri.
Ogni comando è munito di un proprio help.

## font2img.php 

Trasforma un file font in un'immagine png modificabile.

Usando l'opzione -n saranno introdotte delle bande laterali di separazione dei singoli caratteri.
Usando l'opzione --mktpl si può creare anche un template da impostare con il parametro --tpl su img2font.php per riconvertire il font (operazione inversa).

## font-edit.php

Compila e decompila un font da file binario a codice sorgente e viceversa.

## img2font.php

Trasforma un'immagine PNG a 24 bit in un file sorgente per i font.

N.B. Controllare il sorgente:

Per esempio 0x30 è il carattere "0", 0x40 è il carattere "@" e così via.
Se ci fossero problemi, prova a vedere se hai dimenticato il parametro --first oppure se la larghezza dell'immagine non è multipla della dimensione del carattere disegnato (con eventualmente una banda rosso scuro a destra).

É anche possibile indicare all'interno dei pixel dell'immagine dove andare a leggere tramite le opzioni --px e --py. Questo è utile usarlo in tutte quelle situazioni in cui i pixel stessi dei caratteri presentino un bordo.
L'immagine deve essere monocromatica (bianco o nero) a 24 bits o scala di grigi a 256 colori.
Usando l'opzione -n sarà considerato 1 il valore del colore superiore a 63. Questa opzione si usa anche per rimuovere la griglia dei caratteri.
Può anche essere che tra un carattere e l'altro ci siano degli spazi. É possibile specificare il taglio, o questo spazio, tramite le opzioni --icw e --ich.
```
Vedere font-images/example/lcd.png
```
In questo caso i pixel hanno un bordo ed anche i caratteri hanno a loro volta un bordo. Specificando la dimensione dei caratteri nell'immagine, la dimensione dei pixel ed infine il punto da leggere relativo alla posizione del singolo pixel, si può ricreare il font senza dover rielaborare l'immagine.
Per qunato riguarda il margine interno dell'immagine stessa, occorre tagliarla in modo adeguato altrimenti si scombina la sequenza dei caratteri.

## font-images

Questa directory contiene delle immagini dei font con i relativi teplate estratte con font2img.php

Vedere sotto directory img.

## font-sources

Questa direcotry tiene i codici sorgente dei font.
