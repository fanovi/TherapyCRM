/**
 * Sistema di gestione notifiche JavaScript
 * Per la sezione di amministrazione delle notifiche del sistema
 */
window.NotificationSystem = (function () {
  "use strict";

  let config = {
    apiStatsUrl: "",
    csrfToken: "",
    csrfParam: "",
  };

  /**
   * Inizializza il sistema
   */
  function init() {
    // Ottieni URL dagli attributi data o variabili globali
    config.apiStatsUrl =
      window.apiStatsUrl ||
      $("[data-api-stats-url]").data("api-stats-url") ||
      "";

    // Ottieni token CSRF
    config.csrfToken = $("meta[name=csrf-token]").attr("content") || "";
    config.csrfParam = $("meta[name=csrf-param]").attr("content") || "_csrf";

    bindEvents();
    // console.log("Notification System initialized", config);
  }

  /**
   * Collega gli eventi
   */
  function bindEvents() {
    // Auto-refresh stats ogni 5 minuti
    setInterval(refreshStats, 300000);
  }

  /**
   * Aggiorna le statistiche
   */
  function refreshStats() {
    if (!config.apiStatsUrl) {
      // console.warn("URL API statistiche non configurato");
      return;
    }

    $.ajax({
      url: config.apiStatsUrl,
      type: "GET",
      dataType: "json",
      timeout: 10000,
      success: function (response) {
        if (response.success && response.data) {
          updateStatsDisplay(response.data);
          // console.log("Statistiche aggiornate:", response.data);
        } else {
          // console.warn("Risposta API statistiche non valida:", response);
        }
      },
      error: function (xhr, status, error) {
        // console.error("Errore aggiornamento statistiche:", error);
      },
    });
  }

  /**
   * Aggiorna i contatori nell'interfaccia
   */
  function updateStatsDisplay(data) {
    try {
      // Aggiorna i contatori nelle card statistiche
      $('[data-stat="total"]').text(data.total_count || 0);
      $('[data-stat="unread"]').text(data.unread_count || 0);
      $('[data-stat="sent"]').text(data.sent_count || 0);
      $('[data-stat="unsent"]').text(data.unsent_count || 0);

      // Aggiorna i contatori nei filtri
      updateFilterCounts(data);
    } catch (e) {
      // console.error("Errore aggiornamento display statistiche:", e);
    }
  }

  /**
   * Aggiorna i contatori nei filtri
   */
  function updateFilterCounts(data) {
    // Aggiorna contatori nei link dei filtri
    const $unreadFilter = $('a[href*="status=unread"]');
    if ($unreadFilter.length) {
      const text = $unreadFilter.text().replace(/\(\d+\)/, `(${data.unread_count})`);
      $unreadFilter.text(text);
    }

    const $sentFilter = $('a[href*="status=sent"]');
    if ($sentFilter.length) {
      const text = $sentFilter.text().replace(/\(\d+\)/, `(${data.sent_count})`);
      $sentFilter.text(text);
    }

    const $unsentFilter = $('a[href*="status=unsent"]');
    if ($unsentFilter.length) {
      const text = $unsentFilter.text().replace(/\(\d+\)/, `(${data.unsent_count})`);
      $unsentFilter.text(text);
    }
  }

  /**
   * API pubblica
   */
  return {
    init: init,
    refreshStats: refreshStats,
  };
})();

// Auto-inizializzazione quando il DOM è pronto
$(document).ready(function () {
  if (typeof window.NotificationSystem !== "undefined") {
    window.NotificationSystem.init();
  }
});
