import moment from 'moment';

// Funzione helper per creare date relative ad oggi
const createDate = (daysFromToday, hour = 10, minute = 0) => {
  return moment()
    .add(daysFromToday, 'days')
    .hour(hour)
    .minute(minute)
    .second(0)
    .toDate();
};

// Appuntamenti per i pazienti
export const patientAppointments = {
  'paziente@test.com': [
    {
      id: '1',
      date: createDate(1, 10, 30), // Domani alle 10:30
      time: '10:30 - 11:30',
      therapist: {
        id: 'therapist1',
        name: 'Dr. Giulia Verdi',
        specialization: 'Fisioterapia',
        avatar: 'https://i.pravatar.cc/150?img=1',
      },
      type: 'Fisioterapia',
      notes: 'Controllo recupero post-operatorio ginocchio',
      status: 'confermato', // confermato, annullato, completato
      location: 'Studio 1 - Via Roma 123',
      canCancel: true, // Calcolato in base alla data
    },
    {
      id: '2',
      date: createDate(3, 14, 0), // Fra 3 giorni alle 14:00
      time: '14:00 - 15:00',
      therapist: {
        id: 'therapist2',
        name: 'Dr. Marco Rossi',
        specialization: 'Osteopatia',
        avatar: 'https://i.pravatar.cc/150?img=2',
      },
      type: 'Consulenza Osteopatica',
      notes: 'Prima visita - problemi posturali',
      status: 'confermato',
      location: 'Studio 2 - Via Milano 45',
      canCancel: true,
    },
    {
      id: '3',
      date: createDate(-1, 9, 0), // Ieri (passato)
      time: '09:00 - 10:00',
      therapist: {
        id: 'therapist1',
        name: 'Dr. Giulia Verdi',
        specialization: 'Fisioterapia',
        avatar: 'https://i.pravatar.cc/150?img=1',
      },
      type: 'Fisioterapia',
      notes: 'Sessione di recupero',
      status: 'completato',
      location: 'Studio 1 - Via Roma 123',
      canCancel: false,
    },
    {
      id: '4',
      date: createDate(7, 11, 0), // Fra una settimana
      time: '11:00 - 12:00',
      therapist: {
        id: 'therapist1',
        name: 'Dr. Giulia Verdi',
        specialization: 'Fisioterapia',
        avatar: 'https://i.pravatar.cc/150?img=1',
      },
      type: 'Fisioterapia',
      notes: 'Controllo settimanale',
      status: 'confermato',
      location: 'Studio 1 - Via Roma 123',
      canCancel: true,
    },
  ],
  'paziente2@test.com': [
    {
      id: '5',
      date: createDate(2, 15, 30), // Dopodomani alle 15:30
      time: '15:30 - 16:30',
      therapist: {
        id: 'therapist1',
        name: 'Dr. Giulia Verdi',
        specialization: 'Fisioterapia',
        avatar: 'https://i.pravatar.cc/150?img=1',
      },
      type: 'Fisioterapia',
      notes: 'Riabilitazione spalla',
      status: 'confermato',
      location: 'Studio 1 - Via Roma 123',
      canCancel: true,
    },
  ],
};

