import { useEffect, useMemo, useRef, useState, type ChangeEvent, type CSSProperties } from "react";
import "./tenantCopy.css";

type SitePage = "home" | "services" | "about";
type ModuleName = "Medlemskap" | "Utleie" | "Arrangementer" | "Salg" | "Økonomi";
type ServiceId = "membership" | "rental" | "event-dinner" | "event-dance" | "sales";

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
  const [news, setNews] = useState(initialNews);
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
  const activeNews = news.find((item) => item.id === selectedNewsId) ?? news[0];
  const visibleNews = news.filter((item) => item.visible);
  const heroNews = visibleNews[0];
  const activeServices = useMemo(
    () => Object.entries(serviceMeta).filter(([, service]) => enabledModules.includes(service.module)) as [ServiceId, (typeof serviceMeta)[ServiceId]][],
    [enabledModules],
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

  const updateTheme = (key: keyof typeof theme, value: string | number) => {
    setTheme((current) => ({ ...current, [key]: value }));
  };
  const openControlPanel = (editTarget: SitePage = page) => {
    if (interactionMode === "read") {
      setReaderTitle(editTarget === "home" ? (news.find(item => item.id === selectedNewsId)?.title || "Nyhet") : editTarget === "services" ? serviceMeta[selectedService].title : "Om oss");
      return;
    }
    if (editTarget === "home" && interactionMode !== "content") {
      setFeedback("Denne modusen brukes på tjenestekort. Velg Rediger innhold for nyheter.");
      return;
    }
    setEditingPage(editTarget);
    setFieldEditorOpen(interactionMode === "fields");
  };
  const chooseMode = (mode: string) => {
    setInteractionMode(mode);
    setEditingPage(null);
    setReaderTitle("");
  };
  useEffect(() => {
    const revealOnScroll = () => { if (window.scrollY > 80) setControlOpen(true); };
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
    setFeedback("");
  };
  const updateNews = (id: string, key: "title" | "summary", value: string) => {
    setNews((current) => current.map((item) => item.id === id ? { ...item, [key]: value } : item));
  };
  const toggleNews = (id: string) => {
    setNews((current) => current.map((item) => item.id === id ? { ...item, visible: !item.visible } : item));
    setFeedback("Visningen på Hjem er oppdatert i denne skissen.");
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
                  {heroNews.image && <button type="button" className="tc-hero-image-button" onClick={() => { setSelectedNewsId(heroNews.id); openControlPanel("home"); }} aria-label={`Rediger bildet for ${heroNews.title}`}><img src={heroNews.image} alt="" /><span className="tc-image-edit-hint">Rediger bilde</span></button>}
                </section>
              ) : (
                <div className="tc-home-empty"><strong>Ingen saker på Hjem akkurat nå.</strong><span>Legg til eller vis en sak i administrasjonspanelet.</span><button type="button" onClick={() => openControlPanel("home")}>Åpne nyhetsadministrasjon <Arrow /></button></div>
              )}
              {theme.homepageModule !== "news" && <section className="tc-home-module-preview">
                <span className="tc-kicker">{theme.homepageModule === "services" ? "UTVALGTE TJENESTER" : "KOMMENDE ARRANGEMENT"}</span>
                <strong>{theme.homepageModule === "services" ? "Oppdag tilbud fra Bergsøy" : "Neste på planen · Høstfest"}</strong>
                <span>{theme.homepageModule === "services" ? activeServices.map(([, service]) => service.title).slice(0, 3).join(" · ") || "Aktiver moduler for å vise tilbud." : "Fredag 23. oktober · Høgtun Kulturhus"}</span>
              </section>}
              <div className="tc-home-news-list">
                {visibleNews.slice(heroNews && heroEnabled ? 1 : 0).map((item) => (
                  <button type="button" className="tc-home-story" key={item.id} aria-label={`Rediger ${item.title}`} onClick={() => { setSelectedNewsId(item.id); openControlPanel("home"); }}>
                    {item.image ? <img src={item.image} alt="" /> : <div className="tc-story-placeholder">BD</div>}
                    <span>{item.title}</span>
                    <small>{item.date}</small>
                    <em>Rediger</em>
                  </button>
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
                {enabledModules.includes("Arrangementer") && <button type="button" className="tc-latest-event" aria-label="Rediger siste arrangement" onClick={() => editService("event-dance")}>
                  <span className="tc-edit-marker">Rediger arrangement</span>
                  <img src="/__mockup/images/portal-reimagined-dance.jpg" alt="" />
                  <span><small>SISTE ARRANGEMENT</small><strong>ROMJULSDANS</strong><span>man. 28. des. 2026 kl. 19:00</span><b>Se arrangementet <Arrow /></b></span>
                </button>}
              </section>
              <div className="tc-service-groups">
                {enabledModules.includes("Medlemskap") && (
                  <section className="tc-service-group">
                    <header><div><h2>Medlemskap</h2><p>Medlemskap for danseglede.</p></div><span>1 tilbud</span></header>
                    <button type="button" className="tc-public-service tc-membership-card" aria-label="Rediger medlemskapstilbudet Bergsøy Medlem" onClick={() => editService("membership")}>
                      <span className="tc-service-image tc-member-logo"><img src="/__mockup/images/portal-reimagined-logo.png" alt="" /></span>
                      <span className="tc-service-copy"><strong>Bergsøy Medlem</strong><small>Med i Bergsøy Danseklubb!</small><span className="tc-price-line">Pris <b>250,00 kr</b></span><span className="tc-blue-cta">Sjekk medlemskap</span></span>
                      <span className="tc-edit-marker">Rediger tilbud</span>
                    </button>
                  </section>
                )}
                {enabledModules.includes("Utleie") && (
                  <section className="tc-service-group">
                    <header><div><h2>Utleie</h2><p>Lokaler og andre utleieobjekter.</p></div><span>1 tilbud</span></header>
                    <button type="button" className="tc-public-service" aria-label="Rediger utleietilbudet Høgtun Selskapslokale" onClick={() => editService("rental")}>
                      <span className="tc-service-image"><img src="/__mockup/images/portal-reimagined-venue.jpg" alt="" /></span>
                      <span className="tc-service-copy"><strong>Høgtun Selskapslokale</strong><small>Næringslokale i hjertet av Fosnavåg. Enkelt å leie hele døgnet.</small><span className="tc-service-address">Vågsgata 61, 6090 Fosnavåg</span><span className="tc-feature-chips"><small>60 sitteplasser</small><small>168 m²</small></span><span className="tc-blue-cta">Se detaljer og ledige datoer</span></span>
                      <span className="tc-edit-marker">Rediger tilbud</span>
                    </button>
                  </section>
                )}
                {enabledModules.includes("Arrangementer") && (
                  <section className="tc-service-group tc-group-wide">
                    <header><div><h2>Arrangementer</h2><p>Dato, sted og påmelding.</p></div><span>2 tilbud</span></header>
                    <div className="tc-event-service-grid">
                      {(["event-dinner", "event-dance"] as ServiceId[]).map((id) => {
                        const service = serviceMeta[id];
                        return <button type="button" className="tc-public-service tc-event-service" key={id} aria-label={`Rediger arrangementet ${service.title}`} onClick={() => editService(id)}>
                          <span className="tc-service-image"><img src={service.image} alt="" /></span>
                          <span className="tc-service-copy"><strong>{service.title}</strong><small>{service.description}</small><span className="tc-service-meta"><i>DATO</i><b>{id === "event-dinner" ? "lør. 5. des. 2026 kl. 19:00" : "man. 28. des. 2026 kl. 19:00"}</b><i>STED</i><b>Høgtun, Vågsgata 61</b></span><span className="tc-service-bottom"><span><small>Ordinærpris</small><b>{service.price}</b></span><span className="tc-blue-cta">Reserver billett</span></span></span>
                          <span className="tc-edit-marker">Rediger arrangement</span>
                        </button>;
                      })}
                    </div>
                  </section>
                )}
                {enabledModules.includes("Salg") && (
                  <section className="tc-service-group">
                    <header><div><h2>Varer</h2><p>Produkter fra klubben.</p></div><span>1 tilbud</span></header>
                    <button type="button" className="tc-public-service" aria-label="Rediger produktet Dansekalender 2027" onClick={() => editService("sales")}>
                      <span className="tc-service-image"><img src="/__mockup/images/portal-reimagined-dance.jpg" alt="" /></span>
                      <span className="tc-service-copy"><strong>Dansekalender 2027</strong><small>En liten bit av dansegleden med hjem.</small><span className="tc-price-line">Pris <b>149,00 kr</b></span><span className="tc-blue-cta">Se varen</span></span>
                      <span className="tc-edit-marker">Rediger vare</span>
                    </button>
                  </section>
                )}
                {createdOffers.length > 0 && <section className="tc-service-group tc-created-offers">
                  <header><div><h2>Nye tilbud</h2><p>Opprettet lokalt i forhåndsvisningen.</p></div><span>{createdOffers.length} tilbud</span></header>
                  <div className="tc-event-service-grid">
                    {createdOffers.map(offer => <button type="button" className="tc-public-service tc-event-service" key={offer.id} onClick={() => setReaderTitle(offer.title)}>
                      <span className="tc-service-image"><img src={offer.module === "Medlemskap" ? serviceMeta.membership.image : offer.module === "Utleie" ? serviceMeta.rental.image : serviceMeta["event-dance"].image} alt="" /></span>
                      <span className="tc-service-copy"><strong>{offer.title}</strong><small>{offer.module}</small>{offer.price && <span className="tc-price-line"><b>{offer.price}</b></span>}<span className="tc-blue-cta">Se tilbudet</span></span>
                    </button>)}
                  </div>
                </section>}
                {!activeServices.length && <div className="tc-no-services"><strong>Ingen tjenester vises ennå.</strong><span>Aktiver en modul i administrasjonspanelet for å fylle siden.</span><button type="button" onClick={() => openControlPanel("services")}>Administrer moduler <Arrow /></button></div>}
              </div>
            </div>
          )}

          {page === "about" && (
            <div className="tc-about-page">
              <section className="tc-about-intro">
                <span className="tc-kicker">BERGSØY DANSEKLUBB</span>
                <h1>Om oss</h1>
                <p>{aboutText}</p>
                <span className="tc-edit-marker">Rediger Om oss</span>
                <button type="button" className="tc-text-link" onClick={() => openControlPanel("about")}>Rediger sideinnhold <Arrow /></button>
              </section>
              <section className="tc-about-contact">
                <div className="tc-contact-card"><span className="tc-contact-mark">KONTAKT</span><h2>Ta gjerne kontakt</h2><p>Vi svarer så snart vi kan.</p><a href={`tel:${theme.phone.replaceAll(" ", "")}`}>{theme.phone}</a><a href={`mailto:${theme.email}`}>{theme.email}</a></div>
                <div className="tc-contact-card"><span className="tc-contact-mark">FINN OSS</span><h2>Høgtun Kulturhus</h2><p>{theme.address}</p><span className="tc-map-placeholder">62°20′ N · 5°38′ Ø</span></div>
              </section>
            </div>
          )}
        </div>

        <footer className={`tc-site-footer tc-footer-${theme.footerLayout}`}>
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
        </footer>
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
              {page === "home" && <button type="button" className="tc-header-create" onClick={startNewNews}>＋ Opprett ny sak</button>}
              {page === "services" && <button type="button" className="tc-header-create" onClick={startNewOffer}>＋ Opprett ny tjeneste</button>}
              <button type="button" className="tc-control-close" onClick={() => setControlOpen(false)} aria-label="Lukk kontrollrom">×</button>
            </div>
            <div className="tc-greeting-row">
              <div>
                <h2>Hei, Atle.</h2>
                <p>Du er oppført med {enabledModules.join(", ") || "ingen moduler"}.</p>
              </div>
              <div className="tc-staff-badge"><span>AK</span><div><small>INNLOGGET SOM</small><strong>Atle Karl</strong><small>tenant_staff · Bergsøy Danseklubb</small></div></div>
            </div>
            <div className="tc-module-access">
              <span>MODULTILGANG</span>
              <div>{enabledModules.length ? enabledModules.map((module) => <span className="tc-module-chip" key={module}>{module}</span>) : <span className="tc-no-module-chip">Ingen moduler valgt</span>}</div>
            </div>
            <div className="tc-edit-mode-options" role="radiogroup" aria-label="Hva skal klikk på innhold gjøre?">
              <span className="tc-edit-mode-title">VED KLIKK PÅ INNHOLD</span>
              {[["read", "Les", "Som på kundesiden"], ["content", "Rediger innhold", "Nyheter og tjenester"], ...(enabledModules.includes("Medlemskap") ? [["benefits", "Medlemsfordeler", "Velg et tjenestekort"]] : []), ...(enabledModules.some(module => ["Arrangementer", "Utleie", "Salg"].includes(module)) ? [["fields", "Skjemafelt", "Velg en tjeneste"]] : [])].map(([mode, title, hint]) => <label key={mode} className={interactionMode === mode ? "checked" : ""}><input type="radio" name="interaction-mode" checked={interactionMode === mode} onChange={() => chooseMode(mode)} /><span><b>{title}</b><small>{hint}</small></span></label>)}
            </div>
          </div>
        </header>

      </section>
        {editingPage && <aside className={`tc-workspace-wrap tc-floating-inspector tc-mode-${interactionMode}`} aria-label="Rediger valgt innhold">
          <button type="button" className="tc-inspector-dismiss" onClick={() => setEditingPage(null)}>Lukk redigering ×</button>
          <div className="tc-workspace-top">
            <div><span className="tc-kicker">NETTSIDEADMINISTRASJON</span><h2>{page === "home" ? "Hjem" : page === "services" ? "Tjenester" : "Om oss"}</h2></div>
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
                  {selectedService === "membership" && <>
                    <div className="tc-plan-tabs" role="tablist" aria-label="Medlemskapsplaner"><button type="button" className="active">Bergsøy Medlem</button><button type="button" onClick={() => setFeedback("Familieplanen er eksempeldata i denne skissen.")}>Familie</button><button type="button" className="tc-add-plan" onClick={() => setFeedback("Ny plan kan opprettes her.")}>+ Plan</button></div>
                    <label className="tc-check-line"><input type="checkbox" checked={benefitEnabled} onChange={(event) => setBenefitEnabled(event.target.checked)} /><span><strong>Medlemsfordel på tilbud</strong><small>{benefitEnabled ? "250 kr vises som medlemspris" : "Tilbudet bruker ordinær pris"}</small></span></label>
                    {benefitEnabled && <div className="tc-inline-price"><span>Ordinærpris <b>250 kr</b></span><span>Medlemspris <b>250 kr</b></span><button type="button" onClick={() => setFeedback("Medlemsfordelen er endret lokalt.")}>Endre fordel</button></div>}
                  </>}
                  {selectedService !== "membership" && <label className="tc-check-line"><input type="checkbox" checked={memberExclusive} onChange={(event) => setMemberExclusive(event.target.checked)} /><span><strong>Kun for medlemmer</strong><small>{memberExclusive ? "Tilbudet skjules for ikke-medlemmer" : "Tilbudet er synlig for alle"}</small></span></label>}
                  {(selectedService === "rental" || selectedService === "event-dinner" || selectedService === "event-dance") && <>
                    <label className="tc-check-line"><input type="checkbox" checked={memberVerification} onChange={(event) => setMemberVerification(event.target.checked)} /><span><strong>Medlemsverifisering</strong><small>Kontroller medlemsstatus ved innsending</small></span></label>
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
                <div><h3>Om bedriften og nettstedet</h3><p>Innhold, uttrykk og kontaktpunkter samles her – ett sted.</p></div>
                <button type="button" className="tc-primary-button" onClick={() => setFeedback("Innstillingene er oppdatert i den lokale forhåndsvisningen.")}>Lagre visning</button>
              </div>
              <div className="tc-about-editor-grid">
                <div className="tc-about-copy-editor">
                  <label>Om oss-tekst<textarea rows={6} value={aboutText} onChange={(event) => setAboutText(event.target.value)} /></label>
                  <div className="tc-contact-fields">
                    <label>Telefon<input value={theme.phone} onChange={(event) => updateTheme("phone", event.target.value)} /></label>
                    <label>E-post<input value={theme.email} onChange={(event) => updateTheme("email", event.target.value)} /></label>
                    <label>Adresse<input value={theme.address} onChange={(event) => updateTheme("address", event.target.value)} /></label>
                    <label>Facebook-lenke<input value={theme.facebook} onChange={(event) => updateTheme("facebook", event.target.value)} /></label>
                    <label>Instagram-lenke<input value={theme.instagram} onChange={(event) => updateTheme("instagram", event.target.value)} /></label>
                    <label>Footertekst<input value={theme.footerText} onChange={(event) => updateTheme("footerText", event.target.value)} /></label>
                    <label>Footerlenker<input value={theme.footerLinks} onChange={(event) => updateTheme("footerLinks", event.target.value)} /></label>
                  </div>
                </div>
                <div className="tc-theme-editor">
                  <div className="tc-theme-heading"><span><strong>Utseende på tenant_copy</strong><small>Endringer vises direkte i forhåndsvisningen øverst.</small></span><span className="tc-live-preview">Live</span></div>
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
                </div>
              </div>
              <p className="tc-feedback" role="status">{feedback}</p>
            </div>
          )}
        </aside>}
    </main>
  );
}
