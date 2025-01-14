<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'ardora', language 'it'
 *
 * @package    mod_ardora
 * @copyright  2025 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['ardora:addinstance'] = 'Aggiungi un nuovo ardora';
$string['ardora:exportardora'] = 'Esporta ardora';
$string['ardora:grade'] = 'Valuta le consegne di Ardora';
$string['ardora:view'] = 'Visualizza ardora';
$string['ardoracontent'] = 'File e sottocartelle';
$string['ardoradetails_sizedate'] = '{$a->size} {$a->date}';
$string['ardoradetails_sizetype'] = '{$a->size} {$a->type}';
$string['ardoradetails_sizetypedate'] = '{$a->size} {$a->type} {$a->date}';
$string['ardoradetails_typedate'] = '{$a->type} {$a->date}';
$string['cachedef_courseid_cache'] = 'Cache per memorizzare gli ID dei corsi nella sessione.';
$string['clicktodownload'] = 'Fai clic sul link {$a} per scaricare il file.';
$string['clicktoopen2'] = 'Fai clic sul link {$a} per visualizzare il file.';
$string['completionpassgrade'] = 'Lo studente deve raggiungere la votazione minima per completare questa attività.';
$string['completionpassgrade_help'] = 'Se questa opzione è abilitata, l\'attività sarà considerata completata solo quando lo studente raggiunge la votazione minima stabilita.';
$string['configdisplayoptions'] = 'Seleziona tutte le opzioni che dovrebbero essere disponibili, le impostazioni esistenti non saranno modificate. Tieni premuto il tasto CTRL per selezionare più campi.';
$string['configframesize'] = 'Quando una pagina web o un file caricato viene visualizzato all\'interno di un frame, questo valore è l\'altezza (in pixel) del frame superiore (che contiene la navigazione).';
$string['configparametersettings'] = 'Questo imposta il valore predefinito per il pannello delle impostazioni dei parametri nel modulo di aggiunta di nuovi ardoras. Dopo la prima volta, questa diventa una preferenza individuale dell\'utente.';
$string['configpopup'] = 'Quando aggiungi un nuovo ardora che può essere mostrato in una finestra popup, questa opzione dovrebbe essere abilitata per impostazione predefinita?';
$string['configpopupdirectories'] = 'Le finestre popup dovrebbero mostrare i link alle directory per impostazione predefinita?';
$string['configpopupheight'] = 'Quale dovrebbe essere l\'altezza predefinita per le nuove finestre popup?';
$string['configpopuplocation'] = 'Le finestre popup dovrebbero mostrare la barra degli indirizzi per impostazione predefinita?';
$string['configpopupmenubar'] = 'Le finestre popup dovrebbero mostrare la barra dei menu per impostazione predefinita?';
$string['configpopupresizable'] = 'Le finestre popup dovrebbero essere ridimensionabili per impostazione predefinita?';
$string['configpopupscrollbars'] = 'Le finestre popup dovrebbero essere scorrevoli per impostazione predefinita?';
$string['configpopupstatus'] = 'Le finestre popup dovrebbero mostrare la barra di stato per impostazione predefinita?';
$string['configpopuptoolbar'] = 'Le finestre popup dovrebbero mostrare la barra degli strumenti per impostazione predefinita?';
$string['configpopupwidth'] = 'Quale dovrebbe essere la larghezza predefinita per le nuove finestre popup?';
$string['contentheader'] = 'Contenuto';
$string['courseidnotfound'] = "L'ID del corso non è stato trovato.";
$string['displayoptions'] = 'Opzioni di visualizzazione disponibili';
$string['displayselect'] = 'Visualizzazione';
$string['displayselect_help'] = 'Questa impostazione, insieme al tipo di file e se il browser consente l\'embedding, determina come viene visualizzato il file. Le opzioni possono includere:

 * Automatico - Viene selezionata automaticamente la migliore opzione di visualizzazione per il tipo di file
 * Includi - Il file viene visualizzato all\'interno della pagina sotto la barra di navigazione insieme alla descrizione del file e a eventuali blocchi
 * Forza il download - Viene richiesto all\'utente di scaricare il file
 * Apri - Viene visualizzato solo il file nella finestra del browser
 * In popup - Il file viene visualizzato in una nuova finestra del browser senza menu o barra degli indirizzi
 * In cornice - Il file viene visualizzato all\'interno di una cornice sotto la barra di navigazione e la descrizione del file
 * Nuova finestra - Il file viene visualizzato in una nuova finestra del browser con menu e barra degli indirizzi';
