/**
 * Revoca assenza su appuntamenti di gruppo: lo stato del gruppo è aggregato
 * (confermato/parziale/assente/completato), quindi le assenze revocabili
 * vanno lette sui singoli group_patients.
 */
jest.mock('../src/services/axiosConfig', () => ({
  __esModule: true,
  default: {post: jest.fn(), get: jest.fn()},
}));

const {getAbsentGroupPatients, canRemoveAbsence} = require('../src/api/calendar');

const groupWith = (statuses, groupStatus = 'parziale') => ({
  id: 100,
  is_group: true,
  status: groupStatus,
  datetime: new Date().toISOString(),
  group_patients: statuses.map((status, i) => ({
    id: i + 1,
    appointment_id: 100 + i,
    name: `Paziente ${i + 1}`,
    status,
  })),
});

describe('getAbsentGroupPatients', () => {
  it('restituisce solo i pazienti assenti, con il loro appointment_id', () => {
    const group = groupWith([
      'confermato',
      'assente_giustificato',
      'assente_non_giustificato',
      'completato',
    ]);

    expect(
      getAbsentGroupPatients(group).map(p => p.appointment_id),
    ).toEqual([101, 102]);
  });

  it('trova le assenze anche quando il gruppo è interamente assente', () => {
    const group = groupWith(
      ['assente_giustificato', 'assente_giustificato'],
      'assente',
    );

    expect(getAbsentGroupPatients(group)).toHaveLength(2);
  });

  it('restituisce lista vuota senza pazienti assenti o senza group_patients', () => {
    expect(getAbsentGroupPatients(groupWith(['confermato'], 'confermato'))).toEqual([]);
    expect(getAbsentGroupPatients({is_group: true})).toEqual([]);
    expect(getAbsentGroupPatients(null)).toEqual([]);
  });

  it('canRemoveAbsence sul gruppo è false perché lo stato è aggregato (motivo del bug)', () => {
    const group = groupWith(['confermato', 'assente_giustificato']);

    expect(canRemoveAbsence(group, {isTherapist: true})).toBe(false);
    expect(getAbsentGroupPatients(group)).toHaveLength(1);
  });
});
