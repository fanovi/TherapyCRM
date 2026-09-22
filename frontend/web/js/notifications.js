/**
 * Sistema di gestione notifiche JavaScript
 * Per la sezione di amministrazione delle notifiche del sistema
 */
window.NotificationSystem = (function () {
  "use strict";

  let initialized = false;
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
    if (initialized) {
      return;
    }
    initialized = true;

    config.apiMarkReadUrl =
      window.apiMarkReadUrl ||
      $("[data-api-mark-read-url]").data("api-mark-read-url") ||
      "";
    config.apiStatsUrl =
      window.apiStatsUrl ||
      $("[data-api-stats-url]").data("api-stats-url") ||
      "";

    config.csrfToken = $("meta[name=csrf-token]").attr("content") || "";
    config.csrfParam = $("meta[name=csrf-param]").attr("content") || "_csrf";

    bindEvents();
  }

  /**
   * Collega gli eventi
   */
  function bindEvents() {
    $(document).on("click", "#mark-all-read-btn", handleMarkAllRead);
    $(document).on("click", ".mark-read-btn", handleMarkRead);

    setInterval(refreshStats, 300000);
  }

  function handleMarkRead(e) {
    e.preventDefault();

    const $btn = $(this);
    const notificationId = $btn.data("id");

    if (!notificationId) {
      return;
    }

    markAsRead([notificationId], $btn);
  }

  function handleMarkAllRead(e) {
    e.preventDefault();

    if (!confirm("Sei sicuro di voler segnare tutte le notifiche come lette?")) {
      return;
    }

    markAllAsRead($(this));
  }

  function csrfData(extra) {
    return Object.assign({}, extra, {
      [config.csrfParam]: config.csrfToken,
    });
  }

  function setButtonLoading($btn, loading, loadingText) {
    if (!$btn || !$btn.length) {
      return;
    }

    if (loading) {
      $btn.data("original-html", $btn.html());
      $btn.prop("disabled", true);
      $btn.html(loadingText || "Attendere...");
    } else {
      $btn.prop("disabled", false);
      const original = $btn.data("original-html");
      if (original) {
        $btn.html(original);
      }
    }
  }

  function markAsRead(ids, $triggerBtn) {
    if (!config.apiMarkReadUrl) {
      return;
    }

    setButtonLoading($triggerBtn, true);

    $.ajax({
      url: config.apiMarkReadUrl,
      type: "POST",
      data: csrfData({ ids: ids }),
      dataType: "json",
      success: function (response) {
        if (response.success) {
          window.location.reload();
        } else {
          alert(response.message || "Errore durante l'operazione");
        }
      },
      error: function () {
        alert("Errore di connessione");
      },
      complete: function () {
        setButtonLoading($triggerBtn, false);
      },
    });
  }

  function markAllAsRead($triggerBtn) {
    if (!config.apiMarkReadUrl) {
      return;
    }

    setButtonLoading($triggerBtn, true, "Elaborazione...");

    $.ajax({
      url: config.apiMarkReadUrl,
      type: "POST",
      data: csrfData({ mark_all: true }),
      dataType: "json",
      success: function (response) {
        if (response.success) {
          window.location.reload();
        } else {
          alert(response.message || "Errore durante l'operazione");
        }
      },
      error: function () {
        alert("Errore di connessione");
      },
      complete: function () {
        setButtonLoading($triggerBtn, false);
      },
    });
  }

  /**
   * Aggiorna le statistiche
   */
  function refreshStats() {
    if (!config.apiStatsUrl) {
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
        }
      },
    });
  }

  /**
   * Aggiorna i contatori nell'interfaccia
   */
  function updateStatsDisplay(data) {
    try {
      $('[data-stat="total"]').text(data.total_count || 0);
      $('[data-stat="unread"]').text(data.unread_count || 0);
      $('[data-stat="sent"]').text(data.sent_count || 0);
      $('[data-stat="unsent"]').text(data.unsent_count || 0);

      updateFilterCounts(data);
    } catch (e) {
      // ignore display errors
    }
  }

  /**
   * Aggiorna i contatori nei filtri
   */
  function updateFilterCounts(data) {
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

  return {
    init: init,
    refreshStats: refreshStats,
  };
})();

$(document).ready(function () {
  if (typeof window.NotificationSystem !== "undefined") {
    window.NotificationSystem.init();
  }
});
