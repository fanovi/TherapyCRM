/**
 * Sistema Notifiche Terapisti - flusso interamente gestito con SweetAlert2.
 */

$(document).ready(function () {
  let selectedTherapists = [];

  init();

  function init() {
    bindEvents();
    updateUI();
  }

  function bindEvents() {
    $(document).on("change", "#select-all-therapists", function () {
      const isChecked = $(this).is(":checked");
      $(".therapist-checkbox").prop("checked", isChecked);
      updateSelectedTherapists();
    });

    $(document).on("change", ".therapist-checkbox", function () {
      updateSelectedTherapists();
      updateSelectAllState();
    });

    $(document).on("click", "#send-notifications-btn", function (e) {
      e.preventDefault();
      if (selectedTherapists.length === 0) {
        swalWarning(
          "Nessun terapista selezionato",
          "Seleziona almeno un terapista per inviare le notifiche."
        );
        return;
      }
      openSwalSendModal();
    });
  }

  function updateSelectedTherapists() {
    selectedTherapists = [];
    $(".therapist-checkbox:checked").each(function () {
      selectedTherapists.push(parseInt($(this).val()));
    });
    updateUI();
  }

  function updateSelectAllState() {
    const total = $(".therapist-checkbox").length;
    const checked = $(".therapist-checkbox:checked").length;
    $("#select-all-therapists").prop(
      "indeterminate",
      checked > 0 && checked < total
    );
    $("#select-all-therapists").prop("checked", checked === total && total > 0);
  }

  function updateUI() {
    const count = selectedTherapists.length;
    $("#selected-therapists-count").text(count);

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

    const url = window.sendNotificationUrl || "/therapist/send-notification";
    const csrf = $("meta[name=csrf-token]").attr("content");
    const count = selectedTherapists.length;

    const result = await Swal.fire({
      title: "Invia Notifica",
      width: "640px",
      html: `
        <div style="text-align:left;font-size:13px;color:#6b7280;margin-bottom:12px;">
          Destinatari: <strong>${count} terapista${count === 1 ? "" : "i"}</strong> selezionato${count === 1 ? "" : "i"}.
          La notifica viene recapitata agli account dei terapisti selezionati.
        </div>
        <input id="swal-notif-title" class="swal2-input" placeholder="Titolo notifica" maxlength="100" autocomplete="off" />
        <textarea id="swal-notif-message" class="swal2-textarea" placeholder="Messaggio" maxlength="500" rows="4"></textarea>
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
              therapist_ids: selectedTherapists,
              title: title,
              message: message,
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
    selectedTherapists = [];
    $(".therapist-checkbox").prop("checked", false);
    $("#select-all-therapists").prop("checked", false);
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
