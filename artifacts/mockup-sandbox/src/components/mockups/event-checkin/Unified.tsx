import { useEffect, useMemo, useRef, useState } from "react";
import {
  ArrowLeft,
  ArrowRight,
  CalendarDays,
  Check,
  CheckCircle2,
  ChevronDown,
  CircleDollarSign,
  Clock3,
  CreditCard,
  Ellipsis,
  Expand,
  Mail,
  MapPin,
  Minimize,
  Plus,
  QrCode,
  Search,
  ShieldCheck,
  Smartphone,
  UserRound,
  UsersRound,
  X,
} from "lucide-react";
import "./Unified.css";

type Attendee = {
  id: string;
  first: string;
  last: string;
  email?: string;
  phone?: string;
  price: number;
  payment: "paid" | "unpaid" | "free";
  checkedIn: boolean;
  checkedAt?: string;
  method?: string;
  manual?: boolean;
};

type LedgerRow = { id: string; attendee: string; amount: number; method: string; time: string };

const seedAttendees: Attendee[] = [
  { id: "a1", first: "Ingrid", last: "Solberg", email: "ingrid.solberg@example.no", price: 690, payment: "paid", checkedIn: true, checkedAt: "18:04", method: "Vipps" },
  { id: "a2", first: "Marius", last: "Haugen", email: "marius.haugen@example.no", price: 690, payment: "paid", checkedIn: false, method: "Kort" },
  { id: "a3", first: "Sofie", last: "Lunde", email: "sofie.lunde@example.no", price: 690, payment: "unpaid", checkedIn: false },
  { id: "a4", first: "Anders", last: "Berg", email: "anders.berg@example.no", price: 0, payment: "free", checkedIn: true, checkedAt: "18:11" },
  { id: "a5", first: "Nora", last: "Eide", email: "nora.eide@example.no", price: 690, payment: "paid", checkedIn: false, method: "Vipps" },
  { id: "a6", first: "Jonas", last: "Dahl", email: "jonas.dahl@example.no", price: 690, payment: "unpaid", checkedIn: false },
  { id: "a7", first: "Emilie", last: "Nygård", email: "emilie.nygard@example.no", price: 690, payment: "paid", checkedIn: false, method: "Kort" },
  { id: "a8", first: "Thomas", last: "Vik", email: "thomas.vik@example.no", price: 690, payment: "paid", checkedIn: true, checkedAt: "18:19", method: "Vipps" },
  { id: "a9", first: "Kari", last: "Moen", email: "kari.moen@example.no", price: 690, payment: "unpaid", checkedIn: false },
];

const formatNok = (amount: number) =>
  amount === 0 ? "Gratis" : `${new Intl.NumberFormat("nb-NO").format(amount)} kr`;
const exampleBarcode = (person: Attendee) => `990000000${person.id.replace(/\D/g, "").slice(-4).padStart(4, "0")}`;

