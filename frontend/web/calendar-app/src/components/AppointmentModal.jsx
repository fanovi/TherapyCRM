import React, { useState, useEffect } from "react";
import calendarService from "../services/calendarService";

const AppointmentModal = ({ appointment, userRole, onClose, onUpdate }) => {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    status: appointment.status || "scheduled",
    reason: "",
    notes: appointment.notes || "",
  });
  const [availableTherapists, setAvailableTherapists] = useState([]);

  useEffect(() => {
    // Carica terapisti disponibili se è manager
    if (userRole === "manager") {
      loadAvailableTherapists();
    }
  }, [userRole]);

  const loadAvailableTherapists = async () => {
    try {
      const response = await calendarService.getTherapists();
      if (response.success) {
        setAvailableTherapists(response.data);
      }
    } catch (error) {
      console.error("Error loading therapists:", error);
    }
  };

  const handleStatusChange = (newStatus) => {
    setFormData((prev) => ({
      ...prev,
      status: newStatus,
      reason: newStatus.includes("absent") ? prev.reason : "",
    }));
  };

  const handleSave = async () => {
    setLoading(true);
    try {
      let response;

      if (formData.status !== appointment.status) {
        // Aggiorna presenza/assenza
        response = await calendarService.markAttendance(
          appointment.id,
          formData.status,
          formData.reason
        );
      } else {
        // Altri aggiornamenti (se necessario)
        response = { success: true, data: appointment };
      }

      if (response.success) {
        const updatedAppointment = {
          ...appointment,
          status: formData.status,
          backgroundColor: getStatusColor(formData.status),
          borderColor: getStatusColor(formData.status),
          extendedProps: {
            ...appointment.extendedProps,
            status: formData.status,
          },
        };

        onUpdate(updatedAppointment);
        showToast("Appuntamento aggiornato con successo");
      } else {
        alert(response.error || "Errore durante il salvataggio");
      }
    } catch (error) {
      console.error("Error saving appointment:", error);
      alert("Errore durante il salvataggio");
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      completed: "#22c55e",
      absent_justified: "#f59e0b",
      absent_not_justified: "#ef4444",
      cancelled: "#6b7280",
      scheduled: "#3b82f6",
    };
    return colors[status] || colors.scheduled;
  };

  const getStatusLabel = (status) => {
    const labels = {
      completed: "Presente",
      absent_justified: "Assente Giustificato",
      absent_not_justified: "Assente Non Giustificato",
      cancelled: "Annullato",
      scheduled: "Programmato",
    };
    return labels[status] || "Programmato";
  };

  const showToast = (message) => {
    const toast = document.createElement("div");
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #22c55e;
      color: white;
      padding: 12px 20px;
      border-radius: 6px;
      z-index: 1001;
      font-weight: 500;
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
      document.body.removeChild(toast);
    }, 3000);
  };

  const formatDateTime = (dateTime) => {
    return new Date(dateTime).toLocaleString("it-IT", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const formatDuration = (minutes) => {
    if (minutes < 60) {
      return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return remainingMinutes > 0
      ? `${hours}h ${remainingMinutes}min`
      : `${hours}h`;
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h3 className="modal-title">Dettagli Appuntamento</h3>
          <button className="modal-close" onClick={onClose}>
            ×
          </button>
        </div>

        <div className="modal-body">
          {/* Informazioni base */}
          <div className="appointment-info">
            <div className="info-row">
              <label>Paziente:</label>
              <span className="font-semibold">
                {appointment.patientName || appointment.title}
              </span>
            </div>

            <div className="info-row">
              <label>Terapista:</label>
              <span>{appointment.therapistName}</span>
            </div>

            <div className="info-row">
              <label>Trattamento:</label>
              <span>{appointment.treatmentName}</span>
            </div>

            <div className="info-row">
              <label>Data e Ora:</label>
              <span>{formatDateTime(appointment.start)}</span>
            </div>

            <div className="info-row">
              <label>Durata:</label>
              <span>{formatDuration(appointment.duration)}</span>
            </div>

            <div className="info-row">
              <label>Luogo:</label>
              <span>
                {appointment.location === "office" ? "Studio" : "Domicilio"}
              </span>
            </div>
          </div>

          {/* Gestione presenza/assenza per terapisti */}
          {userRole === "therapist" && (
            <div className="attendance-section">
              <h4 className="section-title">Presenza</h4>

              <div className="status-buttons">
                <button
                  className={`status-btn ${
                    formData.status === "completed" ? "active" : ""
                  }`}
                  onClick={() => handleStatusChange("completed")}
                  style={{
                    backgroundColor:
                      formData.status === "completed" ? "#22c55e" : "#f3f4f6",
                    color:
                      formData.status === "completed" ? "white" : "#374151",
                  }}
                >
                  ✓ Presente
                </button>

                <button
                  className={`status-btn ${
                    formData.status === "absent_justified" ? "active" : ""
                  }`}
                  onClick={() => handleStatusChange("absent_justified")}
                  style={{
                    backgroundColor:
                      formData.status === "absent_justified"
                        ? "#f59e0b"
                        : "#f3f4f6",
                    color:
                      formData.status === "absent_justified"
                        ? "white"
                        : "#374151",
                  }}
                >
                  ⚠ Assente Giustificato
                </button>

                <button
                  className={`status-btn ${
                    formData.status === "absent_not_justified" ? "active" : ""
                  }`}
                  onClick={() => handleStatusChange("absent_not_justified")}
                  style={{
                    backgroundColor:
                      formData.status === "absent_not_justified"
                        ? "#ef4444"
                        : "#f3f4f6",
                    color:
                      formData.status === "absent_not_justified"
                        ? "white"
                        : "#374151",
                  }}
                >
                  ✗ Assente Non Giustificato
                </button>
              </div>

              {/* Motivo assenza */}
              {formData.status.includes("absent") && (
                <div className="reason-section">
                  <label htmlFor="reason">Motivo assenza:</label>
                  <select
                    id="reason"
                    value={formData.reason}
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        reason: e.target.value,
                      }))
                    }
                    className="reason-select"
                  >
                    <option value="">Seleziona motivo</option>
                    <option value="health">Motivi di salute</option>
                    <option value="family">Motivi familiari</option>
                    <option value="transport">Problemi di trasporto</option>
                    <option value="other">Altro</option>
                  </select>
                </div>
              )}
            </div>
          )}

          {/* Gestione per manager */}
          {userRole === "manager" && (
            <div className="manager-section">
              <h4 className="section-title">Gestione Appuntamento</h4>

              <div className="current-status">
                <label>Status attuale:</label>
                <span
                  className="status-badge"
                  style={{
                    backgroundColor: getStatusColor(appointment.status),
                    color: "white",
                    padding: "4px 8px",
                    borderRadius: "4px",
                    fontSize: "12px",
                  }}
                >
                  {getStatusLabel(appointment.status)}
                </span>
              </div>

              {availableTherapists.length > 0 && (
                <div className="therapist-change">
                  <label htmlFor="therapist">Cambia terapista:</label>
                  <select
                    id="therapist"
                    defaultValue={appointment.therapistId}
                    className="therapist-select"
                  >
                    {availableTherapists.map((therapist) => (
                      <option key={therapist.id} value={therapist.id}>
                        {therapist.title} -{" "}
                        {therapist.extendedProps.specialization}
                      </option>
                    ))}
                  </select>
                </div>
              )}
            </div>
          )}

          {/* Note */}
          <div className="notes-section">
            <label htmlFor="notes">Note:</label>
            <textarea
              id="notes"
              value={formData.notes}
              onChange={(e) =>
                setFormData((prev) => ({ ...prev, notes: e.target.value }))
              }
              className="notes-textarea"
              rows="3"
              placeholder="Aggiungi note..."
            />
          </div>
        </div>

        <div className="modal-footer">
          <button className="btn-cancel" onClick={onClose}>
            Annulla
          </button>
          <button className="btn-save" onClick={handleSave} disabled={loading}>
            {loading ? "Salvataggio..." : "Salva"}
          </button>
        </div>
      </div>

      {/* Stili CSS inline */}
      <style jsx>{`
        .modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.5);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          padding: 20px;
        }

        .modal-content {
          background: white;
          border-radius: 8px;
          max-width: 500px;
          width: 100%;
          max-height: 90vh;
          overflow-y: auto;
          box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 20px;
          border-bottom: 1px solid #e5e7eb;
        }

        .modal-title {
          font-size: 18px;
          font-weight: 600;
          color: #111827;
          margin: 0;
        }

        .modal-close {
          background: none;
          border: none;
          font-size: 24px;
          color: #6b7280;
          cursor: pointer;
          padding: 0;
          width: 30px;
          height: 30px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .modal-close:hover {
          color: #374151;
        }

        .modal-body {
          padding: 20px;
        }

        .appointment-info {
          margin-bottom: 20px;
        }

        .info-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 8px 0;
          border-bottom: 1px solid #f3f4f6;
        }

        .info-row label {
          font-weight: 500;
          color: #6b7280;
        }

        .section-title {
          font-size: 16px;
          font-weight: 600;
          color: #111827;
          margin: 20px 0 12px 0;
        }

        .status-buttons {
          display: flex;
          gap: 8px;
          margin-bottom: 16px;
          flex-wrap: wrap;
        }

        .status-btn {
          padding: 8px 16px;
          border: 1px solid #d1d5db;
          border-radius: 6px;
          cursor: pointer;
          font-size: 14px;
          font-weight: 500;
          transition: all 0.2s;
        }

        .status-btn:hover {
          transform: translateY(-1px);
        }

        .reason-section {
          margin-top: 16px;
        }

        .reason-section label {
          display: block;
          font-weight: 500;
          color: #374151;
          margin-bottom: 8px;
        }

        .reason-select,
        .therapist-select {
          width: 100%;
          padding: 8px 12px;
          border: 1px solid #d1d5db;
          border-radius: 6px;
          font-size: 14px;
        }

        .current-status {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 16px;
        }

        .therapist-change {
          margin-top: 16px;
        }

        .therapist-change label {
          display: block;
          font-weight: 500;
          color: #374151;
          margin-bottom: 8px;
        }

        .notes-section {
          margin-top: 20px;
        }

        .notes-section label {
          display: block;
          font-weight: 500;
          color: #374151;
          margin-bottom: 8px;
        }

        .notes-textarea {
          width: 100%;
          padding: 8px 12px;
          border: 1px solid #d1d5db;
          border-radius: 6px;
          font-size: 14px;
          resize: vertical;
        }

        .modal-footer {
          display: flex;
          justify-content: flex-end;
          gap: 12px;
          padding: 20px;
          border-top: 1px solid #e5e7eb;
        }

        .btn-cancel,
        .btn-save {
          padding: 8px 16px;
          border-radius: 6px;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.2s;
        }

        .btn-cancel {
          background: #f3f4f6;
          color: #374151;
          border: 1px solid #d1d5db;
        }

        .btn-cancel:hover {
          background: #e5e7eb;
        }

        .btn-save {
          background: #3b82f6;
          color: white;
          border: 1px solid #3b82f6;
        }

        .btn-save:hover:not(:disabled) {
          background: #2563eb;
        }

        .btn-save:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        @media (max-width: 640px) {
          .modal-overlay {
            padding: 10px;
          }

          .modal-content {
            max-height: 95vh;
          }

          .status-buttons {
            flex-direction: column;
          }

          .status-btn {
            width: 100%;
          }

          .info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
          }
        }
      `}</style>
    </div>
  );
};

export default AppointmentModal;
