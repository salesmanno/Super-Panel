import { useEffect, useRef, useState, type MouseEvent } from "react";
import "./Overview.css";

type DetailKey = "requests" | "access" | "events" | "rental" | "next-event";
type IconName = "building" | "user" | "list" | "clock" | "logout" | "home" | "calendar" | "cart" | "people" | "wallet" | "megaphone" | "arrow" | "chevron" | "close" | "key" | "file" | "bell" | "spark";

const iconPaths: Record<IconName, string> = {
  building: "M3 21h18M5 21V5l7-3 7 3v16M9 9h1m4 0h1M9 13h1m4 0h1M10 21v-4h4v4",
  user: "M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z",
  list: "M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01",
  clock: "M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z",
  logout: "M10 17l5-5-5-5m5 5H3m9-9h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6",
  home: "m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z",
  calendar: "M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z",
  cart: "M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6m4 13h.01M18 21h.01",
  people: "M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 10v-2a4 4 0 0 0-3-3.87m-1-12.13a4 4 0 0 1 0 7.75",
  wallet: "M4 5.5A2.5 2.5 0 0 1 6.5 3H20v18H6.5A2.5 2.5 0 0 1 4 18.5Zm0 0V18m16-8h-5a2 2 0 0 0 0 4h5m-5-2h.01",
  megaphone: "m3 11 18-5v12L3 13Zm0 0v2a3 3 0 0 0 3 3h1l2 5h3l-2.5-6.25M21 9v6",
  arrow: "M5 12h14m-6-6 6 6-6 6",
  chevron: "m9 18 6-6-6-6",
  close: "m18 6-12 12M6 6l12 12",
  key: "M21 2 11 12m0 0a5 5 0 1 1-7.07 0A5 5 0 0 1 11 12Zm5-5 3 3m-6 0 3 3",
  file: "M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 16h8m-8 4h8",
  bell: "M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4",
  spark: "m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8Zm7 11 .9 2.1L22 17l-2.1.9L19 20l-.9-2.1L16 17l2.1-.9Z",
};

function Icon({ name, size = 18 }: { name: IconName; size?: number }) {
  return <svg aria-hidden="true" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round"><path d={iconPaths[name]} /></svg>;
}

const detailContent: Record<DetailKey, { eyebrow: string; title: string; description: string; facts: [string, string][]; note: string }> = {
  requests: {
    eyebrow: "UTLEIE · TIL BEHANDLING",
    title: "3 forespørsler venter",
    description: "Gå gjennom dato og behov før dere svarer. Ingen forespørsel er bekreftet ennå.",
    facts: [["Klubbhuset", "24. oktober · John"], ["Møterommet", "25. oktober · Mari"], ["Storsalen", "2. november · Eirik"]],
    note: "Åpne utleieoversikten for å se hele forespørselen og svare.",
  },
  access: {
    eyebrow: "UTLEIE · I MORGEN",
    title: "Bekreft adgangskode",
    description: "En utleie starter i morgen. Kontroller at tilgangsinformasjonen er klar til utsending.",
    facts: [["Lokale", "Klubbhuset"], ["Tid", "20. oktober kl. 18:00"], ["Tilgang", "Kode ikke vist i denne skissen"]],
    note: "Av sikkerhetshensyn vises ingen faktisk adgangskode her.",
  },
  events: {
    eyebrow: "ARRANGEMENT · UTKAST",
    title: "2 utkast trenger en siste sjekk",
    description: "Fullfør detaljene før arrangementene publiseres for publikum.",
    facts: [["Høstfest", "23. oktober · 19:00"], ["Åpent hus", "7. november · tidspunkt mangler"]],
    note: "Utkastene er kun eksempeldata og kan ikke publiseres fra denne skissen.",
  },
  rental: {
    eyebrow: "NESTE PÅ PLANEN · UTLEIE",
    title: "Klubbhuset i morgen",
    description: "Neste utleie er planlagt til tirsdag 20. oktober.",
    facts: [["Tid", "18:00"], ["Sted", "Klubbhuset"], ["Forberedelse", "Bekreft at tilgangsinformasjonen er klar"]],
    note: "Planlagt aktivitet. Ingen reell booking eller kode er koblet til.",
  },
  "next-event": {
    eyebrow: "NESTE PÅ PLANEN · ARRANGEMENT",
    title: "Høstfest",
    description: "Neste arrangement i kalenderen.",
    facts: [["Dato", "Fredag 23. oktober"], ["Tid", "19:00"], ["Status", "Utkast – trenger oppmerksomhet"]],
    note: "Eksempeldata for konseptskissen.",
  },
};

