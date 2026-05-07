/**
 * Sistema Notifiche Terapisti - allineato a patient-notifications.js
 */

$(document).ready(function () {
  let selectedTherapists = [];

  init();

  function init() {
    bindEvents();
    updateUI();
  }

  function bindEvents() {
    // Checkbox "Seleziona tutto"
    $(document).on("change", "#select-all-therapists", function () {
      const isChecked = $(this).is(":checked");
      $(".therapist-checkbox").prop("checked", isChecked);
      updateSelectedTherapists();
    });

    // Checkbox singoli terapisti
    $(document).on("change", ".therapist-checkbox", function () {
      updateSelectedTherapists();
      updateSelectAllState();
    });

    // Bottone "Invia Notifica"
    $(document).on("click", "#send-notifications-btn", function (e) {
      e.preventDefault();
      if (selectedTherapists.length === 0) {
        swalWarning(
          "Nessun terapista selezionato",
          "Seleziona almeno un terapista per inviare le notifiche."
        );
        return;
      }
      openModal();
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

  function openModal() {
    const modal = document.getElementById("notificationModal");
    const modalData = Alpine.$data(modal.querySelector("[x-data]"));

    if (modalData) {
      modalData.selectedCount = selectedTherapists.length;
      modalData.showModal = true;
      modalData.errors = "";
      modalData.success = "";
      modalData.title = "";
      modalData.message = "";

      modal.classList.remove("hidden");
      modal.classList.add("flex");
    }
  }

  // Funzione globale per inviare le notifiche (chiamata dal modal Alpine)
  window.sendTherapistNotifications = async function () {
    const modal = document.getElementById("notificationModal");
    const modalData = Alpine.$data(modal.querySelector("[x-data]"));

    if (!modalData) return;

    const title = modalData.title?.trim();
    const message = modalData.message?.trim();

    const dataToSend = {
      therapist_ids: selectedTherapists,
      title: title,
      message: message,
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
        url: window.sendNotificationUrl || "/therapist/send-notification",
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
    selectedTherapists = [];
    $(".therapist-checkbox").prop("checked", false);
    $("#select-all-therapists").prop("checked", false);
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
