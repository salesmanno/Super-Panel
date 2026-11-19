import { useEffect, useMemo, useRef, useState, type ChangeEvent, type CSSProperties } from "react";
import "./tenantCopy.css";

type SitePage = "home" | "services" | "about";
type ModuleName = "Medlemskap" | "Utleie" | "Arrangementer" | "Salg" | "Økonomi";
type ServiceId = "membership" | "rental" | "event-dinner" | "event-dance" | "sales";
type AboutTab = "company" | "staff" | "email" | "economy" | "website";
type EmailFolder = "inbox" | "sent" | "queue" | "drafts";
type EconomyView = "results" | "payments";

type NewsItem = {
  id: string;
  title: string;
  summary: string;
  image: string;
  visible: boolean;
  date: string;
};

const initialNews: NewsItem[] = [
  {
    id: "julebord",
    title: "JULEBORD FOR MEDLEMMER",
    summary: "Kjære medlemmer i Bergsøy Danseklubb! Vi har gleden av å invitere alle våre medlemmer til et hyggelig og sosialt julebord på Høgtun.",
    image: "/__mockup/images/portal-reimagined-julebord.jpg",
    visible: true,
    date: "5. desember 2026",
  },
  {
    id: "romjul",
    title: "ROMJULSDANS",
    summary: "Dahl til vår lokale dansebandkveld på Høgtun Kulturhus.",
    image: "/__mockup/images/portal-reimagined-romjul.jpg",
    visible: true,
    date: "28. desember 2026",
  },
  {
    id: "arsmote",
    title: "ÅRSMØTE",
    summary: "Saksliste og tidspunkt kommer. Hold av kvelden.",
    image: "",
    visible: false,
    date: "12. februar 2027",
  },
];

const serviceMeta: Record<ServiceId, { module: ModuleName; title: string; image: string; description: string; price: string }> = {
  membership: {
    module: "Medlemskap",
    title: "Bergsøy Medlem",
    image: "/__mockup/images/portal-reimagined-logo.png",
    description: "Medlemskap for danseglede. Bli med i Bergsøy Danseklubb!",
    price: "250,00 kr",
  },
  rental: {
    module: "Utleie",
    title: "Høgtun Selskapslokale",
    image: "/__mockup/images/portal-reimagined-venue.jpg",
    description: "Næringslokale i hjertet av Fosnavåg. Et innbydende lokale for gode øyeblikk.",
    price: "Fra 1 250 kr",
  },
  "event-dinner": {
    module: "Arrangementer",
    title: "Julebord med julemiddag",
    image: "/__mockup/images/portal-reimagined-dinner.jpg",
    description: "Vi inviterer til julebord for alle våre medlemmer.",
    price: "500,00 kr",
  },
  "event-dance": {
    module: "Arrangementer",
    title: "Romjulsdans",
    image: "/__mockup/images/portal-reimagined-dance.jpg",
    description: "Dans til levende musikk på Høgtun Kulturhus.",
    price: "400,00 kr",
  },
  sales: {
    module: "Salg",
    title: "Dansekalender 2027",
    image: "/__mockup/images/portal-reimagined-dance.jpg",
    description: "Årets dansekalender fra Bergsøy Danseklubb.",
    price: "149,00 kr",
  },
};

const initialModules: ModuleName[] = ["Medlemskap", "Utleie", "Arrangementer", "Økonomi"];
const allModuleChoices: ModuleName[] = ["Medlemskap", "Utleie", "Arrangementer", "Salg", "Økonomi"];
const moduleDescriptions: Record<ModuleName, string> = {
  Medlemskap: "Planer, medlemmer og fordeler",
  Utleie: "Lokaler, priser og forespørsler",
  Arrangementer: "Påmelding, billett og innsjekk",
  Salg: "Varesalg og nettbutikk",
  Økonomi: "Betalinger, inntekter og utgifter",
};
const demoEmails: Record<EmailFolder, { id: string; from: string; address: string; subject: string; preview: string; time: string; body: string }[]> = {
  inbox: [
    { id: "mail-1", from: "Kurt Remøy", address: "kurt.remoy@example.no", subject: "Påmelding til julebord", preview: "Hei, jeg forsøkte å melde Aud på …", time: "09:42", body: "Hei! Jeg forsøkte å melde Aud på julebordet, men fikk ikke bekreftelsen. Kan dere sjekke om påmeldingen gikk gjennom?" },
    { id: "mail-2", from: "Medlemstjenesten", address: "varsler@salesman.no", subject: "Medlemsfornyelse behandlet", preview: "Betalingen er registrert og medlemskapet …", time: "08:16", body: "En medlemsfornyelse er registrert. Dette er et lokalt eksempel i e-postvisningen." },
    { id: "mail-3", from: "Høgtun Selskapslokale", address: "utleie@example.no", subject: "Spørsmål om helgeleie", preview: "Vi ønsker å avklare tidspunkt for …", time: "I går", body: "Vi ønsker å avklare tidspunkt og adgang før reservasjonen godkjennes." },
  ],
  sent: [
    { id: "mail-4", from: "Bergsøy Danseklubb", address: "bergsoy.d-k@salesman.no", subject: "Bekreftelse på julebord", preview: "Takk for reservasjonen. Her er detaljene …", time: "I går", body: "Takk for reservasjonen. Vi gleder oss til å se dere på Høgtun." },
    { id: "mail-5", from: "Bergsøy Danseklubb", address: "bergsoy.d-k@salesman.no", subject: "Svar på leieforespørsel", preview: "Vi har mottatt forespørselen din …", time: "16. nov.", body: "Vi har mottatt forespørselen og kommer tilbake med en avklaring." },
  ],
  queue: [
    { id: "mail-6", from: "Automatisk utsending", address: "system", subject: "Medlemsfornyelse · 1 mottaker", preview: "Venter på neste planlagte kjøring …", time: "Kø", body: "Denne meldingen er et eksempel på en melding som står i utsendingskø." },
  ],
  drafts: [
    { id: "mail-7", from: "Atle Karl", address: "kladd", subject: "Informasjon før romjulsdans", preview: "Praktisk informasjon om inngang og …", time: "Kladd", body: "Praktisk informasjon om inngang, parkering og starttidspunkt." },
  ],
};
const demoLedger = [
  { id: "ledger-1", module: "Medlemskap", description: "Bergsøy Medlem · medlemskap", customer: "Arild Slettestøl", time: "18.11.2026 · 09:24", amount: 500, method: "Vipps", status: "Gjennomført", kind: "income" as const },
  { id: "ledger-2", module: "Arrangementer", description: "Julebord for medlemmer", customer: "Gry Myrseth", time: "18.11.2026 · 08:51", amount: 500, method: "Vipps", status: "Gjennomført", kind: "income" as const },
  { id: "ledger-3", module: "Utleie", description: "Høgtun Selskapslokale · helgeleie", customer: "Kulturhuset Fosnavåg", time: "17.11.2026 · 15:12", amount: 3200, method: "Faktura", status: "Åpen", kind: "income" as const },
  { id: "ledger-4", module: "Økonomi", description: "Vipps-gebyrer · november", customer: "Vipps MobilePay", time: "17.11.2026 · 12:00", amount: -184.5, method: "Vipps", status: "Bokført", kind: "expense" as const },
  { id: "ledger-5", module: "Økonomi", description: "Leie av øvingslokale", customer: "Høgtun Kulturhus", time: "16.11.2026 · 10:15", amount: -1200, method: "Bank", status: "Betalt", kind: "expense" as const },
  { id: "ledger-6", module: "Salg", description: "Dansekalender 2027 · 3 stk.", customer: "Kari Remøy", time: "15.11.2026 · 19:31", amount: 447, method: "Kontant", status: "Gjennomført", kind: "income" as const },
];

function Arrow({ direction = "right" }: { direction?: "left" | "right" | "up" | "down" }) {
  const paths = {
    left: "M19 12H5m7 7-7-7 7-7",
    right: "M5 12h14m-7-7 7 7-7 7",
    up: "M12 19V5m-7 7 7-7 7 7",
    down: "M12 5v14m-7-7 7 7 7-7",
  };
  return <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round"><path d={paths[direction]} /></svg>;
}

