import React, { useEffect, useRef, useState } from "react";
import { Search, Eye, Loader2, Users, X, AlertCircle, Check } from "lucide-react";
import { therapyAPI } from "@/lib/api";

type Candidate = {
  id: number;
  name: string;
  fiscalCode: string | null;
  birthDate: string | null;
  eligible: boolean;
  reasons: string[];
};

interface Props {
  isOpen: boolean;
  onClose: () => void;
  appointmentId: number;
  onConfirm: (candidates: Candidate[]) => Promise<void>;
}

const PAGE_SIZE = 20;
const DEBOUNCE_MS = 350;

export const AddPatientToGroupCombobox: React.FC<Props> = ({
  isOpen,
  onClose,
  appointmentId,
  onConfirm,
}) => {
  const [query, setQuery] = useState("");
  const [debouncedQuery, setDebouncedQuery] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<Candidate[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const abortRef = useRef<AbortController | null>(null);

  // Debounce query
  useEffect(() => {
    const t = setTimeout(() => {
      setDebouncedQuery(query);
      setPage(1);
    }, DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [query]);

  // Fetch on open / query / page change
  useEffect(() => {
    if (!isOpen) return;
    abortRef.current?.abort();
    const ctrl = new AbortController();
    abortRef.current = ctrl;

    setLoading(true);
    setError(null);
    therapyAPI
      .getGroupCandidatePatients({
        appointmentId,
        search: debouncedQuery,
        page,
        pageSize: PAGE_SIZE,
      })
      .then((res) => {
        if (ctrl.signal.aborted) return;
        if (page === 1) {
          setItems(res.items);
        } else {
          setItems((prev) => [...prev, ...res.items]);
        }
        setTotal(res.total);
      })
      .catch((e) => {
        if (ctrl.signal.aborted) return;
        setError(e instanceof Error ? e.message : "Errore caricamento");
      })
      .finally(() => {
        if (!ctrl.signal.aborted) setLoading(false);
      });

    return () => ctrl.abort();
  }, [isOpen, debouncedQuery, page, appointmentId]);

  // Reset on close
  useEffect(() => {
    if (!isOpen) {
      setQuery("");
      setDebouncedQuery("");
      setPage(1);
      setItems([]);
      setTotal(0);
      setError(null);
      setSubmitting(false);
      setSelectedIds(new Set());
    }
  }, [isOpen]);

  const hasMore = items.length < total;
  const selectedCount = selectedIds.size;

  const toggleSelect = (c: Candidate) => {
    if (!c.eligible || submitting) return;
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(c.id)) {
        next.delete(c.id);
      } else {
        next.add(c.id);
      }
      return next;
    });
  };

  const handleSubmit = async () => {
    if (selectedCount === 0 || submitting) return;
    const selected = items.filter((i) => selectedIds.has(i.id));
    try {
      setSubmitting(true);
      setError(null);
      await onConfirm(selected);
      onClose();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Errore aggiunta pazienti");
    } finally {
      setSubmitting(false);
    }
  };

  const handleOpenCalendar = (e: React.MouseEvent, patientId: number) => {
    e.stopPropagation();
    window.open(`/calendar/${patientId}`, "_blank");
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[10000] flex items-start justify-center bg-black/50 p-4 pt-20">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
        <div className="flex items-center justify-between p-4 border-b">
          <div className="flex items-center gap-2">
            <Users className="w-5 h-5 text-blue-600" />
            <h3 className="font-semibold text-gray-800">
              Aggiungi pazienti al gruppo
            </h3>
          </div>
          <button
            onClick={onClose}
            disabled={submitting}
            className="text-gray-400 hover:text-gray-600 disabled:opacity-50"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="p-4 border-b">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Nome, cognome, codice fiscale, data nascita..."
              className="w-full pl-9 pr-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              autoFocus
              disabled={submitting}
            />
          </div>
        </div>

        <div className="flex-1 overflow-y-auto">
          {error && (
            <div className="p-3 m-3 bg-red-50 border border-red-200 rounded text-sm text-red-700 flex items-start gap-2">
              <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
              <span>{error}</span>
            </div>
          )}

          {items.length === 0 && !loading && !error && (
            <div className="p-8 text-center text-sm text-gray-500">
              {debouncedQuery
                ? "Nessun paziente trovato"
                : "Inizia a digitare per cercare"}
            </div>
          )}

          <ul className="divide-y">
            {items.map((c) => {
              const isSelected = selectedIds.has(c.id);
              return (
                <li
                  key={c.id}
                  onClick={() => toggleSelect(c)}
                  className={`px-4 py-3 flex items-start gap-3 transition-colors ${
                    c.eligible
                      ? "hover:bg-blue-50 cursor-pointer"
                      : "opacity-60 bg-gray-50 cursor-not-allowed"
                  } ${isSelected ? "bg-blue-100 hover:bg-blue-100" : ""}`}
                >
                  <div className="flex-shrink-0 mt-0.5">
                    {c.eligible ? (
                      <div
                        className={`w-4 h-4 rounded border-2 flex items-center justify-center ${
                          isSelected
                            ? "bg-blue-600 border-blue-600"
                            : "border-gray-300"
                        }`}
                      >
                        {isSelected && <Check className="w-3 h-3 text-white" />}
                      </div>
                    ) : (
                      <div className="w-4 h-4" />
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-gray-800 truncate">
                        {c.name}
                      </span>
                      {!c.eligible && (
                        <span className="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded">
                          NON DISPONIBILE
                        </span>
                      )}
                    </div>
                    <div className="text-xs text-gray-500 truncate">
                      {c.fiscalCode || "-"}
                      {c.birthDate ? ` • ${c.birthDate}` : ""}
                    </div>
                    {!c.eligible && c.reasons.length > 0 && (
                      <ul className="mt-1 text-[11px] text-red-600 list-disc list-inside">
                        {c.reasons.map((r, i) => (
                          <li key={i}>{r}</li>
                        ))}
                      </ul>
                    )}
                  </div>
                  <button
                    onClick={(e) => handleOpenCalendar(e, c.id)}
                    title="Apri calendario paziente"
                    className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-100 rounded"
                  >
                    <Eye className="w-4 h-4" />
                  </button>
                </li>
              );
            })}
          </ul>

          {loading && (
            <div className="p-4 flex items-center justify-center gap-2 text-sm text-gray-500">
              <Loader2 className="w-4 h-4 animate-spin" />
              Caricamento...
            </div>
          )}

          {hasMore && !loading && (
            <div className="p-3 flex justify-center">
              <button
                onClick={() => setPage((p) => p + 1)}
                className="text-sm text-blue-600 hover:underline"
                disabled={submitting}
              >
                Carica altri ({total - items.length} rimanenti)
              </button>
            </div>
          )}
        </div>

        <div className="px-4 py-3 border-t flex items-center justify-between gap-3">
          <span className="text-xs text-gray-500">
            {items.length} di {total} • {selectedCount} selezionato/i
          </span>
          <div className="flex gap-2">
            <button
              onClick={onClose}
              disabled={submitting}
              className="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded disabled:opacity-50"
            >
              Annulla
            </button>
            <button
              onClick={handleSubmit}
              disabled={selectedCount === 0 || submitting}
              className="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
            >
              {submitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
              Aggiungi {selectedCount > 0 ? `(${selectedCount})` : ""}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
