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

const companyId = 42;
const staffDirectory = [
  { id: 11, companyId, name: "Mari Nilsen" },
  { id: 12, companyId, name: "Eirik Solberg" },
  { id: 13, companyId, name: "Sofie Dahl" },
  { id: 90, companyId: 77, name: "Ukjent ansatt" },
];
const initialTenantStaff = [
  { id: 201, staffId: 11, companyId, modules: ["Rental", "Events"] },
  { id: 202, staffId: 12, companyId, modules: ["Rental"] },
  { id: 203, staffId: 13, companyId, modules: [] },
  { id: 204, staffId: 90, companyId: 77, modules: ["Sales"] },
  { id: 205, staffId: null, companyId, modules: ["News"] },
];
const delegatedModules = ["Rental", "Events", "Sales", "Medlemskap"];
const activityItems = [
  { id: "rental-request", timestamp: "2026-10-19T10:42:00", actor: "John", action: "sendte forespørsel om", target: "Klubbhuset · 24. okt.", shortTime: "Man. 19. okt. · 10:42" },
  { id: "event-draft", timestamp: "2026-10-19T09:18:00", actor: "Sofie", action: "lagret utkastet", target: "Høstfest · fre. 23. okt.", shortTime: "Man. 19. okt. · 09:18" },
  { id: "rental-updated", timestamp: "2026-10-18T16:05:00", actor: "Mari", action: "oppdaterte tidspunkt for", target: "Møterommet · 25. okt.", shortTime: "Søn. 18. okt. · 16:05" },
  { id: "access-ready", timestamp: "2026-10-18T13:32:00", actor: "Eirik", action: "bekreftet klargjøring av", target: "Klubbhuset · utleie", shortTime: "Søn. 18. okt. · 13:32" },
].sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());

const attentionItems = [
  { id: "rental-request", timestamp: "2026-10-19T10:42:00", actor: "John", title: "Ny utleieforespørsel", detail: "Klubbhuset · 24. oktober", module: "Rental", icon: "file" as const },
  { id: "event-draft", timestamp: "2026-10-19T09:18:00", actor: "Sofie", title: "Arrangement trenger en siste sjekk", detail: "Høstfest · 23. oktober", module: "Events", icon: "calendar" as const },
  { id: "access-ready", timestamp: "2026-10-19T08:50:00", actor: "Utleie", title: "Bekreft adgangskode", detail: "Klubbhuset · utleie i morgen", module: "Rental", icon: "key" as const },
  { id: "event-draft-old", timestamp: "2026-10-18T16:05:00", actor: "Mari", title: "Utkast venter på gjennomgang", detail: "Åpent hus · 7. november", module: "Events", icon: "calendar" as const },
].sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());

