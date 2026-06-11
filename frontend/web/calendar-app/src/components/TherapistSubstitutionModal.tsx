import React, { useState, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { AlertCircle, UserX, UserCheck, ChevronsUpDown, Check } from "lucide-react";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { format } from "date-fns";
import { it } from "date-fns/locale";
import { Appointment, Therapist } from "@/types/therapy";
import { therapyAPI } from "@/lib/api";
// Aggiungi questo import per il Checkbox
import { Checkbox } from "@/components/ui/checkbox";

interface TherapistSubstitutionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (substitutionData: {
    appointmentId: number;
    newTherapistId: number;
    reason?: string;
    dontRegisterAbsence?: boolean;
    applyToGroup?: boolean;
  }) => void;
  appointment: Appointment | null;
  therapists: Therapist[];
  isABARegime?: boolean;
  /** Se false su appuntamento di gruppo, sostituisce solo il paziente selezionato (default true) */
  applyToGroup?: boolean;
}

export const TherapistSubstitutionModal: React.FC<
  TherapistSubstitutionModalProps
> = ({
  isOpen,
  onClose,
  onConfirm,
  appointment,
  therapists,
  isABARegime,
  applyToGroup = true,
}) => {
  const isGroupAppointment =
    appointment?.groupSessionId !== null &&
    appointment?.groupSessionId !== undefined &&
    appointment?.groupPatients &&
    appointment.groupPatients.length > 1;
  // Sostituzione di un singolo paziente estratto da un gruppo
  const isSingleFromGroup =
    appointment?.groupSessionId !== null &&
    appointment?.groupSessionId !== undefined &&
    !applyToGroup;
  const [selectedTherapistId, setSelectedTherapistId] = useState<string>("");
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(false);
  const [availableTherapists, setAvailableTherapists] = useState<Therapist[]>(
    []
  );

  // Aggiorna lo stato del componente aggiungendo:
  const [dontRegisterAbsence, setDontRegisterAbsence] = useState(false);
  const [loadingTherapists, setLoadingTherapists] = useState(false);
  const [originalTherapist, setOriginalTherapist] = useState<Therapist | null>(
    null
  );
  const [hasForced, setHasForced] = useState(false);
  const [showAllTherapists, setShowAllTherapists] = useState(true);
  const [filterABA, setFilterABA] = useState(false);
  const [comboboxOpen, setComboboxOpen] = useState(false);

  // Reset del form e caricamento terapisti quando si apre/chiude la modale
  useEffect(() => {
    if (isOpen && appointment?.therapist) {
      setSelectedTherapistId("");
      setReason("");
      setDontRegisterAbsence(false);
      setAvailableTherapists([]);
      setOriginalTherapist(null);
      setHasForced(false);
      setShowAllTherapists(true);
      setFilterABA(false);

      // Carica tutti i terapisti (default = nessun filtro)
      loadTherapistsBySpecialization(true);
    }
  }, [isOpen, appointment]);

  // Reload quando si cambia toggle "tutti i terapisti" o filtro ABA
  useEffect(() => {
    if (!isOpen) return;
    setAvailableTherapists([]);
    setHasForced(false);
    loadTherapistsBySpecialization(showAllTherapists);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [showAllTherapists, filterABA]);


  if (!appointment) return null;

  const loadTherapistsBySpecialization = async (force: boolean = false) => {
    if (!appointment?.therapist?.name) return;

    setLoadingTherapists(true);
    try {
      // Usa l'array therapists già disponibile per trovare il terapista originale
      const originalTherapistData = therapists.find(
        (t) => t.id === appointment.therapist?.id
      );

      // Filtro per specializzazione: priorità alla specializzazione storicizzata
      // sull'appuntamento (in che veste il terapista la stava erogando), così il
      // sostituto deve avere proprio quella specializzazione anche se l'originale
      // ne ha più di una. Fallback alla legacy del terapista per appuntamenti
      // pre-migrazione che non hanno specializationId valorizzato.
      const filterSpecializationId =
        appointment.specializationId ?? originalTherapistData?.specializationId;
      if (!filterSpecializationId) {
        throw new Error(
          "Specializzazione di riferimento non disponibile per il filtro sostituti."
        );
      }

      if (originalTherapistData) {
        setOriginalTherapist(originalTherapistData);
      }

      // Estrai data e ora dall'appuntamento per il controllo disponibilità
      const appointmentDate = new Date(appointment.datetime);
      const dateString = appointmentDate.toISOString().split("T")[0]; // Y-m-d
      const timeString = appointmentDate.toTimeString().slice(0, 5); // H:i

      // Usa l'API con controllo disponibilità (il backend escluderà automaticamente il terapista originale)
      const therapistsBySpecialization =
        await therapyAPI.getTherapistsBySpecialization(
          filterSpecializationId,
          {
            date: dateString,
            time: timeString,
            duration: appointment.duration,
            appointmentId: appointment.id, // Backend userà questo per escludere il terapista originale
            force: force, // Se true, restituisce tutti i terapisti ignorando filtro spec/coordinator
            applyABAFilter: filterABA, // Indipendente: se true forza is_aba=1 per pazienti ABA
          },
          appointment.patient?.id
        );

      if (force) {
        // Merge: aggiungi nuovi terapisti a quelli esistenti (evita duplicati)
        setAvailableTherapists((prev) => {
          const existingIds = new Set(prev.map((t) => t.id));
          const newTherapists = therapistsBySpecialization.filter(
            (t: Therapist) => !existingIds.has(t.id)
          );
          return [...prev, ...newTherapists];
        });
        setHasForced(true);
      } else {
        setAvailableTherapists(therapistsBySpecialization);
      }
    } catch (error) {
      console.error("Errore nel caricamento terapisti:", error);
      if (!force) {
        setAvailableTherapists([]);
      }
    } finally {
      setLoadingTherapists(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedTherapistId) {
      return;
    }

    setLoading(true);

    try {
      const response = await therapyAPI.substituteTherapist({
        appointmentId: appointment.id,
        newTherapistId: parseInt(selectedTherapistId),
        reason: reason.trim() || undefined,
        dontRegisterAbsence: dontRegisterAbsence, // 🔥 NUOVO
        applyToGroup: applyToGroup,
      });

      // Se la sostituzione è andata a buon fine, chiama anche il callback per aggiornare la UI
      await onConfirm({
        appointmentId: appointment.id,
        newTherapistId: parseInt(selectedTherapistId),
        reason: reason.trim() || undefined,
        dontRegisterAbsence: dontRegisterAbsence, // 🔥 NUOVO
        applyToGroup: applyToGroup,
      });

      onClose();
    } catch (error) {
      console.error("Errore durante la sostituzione:", error);
      // L'errore verrà gestito dal componente padre
      throw error;
    } finally {
      setLoading(false);
    }
  };

  const appointmentDate = new Date(appointment.datetime);
  const selectedTherapist = availableTherapists.find(
    (t) => t.id.toString() === selectedTherapistId
  );

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md py-4">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <UserX className="h-5 w-5 text-purple-600" />
            Sostituzione Terapista
            {isGroupAppointment && applyToGroup && (
              <span className="text-sm font-normal text-blue-600">
                (Gruppo - {appointment.groupPatients?.length} pazienti)
              </span>
            )}
            {isSingleFromGroup && (
              <span className="text-sm font-normal text-blue-600">
                (Solo paziente selezionato)
              </span>
            )}
            {appointment.status === "therapist_absent" && (
              <span className="text-sm font-normal text-purple-600">
                (Assente)
              </span>
            )}
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-3">
          {/* Informazioni appuntamento */}

          <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
            <div className="space-y-1 text-sm text-purple-700">
              {isGroupAppointment && applyToGroup ? (
                <>
                  <p>
                    <strong>Appuntamento di gruppo:</strong>{" "}
                  </p>
                  <div className="ml-2">
                    {appointment.groupPatients?.map((patient, index) => (
                      <span key={patient.id}>
                        {patient.name}
                        {index < appointment.groupPatients!.length - 1
                          ? ", "
                          : ""}
                      </span>
                    ))}
                  </div>
                  <p className="mt-2">
                    <strong>Terapista:</strong> {appointment.therapist?.name}
                    {originalTherapist?.specialization && (
                      <>
                        {" "}
                        • <strong>Spec.:</strong>{" "}
                        {originalTherapist.specialization}
                      </>
                    )}
                  </p>
                </>
              ) : (
                <p>
                  <strong>Paziente:</strong> {appointment.patient?.name} •
                  <strong> Terapista:</strong> {appointment.therapist?.name}
                  {originalTherapist?.specialization && (
                    <>
                      {" "}
                      • <strong>Spec.:</strong>{" "}
                      {originalTherapist.specialization}
                    </>
                  )}
                </p>
              )}
              <p>
                <strong>Data:</strong>{" "}
                {format(appointmentDate, "dd MMMM yyyy", { locale: it })} •
                <strong> Orario:</strong> {format(appointmentDate, "HH:mm")} •
                <strong> Durata:</strong> {appointment.duration} min
              </p>
            </div>
          </div>

          {isGroupAppointment && applyToGroup && (
            <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
              <div className="flex items-start gap-2">
                <AlertCircle className="h-4 w-4 text-blue-600 mt-0.5" />
                <div className="text-sm text-blue-800">
                  <p className="text-xs mt-1">
                    La sostituzione verrà applicata a tutti i{" "}
                    {appointment.groupPatients?.length} pazienti del gruppo
                    contemporaneamente.
                  </p>
                </div>
              </div>
            </div>
          )}

          {isSingleFromGroup && (
            <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
              <div className="flex items-start gap-2">
                <AlertCircle className="h-4 w-4 text-blue-600 mt-0.5" />
                <div className="text-sm text-blue-800">
                  <p className="text-xs mt-1">
                    Verrà spostato <strong>solo</strong>{" "}
                    {appointment.patient?.name || "il paziente selezionato"} sul
                    terapista prescelto. Gli altri pazienti del gruppo restano
                    con il terapista attuale e non verrà registrata alcuna
                    assenza.
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Toggle filtri */}
          <div className="space-y-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
            <div className="flex items-center justify-between gap-2">
              <Label
                htmlFor="filter-by-spec"
                className="flex items-center gap-2 text-sm cursor-pointer select-none"
              >
                <Checkbox
                  id="filter-by-spec"
                  checked={!showAllTherapists}
                  onCheckedChange={(checked) =>
                    setShowAllTherapists(checked !== true)
                  }
                />
                <span>
                  Filtra per spec. {originalTherapist?.specialization || ""}
                </span>
              </Label>
              {availableTherapists.length > 0 && (
                <span className="text-xs text-gray-600">
                  {availableTherapists.filter((t) => t.isAvailable).length}/{availableTherapists.length} liberi
                </span>
              )}
            </div>
            {isABARegime && (
              <Label
                htmlFor="filter-by-aba"
                className="flex items-center gap-2 text-sm cursor-pointer select-none"
              >
                <Checkbox
                  id="filter-by-aba"
                  checked={filterABA}
                  onCheckedChange={(checked) => setFilterABA(checked === true)}
                />
                <span>Solo terapisti ABA (paziente in piano ABA)</span>
              </Label>
            )}
          </div>

          {loadingTherapists ? (
            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
              <div className="flex items-center gap-2">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                <span className="text-blue-800">
                  Caricamento terapisti...
                </span>
              </div>
            </div>
          ) : availableTherapists.length === 0 ? (
            <div className="bg-orange-50 p-4 rounded-lg border border-orange-200">
              <div className="flex items-center gap-2 mb-2">
                <AlertCircle className="h-4 w-4 text-orange-600" />
                <span className="font-medium text-orange-800">
                  Nessun terapista trovato
                </span>
              </div>
              <p className="text-sm text-orange-700">
                {showAllTherapists
                  ? "Nessun terapista nel sistema."
                  : `Nessun terapista con specializzazione ${originalTherapist?.specialization}. Attiva "Tutti i terapisti".`}
              </p>
            </div>
          ) : (
            <>
              <form onSubmit={handleSubmit} className="space-y-3">
                {/* Combobox cercabile */}
                <div className="space-y-2">
                  <Label>Nuovo Terapista *</Label>
                  <Popover open={comboboxOpen} onOpenChange={setComboboxOpen}>
                    <PopoverTrigger asChild>
                      <Button
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={comboboxOpen}
                        className="w-full justify-between font-normal"
                      >
                        {selectedTherapist ? (
                          <span className="flex items-center gap-2 truncate">
                            {selectedTherapist.isAvailable ? (
                              <UserCheck className="h-4 w-4 text-green-600 flex-shrink-0" />
                            ) : (
                              <UserX className="h-4 w-4 text-orange-500 flex-shrink-0" />
                            )}
                            <span className="truncate">
                              {selectedTherapist.name}
                              <span className="text-xs text-gray-500 ml-1">
                                ({selectedTherapist.specialization})
                              </span>
                            </span>
                          </span>
                        ) : (
                          <span className="text-gray-500">
                            Seleziona terapista sostituto...
                          </span>
                        )}
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent
                      className="w-[var(--radix-popover-trigger-width)] p-0"
                      align="start"
                    >
                      <Command>
                        <CommandInput placeholder="Cerca per nome o specializzazione..." />
                        <CommandList>
                          <CommandEmpty>Nessun risultato</CommandEmpty>
                          <CommandGroup>
                            {availableTherapists.map((therapist) => {
                              const value = `${therapist.name} ${therapist.specialization || ""}`;
                              const isSelected =
                                selectedTherapistId === therapist.id.toString();
                              return (
                                <CommandItem
                                  key={therapist.id}
                                  value={value}
                                  onSelect={() => {
                                    setSelectedTherapistId(
                                      therapist.id.toString()
                                    );
                                    setComboboxOpen(false);
                                  }}
                                >
                                  <Check
                                    className={cn(
                                      "mr-2 h-4 w-4",
                                      isSelected ? "opacity-100" : "opacity-0"
                                    )}
                                  />
                                  {therapist.isAvailable ? (
                                    <UserCheck className="h-4 w-4 text-green-600 mr-2 flex-shrink-0" />
                                  ) : (
                                    <UserX className="h-4 w-4 text-orange-500 mr-2 flex-shrink-0" />
                                  )}
                                  <div className="flex-1 min-w-0">
                                    <div className="truncate">
                                      {therapist.name}
                                      <span className="text-xs text-gray-500 ml-1">
                                        ({therapist.specialization})
                                      </span>
                                    </div>
                                    {!therapist.isAvailable && (
                                      <div className="text-[11px] text-orange-600">
                                        {therapist.unavailabilityReason ||
                                          "Occupato — verra' creato gruppo"}
                                      </div>
                                    )}
                                  </div>
                                </CommandItem>
                              );
                            })}
                          </CommandGroup>
                        </CommandList>
                      </Command>
                    </PopoverContent>
                  </Popover>
                </div>

                {/* Warning quando si seleziona terapista occupato */}
                {selectedTherapist && !selectedTherapist.isAvailable && (
                  <div className="bg-amber-50 p-2 rounded-lg border border-amber-200 flex items-start gap-2">
                    <AlertCircle className="h-4 w-4 text-amber-600 mt-0.5 flex-shrink-0" />
                    <span className="text-sm text-amber-800">
                      Il terapista ha gia' un appuntamento in questo slot.
                      Verra' fuso in un gruppo con il/i paziente/i sostituito/i.
                    </span>
                  </div>
                )}

                {/* Preview nuovo terapista */}
                {selectedTherapist && (
                  <div className="bg-green-50 p-2 rounded-lg border border-green-200 flex items-center gap-2">
                    <UserCheck className="h-4 w-4 text-green-600" />
                    <span className="text-sm text-green-800">
                      <strong>{selectedTherapist.name}</strong> •{" "}
                      {selectedTherapist.specialization}
                      {selectedTherapist.weeklyHours &&
                        ` • ${selectedTherapist.weeklyHours}h/sett`}
                    </span>
                  </div>
                )}

                {/* Motivo sostituzione */}
                <div className="space-y-2">
                  <Label htmlFor="reason">Motivo della sostituzione</Label>
                  <Textarea
                    id="reason"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder="Inserisci il motivo della sostituzione (opzionale)"
                    rows={3}
                  />
                </div>
                {/* 🔥 NUOVO: Checkbox per non registrare assenza
                    (nascosta per sostituzione singolo paziente da gruppo:
                    il backend non registra mai l'assenza in quel caso) */}
                {!isSingleFromGroup && (
                  <div className="flex items-center space-x-2 p-2 bg-blue-50 rounded-lg border border-blue-200">
                    <Checkbox
                      id="dont-register-absence"
                      checked={dontRegisterAbsence}
                      onCheckedChange={(checked) =>
                        setDontRegisterAbsence(checked === true)
                      }
                    />
                    <Label
                      htmlFor="dont-register-absence"
                      className="text-sm text-blue-800 cursor-pointer"
                    >
                      Non registrare assenza del terapista originale
                    </Label>
                  </div>
                )}

                {/* Pulsanti */}
                <div className="flex gap-3 pt-4">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={onClose}
                    className="flex-1"
                    disabled={loading}
                  >
                    Annulla
                  </Button>
                  <Button
                    type="submit"
                    disabled={!selectedTherapistId || loading}
                    className={`flex-1 ${
                      selectedTherapist && !selectedTherapist.isAvailable
                        ? "bg-amber-600 hover:bg-amber-700"
                        : "bg-purple-600 hover:bg-purple-700"
                    }`}
                  >
                    {loading
                      ? "Sostituendo..."
                      : selectedTherapist && !selectedTherapist.isAvailable
                        ? "Conferma e crea gruppo"
                        : "Conferma Sostituzione"}
                  </Button>
                </div>
              </form>
            </>
          )}
          {/* Pulsanti alternativi se non ci sono terapisti disponibili */}
          {!loadingTherapists && availableTherapists.length === 0 && (
            <div className="flex gap-3 pt-4">
              <Button
                type="button"
                variant="outline"
                onClick={onClose}
                className="flex-1"
              >
                Chiudi
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
};