const modules: { label: string; icon: IconName }[] = [
  { label: "Rental", icon: "home" },
  { label: "Events", icon: "calendar" },
  { label: "Sales", icon: "cart" },
  { label: "Medlemskap", icon: "people" },
  { label: "Økonomi", icon: "wallet" },
  { label: "News", icon: "megaphone" },
];

export function Overview() {
  const [activeModule, setActiveModule] = useState("Rental");
  const [detail, setDetail] = useState<DetailKey | null>(null);
  const [activityOpen, setActivityOpen] = useState(true);
  const dialogRef = useRef<HTMLDivElement>(null);
  const returnFocusRef = useRef<HTMLElement | null>(null);

  const openDetail = (key: DetailKey, trigger: HTMLElement) => {
    returnFocusRef.current = trigger;
    setDetail(key);
  };

  const closeDetail = () => {
    setDetail(null);
    window.setTimeout(() => returnFocusRef.current?.focus(), 0);
  };

  useEffect(() => {
    if (!detail) return;
    const dialog = dialogRef.current;
    dialog?.focus();
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        event.preventDefault();
        closeDetail();
      }
      if (event.key === "Tab" && dialog) {
        const focusable = Array.from(dialog.querySelectorAll<HTMLElement>("button, [href], [tabindex]:not([tabindex='-1'])"))
          .filter((el) => !el.hasAttribute("disabled"));
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
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
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [detail]);

  const showDetail = (key: DetailKey) => (event: MouseEvent<HTMLElement>) => openDetail(key, event.currentTarget);
  const currentDetail = detail ? detailContent[detail] : null;

  return (
    <main className="pa-shell">
      <div className="pa-topnav">
        <div className="pa-top-inner">
          <div className="pa-brand"><Icon name="building" size={19} /><span>Fjordvik Kulturhus</span></div>
          <div className="pa-top-actions">
            <span className="pa-user"><Icon name="user" size={16} /><span>Alex</span></span>
            <span className="pa-top-button"><Icon name="list" size={15} /><span>Aktivitetslogg</span></span>
            <span className="pa-top-button"><Icon name="clock" size={15} /><span>Sessions</span></span>
            <span className="pa-top-button pa-logout"><Icon name="logout" size={15} /><span>Logg ut</span></span>
          </div>
        </div>
      </div>

      <section className="pa-overview" aria-label="Oppfølging og plan">
        <div className="pa-overview-inner">
          <div className="pa-overview-head">
            <div>
              <div className="pa-greeting">God morgen, Alex <span className="pa-dot" /></div>
              <p className="pa-context">Her er det som trenger deg i dag.</p>
            </div>
            <span className="pa-example">Designskisse · eksempeldata</span>
          </div>

          <div className="pa-columns">
            <section className="pa-followup" aria-labelledby="pa-followup-title">
              <div className="pa-section-title">
                <span className="pa-title-mark"><Icon name="bell" size={15} /></span>
                <h1 id="pa-followup-title">Må følges opp</h1>
                <span className="pa-total">6</span>
              </div>
              <div className="pa-task-list">
                <button type="button" className="pa-task" onClick={showDetail("requests")}>
                  <span className="pa-task-icon pa-warm"><Icon name="file" size={17} /></span>
                  <span className="pa-task-copy"><strong>Utleieforespørsler</strong><small>3 venter på svar</small></span>
                  <span className="pa-task-count">3</span><Icon name="chevron" size={16} />
                </button>
                <button type="button" className="pa-task" onClick={showDetail("access")}>
                  <span className="pa-task-icon pa-teal"><Icon name="key" size={17} /></span>
                  <span className="pa-task-copy"><strong>Adgangskode</strong><small>Bekreftelse før utleie i morgen</small></span>
                  <span className="pa-task-count pa-one">1</span><Icon name="chevron" size={16} />
                </button>
                <button type="button" className="pa-task" onClick={showDetail("events")}>
                  <span className="pa-task-icon pa-blue"><Icon name="calendar" size={17} /></span>
                  <span className="pa-task-copy"><strong>Arrangementer</strong><small>2 utkast trenger en siste sjekk</small></span>
                  <span className="pa-task-count">2</span><Icon name="chevron" size={16} />
                </button>
              </div>
            </section>

            <section className="pa-next" aria-labelledby="pa-next-title">
              <div className="pa-section-title">
                <span className="pa-title-mark pa-next-mark"><Icon name="calendar" size={15} /></span>
                <h2 id="pa-next-title">Neste på planen</h2>
              </div>
              <div className="pa-schedule">
                <button type="button" className="pa-schedule-item" onClick={showDetail("rental")}>
                  <span className="pa-date"><b>20</b><small>OKT</small></span>
                  <span className="pa-schedule-copy"><small>I MORGEN · 18:00</small><strong>Klubbhuset</strong><span>Utleie</span></span>
                  <Icon name="chevron" size={16} />
                </button>
                <button type="button" className="pa-schedule-item" onClick={showDetail("next-event")}>
                  <span className="pa-date pa-date-event"><b>23</b><small>OKT</small></span>
                  <span className="pa-schedule-copy"><small>FREDAG · 19:00</small><strong>Høstfest</strong><span>Arrangement</span></span>
                  <Icon name="chevron" size={16} />
                </button>
              </div>
            </section>
          </div>

          <div className={`pa-activity ${activityOpen ? "is-open" : ""}`}>
            <div className="pa-activity-main">
              <span className="pa-activity-icon"><Icon name="spark" size={16} /></span>
              {activityOpen && <><span className="pa-activity-label">Sist nytt</span><p><strong>John</strong> har sendt en utleieforespørsel for <strong>Klubbhuset</strong>, 24. oktober.</p><time>For 18 min siden</time></>}
            </div>
            <button type="button" className="pa-activity-toggle" aria-expanded={activityOpen} onClick={() => setActivityOpen((open) => !open)}>
              <span>{activityOpen ? "Skjul" : "Vis siste aktivitet"}</span><span className={`pa-toggle-chevron ${activityOpen ? "turned" : ""}`}><Icon name="chevron" size={15} /></span>
            </button>
          </div>
        </div>
      </section>

      <nav className="pa-module-nav" aria-label="Moduler">
        <div className="pa-module-inner" role="tablist" aria-label="Velg modul">
          {modules.map((module) => (
            <button
              type="button"
              role="tab"
              aria-selected={activeModule === module.label}
              className={`pa-module-tab ${activeModule === module.label ? "active" : ""}`}
              key={module.label}
              onClick={() => setActiveModule(module.label)}
            ><Icon name={module.icon} size={17} /><span>{module.label}</span></button>
          ))}
        </div>
      </nav>

      <section className="pa-module-continuity" aria-live="polite">
        <span className="pa-continuity-line" />
        <p><strong>{activeModule}</strong><span> · </span>{activeModule === "Rental" ? "Utleieoversikten fortsetter her." : `Modulvisning for ${activeModule} fortsetter her.`}</p>
      </section>

      {currentDetail && (
        <div className="pa-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) closeDetail(); }}>
          <section className="pa-dialog" role="dialog" aria-modal="true" aria-labelledby="pa-dialog-title" tabIndex={-1} ref={dialogRef}>
            <div className="pa-dialog-top"><span>{currentDetail.eyebrow}</span><button type="button" aria-label="Lukk detaljvisning" onClick={closeDetail}><Icon name="close" size={19} /></button></div>
            <h2 id="pa-dialog-title">{currentDetail.title}</h2>
            <p className="pa-dialog-description">{currentDetail.description}</p>
            <dl className="pa-facts">{currentDetail.facts.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl>
            <p className="pa-dialog-note"><Icon name="spark" size={16} />{currentDetail.note}</p>
            <div className="pa-dialog-footer"><span>Kun forhåndsvisning</span><button type="button" onClick={closeDetail}>Ferdig <Icon name="arrow" size={16} /></button></div>
          </section>
        </div>
      )}
    </main>
  );
}