export function TenantCopy() {
  const [page, setPage] = useState<SitePage>("home");
  const [controlOpen, setControlOpen] = useState(false);
  const [editingPage, setEditingPage] = useState<SitePage | null>(null);
  const [interactionMode, setInteractionMode] = useState("read");
  const [readerTitle, setReaderTitle] = useState("");
  const [aboutTab, setAboutTab] = useState<AboutTab>("company");
  const isSupervisor = true;
  const [emailModuleEnabled, setEmailModuleEnabled] = useState(true);
  const [websiteModuleEnabled, setWebsiteModuleEnabled] = useState(true);
  const [companyNumber, setCompanyNumber] = useState("987 654 321");
  const [companyProfile, setCompanyProfile] = useState({
    subdomain: "bergsoy.salesman.no",
    aboutUs: "Bergsøy Danseklubb skaper møteplasser, danseglede og gode opplevelser på Sunnmøre. Vi samler mennesker på tvers av generasjoner – på dansegulvet og rundt bordet.",
    contactPhone: "48145008",
    city: "Fosnavåg",
    zip: "6090",
    state: "Møre og Romsdal",
    country: "Norge",
  });
  const [emailAutomation, setEmailAutomation] = useState({ reservation: true, renewal: true, receipt: false });
  const [economySettings, setEconomySettings] = useState({ vatRegistered: false, cash: true, vipps: true, invoice: false });
  const [emailFolder, setEmailFolder] = useState<EmailFolder>("inbox");
  const [selectedEmailId, setSelectedEmailId] = useState("mail-1");
  const [economyView, setEconomyView] = useState<EconomyView>("results");
  const [selectedLedgerId, setSelectedLedgerId] = useState("ledger-1");
  const [economyFilter, setEconomyFilter] = useState<"all" | "income" | "expense">("all");
  const [staffMembers, setStaffMembers] = useState([
    { id: "atle", name: "Atle Karl", role: "Administrator", firstName: "Atle", lastName: "Karl", email: "atle@salesman.no", phone: "900 11 222", jobTitle: "Daglig leder", department: "Administrasjon", linkedUser: "atlekarl · innlogget bruker", supervisor: true, modules: ["Medlemskap", "Utleie", "Arrangementer", "Økonomi"] as ModuleName[] },
    { id: "kari", name: "Kari Remøy", role: "Arrangementsansvarlig", firstName: "Kari", lastName: "Remøy", email: "kari.remoy@example.no", phone: "905 34 210", jobTitle: "Arrangementsansvarlig", department: "Arrangement", linkedUser: "kari-remoy · ansattkoblet", supervisor: false, modules: ["Arrangementer"] as ModuleName[] },
    { id: "ola", name: "Ola Myrseth", role: "Utleieansvarlig", firstName: "Ola", lastName: "Myrseth", email: "ola.myrseth@example.no", phone: "930 45 678", jobTitle: "Utleieansvarlig", department: "Utleie", linkedUser: "", supervisor: false, modules: ["Utleie"] as ModuleName[] },
  ]);
  const [news, setNews] = useState(initialNews);
  const [hiddenServices, setHiddenServices] = useState<string[]>([]);
  const [hiddenOffers, setHiddenOffers] = useState<string[]>([]);
  const [createdOffers, setCreatedOffers] = useState<Array<{id: string; title: string; module: ModuleName; price: string}>>([]);
  const [newOfferOpen, setNewOfferOpen] = useState(false);
  const [newOfferTitle, setNewOfferTitle] = useState("");
  const [newOfferModule, setNewOfferModule] = useState<ModuleName>("Medlemskap");
  const [newOfferPrice, setNewOfferPrice] = useState("");
  const [selectedNewsId, setSelectedNewsId] = useState("julebord");
  const [enabledModules, setEnabledModules] = useState<ModuleName[]>(initialModules);
  const [selectedService, setSelectedService] = useState<ServiceId>("membership");
  const [fieldEditorOpen, setFieldEditorOpen] = useState(false);
  const [checkedFields, setCheckedFields] = useState(["Fornavn", "Etternavn", "E-postadresse", "Telefonnummer"]);
  const [benefitEnabled, setBenefitEnabled] = useState(true);
  const [memberVerification, setMemberVerification] = useState(true);
  const [memberExclusive, setMemberExclusive] = useState(false);
  const [feedback, setFeedback] = useState("");
  const [aboutText, setAboutText] = useState("Bergsøy Danseklubb skaper møteplasser, danseglede og gode opplevelser på Sunnmøre. Vi samler mennesker på tvers av generasjoner – på dansegulvet og rundt bordet.");
  const [companyName, setCompanyName] = useState("Bergsøy Danseklubb");
  const [companyLogo, setCompanyLogo] = useState("/__mockup/images/portal-reimagined-logo.png");
  const [heroEnabled, setHeroEnabled] = useState(true);
  const [logoUploadFeedback, setLogoUploadFeedback] = useState("");
  const [seoTitle, setSeoTitle] = useState("Bergsøy Danseklubb – dans, fellesskap og arrangement");
  const [seoDescription, setSeoDescription] = useState("Bli kjent med Bergsøy Danseklubb. Finn medlemskap, utleie, arrangementer og danseglede i Fosnavåg.");
  const [seoCanonicalUrl, setSeoCanonicalUrl] = useState("https://bergsoy.salesman.no/");
  const [seoSocialImage, setSeoSocialImage] = useState("https://i.ibb.co/bergsoy/portal-preview.jpg");
  const [seoIndexable, setSeoIndexable] = useState(true);
  const [theme, setTheme] = useState({
    headerBg: "#2b4f98",
    bodyBg: "#e1f0ff",
    primaryColor: "#3968b5",
    secondaryColor: "#d0e2f7",
    headerText: "#ffffff",
    headerHeight: 88,
    logoHeight: 56,
    logoPosition: "center",
    namePosition: "beside",
    navPosition: "center",
    headerLayout: "centered",
    navigationStyle: "underline",
    footerLayout: "standard",
    homepageModule: "news",
    fontFamily: "Georgia, serif",
    fontSize: 15,
    headingSize: 29,
    borderRadius: 8,
    phone: "48145008",
    email: "bergsoy.d-k@salesman.no",
    address: "Vågsgata 61, 6090 Fosnavåg",
    facebook: "https://facebook.com/bergsoydanseklubb",
    instagram: "https://instagram.com/bergsoydans",
    footerText: "© 2026 Bergsøy Danseklubb",
    footerLinks: "Om oss · Kontakt",
  });
  const fileInputRef = useRef<HTMLInputElement>(null);
  const logoInputRef = useRef<HTMLInputElement>(null);
  const lastScrollY = useRef(0);
  const activeNews = news.find((item) => item.id === selectedNewsId) ?? news[0];
  const visibleNews = news.filter((item) => item.visible);
  const heroNews = visibleNews[0];
  const activeServices = useMemo(
    () => Object.entries(serviceMeta).filter(([, service]) => enabledModules.includes(service.module)) as [ServiceId, (typeof serviceMeta)[ServiceId]][],
    [enabledModules],
  );
  const canManageHomeCards = interactionMode === "content" || interactionMode === "news";
  const canManageServiceCards = interactionMode === "content";
  const canManageBenefits = interactionMode === "benefits";
  const canManageFields = interactionMode === "fields";
  const visibleEventServiceIds = (["event-dinner", "event-dance"] as ServiceId[]).filter(
    id => canManageServiceCards || !hiddenServices.includes(id),
  );
  const displayedHomeStories = news.filter((item) =>
    (canManageHomeCards || item.visible) && (!heroNews || !heroEnabled || item.id !== heroNews.id),
  );
  const footerHeight = Math.round(theme.headerHeight * 0.6);
  const siteStyle = {
    "--tc-header-bg": theme.headerBg,
    "--tc-header-ink": theme.headerText,
    "--tc-body-bg": theme.bodyBg,
    "--tc-primary": theme.primaryColor,
    "--tc-secondary": theme.secondaryColor,
    "--tc-font": theme.fontFamily,
    "--tc-font-size": `${theme.fontSize}px`,
    "--tc-heading-size": `${theme.headingSize}px`,
    "--tc-radius": `${theme.borderRadius}px`,
    "--tc-header-height": `${theme.headerHeight}px`,
    "--tc-logo-height": `${theme.logoHeight}px`,
    "--tc-footer-height": `${footerHeight}px`,
  } as CSSProperties;
  const renderSeoSettings = () => (
    <section className="tc-settings-card tc-seo-card">
      <div className="tc-seo-heading">
        <div><span className="tc-kicker">SØKEMOTORER OG DELING</span><h3>SEO for bedriften</h3><p>Bestem hvordan nettstedet vises i søkeresultater og når en lenke deles.</p></div>
        <span className="tc-seo-badge"><i />{seoIndexable ? "Indeksering på" : "Skjult fra søk"}</span>
      </div>
      <div className="tc-seo-fields">
        <label className="tc-seo-wide">Tittel i søkeresultat
          <input autoComplete="off" maxLength={70} value={seoTitle} onChange={event => setSeoTitle(event.target.value)} placeholder={`${companyName} – kort beskrivelse`} />
          <small className={seoTitle.length > 60 ? "over-limit" : ""}>{seoTitle.length}/60 anbefalt tegn</small>
        </label>
        <label className="tc-seo-wide">Beskrivelse i søkeresultat
          <textarea rows={3} maxLength={180} value={seoDescription} onChange={event => setSeoDescription(event.target.value)} placeholder="Beskriv bedriften kort og konkret." />
          <small className={seoDescription.length > 160 ? "over-limit" : ""}>{seoDescription.length}/160 anbefalt tegn</small>
        </label>
        <label className="tc-seo-wide">Kanonisk nettadresse
          <input autoComplete="off" type="url" value={seoCanonicalUrl} onChange={event => setSeoCanonicalUrl(event.target.value)} placeholder="https://www.bedrift.no/" />
          <small>Bruk hovedadressen som skal vises i søkemotorer.</small>
        </label>
        <label className="tc-seo-wide">Bilde ved deling
          <input autoComplete="off" type="url" value={seoSocialImage} onChange={event => setSeoSocialImage(event.target.value)} placeholder="https://…" />
          <small>Open Graph-bilde for lenker delt i sosiale medier.</small>
        </label>
      </div>
      <label className="tc-setting-toggle tc-seo-indexing"><input type="checkbox" checked={seoIndexable} onChange={event => setSeoIndexable(event.target.checked)} /><span><strong>Tillat søkemotorer å indeksere nettstedet</strong><small>Skru av for test- eller private sider.</small></span></label>
      <div className="tc-serp-preview">
        <span>FORHÅNDSVISNING I SØK</span>
        <strong>{seoTitle || companyName}</strong>
        <small>{seoCanonicalUrl || "https://bedrift.no/"}</small>
        <p>{seoDescription || "Bedriftens beskrivelse vises her."}</p>
      </div>
    </section>
  );

  const updateTheme = (key: keyof typeof theme, value: string | number) => {
    setTheme((current) => ({ ...current, [key]: value }));
  };
  const updateCompanyProfile = (key: keyof typeof companyProfile, value: string) => {
    setCompanyProfile(current => ({ ...current, [key]: value }));
  };
  const updateStaffField = (
    id: string,
    key: "firstName" | "lastName" | "email" | "phone" | "jobTitle" | "department",
    value: string,
  ) => {
    setStaffMembers(current => current.map(person => {
      if (person.id !== id) return person;
      const next = { ...person, [key]: value };
      if (key === "firstName" || key === "lastName") next.name = `${next.firstName} ${next.lastName}`.trim();
      if (key === "jobTitle") next.role = value;
      return next;
    }));
  };
  const openControlPanel = (editTarget: SitePage = page) => {
    if (editTarget === "about") {
      setAboutTab("company");
      setEditingPage(null);
      return;
    }
    setControlOpen(true);
    if (interactionMode === "read") {
      setReaderTitle(editTarget === "home" ? (news.find(item => item.id === selectedNewsId)?.title || "Nyhet") : editTarget === "services" ? serviceMeta[selectedService].title : "Om oss");
      return;
    }
    if (editTarget === "home" && interactionMode !== "content" && interactionMode !== "news") {
      setFeedback("Velg Alle nyheter eller Rediger innhold for å endre en sak.");
      return;
    }
    setEditingPage(editTarget);
    setFieldEditorOpen(interactionMode === "fields" || canManageFields);
  };
  const chooseMode = (mode: string) => {
    setInteractionMode(mode);
    setEditingPage(mode === "content" && page !== "about" ? page : null);
    setReaderTitle("");
    setFeedback("");
  };
  const openAllNews = () => {
    setInteractionMode("news");
    setEditingPage("home");
    setReaderTitle("");
    setControlOpen(true);
  };
  const addStaffMember = () => {
    setStaffMembers(current => [...current, { id: `staff-${Date.now()}`, name: "Ny medarbeider", role: "Medarbeider", firstName: "Ny", lastName: "medarbeider", email: "", phone: "", jobTitle: "Medarbeider", department: "Administrasjon", linkedUser: "", supervisor: false, modules: [] }]);
    setFeedback("En ny medarbeider er lagt til lokalt.");
  };
  const toggleStaffModule = (id: string, module: ModuleName) => {
    setStaffMembers(current => current.map(person => person.id === id ? { ...person, modules: person.modules.includes(module) ? person.modules.filter(value => value !== module) : [...person.modules, module].filter(value => enabledModules.includes(value)) } : person));
  };
  useEffect(() => {
    lastScrollY.current = window.scrollY;
    const revealOnScroll = () => {
      const currentY = window.scrollY;
      const direction = currentY - lastScrollY.current;
      if (currentY <= 80) {
        setControlOpen(false);
      } else if (direction > 4) {
        setControlOpen(true);
      } else if (direction < -4) {
        setControlOpen(false);
      }
      lastScrollY.current = currentY;
    };
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") { setEditingPage(null); setReaderTitle(""); setControlOpen(false); }
    };
    window.addEventListener("scroll", revealOnScroll, { passive: true });
    window.addEventListener("keydown", closeOnEscape);
    return () => {
      window.removeEventListener("scroll", revealOnScroll);
      window.removeEventListener("keydown", closeOnEscape);
    };
  }, []);
  const selectPage = (nextPage: SitePage) => {
    setPage(nextPage);
    setInteractionMode("read");
    setEditingPage(null);
    setReaderTitle("");
    setFeedback("");
  };
  const updateNews = (id: string, key: "title" | "summary", value: string) => {
    setNews((current) => current.map((item) => item.id === id ? { ...item, [key]: value } : item));
  };
  const toggleNews = (id: string) => {
    setNews((current) => current.map((item) => item.id === id ? { ...item, visible: !item.visible } : item));
    setFeedback("Visningen på Hjem er oppdatert i denne skissen.");
  };
  const toggleServiceVisibility = (id: string) => {
    setHiddenServices(current => current.includes(id) ? current.filter(item => item !== id) : [...current, id]);
    setFeedback("Synligheten er endret i den lokale forhåndsvisningen.");
  };
  const toggleOfferVisibility = (id: string) => {
    setHiddenOffers(current => current.includes(id) ? current.filter(item => item !== id) : [...current, id]);
    setFeedback("Synligheten er endret i den lokale forhåndsvisningen.");
  };
  const moveNews = (index: number, offset: number) => {
    setNews((current) => {
      const target = index + offset;
      if (target < 0 || target >= current.length) return current;
      const next = [...current];
      [next[index], next[target]] = [next[target], next[index]];
      return next;
    });
  };
  const addNews = () => {
    const id = `news-${Date.now()}`;
    setNews((current) => [{
      id,
      title: "NY SAK",
      summary: "Skriv en kort introduksjon som vises på Hjem.",
      image: "",
      visible: true,
      date: "I dag",
    }, ...current]);
    setSelectedNewsId(id);
    setFeedback("Ny sak er lagt til i forhåndsvisningen.");
  };
  const startNewNews = () => {
    setControlOpen(true);
    setInteractionMode("content");
    setEditingPage("home");
    setReaderTitle("");
    addNews();
  };
  const startNewOffer = () => {
    const availableModules = enabledModules.filter(module => module !== "Økonomi");
    if (availableModules.length === 0) {
      setFeedback("Aktiver en relevant modul før du oppretter et nytt tjenestetilbud.");
      return;
    }
    setControlOpen(true);
    setInteractionMode("content");
    setNewOfferModule(availableModules.some(module => module === newOfferModule) ? newOfferModule : availableModules[0]);
    setNewOfferTitle("");
    setNewOfferPrice("");
    setNewOfferOpen(true);
  };
  const createOffer = () => {
    if (!newOfferTitle.trim()) {
      setFeedback("Skriv inn et navn på tjenesten først.");
      return;
    }
    if (!enabledModules.includes(newOfferModule) || newOfferModule === "Økonomi") {
      setFeedback("Velg en modul du har tilgang til.");
      return;
    }
    setCreatedOffers(current => [{id: `offer-${Date.now()}`, title: newOfferTitle.trim(), module: newOfferModule, price: newOfferPrice.trim()}, ...current]);
    setNewOfferOpen(false);
    setFeedback("Tjenestetilbudet er lagt til i den lokale forhåndsvisningen.");
  };
  const handleLocalImage = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file || !activeNews) return;
    if (!file.type.startsWith("image/")) {
      setFeedback("Velg en bildefil for forhåndsvisningen.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      if (typeof reader.result === "string") {
        setNews((current) => current.map((item) => item.id === activeNews.id ? { ...item, image: reader.result as string } : item));
        setFeedback("Bildet vises lokalt i denne skissen. Det er ikke lastet opp.");
      }
    };
    reader.readAsDataURL(file);
  };
  const handleLocalLogo = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file || !file.type.startsWith("image/")) {
      setLogoUploadFeedback(file ? "Velg en bildefil for logoen." : "");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      if (typeof reader.result === "string") {
        setCompanyLogo(reader.result);
        setLogoUploadFeedback("Logoen vises lokalt i denne skissen. Den er ikke lastet opp.");
      }
    };
    reader.readAsDataURL(file);
  };
  const toggleModule = (module: ModuleName) => {
    const next = enabledModules.includes(module)
      ? enabledModules.filter((item) => item !== module)
      : [...enabledModules, module];
    setEnabledModules(next);
    if (!next.includes(serviceMeta[selectedService].module)) {
      const fallback = Object.entries(serviceMeta).find(([, service]) => next.includes(service.module));
      if (fallback) setSelectedService(fallback[0] as ServiceId);
    }
  };
  const editService = (id: ServiceId) => {
    setSelectedService(id);
    if (interactionMode === "read") {
      setReaderTitle(serviceMeta[id].title);
      return;
    }
    openControlPanel("services");
  };
  const toggleField = (field: string) => {
    setCheckedFields((current) => current.includes(field) ? current.filter((item) => item !== field) : [...current, field]);
  };

  const currentService = serviceMeta[selectedService];
  // Preview-only access catalog: deliberately local and independent of any API/DB.
  const aboutTabs: { id: AboutTab; label: string; sub: string; number: string }[] = [
    { id: "company", label: "Bedriften", sub: "Profil og kontakt", number: "01" },
    { id: "staff", label: "Ansatte", sub: "Brukere og tilganger", number: "02" },
    { id: "email", label: "E-post", sub: "Utsendinger og maler", number: "03" },
    { id: "economy", label: "Økonomi", sub: "Betaling og konto", number: "04" },
    { id: "website", label: "Webside", sub: "Utseende og visning", number: "05" },
  ];
  const activeAboutTab = aboutTabs.find(tab => tab.id === aboutTab) ?? aboutTabs[0];
  const folderMessages = demoEmails[emailFolder];
  const activeEmail = folderMessages.find(mail => mail.id === selectedEmailId) ?? folderMessages[0];
  const filteredLedger = demoLedger.filter(row => economyFilter === "all" || row.kind === economyFilter);
  const ledgerIncome = filteredLedger.filter(row => row.amount > 0).reduce((sum, row) => sum + row.amount, 0);
  const ledgerExpense = Math.abs(filteredLedger.filter(row => row.amount < 0).reduce((sum, row) => sum + row.amount, 0));
  const ledgerResult = ledgerIncome - ledgerExpense;
  const paymentChoices = [
    { key: "cash" as const, name: "Kontant", mark: "KR", detail: "Standard · uten gebyr", configured: true },
    { key: "vipps" as const, name: "Vipps", mark: "V", detail: "Bedriftskonto tilkoblet · gebyr beregnes", configured: true },
    { key: "invoice" as const, name: "Faktura", mark: "F", detail: economySettings.invoice ? "Bankkonto valgt" : "Velg bankkonto før aktivering", configured: economySettings.invoice },
  ];
  return (
    <main className="tc-shell">
      <button type="button" className={`tc-admin-launcher ${controlOpen ? "is-hidden" : ""}`} onClick={() => setControlOpen(true)} aria-label="Åpne administrasjonsvalg">
        <span className="tc-launcher-avatar">AK</span><span><strong>Atle Karl</strong><small>Administrer side</small></span><Arrow direction="down" />
      </button>
      <section className={`tc-public-site ${interactionMode !== "read" ? "tc-is-editing" : ""}`} style={siteStyle} aria-label="Forhåndsvisning av tenant-copy">
        <header className="tc-site-header">
          <div className={`tc-brand-position tc-logo-${theme.logoPosition} tc-name-${theme.namePosition} tc-layout-${theme.headerLayout}`}>
            <img className="tc-company-logo" src={companyLogo} alt={`${companyName}-logo`} />
            <span className="tc-company-name">{companyName}</span>
          </div>
        </header>

        <nav className={`tc-site-nav tc-nav-${theme.navPosition} tc-navigation-${theme.navigationStyle}`} aria-label="Forsidenavigasjon">
          <div className="tc-nav-items">
            <button type="button" className={page === "about" ? "active" : ""} onClick={() => selectPage("about")}>Om oss</button>
            <button type="button" className={page === "home" ? "active" : ""} onClick={() => selectPage("home")}>Hjem</button>
            <button type="button" className={page === "services" ? "active" : ""} onClick={() => selectPage("services")}>Tjenester</button>
          </div>
        </nav>

        <div className="tc-site-content">
          {page === "home" && (
            <div className="tc-home-page">
              {heroNews && heroEnabled ? (
                <section className={`tc-home-hero ${heroNews.image ? "" : "no-image"}`}>
                  <span className="tc-edit-marker">Rediger toppsak</span>
                  <div className="tc-home-hero-copy">
                    <span className="tc-kicker">NYTT FRA BERGSØY</span>
                    <h1>{heroNews.title}</h1>
                    <p>{heroNews.summary}</p>
                    <button type="button" className="tc-text-link" aria-label={`Rediger nyhetssaken ${heroNews.title}`} onClick={() => { setSelectedNewsId(heroNews.id); openControlPanel("home"); }}>
                      Rediger saken <Arrow />
                    </button>
                  </div>
                  {heroNews.image && <div className="tc-hero-image-stack">
                    <button type="button" className="tc-hero-image-button" onClick={() => { setSelectedNewsId(heroNews.id); openControlPanel("home"); }} aria-label={`Åpne bildet for ${heroNews.title}`}><img src={heroNews.image} alt="" /><span className="tc-image-edit-hint">Rediger bilde</span></button>
                    {canManageHomeCards && <button type="button" className={`tc-card-hide ${heroNews.visible ? "" : "is-hidden"}`} aria-label={`${heroNews.visible ? "Skjul" : "Vis"} ${heroNews.title}`} onClick={() => toggleNews(heroNews.id)}>{heroNews.visible ? "Skjul" : "Vis"}</button>}
                  </div>}
                </section>
              ) : (
                <div className="tc-home-empty"><strong>Ingen saker på Hjem akkurat nå.</strong><span>Legg til eller vis en sak i administrasjonspanelet.</span><button type="button" onClick={() => openControlPanel("home")}>Åpne nyhetsadministrasjon <Arrow /></button></div>
              )}
              {theme.homepageModule !== "news" && <section className="tc-home-module-preview">
                <span className="tc-kicker">{theme.homepageModule === "services" ? "UTVALGTE TJENESTER" : "KOMMENDE ARRANGEMENT"}</span>
                <strong>{theme.homepageModule === "services" ? "Oppdag tilbud fra Bergsøy" : "Neste på planen · Høstfest"}</strong>
                <span>{theme.homepageModule === "services" ? activeServices.map(([, service]) => service.title).slice(0, 3).join(" · ") || "Aktiver moduler for å vise tilbud." : "Fredag 23. oktober · Høgtun Kulturhus"}</span>
              </section>}
              <div id="tc-home-news-list" className="tc-home-news-list">
                {displayedHomeStories.map((item) => (
                  <article className={`tc-story-wrap ${item.visible ? "" : "is-hidden"}`} key={item.id}>
                    <button type="button" className="tc-story-media" aria-label={`Åpne bildet for ${item.title}`} onClick={() => { setSelectedNewsId(item.id); openControlPanel("home"); }}>
                      {item.image ? <img src={item.image} alt="" /> : <span className="tc-story-placeholder">BD</span>}
                    </button>
                    {canManageHomeCards && <button type="button" className={`tc-card-hide ${item.visible ? "" : "is-hidden"}`} aria-label={`${item.visible ? "Skjul" : "Vis"} ${item.title}`} onClick={() => toggleNews(item.id)}>{item.visible ? "Skjul" : "Vis"}</button>}
                    <button type="button" className="tc-home-story-copy" aria-label={`Åpne ${item.title}`} onClick={() => { setSelectedNewsId(item.id); openControlPanel("home"); }}>
                      <span>{item.title}</span>
                      <small>{item.date}</small>
                      <em>{interactionMode === "content" ? "Rediger" : "Les saken"}</em>
                    </button>
                  </article>
                ))}
              </div>
            </div>
          )}

          {page === "services" && (
            <div className="tc-services-page">
              <section className={`tc-service-intro ${enabledModules.includes("Arrangementer") ? "" : "without-event"}`}>
                <div>
                  <span className="tc-kicker">BERGSØY DANSEKLUBB</span>
                  <h1>Tjenester</h1>
                  <p>{activeServices.length + createdOffers.length} tilbud fra Bergsøy Danseklubb.</p>
                </div>
                {enabledModules.includes("Arrangementer") && (!hiddenServices.includes("event-dance") || canManageServiceCards) && <div className={`tc-service-card-wrap tc-latest-wrap ${hiddenServices.includes("event-dance") ? "is-hidden" : ""}`}>
                  <button type="button" className="tc-latest-event" aria-label="Åpne siste arrangement" onClick={() => editService("event-dance")}>
                    <span className="tc-edit-marker">Rediger arrangement</span>
                    <img src="/__mockup/images/portal-reimagined-dance.jpg" alt="" />
                    <span><small>SISTE ARRANGEMENT</small><strong>ROMJULSDANS</strong><span>man. 28. des. 2026 kl. 19:00</span><b>Se arrangementet <Arrow /></b></span>
                  </button>
                  {canManageServiceCards && <button type="button" className={`tc-card-hide ${hiddenServices.includes("event-dance") ? "is-hidden" : ""}`} aria-label={`${hiddenServices.includes("event-dance") ? "Vis" : "Skjul"} Romjulsdans`} onClick={() => toggleServiceVisibility("event-dance")}>{hiddenServices.includes("event-dance") ? "Vis" : "Skjul"}</button>}
                </div>}
              </section>
              <div id="tc-service-groups" className="tc-service-groups">
                {enabledModules.includes("Medlemskap") && (!hiddenServices.includes("membership") || canManageServiceCards) && (
                  <section className="tc-service-group">
                    <header><div><h2>Medlemskap</h2><p>Medlemskap for danseglede.</p></div><span>1 tilbud</span></header>
                    <div className={`tc-service-card-wrap ${hiddenServices.includes("membership") ? "is-hidden" : ""}`}>
                      <button type="button" className="tc-public-service tc-membership-card" aria-label="Åpne medlemskapstilbudet Bergsøy Medlem" onClick={() => editService("membership")}>
                        <span className="tc-service-image tc-member-logo"><img src="/__mockup/images/portal-reimagined-logo.png" alt="" /></span>
                        <span className="tc-service-copy"><strong>Bergsøy Medlem</strong><small>Med i Bergsøy Danseklubb!</small><span className="tc-price-line">Pris <b>250,00 kr</b></span><span className="tc-blue-cta">Sjekk medlemskap</span></span>
                        <span className="tc-edit-marker">Rediger tilbud</span>
                      </button>
                      {canManageServiceCards && <button type="button" className={`tc-card-hide ${hiddenServices.includes("membership") ? "is-hidden" : ""}`} aria-label={`${hiddenServices.includes("membership") ? "Vis" : "Skjul"} Bergsøy Medlem`} onClick={() => toggleServiceVisibility("membership")}>{hiddenServices.includes("membership") ? "Vis" : "Skjul"}</button>}
                    </div>
                  </section>
                )}
                {enabledModules.includes("Utleie") && (!hiddenServices.includes("rental") || canManageServiceCards) && (
                  <section className="tc-service-group">
                    <header><div><h2>Utleie</h2><p>Lokaler og andre utleieobjekter.</p></div><span>1 tilbud</span></header>
                    <div className={`tc-service-card-wrap ${hiddenServices.includes("rental") ? "is-hidden" : ""}`}>
                      <button type="button" className="tc-public-service" aria-label="Åpne utleietilbudet Høgtun Selskapslokale" onClick={() => editService("rental")}>
                        <span className="tc-service-image"><img src="/__mockup/images/portal-reimagined-venue.jpg" alt="" /></span>
                        <span className="tc-service-copy"><strong>Høgtun Selskapslokale</strong><small>Næringslokale i hjertet av Fosnavåg. Enkelt å leie hele døgnet.</small><span className="tc-service-address">Vågsgata 61, 6090 Fosnavåg</span><span className="tc-feature-chips"><small>60 sitteplasser</small><small>168 m²</small></span><span className="tc-blue-cta">Se detaljer og ledige datoer</span></span>
                        <span className="tc-edit-marker">Rediger tilbud</span>
                      </button>
                      {canManageServiceCards && <button type="button" className={`tc-card-hide ${hiddenServices.includes("rental") ? "is-hidden" : ""}`} aria-label={`${hiddenServices.includes("rental") ? "Vis" : "Skjul"} Høgtun Selskapslokale`} onClick={() => toggleServiceVisibility("rental")}>{hiddenServices.includes("rental") ? "Vis" : "Skjul"}</button>}
                    </div>
                  </section>
                )}
                {enabledModules.includes("Arrangementer") && visibleEventServiceIds.length > 0 && (
                  <section className="tc-service-group tc-group-wide">
                    <header><div><h2>Arrangementer</h2><p>Dato, sted og påmelding.</p></div><span>{visibleEventServiceIds.length} tilbud</span></header>
                    <div className="tc-event-service-grid">
                      {visibleEventServiceIds.map((id) => {
                        const service = serviceMeta[id];
                        if (hiddenServices.includes(id) && !canManageServiceCards) return null;
                        return <div className={`tc-service-card-wrap ${hiddenServices.includes(id) ? "is-hidden" : ""}`} key={id}>
                          <button type="button" className="tc-public-service tc-event-service" aria-label={`Åpne arrangementet ${service.title}`} onClick={() => editService(id)}>
                            <span className="tc-service-image"><img src={service.image} alt="" /></span>
                            <span className="tc-service-copy"><strong>{service.title}</strong><small>{service.description}</small><span className="tc-service-meta"><i>DATO</i><b>{id === "event-dinner" ? "lør. 5. des. 2026 kl. 19:00" : "man. 28. des. 2026 kl. 19:00"}</b><i>STED</i><b>Høgtun, Vågsgata 61</b></span><span className="tc-service-bottom"><span><small>Ordinærpris</small><b>{service.price}</b></span><span className="tc-blue-cta">Reserver billett</span></span></span>
                            <span className="tc-edit-marker">Rediger arrangement</span>
                          </button>
                          {canManageServiceCards && <button type="button" className={`tc-card-hide ${hiddenServices.includes(id) ? "is-hidden" : ""}`} aria-label={`${hiddenServices.includes(id) ? "Vis" : "Skjul"} ${service.title}`} onClick={() => toggleServiceVisibility(id)}>{hiddenServices.includes(id) ? "Vis" : "Skjul"}</button>}
                        </div>;
                      })}
                    </div>
                  </section>
                )}
                {enabledModules.includes("Salg") && (!hiddenServices.includes("sales") || canManageServiceCards) && (
                  <section className="tc-service-group">
                    <header><div><h2>Varer</h2><p>Produkter fra klubben.</p></div><span>1 tilbud</span></header>
                    <div className={`tc-service-card-wrap ${hiddenServices.includes("sales") ? "is-hidden" : ""}`}>
                      <button type="button" className="tc-public-service" aria-label="Åpne produktet Dansekalender 2027" onClick={() => editService("sales")}>
                        <span className="tc-service-image"><img src="/__mockup/images/portal-reimagined-dance.jpg" alt="" /></span>
                        <span className="tc-service-copy"><strong>Dansekalender 2027</strong><small>En liten bit av dansegleden med hjem.</small><span className="tc-price-line">Pris <b>149,00 kr</b></span><span className="tc-blue-cta">Se varen</span></span>
                        <span className="tc-edit-marker">Rediger vare</span>
                      </button>
                      {canManageServiceCards && <button type="button" className={`tc-card-hide ${hiddenServices.includes("sales") ? "is-hidden" : ""}`} aria-label={`${hiddenServices.includes("sales") ? "Vis" : "Skjul"} Dansekalender 2027`} onClick={() => toggleServiceVisibility("sales")}>{hiddenServices.includes("sales") ? "Vis" : "Skjul"}</button>}
                    </div>
                  </section>
                )}
                {createdOffers.length > 0 && <section className="tc-service-group tc-created-offers">
                  <header><div><h2>Nye tilbud</h2><p>Opprettet lokalt i forhåndsvisningen.</p></div><span>{createdOffers.length} tilbud</span></header>
                  <div className="tc-event-service-grid">
                    {createdOffers.filter(offer => enabledModules.includes(offer.module) && (!hiddenOffers.includes(offer.id) || canManageServiceCards)).map(offer => <div className={`tc-service-card-wrap ${hiddenOffers.includes(offer.id) ? "is-hidden" : ""}`} key={offer.id}>
                      <button type="button" className="tc-public-service tc-event-service" onClick={() => editService(offer.module === "Medlemskap" ? "membership" : offer.module === "Utleie" ? "rental" : "event-dance")}>
                        <span className="tc-service-image"><img src={offer.module === "Medlemskap" ? serviceMeta.membership.image : offer.module === "Utleie" ? serviceMeta.rental.image : serviceMeta["event-dance"].image} alt="" /></span>
                        <span className="tc-service-copy"><strong>{offer.title}</strong><small>{offer.module}</small>{offer.price && <span className="tc-price-line"><b>{offer.price}</b></span>}<span className="tc-blue-cta">Se tilbudet</span></span>
                      </button>
                      {canManageServiceCards && enabledModules.includes(offer.module) && <button type="button" className={`tc-card-hide ${hiddenOffers.includes(offer.id) ? "is-hidden" : ""}`} aria-label={`${hiddenOffers.includes(offer.id) ? "Vis" : "Skjul"} ${offer.title}`} onClick={() => toggleOfferVisibility(offer.id)}>{hiddenOffers.includes(offer.id) ? "Vis" : "Skjul"}</button>}
                    </div>)}
                  </div>
                </section>}
                {!activeServices.length && <div className="tc-no-services"><strong>Ingen tjenester vises ennå.</strong><span>Aktiver en modul i administrasjonspanelet for å fylle siden.</span><button type="button" onClick={() => openControlPanel("services")}>Administrer moduler <Arrow /></button></div>}
              </div>
            </div>
          )}

          {page === "about" && <section className="tc-internal-about" aria-label="Interne bedriftsinnstillinger">
            <header className="tc-internal-about-heading">
              <div><span className="tc-kicker">ADMINISTRASJON <b>/</b> INNSTILLINGER</span><h1>Om oss</h1><p>Bedriftens interne arbeidsflate. Dette er ikke innhold som vises til kundene.</p></div>
              <span className="tc-demo-chip"><i /> LOKAL FORHÅNDSVISNING</span>
            </header>
            <div className="tc-settings-layout">
              <nav className="tc-settings-nav" role="tablist" aria-label="Områder i bedriftsinnstillinger">
                <span className="tc-settings-nav-label">INNSTILLINGER</span>
                {aboutTabs.map(tab => <button key={tab.id} type="button" role="tab" aria-selected={activeAboutTab.id === tab.id} className={activeAboutTab.id === tab.id ? "active" : ""} onClick={() => {setAboutTab(tab.id);setFeedback("");}}>
                  <span className="tc-settings-tab-number">{tab.number}</span><span><b>{tab.label}</b><small>{tab.sub}</small></span><Arrow />
                </button>)}
                <div className="tc-settings-nav-foot"><b>Atle Karl</b><span>Eksempelprofil · veileder</span><small>Alle verdier er lokale demo-data. Ingen database eller modultjeneste blir kontaktet.</small></div>
              </nav>
              <div className="tc-settings-main" role="tabpanel">
                <div className="tc-settings-main-head">
                  <div><span className="tc-kicker">{activeAboutTab.number} <b>/</b> BEDRIFTSOMRÅDE</span><h2>{activeAboutTab.label}</h2><p>{activeAboutTab.sub} for {companyName}.</p></div>
                  {activeAboutTab.id !== "economy" && <button type="button" className="tc-settings-save" onClick={() => setFeedback(`${activeAboutTab.label} er lagret i den lokale forhåndsvisningen.`)}>Lagre endringer <Arrow /></button>}
                </div>
                {activeAboutTab.id === "company" && <div className="tc-company-workspace">
                  <section className="tc-settings-panel tc-company-profile-card">
                    <div className="tc-company-profile-head"><div><span className="tc-kicker">BEDRIFTSPROFIL</span><h3>{companyName}</h3><p>Opplysninger for den valgte bedriften.</p></div><button type="button" className="tc-settings-save" onClick={() => setFeedback("Bedriftsprofilen er oppdatert lokalt i mockupen.")}>Lagre profil</button></div>
                    <div className="tc-subdomain-line"><span>NETTADRESSE</span><code>{companyProfile.subdomain}</code><small>tenant_companies.subdomain</small></div>
                    <div className="tc-company-image-box">
                      <div className="tc-company-image-header" style={{ backgroundColor: theme.headerBg }}><span>header_bg_color</span><code>{theme.headerBg}</code></div>
                      <div className="tc-company-image-stage" style={{ backgroundColor: theme.headerBg }}><img src={companyLogo} alt={`${companyName} bedriftslogo`} /><span>{companyName}</span></div>
                      <div className="tc-company-image-actions"><small>Profilbilde / firmalogo · vises mot bedriftens headerfarge</small><button type="button" className="tc-outline-action" onClick={() => logoInputRef.current?.click()}>Bytt bildefil</button><input ref={logoInputRef} type="file" accept="image/*" onChange={handleLocalLogo} hidden /></div>
                    </div>
                    <div className="tc-company-fields">
                      <label>Firmanavn<input autoComplete="off" value={companyName} onChange={event => setCompanyName(event.target.value)} /></label>
                      <label>Organisasjonsnummer<input autoComplete="off" value={companyNumber} onChange={event => setCompanyNumber(event.target.value)} /></label>
                      <label className="wide">Om bedriften · <code>about_us</code><textarea rows={4} value={companyProfile.aboutUs} onChange={event => updateCompanyProfile("aboutUs", event.target.value)} /></label>
                      <label>Telefon · <code>contact_phone</code><input autoComplete="off" value={companyProfile.contactPhone} onChange={event => updateCompanyProfile("contactPhone", event.target.value)} /></label>
                      <label>Poststed · <code>address_city</code><input autoComplete="off" value={companyProfile.city} onChange={event => updateCompanyProfile("city", event.target.value)} /></label>
                      <label>Postnummer · <code>address_zip</code><input autoComplete="off" value={companyProfile.zip} onChange={event => updateCompanyProfile("zip", event.target.value)} /></label>
                      <label>Fylke / region · <code>address_state</code><input autoComplete="off" value={companyProfile.state} onChange={event => updateCompanyProfile("state", event.target.value)} /></label>
                      <label>Land · <code>address_country</code><input autoComplete="off" value={companyProfile.country} onChange={event => updateCompanyProfile("country", event.target.value)} /></label>
                    </div>
                    <div className="tc-company-preview-foot"><span>Profilen i forhåndsvisningen bruker samme logo og farge.</span><button type="button" className="tc-text-link" onClick={() => setPage("home")}>Se kundesiden <Arrow /></button></div>
                  </section>
                  <p className="tc-settings-feedback">{logoUploadFeedback || "Bildefilen vises kun lokalt i denne mockupen. Ingen opplasting eller databasekobling utføres."}</p>
                </div>}
                {activeAboutTab.id === "staff" && <div className="tc-staff-workspace">
                  <div className="tc-staff-toolbar"><div><span className="tc-kicker">ANSATTREGISTER</span><h3>{staffMembers.length} ansatte</h3><p>Kontaktinformasjon, stilling og eventuell innlogget brukerkonto.</p></div><button type="button" className="tc-settings-save" onClick={addStaffMember}>＋ Legg til ansatt</button></div>
                  <div className="tc-staff-directory">{staffMembers.map(member => <article className="tc-staff-record" key={member.id}>
                    <div className="tc-staff-record-top"><span className="tc-staff-avatar">{member.firstName[0]}{member.lastName[0]}</span><div><strong>{member.firstName} {member.lastName}</strong><small>{member.jobTitle} · {member.department}</small></div>{member.supervisor && <em>VEILEDER</em>}</div>
                    <div className="tc-staff-edit-grid">
                      <label>Fornavn<input autoComplete="off" value={member.firstName} onChange={event => updateStaffField(member.id, "firstName", event.target.value)} /></label>
                      <label>Etternavn<input autoComplete="off" value={member.lastName} onChange={event => updateStaffField(member.id, "lastName", event.target.value)} /></label>
                      <label>E-post<input autoComplete="off" type="email" value={member.email} onChange={event => updateStaffField(member.id, "email", event.target.value)} /></label>
                      <label>Telefon<input autoComplete="off" value={member.phone} onChange={event => updateStaffField(member.id, "phone", event.target.value)} /></label>
                      <label>Stilling<input autoComplete="off" value={member.jobTitle} onChange={event => updateStaffField(member.id, "jobTitle", event.target.value)} /></label>
                      <label>Avdeling<input autoComplete="off" value={member.department} onChange={event => updateStaffField(member.id, "department", event.target.value)} /></label>
                    </div>
                    <div className={`tc-linked-user ${member.linkedUser ? "is-linked" : "is-unlinked"}`}><span className="tc-link-indicator">{member.linkedUser ? "✓" : "·"}</span><span><small>BRUKERTILKNYTNING</small><strong>{member.linkedUser || "Ingen bruker knyttet til denne ansatte"}</strong></span><button type="button" onClick={() => setFeedback(member.linkedUser ? `Brukerkobling for ${member.firstName} vises lokalt.` : `Koble en bruker til ${member.firstName} lokalt.`)}>{member.linkedUser ? "Se bruker" : "Koble bruker"}</button></div>
                    <div className="tc-staff-module-access"><span>MODULTILGANG</span><div>{allModuleChoices.filter(module => enabledModules.includes(module)).map(module => <button type="button" key={module} className={member.modules.includes(module) ? "assigned" : ""} aria-pressed={member.modules.includes(module)} onClick={() => toggleStaffModule(member.id, module)}>{module}{member.modules.includes(module) ? " ×" : " +"}</button>)}</div></div>
                  </article>)}</div>
                </div>}
                {activeAboutTab.id === "email" && <div className="tc-email-workspace">
                  <div className="tc-mail-client">
                    <div className="tc-mail-client-toolbar"><strong>{emailFolder === "inbox" ? "Innboks" : emailFolder === "sent" ? "Sendt" : emailFolder === "queue" ? "Utsendingskø" : "Utkast"}</strong><button type="button" className="tc-settings-save" onClick={() => setFeedback("Ny e-post åpnet i lokal demo.")}>＋ Ny e-post</button></div>
                    <nav className="tc-mail-folders" aria-label="E-postmapper">{([["inbox","Innboks","3"],["sent","Sendt","2"],["queue","Utsendingskø","1"],["drafts","Utkast","1"]] as const).map(([folder,label,count]) => <button type="button" key={folder} className={emailFolder === folder ? "active" : ""} onClick={() => {setEmailFolder(folder);setSelectedEmailId(demoEmails[folder][0]?.id || "");}}><span>{label}</span><b>{count}</b></button>)}</nav>
                    <div className="tc-mail-list" aria-label="Meldinger">{folderMessages.map(mail => <button type="button" key={mail.id} className={selectedEmailId === mail.id ? "active" : ""} onClick={() => setSelectedEmailId(mail.id)}><span className="tc-mail-avatar">{mail.from.split(" ").map(word => word[0]).join("").slice(0,2).toUpperCase()}</span><span className="tc-mail-list-copy"><strong>{mail.from}</strong><b>{mail.subject}</b><small>{mail.preview}</small></span><time>{mail.time}</time></button>)}{!folderMessages.length && <p className="tc-empty-note">Denne mappen er tom.</p>}</div>
                    <article className="tc-mail-reader">{activeEmail ? <><div className="tc-mail-reader-toolbar"><button type="button" onClick={() => setFeedback("Svar er åpnet i lokal e-postdemo.")}>Svar</button><button type="button" onClick={() => setFeedback("Meldingen er arkivert i denne skissen.")}>Arkiver</button><button type="button" onClick={() => setFeedback("Meldingen er markert som lest.")}>⋯</button></div><span className="tc-kicker">{emailFolder === "queue" ? "UTSENDINGSKØ" : emailFolder === "sent" ? "SENDT" : emailFolder === "drafts" ? "UTKAST" : "INNBOKS"}</span><h4>{activeEmail.subject}</h4><div className="tc-mail-sender"><span className="tc-mail-avatar">{activeEmail.from.split(" ").map(word => word[0]).join("").slice(0,2).toUpperCase()}</span><span><strong>{activeEmail.from}</strong><small>{activeEmail.address}</small></span><time>{activeEmail.time}</time></div><p>{activeEmail.body}</p></> : <div className="tc-empty-note">Velg en melding for å lese den.</div>}</article>
                  </div>
                  <section className="tc-mail-automation"><div><span className="tc-kicker">AUTOMATISK UTSENDING</span><h4>Små på/av-valg for hver e-posttype</h4></div><div className="tc-mail-switches">{([["reservation","Påmeldingsbekreftelse"],["renewal","Medlemsfornyelse"],["receipt","Salgsbilag"]] as const).map(([key,label]) => <label key={key} className={emailAutomation[key] ? "enabled" : ""}><span>{label}</span><input type="checkbox" checked={emailAutomation[key]} onChange={event => setEmailAutomation(current => ({...current,[key]:event.target.checked}))} /><i /></label>)}</div></section>
                  <div className="tc-settings-feedback" role="status">{feedback || "Lokale e-posteksempler · ingen meldinger sendes."}</div>
                </div>}
                {activeAboutTab.id === "economy" && <div className="tc-economy-workspace">
                  <div className="tc-economy-toolbar">
                    <div><span className="tc-kicker">ØKONOMI · UNIFIED LEDGER</span><h3>{economyView === "results" ? "Resultater" : "Betalingsløsninger"}</h3><p>{economyView === "results" ? "Inntekter minus utgifter fra eksempelpostene i hovedboken." : "Tilgjengelige betalingsmetoder og aktiveringsvalg."}</p></div>
                    {economyView === "results" ? <button type="button" className="tc-settings-save" onClick={() => setEconomyView("payments")}>Betalingsløsninger <Arrow /></button> : <button type="button" className="tc-outline-action" onClick={() => setEconomyView("results")}><Arrow direction="left" /> Tilbake til resultater</button>}
                  </div>
                  {economyView === "results" ? <>
                    <div className="tc-result-summary">
                      <article className="tc-net-result"><span>RESULTAT · INN − UT</span><strong>{ledgerResult.toLocaleString("nb-NO", {minimumFractionDigits:2,maximumFractionDigits:2})} kr</strong><small>Fra {filteredLedger.length} eksempelposter i unified_ledger.list</small></article>
                      <article><span>INNTEKTER</span><strong>+{ledgerIncome.toLocaleString("nb-NO", {minimumFractionDigits:2,maximumFractionDigits:2})} kr</strong></article>
                      <article><span>UTGIFTER</span><strong>−{ledgerExpense.toLocaleString("nb-NO", {minimumFractionDigits:2,maximumFractionDigits:2})} kr</strong></article>
                    </div>
                    <div className="tc-ledger-heading"><div><span className="tc-kicker">HOVEDBOK</span><h4>Transaksjoner</h4></div><div className="tc-ledger-filters" role="group" aria-label="Filtrer transaksjoner">{([["all","Alle"],["income","Inntekter"],["expense","Utgifter"]] as const).map(([filter,label]) => <button type="button" key={filter} className={economyFilter === filter ? "active" : ""} onClick={() => setEconomyFilter(filter)}>{label}</button>)}</div></div>
                    <div className="tc-ledger-table"><div className="tc-ledger-columns"><span>Modul / beskrivelse</span><span>Kunde</span><span>Tidspunkt</span><span>Betalingsløsning</span><span>Status</span><span>Beløp</span></div>{filteredLedger.map(row => <button type="button" key={row.id} className={`tc-ledger-row ${selectedLedgerId === row.id ? "selected" : ""} ${row.kind}`} onClick={() => setSelectedLedgerId(row.id)}><span><small>{row.module}</small><strong>{row.description}</strong></span><span>{row.customer}</span><time>{row.time}</time><span className="tc-ledger-method">{row.method}</span><span className={`tc-ledger-status ${row.status === "Gjennomført" || row.status === "Betalt" ? "complete" : "pending"}`}>{row.status}</span><strong className={row.amount < 0 ? "expense" : "income"}>{row.amount < 0 ? "−" : "+"}{Math.abs(row.amount).toLocaleString("nb-NO",{minimumFractionDigits:2,maximumFractionDigits:2})} kr</strong></button>)}</div>
                    <div className="tc-ledger-footer"><span>Viser {filteredLedger.length} av {demoLedger.length} poster · lokalt eksempel</span><span>Valgt: {demoLedger.find(row => row.id === selectedLedgerId)?.description || "Ingen"}</span></div>
                  </> : <div className="tc-gateway-grid">{paymentChoices.map(method => <article className={`tc-gateway-card ${economySettings[method.key] ? "is-active" : ""} ${method.configured ? "is-configured" : "is-unconfigured"}`} key={method.key}><div className="tc-gateway-card-top"><span className={`tc-gateway-logo tc-gateway-${method.key}`}>{method.mark}</span><span className={`tc-gateway-light ${method.configured ? economySettings[method.key] ? "green" : "amber" : "gray"}`} /></div><h4>{method.name}</h4><p>{method.detail}</p><button type="button" className={economySettings[method.key] ? "deactivate" : "activate"} onClick={() => {if (method.key === "invoice" && !method.configured) {setEconomySettings(current => ({...current,invoice:true}));setFeedback("Faktura er satt opp med eksempelbankkonto.");} else setEconomySettings(current => ({...current,[method.key]:!current[method.key]}));}}>{method.configured ? economySettings[method.key] ? "Deaktiver" : "Aktiver" : "Konfigurer"}</button>{method.key === "invoice" && economySettings.invoice && <small className="tc-gateway-account">Primærkonto · 1503.28.45109</small>}</article>)}</div>}
                </div>}
                {activeAboutTab.id === "website" && <div className="tc-settings-section-grid tc-website-preview-grid">
                  <section className="tc-settings-panel"><div className="tc-settings-panel-heading"><div><span><h3>Profil og utseende</h3><p>Juster profilen som vises på Hjem og Tjenester.</p></span></div><span className="tc-demo-status">Lokal demo</span></div><div className="tc-form-grid"><label>Firmanavn<input autoComplete="off" value={companyName} onChange={event=>setCompanyName(event.target.value)} /></label><label>Font<select value={theme.fontFamily} onChange={event=>updateTheme("fontFamily",event.target.value)}><option value="Georgia, serif">Georgia</option><option value="DM Sans, sans-serif">DM Sans</option><option value="Trebuchet MS, sans-serif">Trebuchet</option></select></label><label>Headerfarge<span className="tc-color-control"><input type="color" value={theme.headerBg} onChange={event=>updateTheme("headerBg",event.target.value)} /><code>{theme.headerBg}</code></span></label><label>Sidebakgrunn<span className="tc-color-control"><input type="color" value={theme.bodyBg} onChange={event=>updateTheme("bodyBg",event.target.value)} /><code>{theme.bodyBg}</code></span></label></div><div className="tc-form-grid"><label>Logoens plassering<select value={theme.logoPosition} onChange={event=>updateTheme("logoPosition",event.target.value)}><option value="left">Venstre</option><option value="center">Senter</option><option value="right">Høyre</option></select></label><label>Navigasjon<select value={theme.navPosition} onChange={event=>updateTheme("navPosition",event.target.value)}><option value="center">Sentrert</option><option value="left">Venstre</option><option value="right">Høyre</option></select></label></div><div className="tc-footer-calculation"><span>Footerhøyde beregnes fra header</span><strong>{theme.headerHeight}px × 0,6 = {footerHeight}px</strong></div></section>
                  <aside className="tc-website-mini-preview"><div className="tc-website-mini-header" style={{background:theme.headerBg,color:theme.headerText}}><img src={companyLogo} alt="" /><b>{companyName}</b></div><div className="tc-website-mini-nav">Om oss　·　Hjem　·　Tjenester</div><div className="tc-website-mini-body" style={{background:theme.bodyBg}}><span className="tc-kicker">FORHÅNDSVISNING</span><h3>{companyName}</h3><p>Profilendringer vises her mens du justerer.</p></div><span className="tc-website-mini-caption">KUNDESIDE · DEMO</span></aside>
                </div>}
                {feedback && <p className="tc-settings-feedback" role="status">{feedback}</p>}
              </div>
            </div>
          </section>}

          {false && page === "about" && <section className="tc-public-about">
            <div className="tc-about-public-hero">
              <div><span className="tc-kicker">BERGSØY DANSEKLUBB</span><h1>Om oss</h1><p>{aboutText}</p><a className="tc-blue-cta" href={`mailto:${theme.email}`}>Ta kontakt <Arrow /></a></div>
              <div className="tc-about-emblem"><img src={companyLogo} alt={`${companyName} logo`} /><strong>{companyName}</strong><span>Danseglede siden 1987</span></div>
            </div>
            <div className="tc-about-public-content">
              <section><span className="tc-kicker">VÅR KLUBB</span><h2>Plass til alle på dansegulvet.</h2><p>Vi er et lokalt fellesskap der dans, musikk og gode menneskemøter står i sentrum. Gjennom kurs, arrangementer og samlinger skaper vi opplevelser for hele bygda.</p><div className="tc-about-values"><article><strong>Fellesskap</strong><span>Et varmt miljø på tvers av generasjoner.</span></article><article><strong>Bevegelse</strong><span>Plass til å lære, øve og glede seg.</span></article><article><strong>Lokalt</strong><span>Forankret i Fosnavåg og nærområdet.</span></article></div></section>
              <aside className="tc-about-contact"><span className="tc-kicker">KONTAKT OSS</span><h2>Vi hører gjerne fra deg.</h2><a href={`tel:${theme.phone.replaceAll(" ", "")}`}>{theme.phone}</a><a href={`mailto:${theme.email}`}>{theme.email}</a><span>{theme.address}</span><div className="tc-contact-social"><a href={theme.facebook} target="_blank" rel="noreferrer">Facebook <Arrow /></a><a href={theme.instagram} target="_blank" rel="noreferrer">Instagram <Arrow /></a></div></aside>
            </div>
            {interactionMode === "content" && <button type="button" className="tc-about-edit-action" onClick={() => openControlPanel("about")}>Rediger Om oss <Arrow /></button>}
          </section>}

          {/* Bedriftsinnstillinger bor i det flytende arbeidsarket; kundesidens Om oss-body forblir synlig. */}
          {false && page === "about" && (
            <div className="tc-internal-about">
              <header className="tc-internal-about-heading">
                <div><span className="tc-kicker">INTERN ADMINISTRASJON · IKKE KUNDEINNHOLD</span><h1>Om oss</h1><p>Bedriftsinnstillinger og interne arbeidsflater. Dette innholdet vises ikke på kundesiden.</p></div>
                <span className="tc-private-badge"><i /> Privat visning</span>
              </header>
              {aboutTabs.length > 0 ? <>
                <nav className="tc-about-tabs tc-about-body-tabs" role="tablist" aria-label="Interne bedriftsinnstillinger">
                  {aboutTabs.map(tab => <button key={tab.id} type="button" role="tab" aria-selected={aboutTab === tab.id} className={aboutTab === tab.id ? "active" : ""} onClick={() => setAboutTab(tab.id)}>{tab.label}</button>)}
                </nav>
                <p className="tc-demo-access-note">Eksempelvisning uten databasekobling. Faner og data demonstrerer hvordan innholdet følger veileder- og modultilgang.</p>

                {aboutTab === "company" && isSupervisor && <section className="tc-about-content tc-body-settings">
                  <div className="tc-about-section-heading"><div><span className="tc-kicker">TENANTADMIN · VEILEDER</span><h2>Bedriften</h2><p>Interne firmaopplysninger og profil som brukes i systemet.</p></div><button type="button" className="tc-primary-button" onClick={() => setFeedback("Bedriftsopplysningene er oppdatert lokalt i mockupen.")}>Lagre opplysninger</button></div>
                  <div className="tc-settings-grid">
                    <section className="tc-settings-card"><h4>Organisasjon</h4><label>Firmanavn<input autoComplete="off" value={companyName} onChange={event => setCompanyName(event.target.value)} /></label><label>Organisasjonsnummer<input autoComplete="off" value={companyNumber} onChange={event => setCompanyNumber(event.target.value)} /></label><label>Adresse<input autoComplete="off" value={theme.address} onChange={event => updateTheme("address", event.target.value)} /></label><label>Telefon<input autoComplete="off" value={theme.phone} onChange={event => updateTheme("phone", event.target.value)} /></label></section>
                    <section className="tc-settings-card"><h4>Bedriftsprofil</h4><div className="tc-internal-logo"><img src={companyLogo} alt="Bedriftslogo" /><span><strong>{companyName}</strong><small>Logo og identitet i systemet</small><button type="button" className="tc-small-button quiet" onClick={() => logoInputRef.current?.click()}>Bytt logo</button><input ref={logoInputRef} type="file" accept="image/*" onChange={handleLocalLogo} hidden /></span></div><p className="tc-helper">Denne arbeidsflaten er tilgjengelig for <code>tenant_admin.tenant_supervisor = 1</code>.</p><p className="tc-feedback" role="status">{logoUploadFeedback}</p></section>
                  </div>
                </section>}

                {aboutTab === "staff" && isSupervisor && <section className="tc-about-content tc-body-settings">
                  <div className="tc-about-section-heading"><div><span className="tc-kicker">TENANTADMIN · VEILEDER</span><h2>Ansatte</h2><p>Kun ansatte med tilknytning til denne bedriften. Del bare aktive moduler.</p></div><button type="button" className="tc-primary-button" onClick={addStaffMember}>＋ Legg til ansatt</button></div>
                  <div className="tc-superuser-overview"><span className="tc-superuser-mark">SU</span><span><strong>Superbrukeroversikt</strong><small>Brukere med tenant_admin.tenant_supervisor = 1</small></span><b>{staffMembers.filter(member => member.supervisor).length} superbrukere</b></div>
                  <div className="tc-staff-list">{staffMembers.map(member => <article className="tc-staff-row" key={member.id}><span className="tc-staff-avatar">{member.name.split(" ").map(part => part[0]).join("").slice(0,2).toUpperCase()}</span><div className="tc-staff-details"><label>Navn<input autoComplete="off" value={member.name} onChange={event => setStaffMembers(current => current.map(person => person.id === member.id ? {...person, name:event.target.value} : person))} /></label><label>Rolle<input autoComplete="off" value={member.role} onChange={event => setStaffMembers(current => current.map(person => person.id === member.id ? {...person, role:event.target.value} : person))} /></label><div className="tc-delegated-modules"><span>Modultilganger</span>{enabledModules.map(module => <button type="button" key={module} className={member.modules.includes(module) ? "assigned" : ""} aria-pressed={member.modules.includes(module)} onClick={() => toggleStaffModule(member.id,module)}>{module}{member.modules.includes(module) ? " ×" : " +"}</button>)}</div></div><button type="button" className="tc-small-button quiet" onClick={() => setFeedback(`Passordvalg for ${member.name} er åpnet lokalt.`)}>Nytt passord</button></article>)}</div>
                </section>}

                {aboutTab === "email" && emailModuleEnabled && <section className="tc-about-content tc-body-settings">
                  <div className="tc-about-section-heading"><div><span className="tc-kicker">E-POSTMODUL</span><h2>E-post</h2><p>Avsender, automatiseringer, maler og køstatus.</p></div><span className="tc-live-preview">Modul aktiv</span></div>
                  <div className="tc-settings-grid"><section className="tc-settings-card"><h4>Avsender</h4><label>Avsendernavn<input autoComplete="off" value={companyName} onChange={event => setCompanyName(event.target.value)} /></label><label>E-postadresse<input autoComplete="off" type="email" value={theme.email} onChange={event => updateTheme("email", event.target.value)} /></label><div className="tc-status-line"><i /> E-postoppsett tilgjengelig <span>Detaljer skjult</span></div></section><section className="tc-settings-card"><h4>Automatiske utsendinger</h4>{([["reservation","Påmeldingsbekreftelse"],["renewal","Medlemsfornyelse"],["receipt","Salgsbilag"]] as const).map(([key,label]) => <label className="tc-setting-toggle" key={key}><input type="checkbox" checked={emailAutomation[key]} onChange={event => setEmailAutomation(current => ({...current,[key]:event.target.checked}))} /><span>{label}</span></label>)}</section></div>
                  <section className="tc-settings-card tc-template-card"><div className="tc-about-section-heading"><div><h4>Maler og kø</h4><p>Eksempel på e-postmaler for bedriften.</p></div><span className="tc-count-badge">4 maler</span></div><div className="tc-template-list">{[["reservation_confirmation","Påmeldingsbekreftelse"],["membership_renewal","Medlemsfornyelse"],["sales_receipt","Salgsbilag"],["rental_request","Utleieforespørsel"]].map(([key,name])=><button type="button" key={key} onClick={() => setFeedback(`Forhåndsvisning av ${name} er valgt.`)}><span><strong>{name}</strong><small>Bedriftens aktive mal</small></span><code>{key}</code><Arrow /></button>)}</div><div className="tc-mail-queue"><span className="tc-queue-icon">↗</span><span><strong>Utsendingskø</strong><small>Eksempeldata · ingen live kø er lest.</small></span><b>Demo</b></div></section>
                </section>}

                {aboutTab === "economy" && enabledModules.includes("Økonomi") && <section className="tc-about-content tc-body-settings">
                  <div className="tc-about-section-heading"><div><span className="tc-kicker">ØKONOMIMODUL</span><h2>Økonomi</h2><p>Betalingsoppsett og økonomioversikt for bedriften.</p></div><span className="tc-live-preview">Modul aktiv</span></div>
                  <div className="tc-economy-summary"><article><small>INNTEKTER DENNE MÅNEDEN</small><strong>18 450 kr</strong><span>12 fullførte betalinger · demo</span></article><article><small>ÅPNE FAKTURAER</small><strong>2 400 kr</strong><span>3 krav · demo</span></article><article><small>BETALINGSSTATUS</small><strong>Oppdatert</strong><span>Eksempeldata, ikke live</span></article></div>
                  <section className="tc-settings-card"><h4>Betalingsmetoder</h4>{([["cash","Kontant"],["vipps","Vipps"],["invoice","Faktura"]] as const).map(([key,label])=><label className="tc-setting-toggle" key={key}><input type="checkbox" checked={economySettings[key]} onChange={event=>setEconomySettings(current=>({...current,[key]:event.target.checked}))} /><span>{label}</span></label>)}{economySettings.invoice && <label className="tc-bank-account">Primærkonto<input autoComplete="off" defaultValue="1503.28.45109 · Bergsøy Danseklubb" /></label>}</section>
                  <section className="tc-settings-card"><h4>Siste bevegelser · eksempeldata</h4><div className="tc-economy-row"><span>Medlemskap · Bergsøy Medlem</span><small>14. nov. 2026</small><b>+500,00 kr</b></div><div className="tc-economy-row"><span>Lokalleie · Høgtun</span><small>12. nov. 2026</small><b>−1 250,00 kr</b></div></section>
                </section>}

                {aboutTab === "website" && websiteModuleEnabled && <section className="tc-about-content tc-body-settings">
                  <div className="tc-about-section-heading"><div><span className="tc-kicker">DITT NETTSTED · tenant_general_website</span><h2>Webside</h2><p>Generelle innstillinger for kundesiden. Endringer speiles direkte i forhåndsvisningen.</p></div><span className="tc-live-preview">Preview</span></div>
                  <div className="tc-settings-grid"><section className="tc-settings-card"><h4>Profil og farger</h4><label>Firmanavn<input autoComplete="off" value={companyName} onChange={event => setCompanyName(event.target.value)} /></label><label>Headerfarge<span className="tc-color-control"><input type="color" value={theme.headerBg} onChange={event => updateTheme("headerBg",event.target.value)} /><code>{theme.headerBg}</code></span></label><label>Sidebakgrunn<span className="tc-color-control"><input type="color" value={theme.bodyBg} onChange={event => updateTheme("bodyBg",event.target.value)} /><code>{theme.bodyBg}</code></span></label><label>Font<select value={theme.fontFamily} onChange={event => updateTheme("fontFamily",event.target.value)}><option value="Georgia, serif">Georgia</option><option value="DM Sans, sans-serif">DM Sans</option><option value="Trebuchet MS, sans-serif">Trebuchet</option></select></label></section><section className="tc-settings-card"><h4>Visning og navigasjon</h4><label className="tc-setting-toggle"><input type="checkbox" checked={heroEnabled} onChange={event => setHeroEnabled(event.target.checked)} /><span>Vis toppsak på Hjem</span></label><label>Logoens plassering<select value={theme.logoPosition} onChange={event => updateTheme("logoPosition",event.target.value)}><option value="left">Venstre</option><option value="center">Senter</option><option value="right">Høyre</option></select></label><label>Navigasjon<select value={theme.navPosition} onChange={event => updateTheme("navPosition",event.target.value)}><option value="center">Sentrert</option><option value="left">Venstre</option><option value="right">Høyre</option></select></label><div className="tc-footer-calculation"><span>Footerhøyde</span><strong>{theme.headerHeight}px × 0,6 = {footerHeight}px</strong></div></section></div>
                  {renderSeoSettings()}
                </section>}

                {aboutTab && !aboutTabs.some(tab => tab.id === aboutTab) && <section className="tc-empty-note">Denne arbeidsflaten er ikke tilgjengelig for den valgte brukeren eller bedriftens modultilganger.</section>}
              </> : <div className="tc-empty-note tc-about-empty"><strong>Ingen interne innstillinger er tilgjengelige.</strong><span>Visningen styres av veilederstatus og aktive moduler.</span></div>}
              <p className="tc-feedback" role="status">{feedback}</p>
            </div>
          )}
        </div>

        {page !== "about" && <footer className={`tc-site-footer tc-footer-${theme.footerLayout}`}>
          <div className="tc-footer-inner">
            <div className="tc-socials" aria-label="Sosiale medier">
              <a href={theme.facebook} target="_blank" rel="noreferrer" aria-label="Facebook for Bergsøy Danseklubb">f</a>
              <a href={theme.instagram} target="_blank" rel="noreferrer" aria-label="Instagram for Bergsøy Danseklubb">◎</a>
            </div>
            <div><small>TELEFON</small><a href={`tel:${theme.phone.replaceAll(" ", "")}`}>{theme.phone}</a></div>
            <div><small>E-POST</small><a href={`mailto:${theme.email}`}>{theme.email}</a></div>
            <div><small>ADRESSE</small><span>{theme.address}</span></div>
          </div>
          {theme.footerLayout === "extended" && <div className="tc-footer-extra"><span>{theme.footerText}</span><span>{theme.footerLinks}</span></div>}
        </footer>}
      </section>

      {newOfferOpen && <aside className="tc-reader-preview tc-new-service-panel" aria-label="Opprett ny tjeneste">
        <button type="button" onClick={() => setNewOfferOpen(false)}>Lukk ×</button>
        <span className="tc-kicker">TJENESTER · LOKAL MOCKUP</span>
        <h2>Opprett ny tjeneste</h2>
        <p>Velg en modul du har tilgang til. Tjenesten legges inn i kundesidens forhåndsvisning.</p>
        <label>Modul<select value={newOfferModule} onChange={event => setNewOfferModule(event.target.value as ModuleName)}>{enabledModules.filter(module => module !== "Økonomi").map(module => <option key={module} value={module}>{module}</option>)}</select></label>
        <label>Navn<input autoComplete="off" value={newOfferTitle} onChange={event => setNewOfferTitle(event.target.value)} placeholder="For eksempel Sommerdans" /></label>
        <label>Pris (valgfritt)<input autoComplete="off" value={newOfferPrice} onChange={event => setNewOfferPrice(event.target.value)} placeholder="For eksempel 250 kr" /></label>
        <button type="button" className="tc-primary-button" onClick={createOffer}>Legg til på Tjenester</button>
        <small className="tc-local-note">Eksempeldata · ingenting lagres i portalen.</small>
      </aside>}
      {readerTitle && <aside className="tc-reader-preview" aria-label="Kundevisning"><button type="button" onClick={() => setReaderTitle("")}>Lukk ×</button><h2>{page === "home" ? activeNews?.title : readerTitle}</h2><p>{page === "home" ? activeNews?.summary : "Kundens detaljvisning · lokal demonstrasjon, ingen bestilling eller betaling utføres."}</p></aside>}
      <section className={`tc-control-room ${controlOpen ? "is-open" : ""}`} aria-label="Arbeidsmodus for forhåndsvisningen" aria-hidden={!controlOpen} inert={!controlOpen}>
        <header className="tc-control-hero">
          <div className="tc-control-inner">
              <div className="tc-admin-eyebrow"><span className="tc-live-dot" /> KONTROLLROM <span>·</span> BERGSØY DANSEKLUBB
                <div className="tc-admin-actions">
                  {(page === "home" || page === "about") && <button type="button" className="tc-header-create tc-news-link" onClick={openAllNews}>Alle nyheter <Arrow /></button>}
                  {page === "home" && <button type="button" className="tc-header-create" onClick={startNewNews}>＋ Opprett ny sak</button>}
                  {page === "services" && <button type="button" className="tc-header-create" onClick={startNewOffer}>＋ Opprett ny tjeneste</button>}
                </div>
                <button type="button" className="tc-control-close" onClick={() => setControlOpen(false)} aria-label="Lukk kontrollrom">×</button>
            </div>
            <div className="tc-greeting-row">
              <div>
                <h2>Hei, Atle.</h2>
                <p>Du er oppført med {enabledModules.join(", ") || "ingen moduler"}.</p>
              </div>
              <div className="tc-staff-badge"><span>AK</span><div><small>INNLOGGET SOM</small><strong>Atle Karl</strong><small>tenant_admin · tenant_supervisor=1</small></div></div>
            </div>
            <div className="tc-module-access">
              <span>MODULTILGANG</span>
              <div>{enabledModules.length ? enabledModules.map((module) => <span className="tc-module-chip" key={module}>{module}</span>) : <span className="tc-no-module-chip">Ingen moduler valgt</span>}</div>
            </div>
            <div className="tc-edit-mode-options" role="radiogroup" aria-label="Arbeidsmodus for kundesideinnhold">
              <span className="tc-edit-mode-title">VED KLIKK PÅ INNHOLD</span>
              {[["read", "Les", "Som på kundesiden"], ["content", page === "about" ? "Innstillinger" : "Rediger innhold", page === "about" ? "Bedrift, ansatte, e-post og webside" : "Nyheter og tjenester"], ...(page === "services" && enabledModules.includes("Medlemskap") ? [["benefits", "Medlemsfordeler", "Velg et tjenestekort"]] : []), ...(page === "services" && enabledModules.some(module => ["Arrangementer", "Utleie", "Salg"].includes(module)) ? [["fields", "Skjemafelt", "Velg en tjeneste"]] : [])].map(([mode, title, hint]) => <label key={mode} className={interactionMode === mode ? "checked" : ""}><input type="radio" name="interaction-mode" checked={interactionMode === mode} onChange={() => chooseMode(mode)} /><span><b>{title}</b><small>{hint}</small></span></label>)}
            </div>
          </div>
        </header>

      </section>
        {editingPage && <aside className={`tc-workspace-wrap tc-floating-inspector tc-mode-${interactionMode} ${editingPage === "about" ? "tc-about-inspector" : ""}`} aria-label="Rediger valgt innhold">
          <button type="button" className="tc-inspector-dismiss" onClick={() => setEditingPage(null)}>Lukk redigering ×</button>
          <div className="tc-workspace-top">
            <div><span className="tc-kicker">NETTSIDEADMINISTRASJON</span><h2>{editingPage === "home" ? "Alle nyheter" : editingPage === "services" ? "Tjenester" : "Om oss"}</h2></div>
            <span className="tc-local-note">Lokal mockup · endringer lagres ikke</span>
          </div>

          {editingPage === "home" && (
            <div className="tc-editor-panel tc-home-admin">
              <div className="tc-editor-heading">
                <div><h3>Saker på Hjem</h3><p>Rekkefølgen her er rekkefølgen besøkende møter sakene.</p></div>
                <button type="button" className="tc-primary-button" onClick={addNews}><span>+</span> Ny sak</button>
              </div>
              <div className="tc-news-editor-grid">
                <div className="tc-news-list" aria-label="Nyhetssaker">
                  {news.map((item, index) => <article className={`tc-news-row ${selectedNewsId === item.id ? "selected" : ""}`} key={item.id}>
                    <button type="button" className="tc-news-select" onClick={() => setSelectedNewsId(item.id)}>
                      {item.image ? <img src={item.image} alt="" /> : <span className="tc-news-placeholder">N</span>}
                      <span><strong>{item.title || "Uten tittel"}</strong><small>{item.date} · {item.visible ? "Vises på Hjem" : "Skjult"}</small></span>
                    </button>
                    <div className="tc-news-actions">
                      <button type="button" aria-label="Flytt sak opp" disabled={index === 0} onClick={() => moveNews(index, -1)}><Arrow direction="up" /></button>
                      <button type="button" aria-label="Flytt sak ned" disabled={index === news.length - 1} onClick={() => moveNews(index, 1)}><Arrow direction="down" /></button>
                      <button type="button" className={`tc-visibility-button ${item.visible ? "is-visible" : ""}`} onClick={() => toggleNews(item.id)}>{item.visible ? "Synlig" : "Skjult"}</button>
                    </div>
                  </article>)}
                </div>
                {activeNews && <div className="tc-editor-inspector">
                  <div className="tc-inspector-top"><span>VALGT SAK</span><span className={activeNews.visible ? "tc-status-visible" : "tc-status-hidden"}>{activeNews.visible ? "På Hjem" : "Skjult fra Hjem"}</span></div>
                  <label>Tittel<input value={activeNews.title} onChange={(event) => updateNews(activeNews.id, "title", event.target.value)} /></label>
                  <label>Ingress<textarea rows={3} value={activeNews.summary} onChange={(event) => updateNews(activeNews.id, "summary", event.target.value)} /></label>
                  <div className="tc-image-editor">
                    <div>{activeNews.image ? <img src={activeNews.image} alt="" /> : <span>Bildet vises her</span>}</div>
                    <button type="button" className="tc-small-button quiet" onClick={() => fileInputRef.current?.click()}>Velg bildefil</button>
                    <input ref={fileInputRef} type="file" accept="image/*" onChange={handleLocalImage} hidden />
                  </div>
                  <p className="tc-inspector-help">Forhåndsvisningen over følger tittel, ingress, bilde, synlighet og rekkefølge.</p>
                </div>}
              </div>
            </div>
          )}

          {editingPage === "services" && (
            <div className="tc-editor-panel">
              <div className="tc-editor-heading">
                <div><h3>Tjenester som kan vises</h3><p>Aktiverte moduler bestemmer hvilke tjenestekategorier som finnes på den offentlige siden.</p></div>
                <span className="tc-count-badge">{activeServices.length} tilbud</span>
              </div>
              <div className="tc-module-toggle-row">
                {allModuleChoices.map((module) => <button type="button" className={`tc-module-toggle ${enabledModules.includes(module) ? "enabled" : ""}`} key={module} aria-pressed={enabledModules.includes(module)} onClick={() => toggleModule(module)}>
                  <span className="tc-module-check">{enabledModules.includes(module) ? "✓" : "+"}</span><span><strong>{module}</strong><small>{moduleDescriptions[module]}</small></span>
                </button>)}
              </div>
              <div className="tc-services-admin-grid">
                <div className="tc-admin-service-list">
                  {activeServices.map(([id, service]) => <button type="button" key={id} className={`tc-admin-service ${selectedService === id ? "selected" : ""}`} onClick={() => setSelectedService(id)}>
                    <img src={service.image} alt="" /><span><small>{service.module}</small><strong>{service.title}</strong><span>{service.price}</span></span><Arrow />
                  </button>)}
                  {!activeServices.length && <div className="tc-empty-note">Velg en modul ovenfor for å vise tjenestene.</div>}
                </div>
                <div className="tc-service-inspector">
                  <div className="tc-inspector-top"><span>REDIGER TJENESTE</span><span className="tc-service-tag">{currentService.module}</span></div>
                  <h4>{currentService.title}</h4>
                  <p>{currentService.description}</p>
                  {(interactionMode === "content" || canManageBenefits) && selectedService === "membership" && <>
                    <div className="tc-plan-tabs" role="tablist" aria-label="Medlemskapsplaner"><button type="button" className="active">Bergsøy Medlem</button><button type="button" onClick={() => setFeedback("Familieplanen er eksempeldata i denne skissen.")}>Familie</button><button type="button" className="tc-add-plan" onClick={() => setFeedback("Ny plan kan opprettes her.")}>+ Plan</button></div>
                    <label className="tc-check-line"><input type="checkbox" checked={benefitEnabled} onChange={(event) => setBenefitEnabled(event.target.checked)} /><span><strong>Medlemsfordel på tilbud</strong><small>{benefitEnabled ? "250 kr vises som medlemspris" : "Tilbudet bruker ordinær pris"}</small></span></label>
                    {benefitEnabled && <div className="tc-inline-price"><span>Ordinærpris <b>250 kr</b></span><span>Medlemspris <b>250 kr</b></span><button type="button" onClick={() => setFeedback("Medlemsfordelen er endret lokalt.")}>Endre fordel</button></div>}
                  </>}
                  {(interactionMode === "content" || canManageBenefits) && selectedService !== "membership" && <><label className="tc-check-line"><input type="checkbox" checked={benefitEnabled} onChange={(event) => setBenefitEnabled(event.target.checked)} /><span><strong>Medlemsfordel på tilbud</strong><small>{benefitEnabled ? "En aktiv medlemsfordel er satt opp" : "Ingen medlemsfordel"}</small></span></label><label className="tc-check-line"><input type="checkbox" checked={memberExclusive} onChange={(event) => setMemberExclusive(event.target.checked)} /><span><strong>Kun for medlemmer</strong><small>{memberExclusive ? "Tilbudet skjules for ikke-medlemmer" : "Tilbudet er synlig for alle"}</small></span></label></>}
                  {(interactionMode === "content" || canManageBenefits) && (selectedService === "rental" || selectedService === "event-dinner" || selectedService === "event-dance") && <label className="tc-check-line"><input type="checkbox" checked={memberVerification} onChange={(event) => setMemberVerification(event.target.checked)} /><span><strong>Medlemsverifisering</strong><small>Kontroller medlemsstatus ved innsending</small></span></label>}
                  {(interactionMode === "content" || canManageFields) && (selectedService === "rental" || selectedService === "event-dinner" || selectedService === "event-dance" || selectedService === "sales") && <>
                    <div className="tc-field-editor-head"><div><strong>Skjemafelt</strong><small>Velg hvilke opplysninger kunden skal fylle ut.</small></div><button type="button" className="tc-small-button quiet" onClick={() => setFieldEditorOpen((open) => !open)}>{fieldEditorOpen ? "Skjul felt" : "Rediger felt"}</button></div>
                    {fieldEditorOpen && <div className="tc-form-presets">{["Fornavn", "Etternavn", "E-postadresse", "Telefonnummer", "Adresse", "Postnummer", "Poststed", "Alder"].map((field) => <label key={field}><input type="checkbox" checked={checkedFields.includes(field)} onChange={() => toggleField(field)} />{field}</label>)}</div>}
                    <div className="tc-field-summary">{checkedFields.length} felter valgt <span>{checkedFields.slice(0, 4).join(" · ")}{checkedFields.length > 4 ? " …" : ""}</span></div>
                  </>}
                  {selectedService === "sales" && <div className="tc-sales-note">Dette tilbudet hentes fra Salg. Pris og vareinnhold redigeres i modulens vareoversikt.</div>}
                  <p className="tc-feedback" role="status">{feedback}</p>
                </div>
              </div>
            </div>
          )}

          {editingPage === "about" && (
            <div className="tc-editor-panel tc-about-editor">
              <div className="tc-editor-heading">
                <div><h3>Om oss · administrasjon</h3><p>Firma, ansatte, e-post, økonomi og nettsted – delt etter tilgang.</p></div>
                <button type="button" className="tc-primary-button" onClick={() => setFeedback("Endringene er lagret lokalt i mockupen.")}>Lagre endringer</button>
              </div>
              <nav className="tc-about-tabs" role="tablist" aria-label="Administrer bedriftsinnstillinger">
                {aboutTabs.map(tab => <button key={tab.id} type="button" role="tab" aria-selected={aboutTab === tab.id} className={aboutTab === tab.id ? "active" : ""} onClick={() => setAboutTab(tab.id)}>{tab.label}</button>)}
              </nav>
              <p className="tc-demo-access-note">Forhåndsvisning med eksempeldata · fanene vises uten å hente modultilganger eller data fra databasen.</p>

              {aboutTab === "staff" && isSupervisor && <section className="tc-about-content">
                <div className="tc-about-section-heading"><div><span className="tc-kicker">TILGANG OG ROLLER</span><h3>Ansatte</h3><p>Administrer firmabrukere, rolle og moduldelegering. Bare aktive bedriftsmoduler kan deles.</p></div><button type="button" className="tc-primary-button" onClick={addStaffMember}>＋ Inviter ansatt</button></div>
                <div className="tc-superuser-overview"><span className="tc-superuser-mark">SU</span><span><strong>Superbrukeroversikt</strong><small>tenant_admin.tenant_supervisor = 1 · administratorer og veiledere</small></span><b>{staffMembers.filter(member => member.supervisor).length} superbrukere</b></div>
                <div className="tc-staff-list">
                  {staffMembers.map(member => <article className="tc-staff-row" key={member.id}>
                    <span className="tc-staff-avatar">{member.name.split(" ").map(part => part[0]).join("").slice(0,2).toUpperCase()}</span>
                    <div className="tc-staff-details"><label>Navn<input value={member.name} onChange={event => setStaffMembers(current => current.map(person => person.id === member.id ? {...person, name:event.target.value} : person))} /></label><label>Rolle<input value={member.role} onChange={event => setStaffMembers(current => current.map(person => person.id === member.id ? {...person, role:event.target.value} : person))} /></label><div className="tc-delegated-modules"><span>Modultilganger</span>{allModuleChoices.filter(module => enabledModules.includes(module)).map(module => <button type="button" key={module} className={member.modules.includes(module) ? "assigned" : ""} aria-pressed={member.modules.includes(module)} onClick={() => toggleStaffModule(member.id,module)}>{module}{member.modules.includes(module) ? " ×" : " +"}</button>)}</div></div>
                    <button type="button" className="tc-small-button quiet" onClick={() => setFeedback(`Passordvalg for ${member.name} er åpnet i denne skissen.`)}>Passord <Arrow /></button>
                  </article>)}
                </div>
              </section>}

              {aboutTab === "email" && emailModuleEnabled && <section className="tc-about-content">
                <div className="tc-about-section-heading"><div><span className="tc-kicker">E-POSTMODUL</span><h3>Utsendinger og maler</h3><p>Avsender, automatiske utsendinger og køstatus samlet. Hemmeligheter vises aldri her.</p></div><span className="tc-live-preview">Modul aktiv</span></div>
                <div className="tc-settings-grid">
                  <section className="tc-settings-card"><h4>Avsender</h4><label>Visningsnavn<input value={companyName} onChange={event => setCompanyName(event.target.value)} /></label><label>Avsenderadresse<input type="email" value={theme.email} onChange={event => updateTheme("email",event.target.value)} /></label><div className="tc-status-line"><i /> E-postoppsett tilgjengelig <span>Konfigurasjon skjult</span></div></section>
                  <section className="tc-settings-card"><h4>Automatiske e-poster</h4>{([["reservation","Reservasjonsbekreftelse"],["renewal","Medlemsfornyelse"],["receipt","Salgsbilag"]] as const).map(([key,label]) => <label className="tc-setting-toggle" key={key}><input type="checkbox" checked={emailAutomation[key]} onChange={event => setEmailAutomation(current => ({...current,[key]:event.target.checked}))} /><span>{label}</span></label>)}</section>
                </div>
                <section className="tc-settings-card tc-template-card"><div className="tc-about-section-heading"><div><h4>Maler</h4><p>Standardmaler brukt av den aktive e-postmodulen.</p></div><span className="tc-count-badge">4 maler</span></div><div className="tc-template-list">{[["reservation_confirmation","Påmeldingsbekreftelse","Sist endret 12. nov."],["membership_renewal","Medlemsfornyelse","Automatisk utsending"],["sales_receipt","Salgsbilag","Valgfri kvittering"],["rental_request","Utleieforespørsel","Godkjenning og avslag"]].map(([key,name,meta])=><button type="button" key={key} onClick={() => setFeedback(`Forhåndsvisning av ${name} er valgt.`)}><span><strong>{name}</strong><small>{meta}</small></span><code>{key}</code><Arrow /></button>)}</div></section>
                <div className="tc-mail-queue"><span className="tc-queue-icon">↗</span><span><strong>Utsendingskø</strong><small>Ingen feil i eksempelvisningen · køstatus hentes fra e-postmodulen.</small></span><b>Tilgjengelig</b></div>
              </section>}

              {aboutTab === "economy" && enabledModules.includes("Økonomi") && <section className="tc-about-content">
                <div className="tc-about-section-heading"><div><span className="tc-kicker">ØKONOMIMODUL</span><h3>Betalinger og regnskap</h3><p>Betalingsvalg, MVA-status og fakturakonto. Tallene under er eksempeldata.</p></div><span className="tc-live-preview">Modul aktiv</span></div>
                <div className="tc-economy-summary"><article><small>INNTEKTER DENNE MÅNEDEN</small><strong>18 450 kr</strong><span>12 gjennomførte betalinger</span></article><article><small>UTESTÅENDE FAKTURAER</small><strong>2 400 kr</strong><span>3 åpne krav</span></article><article><small>LEDGERSTATUS</small><strong>Synkronisert</strong><span>Oppdatert for 8 min. siden</span></article></div>
                <section className="tc-settings-card"><h4>Skatt og betalingsmetoder</h4><label className="tc-setting-toggle"><input type="checkbox" checked={economySettings.vatRegistered} onChange={event => setEconomySettings(current=>({...current,vatRegistered:event.target.checked}))} /><span>MVA-registrert firma</span></label><p className="tc-helper">MVA-valget styrer hvordan priser og avgiftslinjer vises på økonomioversikten.</p><div className="tc-payment-options">{([["cash","Kontant"],["vipps","Vipps"],["invoice","Faktura"]] as const).map(([key,label])=><label className="tc-setting-toggle" key={key}><input type="checkbox" checked={economySettings[key]} onChange={event=>setEconomySettings(current=>({...current,[key]:event.target.checked}))} /><span>{label}</span></label>)}</div>{economySettings.invoice && <label className="tc-bank-account">Primær bankkonto<input defaultValue="1503.28.45109 · Bergsøy Danseklubb" /><small>Faktura bruker bare den valgte aktive kontoen.</small></label>}</section>
                <section className="tc-settings-card"><h4>Siste bevegelser</h4><div className="tc-economy-row"><span>Medlemskap · Bergsøy Medlem</span><small>14. nov. 2026</small><b>+500,00 kr</b></div><div className="tc-economy-row"><span>Lokalleie · Høgtun</span><small>12. nov. 2026</small><b>−1 250,00 kr</b></div><div className="tc-economy-row"><span>Vipps-gebyr</span><small>12. nov. 2026</small><b>−4,50 kr</b></div></section>
              </section>}

              {aboutTab === "company" && isSupervisor && <div className="tc-about-editor-grid">
                <div className="tc-about-copy-editor">
                  <div className="tc-about-section-heading"><div><span className="tc-kicker">BEDRIFTSINFORMASJON</span><h3>Bedriften</h3><p>Opplysninger som brukes på kundesiden og i kommunikasjon med medlemmer.</p></div></div>
                  <div className="tc-company-fields">
                    <label>Firmanavn<input value={companyName} onChange={(event) => setCompanyName(event.target.value)} /></label>
                    <label>Organisasjonsnummer<input value={companyNumber} onChange={event=>setCompanyNumber(event.target.value)} /></label>
                    <label>Om oss-tekst<textarea rows={5} value={aboutText} onChange={(event) => setAboutText(event.target.value)} /></label>
                  </div>
                  <div className="tc-contact-fields">
                    <label>Telefon<input value={theme.phone} onChange={(event) => updateTheme("phone", event.target.value)} /></label>
                    <label>E-post<input type="email" value={theme.email} onChange={(event) => updateTheme("email", event.target.value)} /></label>
                    <label>Adresse<input value={theme.address} onChange={(event) => updateTheme("address", event.target.value)} /></label>
                    <label>Facebook-lenke<input value={theme.facebook} onChange={(event) => updateTheme("facebook", event.target.value)} /></label>
                    <label>Instagram-lenke<input value={theme.instagram} onChange={(event) => updateTheme("instagram", event.target.value)} /></label>
                  </div>
                </div>
                <section className="tc-settings-card tc-company-logo-settings"><h4>Profilbilde og sidetilgang</h4><div className="tc-brand-editor"><div><span>Logo · eksempel</span><img src={companyLogo} alt="" /><button type="button" className="tc-small-button quiet" onClick={() => logoInputRef.current?.click()}>Bytt logo</button><input ref={logoInputRef} type="file" accept="image/*" onChange={handleLocalLogo} hidden /></div></div><p className="tc-helper">Bedriftfanen og brukeroversikten krever tenant_admin.tenant_supervisor = 1.</p><h4>Tilgjengelige systemmoduler</h4><label className="tc-setting-toggle"><input type="checkbox" checked={emailModuleEnabled} onChange={event=>setEmailModuleEnabled(event.target.checked)} /><span>E-postmodul</span></label><label className="tc-setting-toggle"><input type="checkbox" checked={websiteModuleEnabled} onChange={event=>setWebsiteModuleEnabled(event.target.checked)} /><span>Ditt Nettsted</span></label><p className="tc-feedback" role="status">{logoUploadFeedback}</p></section>
              </div>}

              {aboutTab === "website" && websiteModuleEnabled && <>
                <div className="tc-about-editor-grid tc-website-grid">
                <div className="tc-theme-editor">
                  <div className="tc-theme-heading"><span><strong>Webside · tenant_general_website</strong><small>Utseende og generelle nettsideinnstillinger gjenspeiles i kundeside-preview.</small></span><span className="tc-live-preview">Live</span></div>
                  <div className="tc-brand-editor">
                    <label>Firmanavn<input value={companyName} onChange={(event) => setCompanyName(event.target.value)} /></label>
                    <div><span>Logo · bildefil</span><img src={companyLogo} alt="" /><button type="button" className="tc-small-button quiet" onClick={() => logoInputRef.current?.click()}>Bytt logo</button><input ref={logoInputRef} type="file" accept="image/*" onChange={handleLocalLogo} hidden /><small>{logoUploadFeedback || "PNG med transparens fungerer godt i headeren."}</small></div>
                  </div>
                  <label className="tc-hero-toggle"><input type="checkbox" checked={heroEnabled} onChange={(event) => setHeroEnabled(event.target.checked)} /><span><strong>Vis toppsak på Hjem</strong><small>Første synlige nyhet får stor plass øverst.</small></span></label>
                  <div className="tc-theme-grid">
                    <label>Headerfarge<span className="tc-color-control"><input type="color" value={theme.headerBg} onChange={(event) => updateTheme("headerBg", event.target.value)} /><code>{theme.headerBg}</code></span></label>
                    <label>Headertekst<span className="tc-color-control"><input type="color" value={theme.headerText} onChange={(event) => updateTheme("headerText", event.target.value)} /><code>{theme.headerText}</code></span></label>
                    <label>Bakgrunnsfarge<span className="tc-color-control"><input type="color" value={theme.bodyBg} onChange={(event) => updateTheme("bodyBg", event.target.value)} /><code>{theme.bodyBg}</code></span></label>
                    <label>Primærfarge<span className="tc-color-control"><input type="color" value={theme.primaryColor} onChange={(event) => updateTheme("primaryColor", event.target.value)} /><code>{theme.primaryColor}</code></span></label>
                    <label>Sekundærfarge<span className="tc-color-control"><input type="color" value={theme.secondaryColor} onChange={(event) => updateTheme("secondaryColor", event.target.value)} /><code>{theme.secondaryColor}</code></span></label>
                    <label>Font<select value={theme.fontFamily} onChange={(event) => updateTheme("fontFamily", event.target.value)}><option value="Georgia, serif">Georgia</option><option value="DM Sans, sans-serif">DM Sans</option><option value="Trebuchet MS, sans-serif">Trebuchet</option></select></label>
                    <label>Headerhøyde <output>{theme.headerHeight}px</output><input type="range" min="72" max="150" value={theme.headerHeight} onChange={(event) => updateTheme("headerHeight", Number(event.target.value))} /></label>
                    <label>Logohøyde <output>{theme.logoHeight}px</output><input type="range" min="34" max="84" value={theme.logoHeight} onChange={(event) => updateTheme("logoHeight", Number(event.target.value))} /></label>
                    <label>Brødtekst <output>{theme.fontSize}px</output><input type="range" min="12" max="20" value={theme.fontSize} onChange={(event) => updateTheme("fontSize", Number(event.target.value))} /></label>
                    <label>Overskrift <output>{theme.headingSize}px</output><input type="range" min="22" max="42" value={theme.headingSize} onChange={(event) => updateTheme("headingSize", Number(event.target.value))} /></label>
                    <label>Hjørner <output>{theme.borderRadius}px</output><input type="range" min="0" max="20" value={theme.borderRadius} onChange={(event) => updateTheme("borderRadius", Number(event.target.value))} /></label>
                    <label>Logo i header<select value={theme.logoPosition} onChange={(event) => updateTheme("logoPosition", event.target.value)}><option value="left">Venstre</option><option value="center">Senter</option><option value="right">Høyre</option></select></label>
                    <label>Firmanavn<select value={theme.namePosition} onChange={(event) => updateTheme("namePosition", event.target.value)}><option value="beside">Ved siden av logo</option><option value="center">Midtstilt</option><option value="left">Venstre</option><option value="right">Høyre</option></select></label>
                    <label>Navigasjon<select value={theme.navPosition} onChange={(event) => updateTheme("navPosition", event.target.value)}><option value="center">Sentrert</option><option value="left">Venstre</option><option value="right">Høyre</option></select></label>
                    <label>Headeroppsett<select value={theme.headerLayout} onChange={(event) => updateTheme("headerLayout", event.target.value)}><option value="centered">Samlet / sentrert</option><option value="split">Logo venstre · navn høyre</option></select></label>
                    <label>Navigasjonsstil<select value={theme.navigationStyle} onChange={(event) => updateTheme("navigationStyle", event.target.value)}><option value="underline">Understrek</option><option value="plain">Ren tekst</option><option value="pills">Piller</option></select></label>
                    <label>Footeroppsett<select value={theme.footerLayout} onChange={(event) => updateTheme("footerLayout", event.target.value)}><option value="standard">Standard</option><option value="minimal">Kompakt</option><option value="extended">Utvidet</option></select></label>
                    <label>Hjemmesidens innhold<select value={theme.homepageModule} onChange={(event) => updateTheme("homepageModule", event.target.value)}><option value="news">Nyheter først</option><option value="news-events">Nyheter og arrangement</option><option value="services">Tjenester først</option></select></label>
                  </div>
                  <div className="tc-footer-calculation"><span>Footerhøyde beregnes automatisk</span><strong>{theme.headerHeight}px × 0,6 = {footerHeight}px</strong></div>
                  {renderSeoSettings()}
                </div>
              </div></>}
              <p className="tc-feedback" role="status">{feedback}</p>
            </div>
          )}
        </aside>}
    </main>
  );
}
