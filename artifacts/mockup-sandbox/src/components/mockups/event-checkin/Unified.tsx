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
  hasValidLedger: boolean;
  checkedIn: boolean;
  checkedAt?: string;
  method?: string;
  manual?: boolean;
};

type LedgerRow = { id: string; attendee: string; amount: number; method: string; time: string };

const seedAttendees: Attendee[] = [
  { id: "a1", first: "Ingrid", last: "Solberg", email: "ingrid.solberg@example.no", price: 690, payment: "paid", hasValidLedger: true, checkedIn: true, checkedAt: "18:04", method: "Vipps" },
  { id: "a2", first: "Marius", last: "Haugen", email: "marius.haugen@example.no", phone: "940 23 781", price: 690, payment: "paid", hasValidLedger: false, checkedIn: false, method: "Kort" },
  { id: "a3", first: "Sofie", last: "Lunde", email: "sofie.lunde@example.no", phone: "984 22 130", price: 690, payment: "unpaid", hasValidLedger: false, checkedIn: false },
  { id: "a4", first: "Anders", last: "Berg", email: "anders.berg@example.no", price: 0, payment: "free", hasValidLedger: false, checkedIn: true, checkedAt: "18:11" },
  { id: "a5", first: "Nora", last: "Eide", email: "nora.eide@example.no", price: 690, payment: "paid", hasValidLedger: true, checkedIn: false, method: "Vipps" },
  { id: "a6", first: "Jonas", last: "Dahl", email: "jonas.dahl@example.no", price: 690, payment: "unpaid", hasValidLedger: false, checkedIn: false },
  { id: "a7", first: "Emilie", last: "Nygård", email: "emilie.nygard@example.no", price: 690, payment: "paid", hasValidLedger: true, checkedIn: false, method: "Kort" },
  { id: "a8", first: "Thomas", last: "Vik", email: "thomas.vik@example.no", price: 690, payment: "paid", hasValidLedger: true, checkedIn: true, checkedAt: "18:19", method: "Vipps" },
  { id: "a9", first: "Kari", last: "Moen", email: "kari.moen@example.no", price: 690, payment: "unpaid", hasValidLedger: false, checkedIn: false },
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
  const [vippsRequest, setVippsRequest] = useState<"idle" | "pending" | "expired">("idle");
  const [vippsPayload, setVippsPayload] = useState<{ participant_id: string; participant_ids: string[]; target_phone: string; amount: number } | null>(null);
  const [vippsSecondsLeft, setVippsSecondsLeft] = useState(80);
  const [groupPickerOpen, setGroupPickerOpen] = useState(false);
  const [groupQuery, setGroupQuery] = useState("");
  const [additionalParticipantIds, setAdditionalParticipantIds] = useState<string[]>([]);
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

  const additionalParticipants = useMemo(() => {
    if (!paymentTarget) return [];
    const normalized = groupQuery.trim().toLocaleLowerCase("nb-NO");
    return attendees
      .filter((person) =>
        person.id !== paymentTarget.id &&
        !person.checkedIn &&
        person.price > 0 &&
        !person.hasValidLedger &&
        person.payment === "unpaid"
      )
      .filter((person) => !normalized || `${person.first} ${person.last} ${person.phone ?? ""}`.toLocaleLowerCase("nb-NO").includes(normalized))
      .sort((a, b) => a.last.localeCompare(b.last, "nb"));
  }, [attendees, groupQuery, paymentTarget]);

  const selectedAdditionalParticipants = useMemo(
    () => attendees.filter((person) => additionalParticipantIds.includes(person.id) && !person.checkedIn),
    [additionalParticipantIds, attendees]
  );
  const totalPaymentAmount = paymentTarget
    ? paymentTarget.price + selectedAdditionalParticipants.reduce((total, person) => total + person.price, 0)
    : 0;

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
    setVippsRequest("idle");
    setVippsPayload(null);
    setVippsSecondsLeft(80);
    setGroupPickerOpen(false);
    setGroupQuery("");
    setAdditionalParticipantIds([]);
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

  useEffect(() => {
    if (vippsRequest !== "pending") return;
    const interval = window.setInterval(() => {
      setVippsSecondsLeft((seconds) => {
        if (seconds <= 1) {
          setVippsRequest("expired");
          return 0;
        }
        return seconds - 1;
      });
    }, 1000);
    return () => window.clearInterval(interval);
  }, [vippsRequest]);

  const markCheckedIn = (attendee: Attendee, method?: string) => {
    setActiveRoster("checked");
    setAttendees((current) => current.map((person) =>
      person.id === attendee.id
        ? { ...person, checkedIn: true, checkedAt: new Intl.DateTimeFormat("nb-NO", { hour: "2-digit", minute: "2-digit" }).format(new Date()), ...(method ? { method, payment: "paid" as const, hasValidLedger: true } : {}) }
        : person
    ));
    if (attendee.price > 0 && !attendee.hasValidLedger && method) {
      setLedger((current) => current.some((row) => row.id === attendee.id) ? current : [
        ...current,
        { id: attendee.id, attendee: `${attendee.first} ${attendee.last}`, amount: attendee.price, method, time: new Intl.DateTimeFormat("nb-NO", { hour: "2-digit", minute: "2-digit" }).format(new Date()) },
      ]);
      setLedgerOpen(true);
      showToast(attendee.payment === "paid"
        ? `Ledgerføring gjenopprettet — ingen ny belastning. ${attendee.first} er sjekket inn`
        : `Betaling simulert og ${attendee.first} sjekket inn`);
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
    if (attendee.price > 0 && !attendee.hasValidLedger) {
      previousFocus.current = document.activeElement as HTMLElement;
      setVippsRequest("idle");
      setVippsPayload(null);
      setVippsSecondsLeft(80);
      setGroupPickerOpen(false);
      setGroupQuery("");
      setAdditionalParticipantIds([]);
      setPaymentTarget(attendee);
      return;
    }
    markCheckedIn(attendee);
  };

  const confirmCashPayment = () => {
    if (!paymentTarget) return;
    if (paymentTarget.payment === "paid") return;
    const group = [paymentTarget, ...selectedAdditionalParticipants];
    group.forEach((person) => markCheckedIn(person, "Kontant"));
    if (group.length > 1) showToast(`Kontant mottatt for ${group.length} personer. ${paymentTarget.first} er registrert som betaler.`);
    closeDialog();
  };

  const recordPreviousPayment = () => {
    if (!paymentTarget || paymentTarget.payment !== "paid" || paymentTarget.hasValidLedger) return;
    markCheckedIn(paymentTarget, paymentTarget.method || "Tidligere betaling");
    closeDialog();
  };

  const sendVippsRequest = () => {
    if (!paymentTarget?.phone || paymentTarget.payment === "paid") return;
    const group = [paymentTarget, ...selectedAdditionalParticipants];
    setVippsPayload({
      participant_id: paymentTarget.id,
      participant_ids: group.map((person) => person.id),
      target_phone: paymentTarget.phone.replace(/\s+/g, ""),
      amount: totalPaymentAmount,
    });
    setVippsSecondsLeft(80);
    setVippsRequest("pending");
  };

  const receiveMockVippsConfirmation = () => {
    if (!paymentTarget || !vippsPayload || vippsPayload.participant_id !== paymentTarget.id || paymentTarget.payment === "paid" || vippsRequest !== "pending") return;
    const group = vippsPayload.participant_ids
      .map((id) => attendees.find((person) => person.id === id))
      .filter((person): person is Attendee => Boolean(person && !person.checkedIn));
    if (group.length !== vippsPayload.participant_ids.length) return;
    group.forEach((person) => markCheckedIn(person, "Vipps"));
    if (group.length > 1) showToast(`Vipps-betalingen er bekreftet for ${group.length} personer. ${paymentTarget.first} betalte.`);
    closeDialog();
  };

  const toggleAdditionalParticipant = (person: Attendee) => {
    setAdditionalParticipantIds((current) => {
      if (current.includes(person.id)) return current.filter((id) => id !== person.id);
      if (current.length >= 4) {
        showToast("Maksimalt fem personer kan sjekkes inn sammen.");
        return current;
      }
      return [...current, person.id];
    });
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
      hasValidLedger: false,
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
          <div className="uc-dialog-head"><span className="uc-dialog-icon is-payment"><CircleDollarSign size={18} /></span><button type="button" className="uc-icon-button" aria-label="Lukk og avbryt" onClick={closeDialog}><X size={18} /></button></div>
          <div className="uc-dialog-content">
            <div className="uc-heading-kicker">LOKAL SIMULERING</div>
            <h2 id="uc-payment-title">{paymentTarget.payment === "paid" ? "Bekreft tidligere betaling" : "Ubetalt — velg betalingsmåte"}</h2>
            <p><strong>{paymentTarget.first} {paymentTarget.last}</strong> {paymentTarget.payment === "paid" ? "er allerede registrert som betalt. Velg metoden som faktisk ble brukt — det sendes ingen ny belastning." : "er ikke betalt. Velg hvordan betalingen mottas. Kontant bekreftes her; Vipps må bekreftes fra forespørselen."}</p>
            <div className="uc-payment-amount">
              <span>{paymentTarget.payment === "paid" ? "Allerede betalt" : `Total for ${1 + selectedAdditionalParticipants.length} ${selectedAdditionalParticipants.length ? "personer" : "person"}`}<small>{paymentTarget.payment !== "paid" && selectedAdditionalParticipants.length > 0 ? `Betaler: ${paymentTarget.first} ${paymentTarget.last}` : ""}</small></span>
              <strong>{formatNok(paymentTarget.payment === "paid" ? paymentTarget.price : totalPaymentAmount)}</strong>
            </div>
            {paymentTarget.payment !== "paid" && <section className="uc-group-add" aria-label="Legg flere personer til samme betaling">
              <div className="uc-group-add-heading">
                <div><strong>Flere til samme betaling?</strong><small>{paymentTarget.first} {paymentTarget.last} står som betaler · maks 5 personer totalt</small></div>
                <button type="button" className="uc-group-toggle" onClick={() => setGroupPickerOpen((open) => !open)} aria-expanded={groupPickerOpen} disabled={vippsRequest === "pending"}>
                  {groupPickerOpen ? "Skjul søk" : "Legg til flere til innsjekk"}
                  <ChevronDown size={15} />
                </button>
              </div>
              {selectedAdditionalParticipants.length > 0 && <div className="uc-selected-group" aria-label="Valgt gruppe">
                <span>{paymentTarget.first} betaler for:</span>
                {selectedAdditionalParticipants.map((person) => <span className="uc-group-chip" key={person.id}>{person.first} {person.last}
                  <button type="button" aria-label={`Fjern ${person.first} ${person.last}`} onClick={() => toggleAdditionalParticipant(person)} disabled={vippsRequest === "pending"}><X size={12} /></button>
                </span>)}
              </div>}
              {groupPickerOpen && <div className="uc-group-picker">
                <label className="uc-group-search">
                  <Search size={15} aria-hidden="true" />
                  <input type="search" value={groupQuery} autoComplete="off" autoCorrect="off" spellCheck={false} placeholder="Søk blant ubetalte deltakere" aria-label="Søk blant ubetalte deltakere" onChange={(event) => setGroupQuery(event.target.value)} disabled={vippsRequest === "pending"} />
                </label>
                <div className="uc-group-count">{1 + selectedAdditionalParticipants.length} av 5 personer · {formatNok(totalPaymentAmount)} totalt</div>
                <div className="uc-group-results" role="list" aria-label="Ubetalte deltakere">
                  {additionalParticipants.map((person) => {
                    const isSelected = additionalParticipantIds.includes(person.id);
                    const atCapacity = !isSelected && selectedAdditionalParticipants.length >= 4;
                    return <button type="button" role="listitem" className={`uc-group-result${isSelected ? " is-selected" : ""}`} key={person.id} aria-pressed={isSelected} disabled={atCapacity || vippsRequest === "pending"} onClick={() => toggleAdditionalParticipant(person)}>
                      <span className="uc-group-check">{isSelected ? <Check size={14} /> : <Plus size={14} />}</span>
                      <span><strong>{person.first} {person.last}</strong><small>Ubetalt · {formatNok(person.price)}</small></span>
                      <span className="uc-group-result-action">{isSelected ? "Valgt" : "Legg til"}</span>
                    </button>;
                  })}
                  {additionalParticipants.length === 0 && <div className="uc-group-empty">{groupQuery ? "Ingen ubetalte deltakere samsvarer med søket." : "Ingen flere ubetalte deltakere å legge til."}</div>}
                </div>
                <div className="uc-group-payer-note"><UserRound size={14} /> Betalingen belastes alltid opprinnelig deltaker. Vipps sendes kun til {paymentTarget.first}s registrerte telefonnummer.</div>
              </div>}
            </section>}
            {paymentTarget.payment === "paid"
              ? <>
                <div className="uc-simulation-note"><ShieldCheck size={16} /><span><strong>Ingen ny belastning</strong> · Dette registrerer bare betalingsmåten som allerede ble brukt. Ingen ny Vipps-forespørsel utføres.</span></div>
                <div className="uc-checkin-actions">
                  <button type="button" className="uc-primary-button" onClick={recordPreviousPayment}><Check size={15} /> Registrer eksisterende {paymentTarget.method || "betaling"}</button>
                </div>
              </>
              : <div className="uc-payment-flow">
                <div className="uc-simulation-note"><ShieldCheck size={16} /><span><strong>Velg handling</strong> · Kontant bekreftes av verten. Vipps sendes separat til deltakerens telefon og krever mottatt bekreftelse.</span></div>
                {vippsRequest !== "pending" && <div className="uc-checkin-actions">
                  <button type="button" className="uc-cash-button" onClick={confirmCashPayment}><CreditCard size={17} /><span><strong>Kontant mottatt</strong><small>Bekreft kontant og sjekk inn</small></span></button>
                  <button type="button" className="uc-vipps-button" onClick={sendVippsRequest} disabled={!paymentTarget.phone} aria-describedby="uc-vipps-helper"><Smartphone size={18} /><span><strong>Send Vipps!</strong><small>{vippsRequest === "expired" ? "Send ny forespørsel" : "Be om betaling på telefon"}</small></span></button>
                </div>}
                {!paymentTarget.phone && <p id="uc-vipps-helper" className="uc-vipps-no-phone"><Smartphone size={14} /> Vipps er ikke tilgjengelig uten telefonnummer.</p>}
                {paymentTarget.phone && <p id="uc-vipps-helper" className="uc-vipps-target"><Smartphone size={14} /> Vipps-forespørsel sendes til <strong>{paymentTarget.phone}</strong></p>}
                {vippsRequest !== "idle" && <div className={`uc-vipps-request${vippsRequest === "expired" ? " is-expired" : ""}`}>
                    {vippsRequest === "pending"
                      ? <><span className="uc-vipps-request-icon"><Smartphone size={17} /></span><div><strong>Venter på Vipps-bekreftelse</strong><span>Forespørsel til {vippsPayload?.target_phone}</span><small><Clock3 size={13} /> {Math.floor(vippsSecondsLeft / 60)}:{String(vippsSecondsLeft % 60).padStart(2, "0")} igjen</small></div></>
                      : <><span className="uc-vipps-request-icon is-expired"><Clock3 size={17} /></span><div><strong>Forespørselen utløp</strong><span>Ingen innsjekking ble registrert.</span><small>Send en ny forespørsel eller avbryt.</small></div></>}
                    <div className="uc-vipps-payload"><span>Payload · target_phone</span><code>{vippsPayload?.target_phone}</code><span>{formatNok(vippsPayload?.amount ?? paymentTarget.price)}</span></div>
                    <div className="uc-vipps-demo-note"><ShieldCheck size={14} /> Forhåndsvisning: ingen ekte Vipps-forespørsel blir sendt.</div>
                  </div>}
                {vippsRequest === "pending" && <button type="button" className="uc-cancel-request" onClick={() => setVippsRequest("idle")}>Avbryt forespørselen</button>}
                </div>}
            <div className="uc-dialog-actions">
              <button type="button" className="uc-secondary-button" onClick={closeDialog}>Lukk</button>
              {vippsRequest === "pending" && <button type="button" className="uc-primary-button" onClick={receiveMockVippsConfirmation}><Check size={15} /> Simuler mottatt Vipps-bekreftelse</button>}
            </div>
          </div>
        </section>
      </div>}

      {toast && <div className="uc-toast" role="status" aria-live="polite"><CheckCircle2 size={17} />{toast}</div>}
    </div>
  );
}