export function Unified() {
  const [page, setPage] = useState<"checkin" | "dashboard">("checkin");
  const [eventDay, setEventDay] = useState(true);
  const [attendees, setAttendees] = useState(seedAttendees);
  const [query, setQuery] = useState("");
  const [activeRoster, setActiveRoster] = useState<"waiting" | "checked">("waiting");
  const [menuOpen, setMenuOpen] = useState(false);
  const [menuMessage, setMenuMessage] = useState("");
  const [addOpen, setAddOpen] = useState(false);
  const [paymentTarget, setPaymentTarget] = useState<Attendee | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<"Kontant" | "Vipps">("Vipps");
  const [ledger, setLedger] = useState<LedgerRow[]>([]);
  const [ledgerOpen, setLedgerOpen] = useState(false);
  const [focusMode, setFocusMode] = useState(false);
  const [toast, setToast] = useState("");
  const [newGuest, setNewGuest] = useState({ first: "", last: "", email: "", phone: "" });
  const [formError, setFormError] = useState("");
  const searchRef = useRef<HTMLInputElement>(null);
  const dialogRef = useRef<HTMLDivElement>(null);
  const previousFocus = useRef<HTMLElement | null>(null);
  const toastTimer = useRef<number | undefined>(undefined);

  const checkedCount = attendees.filter((person) => person.checkedIn).length;
  const outstandingCount = attendees.filter((person) => !person.checkedIn).length;
  const checkedPercentage = attendees.length ? Math.round((checkedCount / attendees.length) * 100) : 0;

  const visibleAttendees = useMemo(() => {
    const normalized = query.trim().toLocaleLowerCase("nb-NO");
    return attendees.filter((person) => {
      const belongsInList = activeRoster === "checked" ? person.checkedIn : !person.checkedIn;
      if (!belongsInList) return false;
      const matchesSearch = !normalized || `${person.first} ${person.last} ${person.email ?? ""} ${person.phone ?? ""} ${exampleBarcode(person)}`.toLocaleLowerCase("nb-NO").includes(normalized);
      return matchesSearch;
    }).sort((a, b) => a.last.localeCompare(b.last, "nb"));
  }, [activeRoster, attendees, query]);

  const showToast = (message: string) => {
    setToast(message);
    window.clearTimeout(toastTimer.current);
    toastTimer.current = window.setTimeout(() => setToast(""), 3000);
  };

  const openAddDialog = () => {
    previousFocus.current = document.activeElement as HTMLElement;
    setFormError("");
    setAddOpen(true);
  };

  const closeDialog = () => {
    setAddOpen(false);
    setPaymentTarget(null);
    window.setTimeout(() => previousFocus.current?.focus(), 0);
  };

  useEffect(() => {
    if (!addOpen && !paymentTarget) return;
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        closeDialog();
        return;
      }
      if (event.key === "Tab" && dialogRef.current) {
        const focusables = dialogRef.current.querySelectorAll<HTMLElement>("button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex='0']");
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener("keydown", onKeyDown);
    window.setTimeout(() => dialogRef.current?.querySelector<HTMLElement>("input,button")?.focus(), 0);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [addOpen, paymentTarget]);

  useEffect(() => () => window.clearTimeout(toastTimer.current), []);

  const markCheckedIn = (attendee: Attendee, method?: string) => {
    setActiveRoster("checked");
    setAttendees((current) => current.map((person) =>
      person.id === attendee.id
        ? { ...person, checkedIn: true, checkedAt: new Intl.DateTimeFormat("nb-NO", { hour: "2-digit", minute: "2-digit" }).format(new Date()), ...(method ? { method, payment: "paid" as const } : {}) }
        : person
    ));
    if (attendee.price > 0 && attendee.payment === "unpaid" && method) {
      setLedger((current) => current.some((row) => row.id === attendee.id) ? current : [
        ...current,
        { id: attendee.id, attendee: `${attendee.first} ${attendee.last}`, amount: attendee.price, method, time: new Intl.DateTimeFormat("nb-NO", { hour: "2-digit", minute: "2-digit" }).format(new Date()) },
      ]);
      setLedgerOpen(true);
      showToast(`Betaling simulert og ${attendee.first} sjekket inn`);
    } else {
      showToast(`${attendee.first} er sjekket inn`);
    }
  };

  const handleCheckin = (attendee: Attendee) => {
    if (!eventDay) return;
    if (attendee.checkedIn) {
      showToast(`${attendee.first} er allerede sjekket inn`);
      return;
    }
    if (attendee.payment === "unpaid" && attendee.price > 0) {
      previousFocus.current = document.activeElement as HTMLElement;
      setPaymentTarget(attendee);
      return;
    }
    markCheckedIn(attendee);
  };

  const confirmPayment = () => {
    if (!paymentTarget) return;
    markCheckedIn(paymentTarget, paymentMethod);
    setPaymentTarget(null);
    window.setTimeout(() => previousFocus.current?.focus(), 0);
  };

  const addGuest = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const first = newGuest.first.trim();
    const last = newGuest.last.trim();
    if (!first || !last) {
      setFormError("Fyll inn både fornavn og etternavn.");
      return;
    }
    const guest: Attendee = {
      id: `manual-${Date.now()}`,
      first,
      last,
      email: newGuest.email.trim() || undefined,
      phone: newGuest.phone.trim() || undefined,
      price: 690,
      payment: "unpaid",
      checkedIn: false,
      manual: true,
    };
    setAttendees((current) => [...current, guest]);
    setNewGuest({ first: "", last: "", email: "", phone: "" });
    setAddOpen(false);
    setQuery(`${first} ${last}`);
    setActiveRoster("waiting");
    showToast(`${first} ${last} lagt til i deltakerlisten`);
    window.setTimeout(() => previousFocus.current?.focus(), 0);
  };

  const toggleFocus = async () => {
    if (!focusMode) {
      setFocusMode(true);
      try { await document.documentElement.requestFullscreen?.(); } catch { /* browser may deny fullscreen */ }
    } else {
      setFocusMode(false);
      if (document.fullscreenElement) {
        try { await document.exitFullscreen(); } catch { /* browser may deny fullscreen */ }
      }
    }
  };

  useEffect(() => {
    const onFullscreenChange = () => {
      if (!document.fullscreenElement) setFocusMode(false);
    };
    document.addEventListener("fullscreenchange", onFullscreenChange);
    return () => document.removeEventListener("fullscreenchange", onFullscreenChange);
  }, []);

  const dashboard = (
    <main className="uc-dashboard">
      <header className="uc-dashboard-head">
        <div>
          <div className="uc-eyebrow">ARRANGEMENTER · EKSEMPELDATA</div>
          <h1>Arrangementer</h1>
          <p>Administrer arrangementer og møt gjestene ved døren.</p>
        </div>
        <span className="uc-example-chip"><ShieldCheck size={14} /> Kun forhåndsvisning</span>
      </header>
      <section className="uc-event-grid" aria-label="Eksempelarrangementer">
        <article className="uc-event-card">
          <button className="uc-card-main" type="button" onClick={() => { setPage("checkin"); setMenuOpen(false); }} aria-label="Åpne innsjekking for Julebord 2026">
            <span className="uc-event-art"><CalendarDays size={26} /><small>06 · DES</small></span>
            <span className="uc-card-content">
              <span className="uc-event-kicker">ÅPENT ARRANGEMENT</span>
              <strong>Julebord 2026</strong>
              <span className="uc-event-meta"><CalendarDays size={14} /> Søndag 6. desember · 18:00</span>
              <span className="uc-event-meta"><MapPin size={14} /> Salesman, Oslo</span>
              <span className="uc-card-bottom"><span>9 eksempeldeltakere</span><span className="uc-open-link">Åpne arrangement <ArrowRight size={14} /></span></span>
            </span>
          </button>
          <div className="uc-menu-wrap">
            <button type="button" className="uc-icon-button uc-card-menu" aria-label="Flere valg for Julebord 2026" aria-expanded={menuOpen} onClick={() => { setMenuOpen((value) => !value); setMenuMessage(""); }}>
              <Ellipsis size={18} />
            </button>
            {menuOpen && <div className="uc-popover" role="menu">
              <button role="menuitem" type="button" onClick={() => { setMenuMessage("Arrangementsdetaljer er kun til forhåndsvisning."); setMenuOpen(false); }}>Vis arrangementsdetaljer</button>
              <button role="menuitem" type="button" onClick={() => { setMenuMessage("Redigering er deaktivert i denne forhåndsvisningen."); setMenuOpen(false); }}>Grunnopplysninger</button>
              <button role="menuitem" type="button" onClick={() => { setMenuMessage("Ingen endringer er gjort."); setMenuOpen(false); }}>Flere valg</button>
            </div>}
          </div>
        </article>
      </section>
      <p className="uc-menu-message" role="status" aria-live="polite">{menuMessage}</p>
      <footer className="uc-dashboard-footer">Eksempeldata · Ingen endringer lagres utenfor denne forhåndsvisningen.</footer>
    </main>
  );

  return (
    <div className={`uc-shell${focusMode ? " is-focus" : ""}`}>
      {page === "dashboard" ? dashboard : (
        <>
          {!focusMode && <div className="uc-demo-bar">
            <span><span className="uc-demo-dot" /> EKSEMPEL · LOKAL SIMULERING</span>
            <label className="uc-state-control">
              <span>Demo-tilstand</span>
              <select aria-label="Velg demo-tilstand" value={eventDay ? "day" : "before"} onChange={(event) => setEventDay(event.target.value === "day")}>
                <option value="before">Før arrangementsdato</option>
                <option value="day">Arrangementsdagen</option>
              </select>
            </label>
          </div>}
          <main className="uc-main">
            {!focusMode && <button type="button" className="uc-back" onClick={() => setPage("dashboard")}><ArrowLeft size={16} /> Tilbake til arrangementer</button>}
            <header className="uc-event-header">
              <div className="uc-event-mark"><CalendarDays size={23} /></div>
              <div className="uc-heading-copy">
                <div className="uc-heading-kicker">ARRANGEMENT · EKSEMPEL</div>
                <h1>Julebord 2026</h1>
                <div className="uc-header-meta"><span><CalendarDays size={14} /> Søndag 6. desember 2026</span><span><Clock3 size={14} /> 18:00–23:30</span><span><MapPin size={14} /> Salesman, Oslo</span></div>
              </div>
              <button className="uc-focus-button" type="button" onClick={toggleFocus} title={focusMode ? "Avslutt fokusmodus" : "Åpne fokusmodus"}>
                {focusMode ? <Minimize size={16} /> : <Expand size={16} />}<span>{focusMode ? "Avslutt fokus" : "Fokusmodus"}</span>
              </button>
            </header>

            {!eventDay && <div className="uc-readonly-notice" role="status"><ShieldCheck size={17} /><span><strong>Før arrangementsdato</strong> · Deltakerlisten er skrivebeskyttet. Innsjekking, betaling og endringer er ikke tilgjengelig.</span></div>}

            <section className="uc-overview" aria-label="Oppmøtestatus">
              <div className="uc-progress-block">
                <div className="uc-progress-top"><span>Oppmøte</span><strong>{checkedCount}<small> / {attendees.length}</small></strong></div>
                <div className="uc-progress-track" role="progressbar" aria-valuenow={checkedPercentage} aria-valuemin={0} aria-valuemax={100} aria-label={`${checkedPercentage} prosent innsjekket`}><span style={{ width: `${checkedPercentage}%` }} /></div>
                <small>{checkedPercentage}% innsjekket · {outstandingCount} venter</small>
              </div>
              <div className="uc-overview-divider" />
              <div className="uc-overview-stat"><span className="uc-stat-mark is-arrived"><CheckCircle2 size={16} /></span><span><strong>{checkedCount}</strong><small>Innsjekket</small></span></div>
              <div className="uc-overview-stat"><span className="uc-stat-mark is-waiting"><UsersRound size={16} /></span><span><strong>{outstandingCount}</strong><small>Gjenstår</small></span></div>
              <span className="uc-live-pill"><span /> {eventDay ? "ARRANGEMENTSDAGEN" : "IKKE STARTET"}</span>
            </section>

            <section className="uc-roster" aria-labelledby="uc-roster-title">
              <div className="uc-roster-heading">
                <div>
                  <div className="uc-roster-kicker"><UsersRound size={14} /> DELTAKEROVERSIKT</div>
                  <h2 id="uc-roster-title">Deltakere <span>{attendees.length}</span></h2>
                </div>
                <button className="uc-add-button" type="button" onClick={openAddDialog} disabled={!eventDay}><Plus size={16} /> Legg til deltaker</button>
              </div>
              <div className="uc-roster-tabs" role="tablist" aria-label="Deltakerlister">
                <button type="button" role="tab" aria-selected={activeRoster === "waiting"} aria-controls="uc-roster-panel" className={`uc-roster-tab${activeRoster === "waiting" ? " is-active" : ""}`} onClick={() => { setActiveRoster("waiting"); setQuery(""); }}>
                  <span>Ikke innsjekket</span><small>{outstandingCount}</small>
                </button>
                <button type="button" role="tab" aria-selected={activeRoster === "checked"} aria-controls="uc-roster-panel" className={`uc-roster-tab${activeRoster === "checked" ? " is-active" : ""}`} onClick={() => { setActiveRoster("checked"); setQuery(""); }}>
                  <span>Innsjekket</span><small>{checkedCount}</small>
                </button>
              </div>
              <div className="uc-tools">
                <label className="uc-search">
                  <Search size={17} aria-hidden="true" />
                  <input ref={searchRef} type="search" value={query} autoComplete="off" autoCorrect="off" spellCheck={false} inputMode="search" aria-autocomplete="none" onChange={(event) => setQuery(event.target.value)} onKeyDown={(event) => {
                    if (event.key !== "Enter") return;
                    const match = attendees.find(person => exampleBarcode(person) === query.trim());
                    if (!match) return;
                    event.preventDefault();
                    if (!eventDay) { showToast("Listen er skrivebeskyttet før arrangementsdatoen"); return; }
                    handleCheckin(match);
                    setQuery("");
                  }} placeholder="Søk navn, e-post eller strekkode" aria-label="Søk blant deltakere" />
                  {query && <button type="button" className="uc-clear-search" aria-label="Tøm søk" onClick={() => { setQuery(""); searchRef.current?.focus(); }}><X size={14} /></button>}
                </label>
              </div>
              <div className="uc-list-meta"><span>{visibleAttendees.length} av {activeRoster === "checked" ? checkedCount : outstandingCount} i denne listen</span><span title="Prøv eksempelkoden 9900000000002 og trykk Enter"><QrCode size={13} /> Søk eller scan kode</span></div>
              <div className="uc-attendee-list" role="tabpanel" id="uc-roster-panel" aria-label={activeRoster === "checked" ? "Innsjekkede deltakere" : "Deltakere som venter på innsjekking"}>
                {visibleAttendees.map((person) => <AttendeeRow key={person.id} person={person} eventDay={eventDay} onCheckin={handleCheckin} />)}
                {!visibleAttendees.length && <div className="uc-empty"><Search size={20} /><strong>{query ? "Ingen deltakere funnet" : activeRoster === "checked" ? "Ingen er sjekket inn ennå" : "Ingen venter på innsjekking"}</strong><span>{query ? "Prøv et annet søk." : activeRoster === "checked" ? "Sjekk inn en deltaker for å se den her." : "Alle deltakerne har møtt opp."}</span></div>}
              </div>
            </section>
            <div className="uc-footnote"><ShieldCheck size={14} /> Eksempeldata · Endringer er lokale og tilbakestilles ved ny innlasting.</div>
            {eventDay && <div className="uc-add-mobile"><button className="uc-add-button" type="button" onClick={openAddDialog}><Plus size={16} /> Legg til deltaker</button></div>}
            {ledger.length > 0 && <section className={`uc-ledger${ledgerOpen ? " is-open" : ""}`}>
              <button type="button" className="uc-ledger-toggle" onClick={() => setLedgerOpen((value) => !value)} aria-expanded={ledgerOpen}>
                <span><CircleDollarSign size={16} /> Simuleringslogg <small>{ledger.length}</small></span><ChevronDown size={15} />
              </button>
              {ledgerOpen && <div className="uc-ledger-body">{ledger.map((row) => <div className="uc-ledger-row" key={row.id}><span><Check size={14} /> {row.attendee}</span><span>{row.method} · {row.time}</span><strong>{formatNok(row.amount)}</strong></div>)}</div>}
            </section>}
          </main>
        </>
      )}

      {addOpen && <div className="uc-modal-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) closeDialog(); }}>
        <section className="uc-dialog" role="dialog" aria-modal="true" aria-labelledby="uc-add-title" ref={dialogRef}>
          <div className="uc-dialog-head"><span className="uc-dialog-icon"><UserRound size={18} /></span><button type="button" className="uc-icon-button" aria-label="Lukk" onClick={closeDialog}><X size={18} /></button></div>
          <div className="uc-dialog-content">
            <div className="uc-heading-kicker">ARRANGEMENTSDAGEN · EKSEMPEL</div>
            <h2 id="uc-add-title">Legg til deltaker</h2>
            <p>Opprett en enkel deltakeroppføring. Ingen kundeprofil blir laget.</p>
            <form onSubmit={addGuest}>
              <div className="uc-field-pair">
                <label>Fornavn <span>*</span><input autoComplete="given-name" required value={newGuest.first} onChange={(event) => setNewGuest({ ...newGuest, first: event.target.value })} /></label>
                <label>Etternavn <span>*</span><input autoComplete="family-name" required value={newGuest.last} onChange={(event) => setNewGuest({ ...newGuest, last: event.target.value })} /></label>
              </div>
              <label className="uc-field">E-post <span className="uc-optional">Valgfritt</span><span className="uc-input-icon"><Mail size={15} /><input type="email" autoComplete="email" value={newGuest.email} onChange={(event) => setNewGuest({ ...newGuest, email: event.target.value })} placeholder="navn@eksempel.no" /></span></label>
              <label className="uc-field">Telefon <span className="uc-optional">Valgfritt</span><span className="uc-input-icon"><Smartphone size={15} /><input type="tel" autoComplete="tel" value={newGuest.phone} onChange={(event) => setNewGuest({ ...newGuest, phone: event.target.value })} placeholder="+47" /></span></label>
              <div className="uc-add-price"><span>Eksempelbillett</span><strong>690 kr</strong><small>Ubetalt · betaling simuleres ved innsjekking</small></div>
              {formError && <p className="uc-form-error" role="alert">{formError}</p>}
              <div className="uc-dialog-actions"><button type="button" className="uc-secondary-button" onClick={closeDialog}>Avbryt</button><button type="submit" className="uc-primary-button"><Plus size={15} /> Legg til deltaker</button></div>
            </form>
          </div>
        </section>
      </div>}

      {paymentTarget && <div className="uc-modal-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) closeDialog(); }}>
        <section className="uc-dialog uc-payment-dialog" role="dialog" aria-modal="true" aria-labelledby="uc-payment-title" ref={dialogRef}>
          <div className="uc-dialog-head"><span className="uc-dialog-icon is-payment"><CircleDollarSign size={18} /></span><button type="button" className="uc-icon-button" aria-label="Lukk" onClick={closeDialog}><X size={18} /></button></div>
          <div className="uc-dialog-content">
            <div className="uc-heading-kicker">LOKAL SIMULERING</div>
            <h2 id="uc-payment-title">Bekreft betaling</h2>
            <p><strong>{paymentTarget.first} {paymentTarget.last}</strong> har en ubetalt billett. Velg eksempelmetode for å simulere betaling og sjekke inn.</p>
            <div className="uc-payment-amount"><span>Beløp</span><strong>{formatNok(paymentTarget.price)}</strong></div>
            <fieldset className="uc-methods"><legend>Betalingsmetode</legend>
              {(["Vipps", "Kontant"] as const).map((method) => <label key={method} className={paymentMethod === method ? "is-selected" : ""}>
                <input type="radio" name="payment-method" value={method} checked={paymentMethod === method} onChange={() => setPaymentMethod(method)} />
                <span className="uc-method-icon">{method === "Vipps" ? <Smartphone size={17} /> : <CreditCard size={17} />}</span><span><strong>{method}</strong><small>Eksempelmetode</small></span>
                <span className="uc-radio-mark" />
              </label>)}
            </fieldset>
            <div className="uc-simulation-note"><ShieldCheck size={16} /><span><strong>Kun simulering</strong> · Ingen betaling sendes til Vipps eller andre betalingstjenester.</span></div>
            <div className="uc-dialog-actions"><button type="button" className="uc-secondary-button" onClick={closeDialog}>Avbryt</button><button type="button" className="uc-primary-button" onClick={confirmPayment}><Check size={15} /> Bekreft og sjekk inn</button></div>
          </div>
        </section>
      </div>}

      {toast && <div className="uc-toast" role="status" aria-live="polite"><CheckCircle2 size={17} />{toast}</div>}
    </div>
  );
}

