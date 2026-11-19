import { useEffect, useRef, useState } from "react";
import "./eventRoster.css";

type EventId = "event-dinner" | "event-dance";
type Registration = { name: string; email: string; phone: string; ticket: string; payment: string; status: string };
type Person = Registration & { id: string; checked: boolean; method: string };
type Props = {
  eventId: EventId | null;
  title: string;
  registrations: Record<EventId, Registration[]>;
  onClose: () => void;
};

export function EventRoster({ eventId, title, registrations, onClose }: Props) {
  const [records, setRecords] = useState<Record<EventId, Person[]>>(() =>
    Object.fromEntries(Object.entries(registrations).map(([key, rows]) => [
      key, rows.map((row, index) => ({ ...row, id: `${key}-${index}`, checked: false, method: row.payment === "Betalt" ? "Vipps" : "" })),
    ])) as Record<EventId, Person[]>);
  const [query, setQuery] = useState("");
  const [filter, setFilter] = useState("all");
  const [eventDay, setEventDay] = useState(false);
  const [adding, setAdding] = useState(false);
  const [draft, setDraft] = useState({ first: "", last: "", email: "", phone: "", price: "" });
  const [error, setError] = useState("");
  const [paymentId, setPaymentId] = useState<string | null>(null);
  const [method, setMethod] = useState("Kontant");
  const closeRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    if (!eventId) return;
    const previous = document.activeElement as HTMLElement | null;
    setQuery(""); setFilter("all"); setAdding(false); setPaymentId(null); setError("");
    closeRef.current?.focus({ preventScroll: true });
    const escape = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
    };
    document.addEventListener("keydown", escape);
    return () => {
      document.removeEventListener("keydown", escape);
      previous?.focus({ preventScroll: true });
    };
  }, [eventId]); // Changing the selected service starts a fresh view, not a fresh roster.

  if (!eventId) return null;
  const rows = records[eventId];
  const checked = rows.filter(row => row.checked).length;
  const visible = rows.filter(row =>
    `${row.name} ${row.email} ${row.phone}`.toLocaleLowerCase("nb-NO").includes(query.trim().toLocaleLowerCase("nb-NO")) &&
    (filter === "all" || (filter === "checked" ? row.checked : filter === "unpaid" ? row.payment !== "Betalt" : !row.checked)));
  const update = (id: string, patch: Partial<Person>) => setRecords(current => ({
    ...current, [eventId]: current[eventId].map(row => row.id === id ? { ...row, ...patch } : row),
  }));
  const addPerson = () => {
    if (!draft.first.trim() || !draft.last.trim()) { setError("Fyll inn både fornavn og etternavn."); return; }
    const price = draft.price === "" ? (eventId === "event-dinner" ? 500 : 400) : Number(draft.price.replace(",", "."));
    if (!Number.isFinite(price) || price < 0) { setError("Oppgi en gyldig billettpris."); return; }
    const person: Person = {
      id: crypto.randomUUID(), name: `${draft.first.trim()} ${draft.last.trim()}`, email: draft.email.trim(), phone: draft.phone.trim(),
      ticket: `${new Intl.NumberFormat("nb-NO").format(price)} kr`, payment: price === 0 ? "Betalt" : "Ubetalt",
      status: "Påmeldt", checked: false, method: price === 0 ? "Uten betaling" : "",
    };
    setRecords(current => ({ ...current, [eventId]: [...current[eventId], person] }));
    setAdding(false); setDraft({ first: "", last: "", email: "", phone: "", price: "" }); setError("");
  };
  const payment = rows.find(row => row.id === paymentId);

  return <section className="tc-roster" role="dialog" aria-modal="false" aria-labelledby="tc-roster-title">
    <header className="tc-roster-header">
      <div><span>ARRANGEMENT · DELTAKERE OG INNSJEKK</span><h2 id="tc-roster-title">Påmeldte {title}</h2><p>Lokal demonstrasjon · ingen ekte påmelding eller betaling utføres.</p></div>
      <button ref={closeRef} type="button" onClick={onClose} aria-label="Lukk deltakerlisten">×</button>
    </header>
    <div className="tc-roster-summary"><span><b>{rows.length}</b> Påmeldte</span><span><b>{checked}</b> Sjekket inn</span><span><b>{rows.length - checked}</b> Ikke ankommet</span><span><b>{rows.filter(row => row.payment !== "Betalt").length}</b> Ubetalte</span></div>
    <div className="tc-roster-controls">
      <label className="tc-roster-search">Søk etter deltaker<input type="search" placeholder="Navn, e-post eller telefon" value={query} onChange={e => setQuery(e.target.value)} autoComplete="off" /></label>
      <label>Vis<select value={filter} onChange={e => setFilter(e.target.value)}><option value="all">Alle deltakere</option><option value="waiting">Ikke ankommet</option><option value="checked">Sjekket inn</option><option value="unpaid">Ubetalte</option></select></label>
      <button type="button" className="tc-roster-primary" onClick={() => { setAdding(true); setError(""); }}>＋ Legg til deltaker</button>
    </div>
    <div className="tc-roster-demo"><span>{eventDay ? "Innsjekking er åpen i demonstrasjonen." : "Deltakerlisten er tilgjengelig nå. Innsjekking åpner på arrangementsdagen."}</span><label><input type="checkbox" checked={eventDay} onChange={e => { setEventDay(e.target.checked); setPaymentId(null); }} /> Demonstrer arrangementsdag</label></div>
    <div className="tc-roster-scroll">
      {adding && <form className="tc-roster-add" onSubmit={e => { e.preventDefault(); addPerson(); }}>
        <strong>Ny deltaker</strong>
        <div>{([["first", "Fornavn"], ["last", "Etternavn"], ["email", "E-post"], ["phone", "Telefon"], ["price", "Billettpris (kr)"]] as const).map(([key, label]) => <label key={key}>{label}<input autoFocus={key === "first"} type={key === "email" ? "email" : "text"} inputMode={key === "price" ? "decimal" : key === "phone" ? "tel" : undefined} value={draft[key]} placeholder={key === "price" ? eventId === "event-dinner" ? "500" : "400" : ""} onChange={e => setDraft(current => ({ ...current, [key]: e.target.value }))} /></label>)}</div>
        {error && <p role="alert">{error}</p>}
        <footer><button type="button" onClick={() => setAdding(false)}>Avbryt</button><button type="submit" className="tc-roster-primary">Lagre deltaker</button></footer>
      </form>}
      <div className="tc-roster-table" role="table" aria-label="Deltakerliste">
        <div className="tc-roster-tablehead" role="row"><span role="columnheader">Deltaker / kontakt</span><span role="columnheader">Billettpris</span><span role="columnheader">Betaling</span><span role="columnheader">Innsjekking</span></div>
        {visible.map(row => <div className="tc-roster-row" role="row" key={row.id}>
          <div role="cell"><strong>{row.name}</strong><a href={`mailto:${row.email}`}>{row.email || "Ingen e-post"}</a><a href={`tel:${row.phone.replaceAll(" ", "")}`}>{row.phone || "Ingen telefon"}</a></div>
          <div role="cell" className="tc-roster-price"><small>Billettpris</small><b>{row.ticket}</b></div>
          <div role="cell"><span className={`tc-roster-status ${row.payment === "Betalt" ? "paid" : "unpaid"}`}>{row.payment === "Betalt" ? "Betalt" : "Ubetalt"}</span><small>{row.method || "Betales ved ankomst"}</small></div>
          <div role="cell">{row.checked ? <span className="tc-roster-status paid">✓ Sjekket inn</span> : <button type="button" disabled={!eventDay} onClick={() => row.payment === "Betalt" ? update(row.id, { checked: true }) : setPaymentId(row.id)}>{row.payment === "Betalt" ? "Sjekk inn" : "Betaling og innsjekk"}</button>}</div>
        </div>)}
      </div>
      {!visible.length && <p className="tc-roster-empty">Ingen deltakere samsvarer med valget ditt.</p>}
    </div>
    {payment && <form className="tc-roster-payment" onSubmit={e => {
      e.preventDefault();
      if (!eventDay) return;
      update(payment.id, { payment: "Betalt", method, checked: true, status: "Påmeldt" }); setPaymentId(null);
    }}><div><strong>Betaling og innsjekk · {payment.name}</strong><p>{payment.ticket} · bekreft bare betaling som er mottatt. Dette er en lokal demo.</p></div><label>Betalingsmetode<select value={method} onChange={e => setMethod(e.target.value)}><option>Kontant</option><option>Vipps</option><option>Kort</option></select></label><button type="button" onClick={() => setPaymentId(null)}>Avbryt</button><button type="submit" className="tc-roster-primary">Bekreft mottatt og sjekk inn</button></form>}
    <footer className="tc-roster-footer"><span>Viser {visible.length} av {rows.length} deltakere</span><button type="button" onClick={onClose}>Lukk</button></footer>
  </section>;
}