function AttendeeRow({ person, eventDay, onCheckin }: { person: Attendee; eventDay: boolean; onCheckin: (person: Attendee) => void }) {
  const paymentText = person.price === 0 ? "Gratis" : !person.hasValidLedger ? "Ubetalt" : person.payment === "paid" ? "Betalt" : "Ubetalt";
  const paymentClass = person.price > 0 && !person.hasValidLedger ? "unpaid" : person.payment;
  return <article className={`uc-attendee${person.checkedIn ? " is-checked" : ""}`} role="listitem">
    <div className="uc-avatar" aria-hidden="true">{person.first.charAt(0)}{person.last.charAt(0)}</div>
    <div className="uc-person">
      <div className="uc-person-name">{person.first} {person.last}{person.manual && <span className="uc-manual-tag">Manuelt lagt til</span>}</div>
      <div className="uc-person-contact">{person.email || person.phone || "Ingen kontaktopplysninger"}</div>
    </div>
    <span className={`uc-payment-status is-${paymentClass}`} aria-label={`Betalingsstatus: ${paymentText}`}>
      <span />{paymentText}{person.method ? <small> · {person.method}</small> : null}
    </span>
    <div className="uc-attendee-action">
      {person.checkedIn
        ? <span className="uc-checked-status"><CheckCircle2 size={15} /><span>Innsjekket<small>{person.checkedAt || "I dag"}</small></span></span>
        : <button
          type="button"
          className="uc-checkin-button"
          aria-label={`Sjekk inn ${person.first} ${person.last}`}
          disabled={!eventDay}
          onClick={() => onCheckin(person)}
        >
          <Check size={15} /><span>Sjekk inn</span>
        </button>}
    </div>
    <span className="uc-price">{formatNok(person.price)}</span>
  </article>;
}

export default Unified;