$string['displayselect_link'] = 'mod/file/mod';
$string['displayselectexplain'] = 'Scegli il tipo di visualizzazione, sfortunatamente non tutti i tipi sono adatti a tutti i file.';
$string['dnduploadardora'] = 'Crea file ardora';
$string['embedheight'] = 'Altezza';
$string['embedheightexplain'] = 'L\'altezza del frame di scena.';
$string['embedwidth'] = 'Larghezza';
$string['embedwidthexplain'] = 'La larghezza del frame di scena.';
$string['encryptedcode'] = 'Codice crittografato';
$string['filenotfound'] = 'File non trovato, ci dispiace.';
$string['filterfiles'] = 'Utilizza filtri sul contenuto del file';
$string['filterfilesexplain'] = 'Seleziona il tipo di filtraggio del contenuto del file, tieni presente che ciò potrebbe causare problemi con alcune applet Flash e Java. Assicurati che tutti i file di testo siano in codifica UTF-8.';
$string['filtername'] = 'Collegamento automatico ai nomi ardora';
$string['forcedownload'] = 'Forza download';
$string['framesize'] = 'Altezza della cornice';
$string['gradingoptions'] = 'Opzioni di valutazione';
$string['indicator:cognitivedepth'] = 'Profondità cognitiva del file';
$string['indicator:cognitivedepth_help'] = 'Questo indicatore si basa sulla profondità cognitiva raggiunta dallo studente in un ardora di file.';
$string['indicator:cognitivedepthdef'] = 'Profondità cognitiva del file';
$string['indicator:cognitivedepthdef_help'] = 'Il partecipante ha raggiunto questa percentuale di impegno cognitivo offerta dagli ardoras di file durante questo intervallo di analisi (Livelli = Nessuna visualizzazione, Visualizzazione)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Ampiezza sociale del file';
$string['indicator:socialbreadth_help'] = 'Questo indicatore si basa sull\'ampiezza sociale raggiunta dallo studente in un ardora di file.';
$string['indicator:socialbreadthdef'] = 'Ampiezza sociale del file';
$string['indicator:socialbreadthdef_help'] = 'Il partecipante ha raggiunto questa percentuale di impegno sociale offerto dagli ardoras di file durante questo intervallo di analisi (Livelli = Nessuna partecipazione, Partecipante da solo)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['invalidpassinggrade'] = 'Il voto minimo per superare deve essere un numero tra 0 e 100.';
$string['legacyfiles'] = 'Migrazione di file di corsi vecchi';
$string['legacyfilesactive'] = 'Attivo';
$string['legacyfilesdone'] = 'Completato';
$string['maximumgrade'] = 'Voto massimo';
$string['maximumgrade_help'] = 'Specifica il voto massimo che può essere raggiunto per questa attività.';
$string['modifieddate'] = 'Modificato {$a}';
$string['modulename'] = 'Ardora';
$string['modulename_help'] = 'Aiuto esplicativo qui';
$string['modulename_link'] = 'mod/ardora/view';
$string['modulenameplural'] = 'File';
$string['notmigrated'] = 'Questo tipo di ardora ereditato ({$a}) non è stato ancora migrato, ci dispiace.';
$string['optionsheader'] = 'Opzioni di visualizzazione';
$string['page-mod-ardora-x'] = 'Qualsiasi pagina del modulo file';
$string['passinggrade'] = 'Approvato';
$string['passinggrade_help'] = 'Voto minimo che un utente deve raggiungere per considerare l\'attività superata.';
$string['pluginadministration'] = 'Amministrazione del modulo file';
$string['pluginname'] = 'Ardora';
$string['popupardora'] = 'Questo ardora dovrebbe apparire in una finestra popup.';
$string['popupardoralink'] = 'Se non appare, fai clic qui: {$a}';
$string['popupheight'] = 'Altezza della finestra popup (in pixel)';
$string['popupheight_desc'] = 'Altezza predefinita della finestra popup (in pixel).';
$string['popupheightexplain'] = 'Specifica l\'altezza predefinita delle finestre popup.';
$string['popupwidth'] = 'Larghezza della finestra popup (in pixel)';
$string['popupwidth_desc'] = 'Larghezza predefinita della finestra popup (in pixel).';
$string['popupwidthexplain'] = 'Specifica la larghezza predefinita delle finestre popup.';
$string['printintro'] = 'Mostra descrizione ardora';
$string['printintroexplain'] = 'Mostrare la descrizione dell\'ardora sotto il contenuto? Alcuni tipi di visualizzazione potrebbero non mostrare la descrizione anche se abilitata.';
$string['privacy:metadata'] = 'Il plugin ardora file non memorizza dati personali.';
$string['search:activity'] = 'Ardora';
$string['selectmainfile'] = 'Seleziona il file principale facendo clic sull\'icona accanto al nome del file.';
$string['showdate'] = 'Mostra data di caricamento/modifica';
$string['showdate_desc'] = 'Mostrare la data di caricamento/modifica nella pagina del corso?';
$string['showdate_help'] = 'Mostra la data di caricamento/modifica accanto ai link al file.

Se ci sono più file in questo ardora, viene mostrata la data di caricamento/modifica del file principale.';
$string['showsize'] = 'Mostra dimensione';
$string['showsize_desc'] = 'Mostrare la dimensione del file nella pagina del corso?';
$string['showsize_help'] = 'Mostra la dimensione del file, come "3.1 MB", accanto ai link al file.

Se ci sono più file in questo ardora, viene mostrata la dimensione totale di tutti i file.';
$string['showtype'] = 'Mostra tipo';
$string['showtype_desc'] = 'Mostrare il tipo di file (ad esempio, "Documento Word") nella pagina del corso?';
$string['showtype_help'] = 'Mostra il tipo di file, come "Documento Word", accanto ai link al file.

Se ci sono più file in questo ardora, viene mostrato il tipo del file principale.

Se il tipo di file non è conosciuto dal sistema, non verrà mostrato.';
$string['uploadeddate'] = 'Caricato {$a}';
