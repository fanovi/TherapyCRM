import { useEffect, useState } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

// Conferma "terapista assente": l'appuntamento si puo' creare/spostare anche
// se il terapista e' assente, ma l'operatore deve confermarlo (poi procede
// alla sostituzione). Il dialog e' unico e montato in App.tsx; si apre da
// qualunque punto con confirmTherapistAbsence(), che risolve true/false.

type PendingConfirm = {
  message: string;
  resolve: (confirmed: boolean) => void;
};

let openDialog: ((pending: PendingConfirm) => void) | null = null;

export const confirmTherapistAbsence = (message: string): Promise<boolean> =>
  new Promise((resolve) => {
    if (!openDialog) {
      // Host non montato: meglio chiedere comunque che bloccare o procedere alla cieca
      resolve(window.confirm(message));
      return;
    }
    openDialog({ message, resolve });
  });

/** Errore lanciato quando l'operatore annulla la conferma: i chiamanti lo ignorano. */
export class TherapistAbsenceCancelledError extends Error {
  constructor() {
    super("Operazione annullata: terapista assente");
    this.name = "TherapistAbsenceCancelledError";
  }
}

export const isTherapistAbsenceCancelled = (err: unknown): boolean =>
  err instanceof TherapistAbsenceCancelledError;

export const isTherapistAbsentError = (err: unknown): boolean =>
  (err as any)?.code === "THERAPIST_ABSENT";

/**
 * Esegue una chiamata di creazione/modifica appuntamento. Se il server risponde
 * THERAPIST_ABSENT chiede conferma e la ripete con confirmTherapistAbsence=true;
 * se l'operatore annulla lancia TherapistAbsenceCancelledError.
 *
 * @param call riceve il flag di conferma da inviare al server
 * @param preConfirmed true se l'operatore ha gia' confermato (es. al clic sullo slot)
 */
export async function withTherapistAbsenceConfirm<T>(
  call: (confirmTherapistAbsence: boolean) => Promise<T>,
  preConfirmed = false
): Promise<T> {
  if (preConfirmed) {
    return call(true);
  }
  try {
    return await call(false);
  } catch (err) {
    if (!isTherapistAbsentError(err)) {
      throw err;
    }
    const confirmed = await confirmTherapistAbsence(
      `${(err as Error).message} Vuoi procedere comunque? Potrai poi effettuare la sostituzione.`
    );
    if (!confirmed) {
      throw new TherapistAbsenceCancelledError();
    }
    return call(true);
  }
}

export const TherapistAbsenceConfirmDialog = () => {
  const [pending, setPending] = useState<PendingConfirm | null>(null);

  useEffect(() => {
    openDialog = (next) =>
      setPending((current) => {
        // Una conferma ancora aperta viene considerata annullata
        current?.resolve(false);
        return next;
      });
    return () => {
      openDialog = null;
    };
  }, []);

  const close = (confirmed: boolean) => {
    pending?.resolve(confirmed);
    setPending(null);
  };

  return (
    <AlertDialog
      open={pending !== null}
      onOpenChange={(open) => {
        if (!open) close(false);
      }}
    >
      <AlertDialogContent className="z-[1000]">
        <AlertDialogHeader>
          <AlertDialogTitle>Terapista assente</AlertDialogTitle>
          <AlertDialogDescription>{pending?.message}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel onClick={() => close(false)}>Annulla</AlertDialogCancel>
          <AlertDialogAction onClick={() => close(true)}>Procedi comunque</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
};
