import { useMemo, useState } from "react";
import {
  ArrowDown,
  ArrowLeft,
  ArrowUp,
  CalendarDays,
  Check,
  ChevronDown,
  Clock3,
  Image as ImageIcon,
  List,
  Plus,
  Rows3,
  Smartphone,
  Table2,
  Trash2,
  Type,
  Video,
  Monitor,
  Eye,
  GripVertical,
  Link2,
  CircleAlert,
  Save,
  X,
} from "lucide-react";

type ContentType = "text" | "image" | "text_image" | "carousel" | "youtube" | "list" | "table";
type ContentBlock = {
  id: number;
  type: ContentType;
  text?: string;
  url?: string;
  alt?: string;
  caption?: string;
  items?: string[];
  images?: string[];
  headers?: string[];
  rows?: string[][];
};

const typeNames: Record<ContentType, string> = {
  text: "Tekst",
  image: "Bilde",
  text_image: "Tekst og bilde",
  carousel: "Bildekarusell",
  youtube: "YouTube-video",
  list: "Liste",
  table: "Tabell",
};

const initialBlocks: ContentBlock[] = [
  {
    id: 1,
    type: "text",
    text: "Lørdag 14. september åpner vi dørene litt tidligere. Fra kl. 11 kan du møte lokale produsenter, smake på fersk bakst og finne noe godt til helgen.",
  },
  {
    id: 2,
    type: "list",
    items: ["Smaksprøver fra bakeriet kl. 11–13", "Kaffe fra Fjordvik Brenneri", "Gratis inngang for hele familien"],
  },
  {
    id: 3,
    type: "table",
    headers: ["Tidspunkt", "Hva skjer"],
    rows: [["11:00", "Dørene åpner"], ["12:00", "Smaksprøve i kafeen"], ["14:00", "Trekning av gavekurv"]],
  },
];

function CoverArtwork({ className = "" }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 1000 520" role="img" aria-label="Illustrasjon av fjord og lokale fjell">
      <defs>
        <linearGradient id="fjord-sky" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor="#d8e7e2" />
          <stop offset="1" stopColor="#e8d5bd" />
        </linearGradient>
        <linearGradient id="fjord-water" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stopColor="#6a9a98" />
          <stop offset="1" stopColor="#315e66" />
        </linearGradient>
      </defs>
      <rect width="1000" height="520" fill="url(#fjord-sky)" />
      <circle cx="770" cy="120" r="58" fill="#e6a57e" opacity=".86" />
      <path d="M0 330 170 142l122 137L442 78l205 260 143-159 210 204v137H0Z" fill="#718b83" />
      <path d="m340 198 102-120 101 130-61-32-39 19-42-33Z" fill="#f2eee1" opacity=".94" />
      <path d="m687 295 103-116 111 131-65-31-43 21-37-31Z" fill="#f2eee1" opacity=".78" />
      <path d="M0 328q156-38 325 2t340-2q169-34 335 4v188H0Z" fill="url(#fjord-water)" />
      <path d="M0 378q162-27 327 0t334-1q172-27 339 5" fill="none" stroke="#d5d9ca" strokeWidth="7" opacity=".42" />
      <path d="M0 446q166-22 325 0t336 0q171-21 339 3" fill="none" stroke="#d5d9ca" strokeWidth="4" opacity=".34" />
      <path d="M150 330v-78h46v78m-37-57h29m-29 19h29M228 332v-52h40v52" fill="none" stroke="#f2e7d5" strokeWidth="8" opacity=".9" />
      <path d="M213 280 248 250l35 30" fill="none" stroke="#f2e7d5" strokeWidth="8" opacity=".9" />
      <path d="M540 333v-66h54v66m-39-50h24m-24 17h24" fill="none" stroke="#d9c9aa" strokeWidth="7" opacity=".9" />
      <rect width="1000" height="520" fill="#213946" opacity=".08" />
    </svg>
  );
}

function createBlock(type: ContentType, id: number): ContentBlock {
  if (type === "list") return { id, type, items: ["Nytt punkt"] };
  if (type === "table") return { id, type, headers: ["Kolonne 1", "Kolonne 2"], rows: [["", ""]] };
  if (type === "carousel") return { id, type, images: [""] };
  if (type === "image") return { id, type, url: "", alt: "", caption: "" };
  if (type === "youtube") return { id, type, url: "", caption: "" };
  return { id, type, text: "", ...(type === "text_image" ? { url: "", alt: "", caption: "" } : {}) };
}

function TypeIcon({ type, size = 16 }: { type: ContentType; size?: number }) {
  if (type === "image" || type === "text_image" || type === "carousel") return <ImageIcon size={size} />;
  if (type === "youtube") return <Video size={size} />;
  if (type === "list") return <List size={size} />;
  if (type === "table") return <Table2 size={size} />;
  return <Type size={size} />;
}

