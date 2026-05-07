/**
 * Sistema Notifiche Pazienti - Versione Semplificata
 */

$(document).ready(function () {
  let selectedPatients = [];

  // Inizializzazione
  init();

  function init() {
    bindEvents();
    updateUI();
  }

  function bindEvents() {
    // Checkbox "Seleziona tutto"
    $(document).on("change", "#select-all-patients", function () {
      const isChecked = $(this).is(":checked");
      $(".patient-checkbox").prop("checked", isChecked);
      updateSelectedPatients();
    });

    // Checkbox singoli pazienti
    $(document).on("change", ".patient-checkbox", function () {
      updateSelectedPatients();
      updateSelectAllState();
    });

    // Bottone "Invia Notifica"
    $(document).on("click", "#send-notifications-btn", function (e) {
      e.preventDefault();
      if (selectedPatients.length === 0) {
        swalWarning(
          "Nessun paziente selezionato",
          "Seleziona almeno un paziente per inviare le notifiche."
        );
        return;
      }
      openModal();
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

  function openModal() {
    // Trova e apri la modale
    const modal = document.getElementById("notificationModal");
    const modalData = Alpine.$data(modal.querySelector("[x-data]"));

    if (modalData) {
      modalData.selectedCount = selectedPatients.length;
      modalData.showModal = true;
      modalData.errors = "";
      modalData.success = "";
      modalData.title = "";
      modalData.message = "";
      // Reset checkbox con jQuery
      $("#requires-read-confirmation").prop("checked", false);

      // Mostra la modale
      modal.classList.remove("hidden");
      modal.classList.add("flex");
    }
  }

  // Funzione globale per inviare le notifiche (chiamata dal modal Alpine)
  window.sendPatientNotifications = async function () {
    const modal = document.getElementById("notificationModal");
    const modalData = Alpine.$data(modal.querySelector("[x-data]"));

    if (!modalData) return;

    const title = modalData.title?.trim();
    const message = modalData.message?.trim();

    // Leggi direttamente dalla checkbox usando jQuery
    const requiresReadConfirmation = $("#requires-read-confirmation").is(
      ":checked"
    );

    const dataToSend = {
      patient_ids: selectedPatients,
      title: title,
      message: message,
      requires_read_confirmation: requiresReadConfirmation ? 1 : 0,
      _csrf: $("meta[name=csrf-token]").attr("content"),
    };

    if (!title || !message) {
      swalError(
        "Campi mancanti",
        "Inserisci sia il titolo che il messaggio della notifica."
      );
      return;
    }

    modalData.isLoading = true;
    modalData.errors = "";
    modalData.success = "";

    swalLoading("Invio notifiche in corso...");

    try {
      const response = await $.ajax({
        url: window.sendNotificationUrl || "/patient/send-notification",
        type: "POST",
        data: dataToSend,
        dataType: "json",
      });

      if (response.success) {
        modalData.closeModal();
        clearAllSelections();
        swalSuccess(
          "Notifiche inviate",
          response.message || "Notifiche inviate con successo!"
        );
      } else {
        swalError(
          "Errore invio notifiche",
          response.error || "Errore durante l'invio delle notifiche."
        );
      }
    } catch (error) {
      console.error("Errore AJAX:", error);

      let errorMessage = "Errore di comunicazione con il server.";
      if (error.responseJSON?.error) {
        errorMessage = error.responseJSON.error;
      } else if (error.status) {
        errorMessage = `Errore ${error.status}: ${error.statusText}`;
      }
      swalError("Errore invio notifiche", errorMessage);
    } finally {
      modalData.isLoading = false;
    }
  };

  function clearAllSelections() {
    selectedPatients = [];
    $(".patient-checkbox").prop("checked", false);
    $("#select-all-patients").prop("checked", false);
    updateUI();
  }

  // Helper Swal (fallback ad alert nativo se Swal non e' disponibile)
  function swalLoading(title) {
    if (typeof Swal === "undefined") return;
    Swal.fire({
      title: title,
      didOpen: () => Swal.showLoading(),
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
    });
  }

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

  function swalError(title, text) {
    if (typeof Swal === "undefined") {
      alert(title + "\n" + text);
      return;
    }
    Swal.fire({
      icon: "error",
      title: title,
      text: text,
      confirmButtonText: "Ho capito",
      confirmButtonColor: "#dc2626",
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
