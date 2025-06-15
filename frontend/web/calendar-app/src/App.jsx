import React, { useState, useEffect } from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import CalendarView from "./components/CalendarView";
import TherapistDayView from "./components/TherapistDayView";
import "./App.css";
import "./styles/calendar.css";

function App() {
  // Legge configurazione da window.CALENDAR_CONFIG (impostata da Yii2)
  const [config, setConfig] = useState({
    userRole: "manager",
    therapistId: null,
    apiBaseUrl: "/api",
    locale: "it",
  });

  useEffect(() => {
    // Carica configurazione da window se disponibile (integrazione Yii2)
    if (window.CALENDAR_CONFIG) {
      setConfig((prevConfig) => ({
        ...prevConfig,
        ...window.CALENDAR_CONFIG,
      }));
    } else {
      // Fallback: legge da URL params per sviluppo
      const urlParams = new URLSearchParams(window.location.search);
      const urlRole = urlParams.get("role");
      const urlView = urlParams.get("view");
      const urlTherapistId = urlParams.get("therapistId");

      if (urlRole || urlView || urlTherapistId) {
        setConfig((prevConfig) => ({
          ...prevConfig,
          userRole: urlRole || prevConfig.userRole,
          therapistId: urlTherapistId
            ? parseInt(urlTherapistId)
            : prevConfig.therapistId,
        }));
      }
    }
  }, []);

  // Determina quale componente mostrare
  const getMainComponent = () => {
    // Se è un terapista, mostra la vista giornaliera
    if (config.userRole === "therapist") {
      return (
        <TherapistDayView
          userRole={config.userRole}
          therapistId={config.therapistId}
        />
      );
    }

    // Altrimenti mostra il calendario generale
    return <CalendarView userRole={config.userRole} />;
  };

  return (
    <div className="calendar-app">
      <Router>
        <Routes>
          <Route path="/" element={getMainComponent()} />
          <Route
            path="/calendar"
            element={<CalendarView userRole={config.userRole} />}
          />
          <Route
            path="/therapist-day"
            element={
              <TherapistDayView
                userRole={config.userRole}
                therapistId={config.therapistId}
              />
            }
          />
        </Routes>
      </Router>
    </div>
  );
}

export default App;
