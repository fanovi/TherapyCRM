/**
 * Sistema di gestione comunicazioni JavaScript
 * Gestisce le interazioni AJAX per segnare come lette, aggiornamenti, ecc.
 */
window.CommunicationSystem = (function () {
  "use strict";

  let config = {
    markReadUrl: "",
    markAllReadUrl: "",
    csrfToken: "",
  };

  /**
   * Inizializza il sistema
   */
  function init() {
    // Ottieni URLs dalle variabili JavaScript di Yii2
    config.markReadUrl = window.markReadUrl || "/communication/mark-read";
    config.markAllReadUrl =
      window.markAllReadUrl || "/communication/mark-all-read";
    config.csrfToken = $("meta[name=csrf-token]").attr("content") || "";

    bindEvents();
    console.log("Communication System initialized");
  }

  /**
   * Collega gli eventi
   */
  function bindEvents() {
    // Pulsante "Segna tutte come lette"
    $(document).on("click", "#mark-all-read-btn", handleMarkAllRead);

    // Pulsanti "Segna come letta" individuali
    $(document).on("click", ".mark-read-btn", handleMarkRead);

    // Auto-refresh ogni 5 minuti (opzionale)
    // setInterval(refreshUnreadCount, 300000);
  }

  /**
   * Gestisce il clic su "Segna come letta" per una singola comunicazione
   */
  function handleMarkRead(e) {
    e.preventDefault();

    const $btn = $(this);
    const communicationId = $btn.data("id");

    if (!communicationId) {
      console.error("ID comunicazione non trovato");
      return;
    }

    // Disabilita il pulsante durante la richiesta
    $btn.prop("disabled", true);
    const originalText = $btn.html();
    $btn.html(
      '<svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Attendere...'
    );

    $.ajax({
      url: config.markReadUrl,
      type: "POST",
      data: {
        id: communicationId,
        [getCSRFParam()]: config.csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          // Rimuovi il pulsante o aggiorna l'interfaccia
          const $item = $btn.closest(".communication-item");

          // Rimuovi l'indicatore "non letta"
          $item.removeClass("unread").addClass("read");

          // Rimuovi il badge "Non letta"
          $item.find(".bg-orange-100").remove();

          // Rimuovi l'indicatore visuale
          $item.find(".bg-brand-500").remove();

          // Rimuovi il pulsante
          $btn.fadeOut(300, function () {
            $(this).remove();
          });

          // Aggiorna il contatore se presente
          updateUnreadCounter(-1);

          // Mostra messaggio di successo (opzionale)
          showNotification("Comunicazione segnata come letta", "success");
        } else {
          showNotification(
            response.message || "Errore durante l'operazione",
            "error"
          );
          // Ripristina il pulsante
          $btn.prop("disabled", false).html(originalText);
        }
      },
      error: function (xhr, status, error) {
        console.error("Errore AJAX:", error);
        showNotification("Errore di connessione", "error");
        // Ripristina il pulsante
        $btn.prop("disabled", false).html(originalText);
      },
    });
  }

  /**
   * Gestisce il clic su "Segna tutte come lette"
   */
  function handleMarkAllRead(e) {
    e.preventDefault();

    const $btn = $(this);

    // Conferma azione
    if (
      !confirm("Sei sicuro di voler segnare tutte le comunicazioni come lette?")
    ) {
      return;
    }

    // Disabilita il pulsante
    $btn.prop("disabled", true);
    const originalText = $btn.html();
    $btn.html(
      '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Elaborazione...'
    );

    $.ajax({
      url: config.markAllReadUrl,
      type: "POST",
      data: {
        [getCSRFParam()]: config.csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          // Aggiorna l'interfaccia
          $(".communication-item.unread")
            .removeClass("unread")
            .addClass("read");
          $(".bg-orange-100").fadeOut(300);
          $(".bg-brand-500").fadeOut(300);
          $(".mark-read-btn").fadeOut(300);

          // Nasconde il pulsante "Segna tutte come lette"
          $btn.fadeOut(300);

          // Reset contatori
          updateUnreadCounter(0, true);

          // Messaggio di successo
          showNotification(
            response.message ||
              "Tutte le comunicazioni sono state segnate come lette",
            "success"
          );

          // Opzionale: ricarica la pagina dopo 2 secondi
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          showNotification(
            response.message || "Errore durante l'operazione",
            "error"
          );
          $btn.prop("disabled", false).html(originalText);
        }
      },
      error: function (xhr, status, error) {
        console.error("Errore AJAX:", error);
        showNotification("Errore di connessione", "error");
        $btn.prop("disabled", false).html(originalText);
      },
    });
  }

  /**
   * Aggiorna il contatore delle comunicazioni non lette
   */
  function updateUnreadCounter(delta, reset = false) {
    // Cerca elementi che mostrano il conteggio non lette
    const $counters = $(".unread-count, [data-unread-count]");

    $counters.each(function () {
      const $counter = $(this);
      let currentCount = parseInt($counter.text()) || 0;

      if (reset) {
        currentCount = 0;
      } else {
        currentCount = Math.max(0, currentCount + delta);
      }

      $counter.text(currentCount);

      // Nasconde il counter se è zero
      if (currentCount === 0) {
        $counter.fadeOut(300);
      }
    });
  }

  /**
   * Mostra una notifica temporanea
   */
  function showNotification(message, type = "info") {
    // Rimuovi notifiche esistenti
    $(".temp-notification").remove();

    const typeClasses = {
      success: "bg-green-100 border-green-400 text-green-800",
      error: "bg-red-100 border-red-400 text-red-800",
      info: "bg-blue-100 border-blue-400 text-blue-800",
    };

    const $notification = $(`
            <div class="temp-notification fixed top-4 right-4 z-50 max-w-sm p-4 border-l-4 rounded-lg shadow-lg ${
              typeClasses[type] || typeClasses.info
            }">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <button class="ml-3 text-lg font-semibold leading-none cursor-pointer">&times;</button>
                </div>
            </div>
        `);

    $("body").append($notification);

    // Chiusura automatica dopo 4 secondi
    setTimeout(() => {
      $notification.fadeOut(300, function () {
        $(this).remove();
      });
    }, 4000);

    // Chiusura manuale
    $notification.find("button").on("click", function () {
      $notification.fadeOut(300, function () {
        $(this).remove();
      });
    });
  }

  /**
   * Ottiene il nome del parametro CSRF
   */
  function getCSRFParam() {
    return $("meta[name=csrf-param]").attr("content") || "_csrf";
  }

  /**
   * Aggiorna il conteggio delle comunicazioni (per chiamate periodiche)
   */
  function refreshUnreadCount() {
    // Implementazione futura per aggiornamento periodico via AJAX
    console.log("Refresh unread count (da implementare se necessario)");
  }

  // API pubblica
  return {
    init: init,
    markRead: handleMarkRead,
    markAllRead: handleMarkAllRead,
    showNotification: showNotification,
  };
})();

// Auto-inizializzazione quando il DOM è pronto
$(document).ready(function () {
  if (typeof window.CommunicationSystem !== "undefined") {
    window.CommunicationSystem.init();
  }
});