function AttendeeRow({ person, eventDay, onCheckin }: { person: Attendee; eventDay: boolean; onCheckin: (person: Attendee) => void }) {
  const paymentText = person.payment === "paid" ? "Betalt" : person.payment === "unpaid" ? "Ubetalt" : "Gratis";
  return <article className={`uc-attendee${person.checkedIn ? " is-checked" : ""}`} role="listitem">
    <div className="uc-avatar" aria-hidden="true">{person.first.charAt(0)}{person.last.charAt(0)}</div>
    <div className="uc-person">
      <div className="uc-person-name">{person.first} {person.last}{person.manual && <span className="uc-manual-tag">Manuelt lagt til</span>}</div>
      <div className="uc-person-contact">{person.email || person.phone || "Ingen kontaktopplysninger"}</div>
    </div>
    <span className={`uc-payment-status is-${person.payment}`} aria-label={`Betalingsstatus: ${paymentText}`}>
      <span />{paymentText}{person.payment === "paid" && person.method ? <small> · {person.method}</small> : null}
    </span>
    <div className="uc-attendee-action">
      {person.checkedIn
        ? <span className="uc-checked-status"><CheckCircle2 size={15} /><span>Innsjekket<small>{person.checkedAt || "I dag"}</small></span></span>
        : <button type="button" className="uc-checkin-button" disabled={!eventDay} onClick={() => onCheckin(person)}>{person.payment === "unpaid" && person.price > 0 ? <><CircleDollarSign size={15} /> Betal & sjekk inn</> : <><Check size={15} /> Sjekk inn</>}</button>}
    </div>
    <span className="uc-price">{formatNok(person.price)}</span>
  </article>;
}

export default Unified;