export function Composer() {
  const [title, setTitle] = useState("Smakshelg i Fjordvik – bli med på åpningen");
  const [coverUrl, setCoverUrl] = useState("");
  const [category, setCategory] = useState("Nyhet");
  const [priority, setPriority] = useState("2");
  const [publishMode, setPublishMode] = useState("now");
  const [publishAt, setPublishAt] = useState("2025-09-14T10:00");
  const [blocks, setBlocks] = useState<ContentBlock[]>(initialBlocks);
  const [selectedId, setSelectedId] = useState(1);
  const [newType, setNewType] = useState<ContentType>("text");
  const [previewMode, setPreviewMode] = useState<"desktop" | "mobile">("desktop");
  const [notice, setNotice] = useState("");

  const selected = blocks.find((block) => block.id === selectedId) ?? blocks[0];
  const blockIndex = blocks.findIndex((block) => block.id === selected?.id);
  const previewDate = useMemo(() => {
    if (publishMode === "later" && publishAt) {
      return new Intl.DateTimeFormat("nb-NO", { day: "numeric", month: "long", year: "numeric" }).format(new Date(publishAt));
    }
    return "14. september 2025";
  }, [publishAt, publishMode]);

  const updateBlock = (id: number, patch: Partial<ContentBlock>) => {
    setBlocks((current) => current.map((block) => block.id === id ? { ...block, ...patch } : block));
  };
  const moveBlock = (id: number, offset: number) => {
    setBlocks((current) => {
      const index = current.findIndex((block) => block.id === id);
      const next = index + offset;
      if (index < 0 || next < 0 || next >= current.length) return current;
      const copy = [...current];
      [copy[index], copy[next]] = [copy[next], copy[index]];
      return copy;
    });
  };
  const addBlock = () => {
    const next = createBlock(newType, Date.now());
    setBlocks((current) => [...current, next]);
    setSelectedId(next.id);
    setNotice(`${typeNames[newType]} lagt til i nyheten.`);
  };
  const removeBlock = (id: number) => {
    const remaining = blocks.filter((block) => block.id !== id);
    setBlocks(remaining);
    if (selectedId === id) setSelectedId(remaining[0]?.id ?? 0);
    setNotice("Elementet er fjernet fra forhåndsvisningen.");
  };

  const renderBlockEditor = () => {
    if (!selected) return <div className="nc-empty-editor">Legg til et element for å begynne å skrive.</div>;
    if (selected.type === "list") return (
      <div className="nc-list-editor">
        <p className="nc-field-hint">Hvert punkt vises som en egen linje i artikkelen.</p>
        {(selected.items ?? []).map((item, index) => (
          <div className="nc-list-row" key={`${selected.id}-${index}`}>
            <span className="nc-list-bullet">{String(index + 1).padStart(2, "0")}</span>
            <input aria-label={`Listepunkt ${index + 1}`} value={item} onChange={(event) => {
              const items = [...(selected.items ?? [])]; items[index] = event.target.value; updateBlock(selected.id, { items });
            }} />
            <button type="button" className="nc-icon-button nc-danger" aria-label={`Fjern punkt ${index + 1}`} onClick={() => updateBlock(selected.id, { items: (selected.items ?? []).filter((_, i) => i !== index) })}><X size={15} /></button>
          </div>
        ))}
        <button type="button" className="nc-subtle-button" onClick={() => updateBlock(selected.id, { items: [...(selected.items ?? []), ""] })}><Plus size={15} /> Legg til punkt</button>
      </div>
    );
    if (selected.type === "table") return (
      <div className="nc-table-editor">
        <p className="nc-field-hint">Rediger kolonneoverskrifter og celler direkte. Tabellen vises også i leserforhåndsvisningen.</p>
        <div className="nc-table-scroll"><table className="nc-edit-table"><thead><tr>{(selected.headers ?? []).map((header, col) => <th key={col}><input aria-label={`Kolonneoverskrift ${col + 1}`} value={header} onChange={(event) => {
          const headers = [...(selected.headers ?? [])]; headers[col] = event.target.value; updateBlock(selected.id, { headers });
        }} /></th>)}<th aria-label="Radhandlinger" /></tr></thead><tbody>
          {(selected.rows ?? []).map((row, rowIndex) => <tr key={rowIndex}>{(selected.headers ?? []).map((_, col) => <td key={col}><input aria-label={`Rad ${rowIndex + 1}, kolonne ${col + 1}`} value={row[col] ?? ""} onChange={(event) => {
            const rows = (selected.rows ?? []).map((current) => [...current]); rows[rowIndex][col] = event.target.value; updateBlock(selected.id, { rows });
          }} /></td>)}<td><button type="button" className="nc-icon-button nc-danger" aria-label={`Fjern rad ${rowIndex + 1}`} onClick={() => updateBlock(selected.id, { rows: (selected.rows ?? []).filter((_, i) => i !== rowIndex) })}><X size={14} /></button></td></tr>)}
        </tbody></table></div>
        <button type="button" className="nc-subtle-button" onClick={() => updateBlock(selected.id, { rows: [...(selected.rows ?? []), (selected.headers ?? []).map(() => "")] })}><Plus size={15} /> Ny rad</button>
      </div>
    );
    if (selected.type === "carousel") return (
      <div className="nc-editor-fields">
        <p className="nc-field-hint">Legg inn én bildeadresse per felt. Bildene kan sorteres ved å endre rekkefølgen her.</p>
        {(selected.images ?? []).map((url, index) => <div className="nc-input-with-action" key={index}><label className="nc-field"><span>Bilde {index + 1} · URL</span><input type="url" value={url} placeholder="https://…" onChange={(event) => { const images = [...(selected.images ?? [])]; images[index] = event.target.value; updateBlock(selected.id, { images }); }} /></label><button type="button" className="nc-icon-button nc-danger" aria-label={`Fjern bilde ${index + 1}`} onClick={() => updateBlock(selected.id, { images: (selected.images ?? []).filter((_, i) => i !== index) })}><X size={15} /></button></div>)}
        <button type="button" className="nc-subtle-button" onClick={() => updateBlock(selected.id, { images: [...(selected.images ?? []), ""] })}><Plus size={15} /> Legg til bilde</button>
      </div>
    );
    return (
      <div className="nc-editor-fields">
        {(selected.type === "text" || selected.type === "text_image") && <label className="nc-field"><span>Brødtekst</span><textarea rows={7} value={selected.text ?? ""} onChange={(event) => updateBlock(selected.id, { text: event.target.value })} placeholder="Skriv innholdet her…" /></label>}
        {(selected.type === "image" || selected.type === "text_image" || selected.type === "youtube") && <>
          <label className="nc-field"><span>{selected.type === "youtube" ? "YouTube-lenke" : "Bildefilens lenke"}</span><div className="nc-field-icon"><Link2 size={15} /><input type="url" value={selected.url ?? ""} placeholder="https://…" onChange={(event) => updateBlock(selected.id, { url: event.target.value })} /></div></label>
          {selected.type !== "youtube" && <label className="nc-field"><span>Alternativ tekst</span><input value={selected.alt ?? ""} placeholder="Beskriv bildet for lesere" onChange={(event) => updateBlock(selected.id, { alt: event.target.value })} /></label>}
          <label className="nc-field"><span>Bildetekst</span><input value={selected.caption ?? ""} placeholder="Valgfri bildetekst" onChange={(event) => updateBlock(selected.id, { caption: event.target.value })} /></label>
        </>}
        {selected.type === "text" && <div className="nc-writing-note"><Type size={15} /> Godt innhold er konkret: fortell leseren hva som skjer, når det skjer og hvem det er for.</div>}
      </div>
    );
  };

  const renderReaderBlock = (block: ContentBlock, index: number) => {
    if (block.type === "text") return <p key={block.id} className="nc-reader-paragraph">{block.text || "Skriv inn tekst for å vise innholdet her."}</p>;
    if (block.type === "list") return <ul key={block.id} className="nc-reader-list">{(block.items ?? []).filter(Boolean).map((item, i) => <li key={i}>{item}</li>)}</ul>;
    if (block.type === "table") return <div className="nc-reader-table-wrap" key={block.id}><table className="nc-reader-table"><thead><tr>{(block.headers ?? []).map((header, i) => <th key={i}>{header || `Kolonne ${i + 1}`}</th>)}</tr></thead><tbody>{(block.rows ?? []).map((row, i) => <tr key={i}>{(block.headers ?? []).map((_, col) => <td key={col}>{row[col] || "—"}</td>)}</tr>)}</tbody></table></div>;
    if (block.type === "youtube") return <div className="nc-reader-video" key={block.id}><span className="nc-play">▶</span><span>{block.caption || "Videoinnhold"}</span><small>{block.url ? "YouTube · forhåndsvisning" : "YouTube-lenke legges til"}</small></div>;
    if (block.type === "image" || block.type === "text_image" || block.type === "carousel") return (
      <figure className={`nc-reader-figure ${block.type === "text_image" ? "nc-reader-inline-image" : ""}`} key={block.id}>
        {block.type === "carousel" ? <div className="nc-carousel-preview">{(block.images ?? []).slice(0, 3).map((url, i) => <div className="nc-carousel-tile" key={i}>{url ? <img src={url} alt="" /> : <ImageIcon size={20} />}</div>)}</div> : block.url ? <img src={block.url} alt={block.alt ?? ""} /> : <div className="nc-inline-placeholder"><ImageIcon size={22} /><span>Bilde legges til</span></div>}
        {block.caption && <figcaption>{block.caption}</figcaption>}
        {block.type === "text_image" && block.text && <p>{block.text}</p>}
      </figure>
    );
    return <p key={`${block.id}-${index}`} />;
  };

  return (
    <div className="nc-app">
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap');
        .nc-app{--ink:#17324d;--muted:#63798b;--paper:#f4f7f7;--surface:#fffefa;--line:#d8e3e5;--teal:#087f83;--teal-dark:#07686c;--coral:#d9785f;--sand:#ece8df;min-height:100vh;background:#edf2f1;color:var(--ink);font-family:'DM Sans',sans-serif;font-size:14px;padding:0 0 42px}
        .nc-app *{box-sizing:border-box}.nc-app button,.nc-app input,.nc-app select,.nc-app textarea{font:inherit}.nc-app button{cursor:pointer}
        .nc-topbar{height:58px;background:#f7f8f5;border-bottom:1px solid #dce4e2;display:flex;align-items:center;justify-content:space-between;padding:0 clamp(16px,4vw,60px);color:#4d6673}
        .nc-brand{display:flex;align-items:center;gap:10px;font-weight:700;color:var(--ink);letter-spacing:-.02em}.nc-brand-mark{height:29px;width:29px;border-radius:8px;background:#e0ece8;display:grid;place-items:center;color:var(--teal)}.nc-top-right{display:flex;gap:18px;align-items:center;font-size:12px}.nc-user{border-left:1px solid var(--line);padding-left:16px;display:flex;align-items:center;gap:8px}.nc-avatar{width:28px;height:28px;border-radius:50%;background:#eedbd0;color:#8e5748;font-size:10px;font-weight:700;display:grid;place-items:center}
        .nc-page{width:min(1480px,100%);margin:auto;padding:25px clamp(16px,4vw,58px) 0}.nc-breadcrumb{display:flex;gap:8px;align-items:center;color:#768995;font-size:12px;margin-bottom:18px}.nc-breadcrumb b{color:var(--ink);font-weight:600}
        .nc-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:20px}.nc-eyebrow{display:flex;gap:8px;align-items:center;color:var(--teal);font-size:10px;letter-spacing:.13em;font-weight:700;text-transform:uppercase}.nc-head h1{font:400 clamp(30px,4vw,43px)/1.05 'Instrument Serif',Georgia,serif;letter-spacing:-.025em;margin:7px 0 6px}.nc-head p{margin:0;color:var(--muted);font-size:13px}.nc-head-actions{display:flex;gap:9px;align-items:center}
        .nc-preview-pill{display:inline-flex;align-items:center;gap:7px;border:1px solid #c8dfd8;color:#376861;background:#eaf4ef;border-radius:99px;padding:7px 11px;font-size:11px;font-weight:700;white-space:nowrap}.nc-preview-pill span{width:6px;height:6px;border-radius:50%;background:#4f9684}
        .nc-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #cbd8d9;background:#fffefa;color:#42606e;border-radius:7px;padding:9px 12px;font-weight:600;font-size:12px;transition:transform .16s,background .16s,color .16s}.nc-btn:hover{transform:translateY(-1px);background:#f4f8f7}.nc-btn-primary{background:var(--teal);border-color:var(--teal);color:white}.nc-btn-primary:hover{background:var(--teal-dark);color:white}.nc-btn:focus-visible,.nc-icon-button:focus-visible,.nc-block-item:focus-visible{outline:3px solid #e6a28f;outline-offset:2px}
        .nc-demo-banner{display:flex;gap:10px;align-items:center;background:#fff8eb;border:1px solid #f0dec0;color:#765a35;border-radius:8px;padding:10px 13px;margin:0 0 17px;font-size:11px}.nc-demo-banner strong{color:#694b25}.nc-demo-banner svg{flex:none;color:#bb8444}
        .nc-workspace{display:grid;grid-template-columns:minmax(360px,1.12fr) minmax(350px,.88fr);gap:16px;align-items:start}.nc-card{background:var(--surface);border:1px solid var(--line);border-radius:11px;box-shadow:0 4px 18px rgba(23,50,77,.035);overflow:hidden}.nc-card-head{display:flex;align-items:center;justify-content:space-between;padding:15px 17px;border-bottom:1px solid #e8edeb}.nc-card-title{display:flex;align-items:center;gap:9px;font-weight:700;font-size:13px}.nc-step{display:grid;place-items:center;width:23px;height:23px;border-radius:7px;background:#eaf3f0;color:var(--teal);font-size:10px}.nc-card-head small{display:block;color:#83939b;font-size:10px;margin-top:3px;font-weight:400}
        .nc-editor-body{padding:18px}.nc-editor-label{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#718590;font-weight:700;display:block;margin-bottom:8px}.nc-active-block{border:1px solid #cfdcdd;border-radius:9px;background:#fcfdfb;overflow:hidden}.nc-active-head{padding:11px 13px;background:#f1f5f3;border-bottom:1px solid #dce6e4;display:flex;justify-content:space-between;align-items:center}.nc-active-kind{display:flex;align-items:center;gap:8px;color:#315e61;font-size:12px;font-weight:700}.nc-active-controls{display:flex;gap:4px}.nc-icon-button{border:0;background:transparent;color:#70838d;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px}.nc-icon-button:hover{background:#e7efed;color:var(--ink)}.nc-icon-button:disabled{opacity:.35;cursor:not-allowed}.nc-icon-button.nc-danger:hover{background:#fbebe6;color:#b34e3e}.nc-active-content{padding:14px}
        .nc-field{display:block;margin:0 0 13px}.nc-field>span{display:block;color:#506978;font-size:11px;font-weight:700;margin-bottom:6px}.nc-field input,.nc-field textarea,.nc-list-row input,.nc-edit-table input{width:100%;border:1px solid #d2dfe0;background:#fff;border-radius:6px;padding:9px 10px;color:#294454;font-size:12px;outline:0;transition:border .15s,box-shadow .15s}.nc-field input:focus,.nc-field textarea:focus,.nc-list-row input:focus,.nc-edit-table input:focus{border-color:#60a3a0;box-shadow:0 0 0 3px rgba(8,127,131,.11)}.nc-field textarea{resize:vertical;line-height:1.65;min-height:145px}.nc-field input::placeholder,.nc-field textarea::placeholder{color:#a1afb1}.nc-field-icon{display:flex;align-items:center;gap:8px;border:1px solid #d2dfe0;background:#fff;border-radius:6px;padding-left:10px;color:#80939b}.nc-field-icon input{border:0;box-shadow:none!important;padding-left:0}.nc-field-icon:focus-within{border-color:#60a3a0;box-shadow:0 0 0 3px rgba(8,127,131,.11)}.nc-writing-note{display:flex;align-items:flex-start;gap:8px;border-radius:7px;background:#f1f6f3;color:#617a77;padding:10px;font-size:10px;line-height:1.5}.nc-writing-note svg{flex:none;color:var(--teal)}.nc-field-hint{font-size:10px;color:#718590;line-height:1.5;margin:0 0 12px}
        .nc-list-editor{display:flex;flex-direction:column;gap:7px}.nc-list-row{display:flex;align-items:center;gap:8px}.nc-list-row input{flex:1}.nc-list-bullet{width:24px;color:#91a4a5;font:11px 'DM Sans',sans-serif}.nc-subtle-button{display:inline-flex;align-items:center;gap:6px;align-self:flex-start;border:0;background:transparent;color:var(--teal);font-size:11px;font-weight:700;padding:7px 2px}.nc-subtle-button:hover{color:var(--teal-dark)}
        .nc-table-scroll{overflow:auto;border:1px solid #dce5e3;border-radius:7px;margin-bottom:8px}.nc-edit-table{border-collapse:collapse;width:100%;min-width:360px}.nc-edit-table th,.nc-edit-table td{padding:5px;border-bottom:1px solid #e8edeb;text-align:left}.nc-edit-table th{background:#f1f5f3}.nc-edit-table th input{background:transparent;border-color:transparent;font-weight:700;color:#42606b}.nc-edit-table td input{border-color:transparent;padding:7px}.nc-edit-table th input:focus,.nc-edit-table td input:focus{background:#fff;border-color:#60a3a0}.nc-edit-table th:last-child,.nc-edit-table td:last-child{width:34px;padding:3px}
        .nc-empty-editor{padding:24px;text-align:center;color:#83939b;background:#f5f8f6;border-radius:8px;font-size:12px}
        .nc-right-stack{display:grid;gap:14px}.nc-meta-body{padding:15px 16px}.nc-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}.nc-meta-grid .nc-field{margin-bottom:12px}.nc-meta-grid .nc-field input,.nc-meta-grid .nc-field select{width:100%;border:1px solid #d2dfe0;border-radius:6px;padding:9px;background:#fff;color:#294454;font-size:12px;outline:0}.nc-meta-grid .nc-field input:focus,.nc-meta-grid .nc-field select:focus{border-color:#60a3a0;box-shadow:0 0 0 3px rgba(8,127,131,.11)}.nc-full{grid-column:1/-1}.nc-cover-box{border:1px solid #dce5e3;border-radius:8px;overflow:hidden;background:#f3f5f0;margin-top:2px}.nc-cover-image{width:100%;height:130px;display:block;object-fit:cover}.nc-cover-empty{height:130px;position:relative;overflow:hidden;background:linear-gradient(133deg,#c8ddd6 0%,#e4d3ba 55%,#bbd1c9 100%)}.nc-cover-empty:before{content:"";position:absolute;inset:0;background:linear-gradient(140deg,transparent 40%,rgba(255,255,255,.34) 40% 43%,transparent 43%),linear-gradient(35deg,#537b78 0 28%,transparent 28%),linear-gradient(145deg,transparent 0 39%,#829a8d 39% 59%,transparent 59%),linear-gradient(34deg,transparent 0 55%,#6a8d85 55% 70%,transparent 70%)}.nc-cover-empty:after{content:"";position:absolute;width:45px;height:45px;border-radius:50%;background:#e5a47e;right:15%;top:15%;opacity:.92}.nc-cover-control{padding:10px;border-top:1px solid #dce5e3;background:#fff}.nc-cover-control label{display:block;font-size:10px;color:#506978;font-weight:700;margin-bottom:6px}.nc-cover-control input{width:100%;padding:8px 9px;border:1px solid #d2dfe0;border-radius:6px;font-size:11px;color:#294454}.nc-cover-hint{font-size:9px;color:#89999e;margin-top:6px}
        .nc-list-head-right{font-size:10px;color:#89999e}.nc-block-list{padding:7px 9px 10px}.nc-block-item{display:flex;align-items:center;gap:8px;padding:9px 8px;margin:2px 0;border:1px solid transparent;border-radius:7px;cursor:pointer;background:transparent;color:#526b78;text-align:left;width:100%;transition:background .15s,border .15s}.nc-block-item:hover{background:#f5f8f6}.nc-block-item.selected{background:#eef5f2;border-color:#c8ded7;color:#244f55}.nc-block-grip{color:#b0bdbe;display:flex}.nc-block-item .nc-block-icon{color:#658c8c;display:flex}.nc-block-name{flex:1;font-weight:600;font-size:11px}.nc-block-order{font-size:10px;color:#98a6a8;font-variant-numeric:tabular-nums}.nc-block-actions{display:flex;gap:0;opacity:0;transition:opacity .15s}.nc-block-item:hover .nc-block-actions,.nc-block-item.selected .nc-block-actions{opacity:1}.nc-block-actions .nc-icon-button{width:23px;height:23px}.nc-add-row{display:flex;gap:7px;padding:9px 10px 2px;border-top:1px solid #ebefed}.nc-add-row select{min-width:0;flex:1;border:1px solid #d4dfe0;background:#fff;border-radius:6px;padding:8px;color:#46616e;font-size:11px}.nc-add-row .nc-btn{padding:8px 10px}
        .nc-preview-section{margin-top:17px}.nc-preview-toolbar{display:flex;align-items:center;justify-content:space-between;padding:12px 15px;border-bottom:1px solid #e8edeb}.nc-preview-heading{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700}.nc-preview-heading svg{color:var(--teal)}.nc-preview-controls{display:flex;align-items:center;gap:10px}.nc-preview-caption{font-size:10px;color:#87979a}.nc-toggle{display:flex;padding:3px;background:#edf2f0;border-radius:7px;gap:2px}.nc-toggle button{border:0;background:transparent;border-radius:5px;padding:6px 9px;display:flex;gap:6px;align-items:center;color:#71858a;font-size:10px;font-weight:700}.nc-toggle button.active{background:#fffefa;color:var(--teal);box-shadow:0 1px 4px rgba(23,50,77,.1)}
        .nc-preview-mat{background:#e9e8e0;padding:18px;min-height:450px;display:flex;justify-content:center;overflow:auto}.nc-reader{background:#fffdf8;width:100%;max-width:980px;border-radius:5px;box-shadow:0 4px 18px rgba(42,54,50,.09);padding:24px 30px 32px;transition:width .2s}.nc-reader.mobile{max-width:375px;padding:17px 16px 24px}.nc-reader-mast{display:flex;align-items:flex-end;justify-content:space-between;border-bottom:2px solid #17324d;padding-bottom:9px;margin-bottom:20px}.nc-reader-brand{font:400 21px 'Instrument Serif',Georgia,serif;color:#17324d}.nc-reader.mobile .nc-reader-brand{font-size:19px}.nc-reader-nav{display:flex;gap:13px;font-size:9px;color:#617782;text-transform:uppercase;letter-spacing:.08em}.nc-reader.mobile .nc-reader-nav{display:none}.nc-reader-topline{font-size:9px;letter-spacing:.13em;text-transform:uppercase;color:var(--coral);font-weight:700;margin:0 0 7px}.nc-reader h2{font:400 clamp(26px,3.7vw,43px)/1.02 'Instrument Serif',Georgia,serif;letter-spacing:-.025em;max-width:680px;margin:0 0 9px;color:#17324d}.nc-reader.mobile h2{font-size:32px}.nc-reader-byline{display:flex;align-items:center;gap:8px;color:#809095;font-size:9px;margin-bottom:14px}.nc-reader-byline b{color:#56727b;font-weight:600}.nc-reader-hero{height:190px;border-radius:4px;overflow:hidden;margin:0 0 17px;position:relative;background:#c9d7cd}.nc-reader.mobile .nc-reader-hero{height:155px}.nc-reader-hero img{width:100%;height:100%;object-fit:cover}.nc-reader-hero svg{width:100%;height:100%;object-fit:cover}.nc-reader-content{max-width:710px}.nc-reader.mobile .nc-reader-content{max-width:none}.nc-reader-paragraph{font:14px/1.68 'DM Sans',sans-serif;color:#435b63;margin:0 0 14px;white-space:pre-line}.nc-reader.mobile .nc-reader-paragraph{font-size:13px}.nc-reader-list{margin:0 0 17px;padding-left:21px;color:#435b63;font-size:13px;line-height:1.8}.nc-reader-list li::marker{color:var(--teal)}.nc-reader-table-wrap{overflow:auto;margin:14px 0 18px}.nc-reader-table{width:100%;border-collapse:collapse;font-size:11px}.nc-reader-table th{text-align:left;background:#edf3f0;color:#315f61;font-weight:700}.nc-reader-table th,.nc-reader-table td{padding:9px 11px;border-bottom:1px solid #dfe7e2}.nc-reader-table td{color:#54696f}.nc-reader-figure{margin:14px 0 17px}.nc-reader-figure>img{display:block;width:100%;max-height:280px;object-fit:cover;border-radius:4px}.nc-reader-figure figcaption{font-size:9px;color:#7c8d8e;margin-top:6px}.nc-reader-inline-image{display:grid;grid-template-columns:.9fr 1.1fr;gap:14px;align-items:center}.nc-reader-inline-image>img{height:150px}.nc-reader-inline-image p{font:13px/1.65 'DM Sans',sans-serif;color:#435b63}.nc-inline-placeholder{height:130px;background:#edf2ed;color:#7c9292;display:flex;align-items:center;justify-content:center;gap:8px;border-radius:4px;font-size:10px}.nc-carousel-preview{display:flex;gap:7px}.nc-carousel-tile{height:120px;flex:1;background:#e8eee8;border-radius:4px;display:grid;place-items:center;color:#8aa09a;overflow:hidden}.nc-carousel-tile img{width:100%;height:100%;object-fit:cover}.nc-reader-video{height:150px;background:#29434b;color:#fff;border-radius:5px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;font-size:12px;margin:12px 0}.nc-reader-video small{font-size:9px;color:#c5d3ce}.nc-play{width:32px;height:32px;border:1px solid #fff;border-radius:50%;display:grid;place-items:center;font-size:11px;padding-left:2px}.nc-reader-rule{border:0;border-top:1px solid #e5e2d8;margin:15px 0}.nc-reader-footer{color:#899797;font-size:9px;display:flex;justify-content:space-between;border-top:1px solid #e5e2d8;padding-top:10px;margin-top:18px}
        .nc-footer-note{display:flex;justify-content:space-between;align-items:center;margin-top:13px;color:#89989a;font-size:10px}.nc-footer-note strong{color:#59766f;font-weight:600}.nc-toast{position:fixed;right:20px;bottom:18px;padding:10px 14px;border-radius:8px;background:#244f50;color:#f8fbf6;box-shadow:0 8px 24px rgba(23,50,77,.16);display:flex;gap:8px;align-items:center;font-size:11px;z-index:4}
        @media(max-width:900px){.nc-workspace{grid-template-columns:1fr}.nc-right-stack{grid-template-columns:1fr 1fr}.nc-block-card{grid-column:2;grid-row:1}.nc-meta-card{grid-column:1;grid-row:1}.nc-reader{padding:20px}.nc-preview-mat{min-height:390px}}
        @media(max-width:650px){.nc-topbar{height:52px;padding:0 15px}.nc-top-right>span:first-child{display:none}.nc-page{padding:18px 12px 0}.nc-head{align-items:flex-start;flex-direction:column;margin-bottom:15px}.nc-head-actions{width:100%;justify-content:space-between}.nc-workspace{display:flex;flex-direction:column}.nc-card,.nc-right-stack{width:100%}.nc-right-stack{display:flex;flex-direction:column}.nc-meta-card,.nc-block-card{width:100%}.nc-editor-body{padding:13px}.nc-preview-toolbar{align-items:flex-start;gap:8px;flex-direction:column}.nc-preview-caption{display:none}.nc-preview-mat{padding:10px;min-height:380px}.nc-reader{padding:16px 13px 22px}.nc-reader-mast{margin-bottom:15px}.nc-reader-hero{height:135px}.nc-reader.mobile{max-width:100%}.nc-reader-inline-image{grid-template-columns:1fr}.nc-reader-inline-image>img{height:140px}.nc-reader-inline-image p{margin:0}.nc-meta-grid{grid-template-columns:1fr 1fr}.nc-demo-banner{align-items:flex-start}.nc-footer-note{gap:8px;align-items:flex-start}.nc-block-actions{opacity:1}.nc-block-item{padding:9px 6px}.nc-preview-controls{width:100%;justify-content:space-between}}
        @media(prefers-reduced-motion:reduce){.nc-app *{transition:none!important}}
      `}</style>
      <header className="nc-topbar">
        <div className="nc-brand"><span className="nc-brand-mark"><Rows3 size={16} /></span><span>Salesman <span style={{ color: "#7c9094", fontWeight: 500 }}>· Fjordvik Kulturhus</span></span></div>
        <div className="nc-top-right"><span>Tenant-administrasjon</span><div className="nc-user"><span className="nc-avatar">AN</span><span>Alex Nilsen</span><ChevronDown size={13} /></div></div>
      </header>
      <main className="nc-page">
        <div className="nc-breadcrumb"><ArrowLeft size={14} /><span>Nyhetsrom</span><span>/</span><b>Ny sak</b></div>
        <div className="nc-head">
          <div><div className="nc-eyebrow"><span style={{ width: 17, height: 1, background: "currentColor" }} /> REDAKTØR · NYHET</div><h1>Sett historien i arbeid.</h1><p>Bygg en nyttig oppdatering for folk i Fjordvik.</p></div>
          <div className="nc-head-actions"><span className="nc-preview-pill"><span /> Kun forhåndsvisning</span><button type="button" className="nc-btn" onClick={() => setNotice("Kladden er kun simulert – ingenting er lagret.")}><Save size={14} /> Lagre kladd</button><button type="button" className="nc-btn nc-btn-primary" onClick={() => setNotice("Publisering er deaktivert i denne lokale forhåndsvisningen.")}><Eye size={14} /> Se klarhet</button></div>
        </div>
        <div className="nc-demo-banner"><CircleAlert size={16} /><span><strong>Trygg skisse.</strong> Alt du endrer her er lokale eksempeldata. Ingen endringer sendes til portalen, og ingenting blir publisert eller lagret.</span></div>

        <div className="nc-workspace">
          <section className="nc-card" aria-label="Rediger valgt innhold">
            <div className="nc-card-head"><div className="nc-card-title"><span className="nc-step">01</span><div>Innholdselement<small>Velg en blokk til høyre og rediger den her.</small></div></div><span className="nc-preview-pill" style={{ padding: "5px 9px", fontSize: 9 }}>LOKAL REDIGERING</span></div>
            <div className="nc-editor-body">
              <span className="nc-editor-label">Valgt element {selected ? `· ${Math.max(blockIndex + 1, 1)} av ${blocks.length}` : ""}</span>
              {selected ? <div className="nc-active-block">
                <div className="nc-active-head"><span className="nc-active-kind"><TypeIcon type={selected.type} />{typeNames[selected.type]}</span><div className="nc-active-controls">
                  <button className="nc-icon-button" type="button" aria-label="Flytt element opp" disabled={blockIndex <= 0} onClick={() => moveBlock(selected.id, -1)}><ArrowUp size={15} /></button>
                  <button className="nc-icon-button" type="button" aria-label="Flytt element ned" disabled={blockIndex >= blocks.length - 1} onClick={() => moveBlock(selected.id, 1)}><ArrowDown size={15} /></button>
                  <button className="nc-icon-button nc-danger" type="button" aria-label="Fjern valgt element" onClick={() => removeBlock(selected.id)}><Trash2 size={15} /></button>
                </div></div>
                <div className="nc-active-content">{renderBlockEditor()}</div>
              </div> : <div className="nc-empty-editor">Legg til et element for å begynne å skrive.</div>}
              <div className="nc-writing-note" style={{ marginTop: 13 }}><Check size={15} /> Endringene vises umiddelbart i artikkelforhåndsvisningen under.</div>
            </div>
          </section>

          <aside className="nc-right-stack">
            <section className="nc-card nc-meta-card" aria-label="Nyhetsdetaljer">
              <div className="nc-card-head"><div className="nc-card-title"><span className="nc-step">02</span><div>Storydetaljer<small>Opplysninger som følger saken ut.</small></div></div><CalendarDays size={17} color="#71908c" /></div>
              <div className="nc-meta-body">
                <div className="nc-meta-grid">
                  <label className="nc-field nc-full"><span>Tittel</span><input value={title} onChange={(event) => setTitle(event.target.value)} /></label>
                  <label className="nc-field"><span>Type</span><select value={category} onChange={(event) => setCategory(event.target.value)}><option>Nyhet</option><option>Arrangement</option><option>Informasjon</option><option>Aktuelt</option><option>Viktig melding</option></select></label>
                  <label className="nc-field"><span>Prioritet <small style={{ color: "#93a0a1", fontWeight: 400 }}>· 1 er høyest</small></span><select value={priority} onChange={(event) => setPriority(event.target.value)}>{[1,2,3,4,5,6,7,8,9].map((value) => <option key={value}>{value}</option>)}</select></label>
                  <div className="nc-field nc-full">
                    <span>Forsidebilde</span>
                    <div className="nc-cover-box">{coverUrl ? <img className="nc-cover-image" src={coverUrl} alt="Forhåndsvisning av forsidebilde" onError={(event) => { event.currentTarget.style.display = "none"; }} /> : <div className="nc-cover-empty" />}
                      <div className="nc-cover-control"><label htmlFor="nc-cover-url">Bilde-URL</label><input id="nc-cover-url" type="url" value={coverUrl} onChange={(event) => setCoverUrl(event.target.value)} placeholder="La stå tom for illustrasjon" /><div className="nc-cover-hint">Standardillustrasjon vises til en bildeadresse er lagt inn.</div></div>
                    </div>
                  </div>
                  <label className="nc-field nc-full"><span>Publisering</span><select value={publishMode} onChange={(event) => setPublishMode(event.target.value)}><option value="now">Publiser med en gang</option><option value="later">Planlegg publisering</option><option value="draft">Kun kladd</option></select></label>
                  {publishMode === "later" && <label className="nc-field nc-full"><span>Dato og klokkeslett</span><div className="nc-field-icon"><Clock3 size={15} /><input type="datetime-local" value={publishAt} onChange={(event) => setPublishAt(event.target.value)} /></div></label>}
                </div>
              </div>
            </section>

            <section className="nc-card nc-block-card" aria-label="Innholdselementer">
              <div className="nc-card-head"><div className="nc-card-title"><span className="nc-step">03</span><div>Artikkelens rekkefølge<small>Dra tanken videre med flere formater.</small></div></div><span className="nc-list-head-right">{blocks.length} {blocks.length === 1 ? "element" : "elementer"}</span></div>
              <div className="nc-block-list">
                {blocks.map((block, index) => <button type="button" className={`nc-block-item ${selectedId === block.id ? "selected" : ""}`} key={block.id} onClick={() => setSelectedId(block.id)}>
                  <span className="nc-block-grip"><GripVertical size={15} /></span><span className="nc-block-icon"><TypeIcon type={block.type} size={15} /></span><span className="nc-block-name">{typeNames[block.type]}</span><span className="nc-block-order">{String(index + 1).padStart(2, "0")}</span>
                  <span className="nc-block-actions" onClick={(event) => event.stopPropagation()}>
                    <span role="button" tabIndex={0} className="nc-icon-button" aria-label={`Flytt ${typeNames[block.type]} opp`} onClick={() => moveBlock(block.id, -1)} onKeyDown={(event) => { if (event.key === "Enter") moveBlock(block.id, -1); }}><ArrowUp size={13} /></span>
                    <span role="button" tabIndex={0} className="nc-icon-button" aria-label={`Flytt ${typeNames[block.type]} ned`} onClick={() => moveBlock(block.id, 1)} onKeyDown={(event) => { if (event.key === "Enter") moveBlock(block.id, 1); }}><ArrowDown size={13} /></span>
                    <span role="button" tabIndex={0} className="nc-icon-button nc-danger" aria-label={`Fjern ${typeNames[block.type]}`} onClick={() => removeBlock(block.id)} onKeyDown={(event) => { if (event.key === "Enter") removeBlock(block.id); }}><X size={13} /></span>
                  </span>
                </button>)}
                <div className="nc-add-row"><select aria-label="Velg type element" value={newType} onChange={(event) => setNewType(event.target.value as ContentType)}>{(Object.keys(typeNames) as ContentType[]).map((type) => <option value={type} key={type}>{typeNames[type]}</option>)}</select><button className="nc-btn nc-btn-primary" type="button" onClick={addBlock}><Plus size={14} /> Legg til</button></div>
              </div>
            </section>
          </aside>
        </div>

        <section className="nc-card nc-preview-section" aria-label="Leserforhåndsvisning">
          <div className="nc-preview-toolbar">
            <div className="nc-preview-heading"><Eye size={16} /> Leserens nyhetsside <span className="nc-preview-caption">· slik saken ser ut for Fjordvik-kundene</span></div>
            <div className="nc-preview-controls"><div className="nc-toggle" role="group" aria-label="Velg forhåndsvisning"><button type="button" className={previewMode === "desktop" ? "active" : ""} aria-pressed={previewMode === "desktop"} onClick={() => setPreviewMode("desktop")}><Monitor size={13} /> Desktop</button><button type="button" className={previewMode === "mobile" ? "active" : ""} aria-pressed={previewMode === "mobile"} onClick={() => setPreviewMode("mobile")}><Smartphone size={13} /> Mobil</button></div></div>
          </div>
          <div className="nc-preview-mat">
            <article className={`nc-reader ${previewMode === "mobile" ? "mobile" : ""}`}>
              <div className="nc-reader-mast"><span className="nc-reader-brand">Fjordvik <i style={{ color: "#087f83" }}>Kulturhus</i></span><nav className="nc-reader-nav"><span>Hva skjer</span><span>Besøk oss</span><span>Nyheter</span></nav></div>
              <div className="nc-reader-topline">{category} <span style={{ color: "#a3aeaa" }}> / </span> Fjordvik</div>
              <h2>{title || "Skriv inn en tittel på nyheten"}</h2>
              <div className="nc-reader-byline"><span>Av <b>Fjordvik Kulturhus</b></span><span>·</span><span>{previewDate}</span><span>·</span><span>3 min lesetid</span></div>
              <div className="nc-reader-hero">{coverUrl ? <img src={coverUrl} alt="Forsidebilde for nyhetssaken" onError={(event) => { event.currentTarget.style.display = "none"; }} /> : <CoverArtwork />}</div>
              <div className="nc-reader-content">{blocks.map((block, index) => <div key={block.id}>{renderReaderBlock(block, index)}</div>)}</div>
              <hr className="nc-reader-rule" /><div className="nc-reader-footer"><span>Fjordvik Kulturhus · lokalt, sammen</span><span>Del nyheten</span></div>
            </article>
          </div>
        </section>
        <div className="nc-footer-note"><span><strong>Forhåndsvisning · eksempelinnhold</strong> — ingen portaldata er koblet til.</span><span>Ikke publisert <span aria-hidden="true">·</span> Prioritet {priority}</span></div>
      </main>
      {notice && <div className="nc-toast" role="status"><Check size={15} />{notice}<button type="button" aria-label="Lukk beskjed" onClick={() => setNotice("")} style={{ border: 0, background: "transparent", color: "inherit", display: "flex", padding: 2 }}><X size={14} /></button></div>}
    </div>
  );
}