// Appuntamenti per i terapisti
export const therapistAppointments = {
  'terapista@test.com': [
    {
      id: '1',
      date: createDate(1, 10, 30), // Domani alle 10:30
      time: '10:30 - 11:30',
      patient: {
        id: 'patient1',
        name: 'Mario Rossi',
        email: 'paziente@test.com',
        phone: '+39 123 456 7890',
        avatar: 'https://i.pravatar.cc/150?img=3',
      },
      type: 'Fisioterapia',
      notes: 'Controllo recupero post-operatorio ginocchio',
      status: 'confermato',
      location: 'Studio 1',
      duration: 60,
      isFirstVisit: false,
    },
    {
      id: '2',
      date: createDate(2, 15, 30), // Dopodomani alle 15:30
      time: '15:30 - 16:30',
      patient: {
        id: 'patient2',
        name: 'Anna Bianchi',
        email: 'paziente2@test.com',
        phone: '+39 234 567 8901',
        avatar: 'https://i.pravatar.cc/150?img=4',
      },
      type: 'Fisioterapia',
      notes: 'Riabilitazione spalla',
      status: 'confermato',
      location: 'Studio 1',
      duration: 60,
      isFirstVisit: false,
    },
    {
      id: '3',
      date: createDate(3, 14, 0), // Fra 3 giorni alle 14:00
      time: '14:00 - 15:00',
      patient: {
        id: 'patient3',
        name: 'Luca Verdi',
        email: 'luca.verdi@email.com',
        phone: '+39 345 678 9012',
        avatar: 'https://i.pravatar.cc/150?img=5',
      },
      type: 'Consulenza Osteopatica',
      notes: 'Prima visita - problemi posturali',
      status: 'confermato',
      location: 'Studio 2',
      duration: 60,
      isFirstVisit: true,
    },
    {
      id: '4',
      date: createDate(-1, 9, 0), // Ieri (passato)
      time: '09:00 - 10:00',
      patient: {
        id: 'patient1',
        name: 'Mario Rossi',
        email: 'paziente@test.com',
        phone: '+39 123 456 7890',
        avatar: 'https://i.pravatar.cc/150?img=3',
      },
      type: 'Fisioterapia',
      notes: 'Sessione di recupero',
      status: 'completato',
      location: 'Studio 1',
      duration: 60,
      isFirstVisit: false,
    },
    {
      id: '5',
      date: createDate(7, 11, 0), // Fra una settimana
      time: '11:00 - 12:00',
      patient: {
        id: 'patient1',
        name: 'Mario Rossi',
        email: 'paziente@test.com',
        phone: '+39 123 456 7890',
        avatar: 'https://i.pravatar.cc/150?img=3',
      },
      type: 'Fisioterapia',
      notes: 'Controllo settimanale',
      status: 'confermato',
      location: 'Studio 1',
      duration: 60,
      isFirstVisit: false,
    },
    {
      id: '6',
      date: createDate(0, 16, 0), // Oggi alle 16:00
      time: '16:00 - 17:00',
      patient: {
        id: 'patient4',
        name: 'Sofia Neri',
        email: 'sofia.neri@email.com',
        phone: '+39 456 789 0123',
        avatar: 'https://i.pravatar.cc/150?img=6',
      },
      type: 'Massoterapia',
      notes: 'Tensioni muscolari collo',
      status: 'confermato',
      location: 'Studio 1',
      duration: 60,
      isFirstVisit: false,
    },
  ],
};

// Funzione per ottenere appuntamenti per data
export const getAppointmentsByDate = (userEmail, userRole, date) => {
  const dateStr = moment(date).format('YYYY-MM-DD');
  const appointments =
    userRole === 'patient'
      ? patientAppointments[userEmail] || []
      : therapistAppointments[userEmail] || [];

  return appointments.filter(
    apt => moment(apt.date).format('YYYY-MM-DD') === dateStr,
  );
};

// Funzione per verificare se si può cancellare un appuntamento
export const canCancelAppointment = appointmentDate => {
  const now = moment();
  const appointmentMoment = moment(appointmentDate);
  const hoursUntilAppointment = appointmentMoment.diff(now, 'hours');

  return hoursUntilAppointment >= 2;
};

// Funzione per ottenere giorni con appuntamenti (per evidenziarli nel calendario)
export const getMarkedDates = (userEmail, userRole) => {
  const appointments =
    userRole === 'patient'
      ? patientAppointments[userEmail] || []
      : therapistAppointments[userEmail] || [];

  const marked = {};

  appointments.forEach(apt => {
    const dateStr = moment(apt.date).format('YYYY-MM-DD');
    if (!marked[dateStr]) {
      marked[dateStr] = {
        marked: true,
        dotColor: userRole === 'patient' ? '#2196F3' : '#00695C',
        appointments: [],
      };
    }
    marked[dateStr].appointments.push(apt);
  });

  return marked;
};
