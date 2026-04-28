# Lavori fatti oggi sul gestionale - 28/04/2026

## Flusso credenziali paziente

1. Quando crei le credenziali viene chiesta prima la relazione (io stesso / genitore / tutore) e i campi si adattano automaticamente
2. Risolto bug: il salvataggio in modalita "io stesso" non funzionava silenziosamente
3. Generatore password robusta integrato (8-20 caratteri con maiuscola, numero e carattere speciale)
4. Codice fiscale duplicato: dialog chiaro con i dati dell'account esistente e azioni rapide (vai alla scheda / collega paziente)
5. Lista degli account gia' collegati al paziente in cima alla pagina con bottoni Modifica e Rigenera
6. In modalita "io stesso" i campi profilo non sono piu' pre-compilati (evita confusione, vengono comunque salvati correttamente lato server)
7. Dopo aver creato un paziente vieni rimandato direttamente alla sua scheda invece che alla lista
8. Reset password: la sezione "Token Mobile" non appare per gli utenti che non usano l'app

## Flusso account paziente

9. Pagina /patient/accounts: nuovo bottone "Nuovo Account" con modale di ricerca paziente (rispetta permessi)
10. Pagina /patient/accounts: bottoni Modifica e Rigenera credenziali, layout uniforme con il resto dell'app
11. Account "Io stesso" del paziente: ora puo' davvero loggarsi nell'app mobile e appare nelle liste (prima invisibile)
12. Modale "Modifica account" sostituita da una pagina dedicata con form completo
13. Modale "Nuovo Account" rifatta con SweetAlert2
14. Possibilita di cambiare relazione e autorita parentale dei pazienti collegati direttamente da Modifica Account
15. Notifiche pazienti: ora arrivano solo agli account con autorita parentale (prima a tutti i familiari)

## Calendario

16. Vista per il coordinatore: vede solo terapisti e specializzazioni del proprio gruppo
17. Coordinatore senza permesso manage_calendar: vista solo lettura, niente toggle/selettori
18. Cross-origin del calendario React: il backend ora riconosce sempre l'utente loggato (prima qualunque azione veniva attribuita all'admin di default)
19. Bottoni vista (Settimana/Giorno) duplicati nel calendario: rimossa la duplicazione
20. Spostamento appuntamenti: niente piu errori quando si tenta di sovrapporre a un appuntamento privato

## Sidebar e UI

21. Sidebar: voci "Coordinatori" e "Gruppi Coordinatori" accorpate in un'unica voce con sub-item
22. Voci "I Miei Pazienti / I Miei Terapisti" visibili solo ai coordinatori (admin/manager non le vedono piu)
23. Vista Notifiche e dettaglio Notifica completamente ridisegnati piu' chiari
24. Pagina 404 tradotta in italiano e con brand "San Luca"
25. Cards statistiche piu' compatte su Notifiche e Document Request
26. Risolto doppio breadcrumb sulla pagina Modifica Coordinatore
27. Pagina /patient/my-group: nuova colonna "Terapista del gruppo"

## Pagina I Miei Permessi (nuova)

28. Nuova pagina accessibile dal menu utente con ricerca, ruoli, permessi derivati e permessi extra
29. Filtra solo i permessi attivi (era 101, ora corretto a 44)
30. Pagina permessi ruolo: aggiunta barra di ricerca e filtri rapidi per orientarsi tra i 100+ permessi

## Permessi e RBAC

31. Aggiunto permesso "Visualizza pazienti del gruppo" per i coordinatori
32. Permesso "Accesso app mobile": rimosso dalla lista permessi extra dei ruoli che non usano l'app

## Bug fix tecnici

33. Errore "Setting::name" sul report attivita terapista
34. Pagina /patient/my-group mostra il vero nome del terapista (era "Terapista #16")
35. Bug ricerca account paziente: nome tabella corretto
