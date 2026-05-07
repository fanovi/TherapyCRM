/**
 * Sistema Notifiche Pazienti - flusso interamente gestito con SweetAlert2.
 */

$(document).ready(function () {
  let selectedPatients = [];

  init();

  function init() {
    bindEvents();
    updateUI();
  }

  function bindEvents() {
    $(document).on("change", "#select-all-patients", function () {
      const isChecked = $(this).is(":checked");
      $(".patient-checkbox").prop("checked", isChecked);
      updateSelectedPatients();
    });

    $(document).on("change", ".patient-checkbox", function () {
      updateSelectedPatients();
      updateSelectAllState();
    });

    $(document).on("click", "#send-notifications-btn", function (e) {
      e.preventDefault();
      if (selectedPatients.length === 0) {
        swalWarning(
          "Nessun paziente selezionato",
          "Seleziona almeno un paziente per inviare le notifiche."
        );
        return;
      }
      openSwalSendModal();
    });
  }

  function updateSelectedPatients() {
    selectedPatients = [];
    $(".patient-checkbox:checked").each(function () {
      selectedPatients.push(parseInt($(this).val()));
    });
    updateUI();
  }

  function updateSelectAllState() {
    const total = $(".patient-checkbox").length;
    const checked = $(".patient-checkbox:checked").length;
    $("#select-all-patients").prop(
      "indeterminate",
      checked > 0 && checked < total
    );
    $("#select-all-patients").prop("checked", checked === total && total > 0);
  }

  function updateUI() {
    const count = selectedPatients.length;
    $("#selected-patients-count").text(count);

    if (count > 0) {
      $("#notification-actions-bar").removeClass("hidden").addClass("flex");
    } else {
      $("#notification-actions-bar").removeClass("flex").addClass("hidden");
    }
  }

  async function openSwalSendModal() {
    if (typeof Swal === "undefined") {
      alert("Componente Swal non disponibile.");
      return;
    }

    const url = window.sendNotificationUrl || "/patient/send-notification";
    const csrf = $("meta[name=csrf-token]").attr("content");
    const count = selectedPatients.length;

    const result = await Swal.fire({
      title: "Invia Notifica",
      html: `
        <div style="text-align:left;font-size:13px;color:#6b7280;margin-bottom:12px;">
          Destinatari: <strong>${count} paziente${count === 1 ? "" : "i"}</strong> selezionato${count === 1 ? "" : "i"}.
          La notifica viene recapitata a tutti gli account collegati.
        </div>
        <input id="swal-notif-title" class="swal2-input" placeholder="Titolo notifica" maxlength="100" autocomplete="off" />
        <textarea id="swal-notif-message" class="swal2-textarea" placeholder="Messaggio" maxlength="500" rows="4"></textarea>
        <div style="display:flex;align-items:center;justify-content:flex-start;margin-top:8px;">
          <input id="swal-notif-readconf" type="checkbox" style="width:16px;height:16px;margin-right:8px;" />
          <label for="swal-notif-readconf" style="font-size:13px;color:#374151;cursor:pointer;">
            Richiede conferma di lettura
          </label>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: "Invia Notifica",
      cancelButtonText: "Annulla",
      confirmButtonColor: "#2563eb",
      reverseButtons: true,
      allowOutsideClick: () => !Swal.isLoading(),
      didOpen: () => {
        document.getElementById("swal-notif-title").focus();
      },
      preConfirm: async () => {
        const title = (
          document.getElementById("swal-notif-title").value || ""
        ).trim();
        const message = (
          document.getElementById("swal-notif-message").value || ""
        ).trim();
        const requiresReadConfirmation = document.getElementById(
          "swal-notif-readconf"
        ).checked;

        if (!title || !message) {
          Swal.showValidationMessage(
            "Inserisci sia il titolo che il messaggio della notifica."
          );
          return false;
        }

        try {
          const response = await $.ajax({
            url: url,
            type: "POST",
            data: {
              patient_ids: selectedPatients,
              title: title,
              message: message,
              requires_read_confirmation: requiresReadConfirmation ? 1 : 0,
              _csrf: csrf,
            },
            dataType: "json",
          });

          if (!response || !response.success) {
            Swal.showValidationMessage(
              (response && response.error) ||
                "Errore durante l'invio delle notifiche."
            );
            return false;
          }

          return response;
        } catch (err) {
          let errorMessage = "Errore di comunicazione con il server.";
          if (err && err.responseJSON && err.responseJSON.error) {
            errorMessage = err.responseJSON.error;
          } else if (err && err.status) {
            errorMessage = `Errore ${err.status}: ${err.statusText}`;
          }
          Swal.showValidationMessage(errorMessage);
          return false;
        }
      },
    });

    if (result.isConfirmed && result.value) {
      clearAllSelections();
      swalSuccess(
        "Notifiche inviate",
        result.value.message || "Notifiche inviate con successo!"
      );
    }
  }

  function clearAllSelections() {
    selectedPatients = [];
    $(".patient-checkbox").prop("checked", false);
    $("#select-all-patients").prop("checked", false);
    updateUI();
  }

  // Helper Swal (fallback ad alert nativo se Swal non e' disponibile)
  function swalSuccess(title, text) {
    if (typeof Swal === "undefined") {
      alert(title + "\n" + text);
      return;
    }
    Swal.fire({
      icon: "success",
      title: title,
      text: text,
      timer: 1800,
      showConfirmButton: false,
    });
  }

  function swalWarning(title, text) {
    if (typeof Swal === "undefined") {
      alert(title + "\n" + text);
      return;
    }
    Swal.fire({
      icon: "warning",
      title: title,
      text: text,
      confirmButtonText: "OK",
      confirmButtonColor: "#f59e0b",
    });
  }
});
