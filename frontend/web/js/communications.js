/**
 * Sistema di gestione comunicazioni JavaScript - Versione 2.0
 * Utilizza nuovi endpoint API ottimizzati e migliore UX
 */
window.CommunicationSystem = (function () {
  "use strict";

  let config = {
    apiMarkReadUrl: "",
    apiStatsUrl: "",
    csrfToken: "",
    csrfParam: "",
  };

  /**
   * Inizializza il sistema
   */
  function init() {
    // Ottieni URL dagli attributi data o variabili globali
    config.apiMarkReadUrl =
      window.apiMarkReadUrl ||
      $("[data-api-mark-read-url]").data("api-mark-read-url") ||
      "";
    config.apiStatsUrl =
      window.apiStatsUrl ||
      $("[data-api-stats-url]").data("api-stats-url") ||
      "";

    // Ottieni token CSRF
    config.csrfToken = $("meta[name=csrf-token]").attr("content") || "";
    config.csrfParam = $("meta[name=csrf-param]").attr("content") || "_csrf";

    bindEvents();
    // console.log("Communication System v2.0 initialized", config);
  }

  /**
   * Collega gli eventi
   */
  function bindEvents() {
    // Pulsante "Segna tutte come lette"
    $(document).on("click", "#mark-all-read-btn", handleMarkAllRead);

    // Pulsanti "Segna come letta" individuali
    $(document).on("click", ".mark-read-btn", handleMarkRead);

    // Selezione multipla (per future implementazioni)
    $(document).on("change", ".communication-checkbox", handleSelectionChange);

    // Auto-refresh stats ogni 2 minuti
    setInterval(refreshStats, 120000);
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

    markAsRead([communicationId], $btn);
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

    markAllAsRead($btn);
  }

  /**
   * Marca comunicazioni specifiche come lette
   */
  function markAsRead(ids, $triggerBtn = null) {
    if ($triggerBtn) {
      setButtonLoading($triggerBtn, true);
    }

    $.ajax({
      url: config.apiMarkReadUrl,
      type: "POST",
      data: {
        ids: ids,
        [config.csrfParam]: config.csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          // Aggiorna UI per ogni comunicazione processata
          ids.forEach(function (id) {
            updateCommunicationUI(id, true);
          });

          // Aggiorna contatori
          updateCounters(-response.updated_count);

          // Mostra notifica di successo
          showNotification(response.message, "success");

          // Se non ci sono più comunicazioni non lette, nascondi il pulsante "Segna tutte"
          if (response.updated_count > 0) {
            checkMarkAllButtonVisibility();
          }
        } else {
          showNotification(
            response.message || "Errore durante l'operazione",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Errore AJAX:", error);
        showNotification("Errore di connessione", "error");
      },
      complete: function () {
        if ($triggerBtn) {
          setButtonLoading($triggerBtn, false);
        }
      },
    });
  }

  /**
   * Marca tutte le comunicazioni come lette
   */
  function markAllAsRead($triggerBtn = null) {
    if ($triggerBtn) {
      setButtonLoading($triggerBtn, true, "Elaborazione...");
    }

    $.ajax({
      url: config.apiMarkReadUrl,
      type: "POST",
      data: {
        mark_all: true,
        [config.csrfParam]: config.csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          // Aggiorna tutte le comunicazioni non lette nella pagina
          $(".communication-item.unread").each(function () {
            const id = $(this).data("id");
            updateCommunicationUI(id, false); // false = non rimuovere singolarmente i pulsanti
          });

          // Nascondi il pulsante "Segna tutte come lette"
          $("#mark-all-read-btn").fadeOut(300);

          // Aggiorna tutti i contatori
          updateCounters(-response.updated_count);

          // Mostra notifica di successo
          showNotification(response.message, "success");

          // Opzionale: ricarica la pagina dopo 2 secondi per aggiornare tutto
          setTimeout(function () {
            window.location.reload();
          }, 2000);
        } else {
          showNotification(
            response.message || "Errore durante l'operazione",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Errore AJAX:", error);
        showNotification("Errore di connessione", "error");
      },
      complete: function () {
        if ($triggerBtn) {
          setButtonLoading($triggerBtn, false);
        }
      },
    });
  }

  /**
   * Aggiorna l'UI di una singola comunicazione
   */
  function updateCommunicationUI(communicationId, removeSingleButton = true) {
    const $item = $(`.communication-item[data-id="${communicationId}"]`);

    if ($item.length) {
      // Cambia da unread a read
      $item.removeClass("unread").addClass("read");

      // Rimuovi indicatori visivi "non letta"
      $item.find(".bg-orange-100, .bg-orange-500").remove();
      $item
        .find(".text-orange-600, .text-orange-800")
        .removeClass("text-orange-600 text-orange-800")
        .addClass("text-gray-600");

      // Rimuovi badge "Non letta"
      $item.find('.badge:contains("Non letta")').remove();

      // Rimuovi o nascondi il pulsante "Segna come letta" se richiesto
      if (removeSingleButton) {
        $item.find(".mark-read-btn").fadeOut(300, function () {
          $(this).remove();
        });
      }

      // Aggiungi un subtle feedback visivo
      $item.addClass("animate-pulse");
      setTimeout(function () {
        $item.removeClass("animate-pulse");
      }, 1000);
    }
  }

  /**
   * Aggiorna i contatori nella UI
   */
  function updateCounters(deltaUnread) {
    // Aggiorna contatori nelle tab
    const $unreadBadges = $(".bg-orange-100, .bg-orange-900\\/20").filter(
      ":contains(numbers)"
    );

    $unreadBadges.each(function () {
      const $badge = $(this);
      const currentCount = parseInt($badge.text()) || 0;
      const newCount = Math.max(0, currentCount + deltaUnread);
      $badge.text(newCount);

      // Nascondi badge se il conteggio è 0
      if (newCount === 0) {
        $badge.closest(".flex").fadeOut(300);
      }
    });
  }

  /**
   * Controlla se nascondere il pulsante "Segna tutte come lette"
   */
  function checkMarkAllButtonVisibility() {
    const unreadCount = $(".communication-item.unread").length;
    if (unreadCount === 0) {
      $("#mark-all-read-btn").fadeOut(300);
    }
  }

  /**
   * Imposta lo stato di caricamento di un pulsante
   */
  function setButtonLoading($btn, loading, customText = null) {
    if (loading) {
      $btn.prop("disabled", true);
      const originalText = $btn.data("original-text") || $btn.html();
      $btn.data("original-text", originalText);

      const loadingText = customText || "Caricamento...";
      $btn.html(`
        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        ${loadingText}
      `);
    } else {
      $btn.prop("disabled", false);
      const originalText = $btn.data("original-text");
      if (originalText) {
        $btn.html(originalText);
      }
    }
  }

  /**
   * Mostra notifica toast con Tailwind CSS
   */
  function showNotification(message, type = "info", duration = 5000) {
    const typeClasses = {
      success: "bg-green-500 text-white",
      error: "bg-red-500 text-white",
      info: "bg-blue-500 text-white",
      warning: "bg-yellow-500 text-black",
    };

    const iconSvg = {
      success: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`,
      error: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`,
      info: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
      warning: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>`,
    };

    const $notification = $(`
      <div class="fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 ${
        typeClasses[type] || typeClasses.info
      }">
        ${iconSvg[type] || iconSvg.info}
        <span class="font-medium">${message}</span>
        <button class="ml-2 opacity-70 hover:opacity-100" onclick="$(this).parent().remove()">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    `);

    $("body").append($notification);

    // Animazione di entrata
    setTimeout(() => {
      $notification.removeClass("translate-x-full");
    }, 100);

    // Auto-remove
    setTimeout(() => {
      $notification.addClass("translate-x-full");
      setTimeout(() => {
        $notification.remove();
      }, 300);
    }, duration);
  }

  /**
   * Gestisce cambiamenti nella selezione (per future implementazioni)
   */
  function handleSelectionChange() {
    // Implementazione futura per selezione multipla
    console.log("Selection changed");
  }

  /**
   * Aggiorna le statistiche periodicamente
   */
  function refreshStats() {
    $.ajax({
      url: config.apiStatsUrl,
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (response.success) {
          const stats = response.data;
          // Aggiorna contatori se diversi da quelli attuali
          // Implementazione futura per aggiornamento automatico contatori
          console.log("Stats updated:", stats);
        }
      },
      error: function () {
        console.log("Failed to refresh stats");
      },
    });
  }

  // API pubblica
  return {
    init: init,
    markRead: markAsRead,
    markAllRead: markAllAsRead,
    showNotification: showNotification,
    refreshStats: refreshStats,
  };
})();

// Auto-inizializzazione quando il DOM è pronto
$(document).ready(function () {
  if (typeof window.CommunicationSystem !== "undefined") {
    window.CommunicationSystem.init();
  }
});