export function Overview() {
  const [activeModule, setActiveModule] = useState("Rental");
  const [detail, setDetail] = useState<DetailKey | null>(null);
  const [activityIndex, setActivityIndex] = useState(0);
  const [dismissedAttentionIds, setDismissedAttentionIds] = useState<string[]>([]);
  const [profileOpen, setProfileOpen] = useState(false);
  const [managementOpen, setManagementOpen] = useState(false);
  const [currentAdmin, setCurrentAdmin] = useState({ tenant_supervisor: 1 });
  const [tenantStaff, setTenantStaff] = useState(initialTenantStaff);
  const [moduleChoices, setModuleChoices] = useState<Record<number, string>>({});
  const [modulePickerOpen, setModulePickerOpen] = useState<Record<number, boolean>>({});
  const [passwords, setPasswords] = useState<Record<number, string>>({});
  const [feedback, setFeedback] = useState("");
  const dialogRef = useRef<HTMLDivElement>(null);
  const returnFocusRef = useRef<HTMLElement | null>(null);
  const profileRef = useRef<HTMLDivElement>(null);
  const profileTriggerRef = useRef<HTMLButtonElement>(null);
  const linkedTenantUsers = tenantStaff.flatMap((entry) => {
    const staff = staffDirectory.find((person) => person.id === entry.staffId && person.companyId === entry.companyId);
    return entry.companyId === companyId && staff?.companyId === companyId ? [{ ...entry, name: staff.name }] : [];
  });
  const visibleModules = currentAdmin.tenant_supervisor === 1
    ? modules
    : modules.filter((module) => module.label === "Rental");
  const visibleAttentionItems = attentionItems.filter((item) =>
    visibleModules.some((module) => module.label === item.module)
      && !dismissedAttentionIds.includes(item.id)
  );
  const openAttention = (item: (typeof attentionItems)[number]) => {
    if (!visibleModules.some((module) => module.label === item.module)) return;
    setActiveModule(item.module);
    setDismissedAttentionIds((current) => [...current, item.id]);
  };
  const dismissAttention = (itemId: string) => {
    setDismissedAttentionIds((current) => current.includes(itemId) ? current : [...current, itemId]);
  };

  const openDetail = (key: DetailKey, trigger: HTMLElement) => {
    returnFocusRef.current = trigger;
    setDetail(key);
  };

  const closeDetail = () => {
    setDetail(null);
    window.setTimeout(() => returnFocusRef.current?.focus(), 0);
  };

  useEffect(() => {
    if (!detail && !managementOpen) return;
    const dialog = dialogRef.current;
    dialog?.focus();
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        event.preventDefault();
        if (managementOpen) closeManagement();
        else closeDetail();
      }
      if (event.key === "Tab" && dialog) {
        const focusable = Array.from(dialog.querySelectorAll<HTMLElement>("button, input, select, [href], [tabindex]:not([tabindex='-1'])"))
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
  }, [detail, managementOpen]);

  useEffect(() => {
    if (!profileOpen) return;
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setProfileOpen(false);
        window.setTimeout(() => profileRef.current?.querySelector("button")?.focus(), 0);
      }
    };
    document.addEventListener("keydown", onKeyDown);
    return () => document.removeEventListener("keydown", onKeyDown);
  }, [profileOpen]);

  const showDetail = (key: DetailKey) => (event: MouseEvent<HTMLElement>) => openDetail(key, event.currentTarget);
  const currentDetail = detail ? detailContent[detail] : null;
  const openManagement = (trigger: HTMLElement) => {
    returnFocusRef.current = profileTriggerRef.current ?? trigger;
    setFeedback("");
    setPasswords({});
    setProfileOpen(false);
    setManagementOpen(true);
  };
  const closeManagement = () => {
    setManagementOpen(false);
    setPasswords({});
    setModuleChoices({});
    setModulePickerOpen({});
    window.setTimeout(() => returnFocusRef.current?.focus(), 0);
  };
  const canManageTenantUser = (userId: number) => currentAdmin.tenant_supervisor === 1
    && linkedTenantUsers.some((user) => user.id === userId && user.companyId === companyId);
  const revokeModule = (userId: number, module: string) => {
    if (!canManageTenantUser(userId)) return;
    setTenantStaff((current) => current.map((entry) => entry.id === userId
      && entry.companyId === companyId
      ? { ...entry, modules: entry.modules.filter((assigned) => assigned !== module) }
      : entry));
    setFeedback(`${module} er fjernet fra brukeren.`);
  };
  const addModule = (userId: number) => {
    const selected = moduleChoices[userId];
    const user = tenantStaff.find((entry) => entry.id === userId);
    if (!canManageTenantUser(userId) || !selected || !user || user.companyId !== companyId
      || !delegatedModules.includes(selected) || user.modules.includes(selected)) return;
    setTenantStaff((current) => current.map((entry) => entry.id === userId
      && entry.companyId === companyId
      ? { ...entry, modules: [...entry.modules, selected] }
      : entry));
    setModuleChoices((current) => ({ ...current, [userId]: "" }));
    setModulePickerOpen((current) => ({ ...current, [userId]: false }));
    setFeedback(`${selected} er lagt til.`);
  };
  const saveDemoPassword = (userId: number) => {
    if (!canManageTenantUser(userId)) return;
    const password = passwords[userId] ?? "";
    if (password.length < 12) {
      setFeedback("Demopassord må være minst 12 tegn.");
      return;
    }
    setPasswords((current) => ({ ...current, [userId]: "" }));
    setFeedback("Demopassordet er simulert og ikke lagret.");
  };

  return (
    <main className="pa-shell">
      <div className="pa-topnav">
        <div className="pa-top-inner">
          <div className="pa-brand"><Icon name="building" size={19} /><span>Fjordvik Kulturhus</span></div>
          <div className="pa-top-actions">
            <div className="pa-profile-wrap" ref={profileRef}>
              <button type="button" className="pa-profile-trigger" aria-label="Åpne profilmeny" aria-expanded={profileOpen} ref={profileTriggerRef} onClick={() => setProfileOpen((open) => !open)}>
                <Icon name="user" size={16} /><span>Alex</span><span className="pa-profile-chevron">⌄</span>
              </button>
              {profileOpen && <div className="pa-profile-menu" aria-label="Profilmeny">
                <div className="pa-menu-heading">Alex · Fjordvik Kulturhus</div>
                <label className="pa-role-switch">
                  <span><strong>Demorolle</strong><small>{currentAdmin.tenant_supervisor === 1 ? "Firmaveileder" : "Vanlig bruker"}</small></span>
                  <input aria-label="Bytt demorolle mellom firmaveileder og vanlig bruker" type="checkbox" checked={currentAdmin.tenant_supervisor === 1} onChange={(event) => setCurrentAdmin({ tenant_supervisor: event.target.checked ? 1 : 0 })} />
                </label>
                {currentAdmin.tenant_supervisor === 1 && <button type="button" className="pa-menu-action" onClick={(event) => openManagement(event.currentTarget)}>
                  <Icon name="people" size={16} /><span>Administrer firmabrukere</span><Icon name="chevron" size={15} />
                </button>}
                <p className="pa-menu-demo">Kun demonstrasjon · ingen kontoer endres</p>
              </div>}
            </div>
            <span className="pa-top-button pa-logout"><Icon name="logout" size={15} /><span>Logg ut</span></span>
          </div>
        </div>
      </div>

      <section className="pa-overview" aria-label="Oppfølging og plan">
        <div className="pa-overview-inner">
          <div className="pa-overview-head">
            <div>
              <div className="pa-greeting">God morgen, Alex <span className="pa-dot" /></div>
            </div>
            <span className="pa-example">Designskisse · eksempeldata</span>
          </div>

          <div className="pa-columns">
            <section className="pa-followup" aria-labelledby="pa-followup-title">
              <div className="pa-section-title">
                <span className="pa-title-mark"><Icon name="bell" size={15} /></span>
                <h1 id="pa-followup-title">Varsler</h1>
                <span className="pa-total">{visibleAttentionItems.length}</span>
              </div>
              {visibleAttentionItems.length > 0 ? (
                <div className="pa-notice-stack" aria-label={`${visibleAttentionItems.length} varsler, nyeste først`}>
                  {visibleAttentionItems.length > 2 && <>
                    <span className="pa-notice-stack-edge pa-notice-stack-back" aria-hidden="true" />
                    <span className="pa-notice-stack-edge pa-notice-stack-middle" aria-hidden="true" />
                  </>}
                  <article className="pa-notice-card">
                    <span className="pa-notice-mark" aria-hidden="true">
                      <Icon name={visibleAttentionItems[0].icon} size={19} />
                    </span>
                    <button
                      type="button"
                      className="pa-notice-open"
                      aria-label={`Åpne ${visibleAttentionItems[0].title} i ${visibleAttentionItems[0].module}`}
                      onClick={() => openAttention(visibleAttentionItems[0])}
                    >
                      <span className="pa-notice-meta">
                        {visibleAttentionItems[0].module}<span aria-hidden="true">·</span>
                        {new Intl.DateTimeFormat("nb-NO", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })
                          .format(new Date(visibleAttentionItems[0].timestamp))}
                      </span>
                      <strong>{visibleAttentionItems[0].title}</strong>
                      <span className="pa-notice-detail">{visibleAttentionItems[0].actor} · {visibleAttentionItems[0].detail}</span>
                      <span className="pa-notice-cta">Åpne i {visibleAttentionItems[0].module}<Icon name="arrow" size={15} /></span>
                    </button>
                    <button
                      type="button"
                      className="pa-notice-dismiss"
                      aria-label={`Lukk varselet: ${visibleAttentionItems[0].title}`}
                      onClick={() => dismissAttention(visibleAttentionItems[0].id)}
                    ><Icon name="close" size={16} /></button>
                  </article>
                  {visibleAttentionItems.length > 1 && (
                    <span className="pa-notice-stack-count" aria-live="polite">
                      {visibleAttentionItems.length - 1} {visibleAttentionItems.length === 2 ? "varsel til" : "varsler til"}
                    </span>
                  )}
                </div>
              ) : (
                <div className="pa-notice-empty" role="status">
                  <span>Alt fulgt opp</span>
                  <small>Ingen nye varsler for dine moduler.</small>
                </div>
              )}
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

          {currentAdmin.tenant_supervisor === 1 && <div className="pa-activity" aria-label="Siste aktivitet, nyeste først">
            <div className="pa-activity-heading"><span className="pa-activity-icon"><Icon name="spark" size={15} /></span><strong>Aktivitet</strong><span>Nyeste først</span></div>
            <div className="pa-activity-cards" aria-live="polite">
              {activityItems.slice(activityIndex, activityIndex + 3).map((item, index) => <article className={`pa-activity-card ${index === 0 ? "pa-activity-latest" : ""}`} key={item.id}>
                <time dateTime={item.timestamp}>{item.shortTime}</time>
                <p><strong>{item.actor}</strong> {item.action} <b>{item.target}</b></p>
              </article>)}
            </div>
            <div className="pa-activity-controls" aria-label="Bla i aktivitetskort">
              <button type="button" aria-label="Vis nyere aktivitet" disabled={activityIndex === 0} onClick={() => setActivityIndex((index) => Math.max(0, index - 1))}>‹</button>
              <span className="pa-activity-count-desktop">{activityIndex + 1}–{Math.min(activityIndex + 3, activityItems.length)} av {activityItems.length}</span>
              <span className="pa-activity-count-mobile">{activityIndex + 1} av {activityItems.length}</span>
              <button type="button" aria-label="Vis eldre aktivitet" disabled={activityIndex >= activityItems.length - 3} onClick={() => setActivityIndex((index) => Math.min(activityItems.length - 3, index + 1))}>›</button>
            </div>
          </div>}
        </div>
      </section>

      <nav className="pa-module-nav" aria-label="Moduler">
        <div className="pa-module-inner" role="tablist" aria-label="Velg modul">
          {visibleModules.map((module) => (
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
      {managementOpen && currentAdmin.tenant_supervisor === 1 && (
        <div className="pa-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) closeManagement(); }}>
          <section className="pa-dialog pa-users-dialog" role="dialog" aria-modal="true" aria-labelledby="pa-users-title" tabIndex={-1} ref={dialogRef}>
            <div className="pa-dialog-top"><span>DEMO · FIRMAADMINISTRASJON</span><button type="button" aria-label="Lukk brukerdialog" onClick={closeManagement}><Icon name="close" size={19} /></button></div>
            <h2 id="pa-users-title">Firmabrukere</h2>
            <p className="pa-dialog-description">Viser ansatte som er knyttet til Fjordvik Kulturhus. Endringer gjelder bare denne skissen.</p>
            <div className="pa-demo-warning"><Icon name="spark" size={15} /><span>Demomodus · passord lagres ikke og moduler endres kun lokalt.</span></div>
            {linkedTenantUsers.length > 0 ? <div className="pa-user-list">
              {linkedTenantUsers.map((user) => {
                const availableModules = delegatedModules.filter((module) => !user.modules.includes(module));
                return <article className="pa-managed-user" key={user.id}>
                  <div className="pa-managed-user-head"><span className="pa-initials" aria-hidden="true">{user.name.split(" ").map((part) => part[0]).join("").slice(0, 2)}</span><div><strong>{user.name}</strong><small>Firmabruker · eksempeldata</small></div></div>
                  <div className="pa-assigned-modules" aria-label={`Moduler for ${user.name}`}>
                    {user.modules.map((module) => <span className="pa-module-pill" key={module}>{module}<button type="button" aria-label={`Fjern ${module} fra ${user.name}`} onClick={() => revokeModule(user.id, module)}>×</button></span>)}
                    {availableModules.length > 0 ? <div className="pa-add-module">
                      {!modulePickerOpen[user.id] ? <button type="button" className="pa-add-module-pill" onClick={() => setModulePickerOpen((current) => ({ ...current, [user.id]: true }))}>+ modul</button> : <>
                      <label className="sr-only" htmlFor={`module-choice-${user.id}`}>Velg modul for {user.name}</label>
                      <select id={`module-choice-${user.id}`} value={moduleChoices[user.id] ?? ""} onChange={(event) => setModuleChoices((current) => ({ ...current, [user.id]: event.target.value }))}>
                        <option value="">Velg delegert modul</option>
                        {availableModules.map((module) => <option value={module} key={module}>{module}</option>)}
                      </select>
                      <button type="button" aria-label={`Legg til valgt modul for ${user.name}`} disabled={!moduleChoices[user.id]} onClick={() => addModule(user.id)}>Legg til</button>
                      <button type="button" className="pa-picker-cancel" aria-label={`Lukk modulvalg for ${user.name}`} onClick={() => { setModulePickerOpen((current) => ({ ...current, [user.id]: false })); setModuleChoices((current) => ({ ...current, [user.id]: "" })); }}>Avbryt</button>
                      </>}
                    </div> : <span className="pa-no-modules">Ingen flere delegerte moduler</span>}
                  </div>
                  <form className="pa-password-form" onSubmit={(event) => { event.preventDefault(); saveDemoPassword(user.id); }}>
                    <label htmlFor={`demo-password-${user.id}`}>Nytt demopassord <small>minst 12 tegn</small></label>
                    <div><input id={`demo-password-${user.id}`} type="password" autoComplete="new-password" minLength={12} value={passwords[user.id] ?? ""} onChange={(event) => setPasswords((current) => ({ ...current, [user.id]: event.target.value }))} placeholder="Skriv inn nytt passord" /><button type="submit">Simuler tilbakestilling</button></div>
                  </form>
                </article>;
              })}
            </div> : <div className="pa-empty-users"><strong>Ingen firmabrukere ennå</strong><span>Kun ansatte koblet til denne virksomheten vises her.</span></div>}
            <p className="pa-feedback" role="status" aria-live="polite">{feedback}</p>
            <div className="pa-dialog-footer"><span>{linkedTenantUsers.length} brukere · demo</span><button type="button" onClick={closeManagement}>Ferdig <Icon name="arrow" size={16} /></button></div>
          </section>
        </div>
      )}
    </main>
  );
}
