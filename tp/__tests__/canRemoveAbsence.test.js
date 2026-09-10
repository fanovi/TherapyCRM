/**
 * Guard di revoca assenza: deve rispecchiare la semantica del server
 * (api/controllers/CalendarController.php:1587-1601).
 * - Terapista autenticato: nessun vincolo temporale, revoca anche le assenze da gestionale.
 * - Paziente in self-service: almeno 1 ora prima dell'inizio, mai sulle assenze da gestionale.
 */
jest.mock('../src/services/axiosConfig', () => ({
  __esModule: true,
  default: {post: jest.fn(), get: jest.fn()},
}));

const {canRemoveAbsence} = require('../src/api/calendar');

const MINUTE = 60 * 1000;

// Appuntamento assente il cui inizio cade `offsetMinutes` da adesso
// (negativo = gia' iniziato).
const absentAt = (offsetMinutes, extra = {}) => ({
  status: 'assente_giustificato',
  datetime: new Date(Date.now() + offsetMinutes * MINUTE).toISOString(),
  ...extra,
});

describe('canRemoveAbsence - terapista', () => {
  const asTherapist = {isTherapist: true};

  it('consente la revoca sull\'ora corrente (slot gia\' iniziato)', () => {
    expect(canRemoveAbsence(absentAt(-20), asTherapist)).toBe(true);
  });

  it('consente la revoca sull\'ora successiva', () => {
    expect(canRemoveAbsence(absentAt(40), asTherapist)).toBe(true);
  });

  it('consente la revoca dalla terza ora in poi', () => {
    expect(canRemoveAbsence(absentAt(100), asTherapist)).toBe(true);
  });

  it('consente la revoca su un appuntamento di ieri', () => {
    expect(canRemoveAbsence(absentAt(-24 * 60), asTherapist)).toBe(true);
  });

  it('consente la revoca di un\'assenza inserita dal gestionale', () => {
    expect(
      canRemoveAbsence(absentAt(-20, {is_admin_absence: true}), asTherapist),
    ).toBe(true);
  });

  it('accetta anche lo stato assente_non_giustificato', () => {
    expect(
      canRemoveAbsence(
        absentAt(-20, {status: 'assente_non_giustificato'}),
        asTherapist,
      ),
    ).toBe(true);
  });

  it('nega la revoca se lo stato non e\' assente', () => {
    expect(
      canRemoveAbsence(absentAt(-20, {status: 'confermato'}), asTherapist),
    ).toBe(false);
  });
});

describe('canRemoveAbsence - paziente (default, nessuna regressione)', () => {
  it('nega la revoca sull\'ora corrente', () => {
    expect(canRemoveAbsence(absentAt(-20))).toBe(false);
  });

  it('nega la revoca sull\'ora successiva', () => {
    expect(canRemoveAbsence(absentAt(40))).toBe(false);
  });

  it('nega la revoca a meno di 1 ora dall\'inizio', () => {
    expect(canRemoveAbsence(absentAt(59))).toBe(false);
  });

  it('consente la revoca oltre 1 ora dall\'inizio', () => {
    expect(canRemoveAbsence(absentAt(90))).toBe(true);
  });

  it('nega la revoca di un\'assenza inserita dal gestionale', () => {
    expect(canRemoveAbsence(absentAt(90, {is_admin_absence: true}))).toBe(false);
  });

  it('nega la revoca se lo stato non e\' assente', () => {
    expect(canRemoveAbsence(absentAt(90, {status: 'confermato'}))).toBe(false);
  });
});
