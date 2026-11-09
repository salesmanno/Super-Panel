import { useLayoutEffect, useMemo, useRef, useState } from "react";
import {
  ArrowDown,
  ArrowLeft,
  ArrowUp,
  Check,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  CircleAlert,
  GripVertical,
  Image as ImageIcon,
  Link2,
  List,
  Plus,
  Rows3,
  Table2,
  Type,
  Video,
  X,
} from "lucide-react";

type BlockType = "text" | "image" | "carousel" | "list" | "sheet" | "video";
type Block = {
  id: number;
  type: BlockType;
  text?: string;
  url?: string;
  alt?: string;
  caption?: string;
  items?: string[];
  urls?: string[];
  headers?: string[];
  rows?: string[][];
};

const typeNames: Record<BlockType, string> = {
  text: "Tekst",
  image: "Bilde",
  carousel: "Bildekarusell",
  list: "Liste",
  sheet: "Regneark",
  video: "Video",
};

const sampleImage =
  "https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Fronalpstock_big.jpg/1280px-Fronalpstock_big.jpg";
const secondImage =
  "https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Alpine_Valley_in_Switzerland.jpg/1280px-Alpine_Valley_in_Switzerland.jpg";
const youtubeSample = "https://www.youtube.com/watch?v=M7lc1UVf-VE";

const initialBlocks: Block[] = [
  {
    id: 1,
    type: "text",
    text: "Lørdag 14. november åpner vi dørene litt tidligere. Fra kl. 11 kan du møte lokale produsenter, smake på fersk bakst og finne noe godt til helgen.",
  },
  {
    id: 2,
    type: "image",
    url: sampleImage,
    alt: "Fjellandskap med innsjø og små hus",
    caption: "En smak av høst i Fjordvik.",
  },
  {
    id: 3,
    type: "carousel",
    urls: [sampleImage, secondImage],
    caption: "Gløtt fra nærmiljøet",
  },
  {
    id: 4,
    type: "list",
    items: ["Smaksprøver fra bakeriet kl. 11–13", "Kaffe fra Fjordvik Brenneri", "Gratis inngang for hele familien"],
  },
  {
    id: 5,
    type: "sheet",
    headers: ["Tidspunkt", "Hva skjer"],
    rows: [["11:00", "Dørene åpner"], ["12:00", "Smaksprøve i kafeen"], ["14:00", "Trekning av gavekurv"]],
  },
  {
    id: 6,
    type: "video",
    url: youtubeSample,
    caption: "Se en liten hilsen fra arrangørene",
  },
];

function videoIdFromUrl(url: string) {
  try {
    const parsed = new URL(url);
    const host = parsed.hostname.toLowerCase();
    const parts = parsed.pathname.split("/").filter(Boolean);
    const id = host === "youtu.be" || host === "www.youtu.be"
      ? parts[0]
      : parsed.searchParams.get("v") || (["embed", "shorts"].includes(parts[0]) ? parts[1] : "");
    return id && /^[A-Za-z0-9_-]{11}$/.test(id) ? id : "";
  } catch {
    return "";
  }
}

function InlineText({
  value,
  onChange,
  ariaLabel,
  className,
  onFocus,
}: {
  value: string;
  onChange: (value: string) => void;
  ariaLabel: string;
  className?: string;
  onFocus?: () => void;
}) {
  const elementRef = useRef<HTMLDivElement>(null);

  useLayoutEffect(() => {
    const element = elementRef.current;
    if (!element || document.activeElement === element) return;
    if (element.innerText !== value) element.innerText = value;
  }, [value]);

  return (
    <div
      ref={elementRef}
      className={className}
      contentEditable
      suppressContentEditableWarning
      role="textbox"
      aria-label={ariaLabel}
      aria-multiline="true"
      onFocus={onFocus}
      onInput={(event) => onChange(event.currentTarget.innerText)}
    />
  );
}

function BlockIcon({ type, size = 15 }: { type: BlockType; size?: number }) {
  if (type === "image" || type === "carousel") return <ImageIcon size={size} />;
  if (type === "video") return <Video size={size} />;
  if (type === "list") return <List size={size} />;
  if (type === "sheet") return <Table2 size={size} />;
  return <Type size={size} />;
}

function newBlock(type: BlockType, id: number): Block {
  if (type === "image") return { id, type, url: "", alt: "", caption: "" };
  if (type === "carousel") return { id, type, urls: ["", ""], caption: "" };
  if (type === "list") return { id, type, items: [""] };
  if (type === "sheet") return { id, type, headers: ["Kolonne 1", "Kolonne 2"], rows: [["", ""]] };
  if (type === "video") return { id, type, url: "", caption: "" };
  return { id, type, text: "" };
}

export function Composer() {
  const [title, setTitle] = useState("Smakshelg i Fjordvik – bli med på åpningen");
  const [blocks, setBlocks] = useState<Block[]>(initialBlocks);
  const [selectedId, setSelectedId] = useState(2);
  const [insertOpen, setInsertOpen] = useState(false);
  const [coverUrl, setCoverUrl] = useState(sampleImage);
  const [category, setCategory] = useState("Nyhet");
  const [priority, setPriority] = useState("2");
  const [pinned, setPinned] = useState(false);
  const [scheduled, setScheduled] = useState(false);
  const [publishDate, setPublishDate] = useState("10.11.26");
  const [publishTime, setPublishTime] = useState("11:00");
  const [expires, setExpires] = useState("");
  const [productOn, setProductOn] = useState(false);
  const [carouselIndex, setCarouselIndex] = useState<Record<number, number>>({});
  const [previewMode, setPreviewMode] = useState<"desktop" | "mobile">("desktop");
  const [toast, setToast] = useState("");
  const selected = blocks.find((block) => block.id === selectedId);

  const updateBlock = (id: number, patch: Partial<Block>) => {
    setBlocks((current) => current.map((block) => block.id === id ? { ...block, ...patch } : block));
  };
  const moveBlock = (id: number, direction: number) => {
    setBlocks((current) => {
      const index = current.findIndex((block) => block.id === id);
      const nextIndex = index + direction;
      if (index < 0 || nextIndex < 0 || nextIndex >= current.length) return current;
      const next = [...current];
      [next[index], next[nextIndex]] = [next[nextIndex], next[index]];
      return next;
    });
  };
  const reorderBlock = (sourceId: number, targetId: number) => {
    if (!sourceId || sourceId === targetId) return;
    setBlocks((current) => {
      const from = current.findIndex((block) => block.id === sourceId);
      const to = current.findIndex((block) => block.id === targetId);
      if (from < 0 || to < 0) return current;
      const next = [...current];
      const [moved] = next.splice(from, 1);
      next.splice(to, 0, moved);
      return next;
    });
  };
  const addBlock = (type: BlockType) => {
    const block = newBlock(type, Date.now());
    setBlocks((current) => [...current, block]);
    setSelectedId(block.id);
    setInsertOpen(false);
  };
  const removeBlock = (id: number) => {
    const remaining = blocks.filter((block) => block.id !== id);
    setBlocks(remaining);
    setSelectedId(remaining[0]?.id ?? 0);
  };
  const notifyLocal = (message: string) => {
    setToast(message);
    window.setTimeout(() => setToast(""), 2500);
  };
  const scheduleSummary = useMemo(
    () => scheduled ? ` · Planlagt ${publishDate} kl. ${publishTime}` : "",
    [publishDate, publishTime, scheduled],
  );

  const renderPaperBlock = (block: Block, index: number) => {
    const isSelected = block.id === selectedId;
    const editable = (value: string, onChange: (text: string) => void, className = "paper-editable") => (
      <InlineText
        value={value}
        onChange={onChange}
        className={className}
        ariaLabel={`${typeNames[block.type]} ${index + 1}`}
        onFocus={() => setSelectedId(block.id)}
      />
    );

    return (
      <section
        key={block.id}
        className={`paper-block ${isSelected ? "is-selected" : ""}`}
        onClick={() => setSelectedId(block.id)}
      >
        {block.type === "text" && editable(block.text ?? "", (text) => updateBlock(block.id, { text }))}
        {block.type === "image" && (
          <figure className="paper-image">
            {block.url
              ? <img src={block.url} alt={block.alt ?? ""} />
              : <div className="paper-empty"><ImageIcon size={20} />Legg til en gyldig bildeadresse for å vise bildet.</div>}
            {editable(block.caption ?? "", (caption) => updateBlock(block.id, { caption }), "paper-caption paper-editable")}
          </figure>
        )}
        {block.type === "carousel" && (() => {
          const urls = block.urls ?? [];
          const current = Math.min(carouselIndex[block.id] ?? 0, Math.max(urls.length - 1, 0));
          return (
            <figure className="paper-carousel">
              {urls[current]
                ? <img src={urls[current]} alt={`Bilde ${current + 1} i karusellen`} />
                : <div className="paper-empty"><ImageIcon size={20} />Legg til en gyldig bildeadresse for å vise bildet.</div>}
              <div className="carousel-controls">
                <button type="button" aria-label="Forrige bilde" onClick={() => setCarouselIndex((state) => ({ ...state, [block.id]: (current - 1 + urls.length) % urls.length }))}><ChevronLeft size={16} /></button>
                <span>{current + 1} / {Math.max(urls.length, 1)}</span>
                <button type="button" aria-label="Neste bilde" onClick={() => setCarouselIndex((state) => ({ ...state, [block.id]: (current + 1) % urls.length }))}><ChevronRight size={16} /></button>
              </div>
              {editable(block.caption ?? "", (caption) => updateBlock(block.id, { caption }), "paper-caption paper-editable")}
            </figure>
          );
        })()}
        {block.type === "list" && (
          <ul className="paper-list">
            {(block.items ?? []).map((item, itemIndex) => (
              <li key={`${block.id}-${itemIndex}`}>
                <InlineText value={item} ariaLabel={`Punkt ${itemIndex + 1}`} onFocus={() => setSelectedId(block.id)} onChange={(text) => {
                  const items = [...(block.items ?? [])];
                  items[itemIndex] = text;
                  updateBlock(block.id, { items });
                }} />
              </li>
            ))}
          </ul>
        )}
        {block.type === "sheet" && (
          <div className="paper-table-wrap">
            <table className="paper-table"><thead><tr>{(block.headers ?? []).map((header, columnIndex) => (
              <th key={columnIndex}><InlineText value={header} ariaLabel={`Kolonneoverskrift ${columnIndex + 1}`} onFocus={() => setSelectedId(block.id)} onChange={(text) => {
                const headers = [...(block.headers ?? [])];
                headers[columnIndex] = text;
                updateBlock(block.id, { headers });
              }} /></th>
            ))}</tr></thead><tbody>{(block.rows ?? []).map((row, rowIndex) => (
              <tr key={rowIndex}>{(block.headers ?? []).map((_, columnIndex) => (
                <td key={columnIndex}><InlineText value={row[columnIndex] ?? ""} ariaLabel={`Rad ${rowIndex + 1}, kolonne ${columnIndex + 1}`} onFocus={() => setSelectedId(block.id)} onChange={(text) => {
                  const rows = (block.rows ?? []).map((current) => [...current]);
                  rows[rowIndex][columnIndex] = text;
                  updateBlock(block.id, { rows });
                }} /></td>
              ))}</tr>
            ))}</tbody></table>
          </div>
        )}
        {block.type === "video" && (
          <figure className="paper-video-block">
            {videoIdFromUrl(block.url ?? "")
              ? <div className="paper-video"><iframe title={block.caption || "YouTube-video"} src={`https://www.youtube-nocookie.com/embed/${videoIdFromUrl(block.url ?? "")}`} allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerPolicy="strict-origin-when-cross-origin" allowFullScreen /></div>
              : <div className="paper-empty"><Video size={20} />Legg til en YouTube-lenke for å vise videoen.</div>}
            {editable(block.caption ?? "", (caption) => updateBlock(block.id, { caption }), "paper-caption paper-editable")}
          </figure>
        )}
      </section>
    );
  };

  return (
    <div className="news-editor">
      <style>{`
        .news-editor{--ink:#17324d;--muted:#60758a;--paper:#f5f8fa;--teal:#087f8c;--coral:#e56f54;--line:#d8e3ea;min-height:100dvh;background:var(--paper);color:var(--ink);font:14px/1.45 system-ui,-apple-system,"Segoe UI",sans-serif;padding-bottom:28px}
        .news-editor *{box-sizing:border-box}.news-editor button,.news-editor input,.news-editor select{font:inherit}.news-editor button{cursor:pointer}
        .editor-topbar{height:52px;padding:0 clamp(16px,4vw,56px);display:flex;align-items:center;justify-content:space-between;background:#f8fafb;border-bottom:1px solid var(--line);color:#506779}
        .editor-brand{display:flex;align-items:center;gap:9px;font-weight:750;letter-spacing:-.025em;color:var(--ink)}.editor-mark{width:28px;height:28px;border-radius:8px;display:grid;place-items:center;background:#e7f0ed;color:var(--teal)}.editor-context{font-size:12px;color:#6c8090}.editor-account{display:flex;align-items:center;gap:8px;font-size:12px}.editor-avatar{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:#e5efec;color:#3a685f;font-size:10px;font-weight:750}
        .editor-page{max-width:1500px;margin:auto;padding:20px clamp(16px,3.7vw,54px) 0}.editor-crumb{display:flex;align-items:center;gap:7px;color:#788b98;font-size:12px;margin-bottom:14px}.editor-crumb b{color:var(--ink);font-weight:650}
        .compose-heading{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:5px 2px 17px}.compose-kicker{font-size:11px;font-weight:800;letter-spacing:.12em;color:var(--teal)}.compose-heading h1{font:400 32px/1.12 Georgia,"Times New Roman",serif;margin:5px 0 3px;color:var(--ink)}.compose-heading p{margin:0;color:var(--muted);font-size:13px}.local-badge{border:1px solid #c9ddd8;background:#eef6f3;color:#35655e;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:700;white-space:nowrap}
        .compose-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(300px,.75fr);gap:16px;align-items:start}.compose-main,.compose-side{min-width:0}.compose-side{display:grid;align-content:start;gap:14px}
        .paper-card{border:0;background:transparent}.paper-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:3px 0 12px}.card-heading{display:grid;grid-template-columns:auto 1fr;column-gap:10px;align-items:center}.step{grid-row:span 2;width:25px;height:25px;border-radius:7px;display:grid;place-items:center;background:#eaf3f1;color:var(--teal);font-size:10px;font-weight:800}.card-heading strong{font-size:13px;color:var(--ink)}.card-heading small{grid-column:2;color:#728493;font-size:11px;margin-top:2px}.mode-switch{display:flex;gap:3px;padding:3px;border-radius:7px;background:#e9eff0}.mode-switch button{border:0;border-radius:5px;padding:5px 9px;background:transparent;color:#657b86;font-size:11px;font-weight:700}.mode-switch button.active{background:#fff;color:var(--teal);box-shadow:0 1px 4px #17324d1c}
        .paper-mat{padding:clamp(10px,2vw,20px);background:#e6e3dc;border-radius:14px}.paper-sheet{box-sizing:border-box;display:flex;flex-direction:column;width:100%;max-width:820px;min-height:960px;margin:0 auto;padding:clamp(24px,5vw,64px);border-radius:2px;background:#fffefa;color:#263b43;box-shadow:0 10px 30px rgba(40,48,48,.12);font-family:Georgia,"Times New Roman",serif;overflow-wrap:anywhere}.paper-sheet.mobile{max-width:390px;min-height:0;padding:22px}
        .paper-header{display:flex;align-items:end;justify-content:space-between;gap:12px;padding-bottom:12px;margin-bottom:27px;border-bottom:1px solid #cad4cf;color:#46615d;font:700 11px/1.4 system-ui,sans-serif;letter-spacing:.11em;text-transform:uppercase}.paper-title{display:block;width:100%;min-height:1.08em;margin:7px 0 15px;padding:0;border:0;background:transparent;color:#173b42;font:700 clamp(34px,4.4vw,56px)/1.08 Georgia,"Times New Roman",serif;letter-spacing:-.025em;outline:0}.paper-title:focus{box-shadow:0 2px #287b72}.paper-title:empty:before{content:"Skriv inn tittel …";color:#91a09a}
        .paper-byline{margin:0 0 30px;color:#74817d;font:12px/1.5 system-ui,sans-serif}.paper-block{position:relative;margin:15px 0 25px;padding:3px 3px 3px 16px;border-left:2px solid transparent;transition:border-color .16s}.paper-block.is-selected,.paper-block:focus-within{border-left-color:#287b72}.paper-editable{outline:0;overflow-wrap:anywhere}.paper-editable:focus{background:rgba(40,123,114,.035)}.paper-block [contenteditable=true]{outline:0;overflow-wrap:anywhere}.paper-block [contenteditable=true]:focus{background:rgba(40,123,114,.035)}.paper-text,.paper-block>.paper-editable{min-height:1.5em;font:16px/1.75 Georgia,"Times New Roman",serif}.paper-block>.paper-editable:empty:before{content:"Skriv teksten din her …";color:#91a09a;pointer-events:none}
        .paper-image,.paper-carousel,.paper-video-block{margin:0}.paper-image img,.paper-carousel>img{display:block;width:100%;max-height:510px;object-fit:contain;background:#eef0ed}.paper-caption{display:block;min-height:1.3em;margin-top:8px;color:#73817c;font:italic 13px/1.5 Georgia,serif}.paper-caption:empty:before{content:"Bildetekst";color:#9aa6a0}.paper-empty{display:grid;min-height:145px;place-items:center;gap:8px;padding:16px;border:1px dashed #b9c9c2;background:#f4f6f1;color:#687b75;font:13px/1.5 system-ui,sans-serif;text-align:center}.paper-empty svg{color:#6f9288}.carousel-controls{display:flex;justify-content:space-between;align-items:center;margin-top:9px;color:#556a64;font:12px system-ui,sans-serif}.carousel-controls button{width:30px;height:30px;border:1px solid #d2ddd7;border-radius:999px;background:#fff;color:#315c55;display:grid;place-items:center}.carousel-controls button:hover{background:#edf4ef}
        .paper-list{padding-left:22px;font:16px/1.8 Georgia,"Times New Roman",serif}.paper-list li{padding-left:3px}.paper-list li::marker{color:#287b72}.paper-table-wrap{overflow-x:auto}.paper-table{width:100%;border-collapse:collapse;font:13px/1.5 system-ui,sans-serif}.paper-table th,.paper-table td{min-width:50px;padding:10px 9px;border-bottom:1px solid #d9e0dc;text-align:left;outline:0}.paper-table th{background:#edf3ef;color:#355650;font-weight:700}.paper-table td:focus,.paper-table th:focus{box-shadow:inset 0 0 0 2px #87b5a5}.paper-video{position:relative;width:100%;aspect-ratio:16/9;background:#e8ece9}.paper-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
        .paper-insert{margin-top:auto;padding-top:26px;border-top:1px solid #dce4df}.paper-insert>button{width:100%;min-height:48px;border:1px dashed #86a69a;border-radius:8px;background:#f4f7f2;color:#315f56;font:700 14px system-ui,sans-serif}.paper-insert>button:hover{background:#edf4ef;border-color:#6b9887}.type-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px;margin-top:10px}.type-picker button{min-height:42px;border:1px solid #d5e0da;border-radius:7px;background:#fff;color:#37544d;font:600 12px system-ui,sans-serif}.type-picker button:hover{background:#edf4ef;border-color:#8cad9f}
        .inspector-card{min-width:0;background:#fff;border:1px solid #dce5e7;border-radius:14px;box-shadow:0 5px 20px rgba(23,50,77,.045);overflow:hidden}.inspector-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:13px 15px;border-bottom:1px solid #e8edee}.inspector-heading{display:grid;grid-template-columns:auto 1fr;column-gap:9px;align-items:center}.inspector-heading strong{font-size:13px;color:#17324d}.inspector-heading small{grid-column:2;color:#728493;font-size:10px}.inspector-body{padding:14px 15px}.field{display:block;margin:0 0 12px}.field>span,.field-label{display:block;margin-bottom:6px;color:#334e62;font-size:11px;font-weight:700}.field input,.field select,.product-fields input,.product-fields select{width:100%;height:36px;border:1px solid #cad8dc;border-radius:6px;background:#fff;padding:8px 9px;color:#294454;font-size:12px;outline:0}.field input:focus,.field select:focus,.product-fields input:focus{border-color:#087f8c;box-shadow:0 0 0 3px rgba(8,127,140,.12)}.details-row{display:grid;grid-template-columns:1.35fr .85fr;gap:9px}.priority-help{display:block;margin:-5px 0 12px;color:#7a8d99;font-size:10px}
        .cover-card{border:1px solid #e3e8e5;border-radius:9px;overflow:hidden;background:#f4f5f0;margin:4px 0 13px}.cover-image{display:block;width:100%;height:132px;object-fit:cover;background:#e8ece7}.cover-control{padding:10px;background:#fff;border-top:1px solid #e3e8e5}.cover-control label{display:block;margin-bottom:6px;color:#334e62;font-size:11px;font-weight:700}.cover-input-wrap{display:flex;align-items:center;gap:7px;border:1px solid #cad8dc;border-radius:6px;padding:0 8px;color:#7a8d99}.cover-input-wrap input{width:100%;min-width:0;height:34px;border:0;outline:0;color:#294454;font-size:11px}.cover-hint{margin-top:5px;color:#87959c;font-size:9px}
        .check-row{display:flex;align-items:center;gap:8px;margin:11px 0;color:#334e62;font-size:12px}.check-row input{width:15px;height:15px;accent-color:#087f8c}.schedule-fields{padding:10px;border-radius:8px;background:#f4f7f4;margin:0 0 11px}.schedule-fields[hidden],.type-picker[hidden]{display:none!important}.schedule-fields .details-row{grid-template-columns:1.1fr .9fr}.expire-field{margin-top:8px}.author-state{padding:10px 11px;border-radius:8px;background:#eff4f0;color:#42645a;font-size:11px;line-height:1.45}.author-state.missing{background:#fff6e9;color:#795d36}.author-state strong{display:block;font-size:11px;margin-bottom:2px}.product-fields{padding:10px;background:#f4f7f4;border-radius:8px;margin-top:8px}.product-fields .details-row{grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}
        .block-list{display:grid;gap:3px}.block-item{display:flex;align-items:center;gap:6px;min-width:0;border:1px solid transparent;border-radius:8px;padding:6px 5px;background:#fff;color:#334e62;transition:background .15s,border .15s}.block-item:hover,.block-item.active{background:#eef6f3;border-color:#cbded8}.drag-hint{width:23px;height:28px;display:grid;place-items:center;color:#81918d;flex:none;cursor:grab}.block-item-main{display:flex;align-items:center;gap:7px;min-width:0;flex:1;text-align:left;border:0;background:transparent;color:inherit;padding:2px 0}.block-order{color:#8b9a9d;font:11px system-ui,sans-serif;font-variant-numeric:tabular-nums}.block-icon{display:flex;color:#658c8c}.block-name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;font-weight:700}.block-actions{display:flex;gap:0}.icon-btn{width:25px;height:26px;display:grid;place-items:center;border:0;border-radius:5px;background:transparent;color:#6c8088}.icon-btn:hover{background:#dcebe6;color:#17324d}.icon-btn:disabled{opacity:.32;cursor:not-allowed}.icon-btn.danger:hover{background:#fcebe7;color:#a94e3d}.media-panel{margin-top:11px;border:1px solid #dce5e7;border-radius:9px;overflow:hidden}.media-panel-head{display:flex;align-items:center;justify-content:space-between;padding:9px 10px;background:#edf4f2;border-bottom:1px solid #dce5e7;color:#294d50;font-size:11px;font-weight:700}.media-panel-body{display:grid;gap:10px;padding:10px}.media-panel-body label{display:grid;gap:5px;color:#334e62;font-size:10px;font-weight:700}.media-panel-body input,.media-panel-body textarea{width:100%;border:1px solid #cad8dc;border-radius:6px;padding:8px;background:#fff;color:#294454;font:11px system-ui,sans-serif;outline:0}.media-panel-body textarea{min-height:70px;resize:vertical}.media-thumb{width:100%;max-height:135px;object-fit:contain;background:#f0f2ee;border-radius:6px}.media-count{color:#788b94;font-size:10px}
        .compose-footer{display:flex;justify-content:flex-end;gap:8px;padding:13px 0 0;margin-top:14px;border-top:1px solid #dce5e7}.footer-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid #cbd8dc;border-radius:999px;background:#fff;color:#45616d;padding:9px 14px;font-size:12px;font-weight:700}.footer-btn.primary{border-color:#087f8c;background:#087f8c;color:#fff}.footer-btn:hover{filter:brightness(.97)}.toast{position:fixed;right:18px;bottom:18px;z-index:5;padding:10px 13px;border-radius:8px;background:#244f50;color:#f8fbf6;box-shadow:0 8px 24px rgba(23,50,77,.16);font-size:11px}
        @media(max-width:850px){.compose-grid{grid-template-columns:1fr}.compose-side{grid-row:2}.compose-main{grid-row:1}.paper-sheet{min-height:0}.paper-mat{padding:9px}}
        @media(max-width:575px){.editor-topbar{height:48px;padding:0 13px}.editor-context{display:none}.editor-page{padding:15px 12px 0}.compose-heading{align-items:flex-start;flex-direction:column;gap:9px}.compose-heading h1{font-size:28px}.paper-card-head{align-items:flex-start}.paper-sheet{padding:22px 18px}.paper-header{font-size:9px}.paper-title{font-size:36px}.type-picker{grid-template-columns:repeat(2,minmax(0,1fr))}.inspector-body{padding:12px}.compose-footer{flex-wrap:wrap}.footer-btn{flex:1}}
        @media(prefers-reduced-motion:reduce){.news-editor *{transition:none!important}}
      `}</style>
      <header className="editor-topbar">
        <div className="editor-brand"><span className="editor-mark"><Rows3 size={16} /></span><span>Salesman</span><span className="editor-context">· Nyhetsrom</span></div>
        <div className="editor-account"><span>Tenant-administrasjon</span><span className="editor-avatar"><Rows3 size={13} /></span><span>Redaktør</span><ChevronDown size={13} /></div>
      </header>
      <main className="editor-page">
        <div className="editor-crumb"><ArrowLeft size={14} /><span>Nyhetsoversikt</span><span>/</span><b>Opprett ny sak</b></div>
        <header className="compose-heading">
          <div><span className="compose-kicker">NEWS · REDAKTØR</span><h1>Opprett ny sak</h1><p>Rediger ferdig artikkel direkte på arket.</p></div>
          <span className="local-badge"><span style={{ display: "inline-block", width: 6, height: 6, borderRadius: "50%", background: "#438f7c", marginRight: 6, verticalAlign: "middle" }} />Lokal eksempelvisning</span>
        </header>
        <div className="compose-grid">
          <section className="compose-main">
            <article className="paper-card">
              <header className="paper-card-head">
                <div className="card-heading"><span className="step">01</span><strong>Artikkelark</strong><small>Skriv og rediger direkte i saken.</small></div>
                <div className="mode-switch">
                  <button type="button" className={previewMode === "desktop" ? "active" : ""} onClick={() => setPreviewMode("desktop")}>Desktop</button>
                  <button type="button" className={previewMode === "mobile" ? "active" : ""} onClick={() => setPreviewMode("mobile")}>Mobil</button>
                </div>
              </header>
              <div className="paper-mat">
                <article className={`paper-sheet ${previewMode === "mobile" ? "mobile" : ""}`}>
                  <header className="paper-header"><span>Nyhet · Fjordvik</span><span>{category}</span></header>
                  <InlineText className="paper-title" value={title} ariaLabel="Nyhetstittel" onChange={setTitle} />
                  <p className="paper-byline">Av Sofie Nilsen · Fjordvik Kulturhus{scheduleSummary}</p>
                  {coverUrl && <figure className="paper-image cover-paper-image"><img src={coverUrl} alt="Forsidebilde" /></figure>}
                  {blocks.map(renderPaperBlock)}
                  <div className="paper-insert">
                    <button type="button" onClick={() => setInsertOpen((open) => !open)}>{insertOpen ? "Lukk elementvalg" : "+ Opprett nytt element"}</button>
                    {insertOpen && <div className="type-picker">{(["text", "image", "carousel", "list", "sheet", "video"] as BlockType[]).map((type) => (
                      <button type="button" key={type} onClick={() => addBlock(type)}><BlockIcon type={type} size={15} /> {typeNames[type]}</button>
                    ))}</div>}
                  </div>
                </article>
              </div>
            </article>
            <footer className="compose-footer">
              <button type="button" className="footer-btn" onClick={() => notifyLocal("Lokal eksempelvisning · ingen endringer er lagret.")}>Til nyhetsoversikt</button>
              <button type="button" className="footer-btn" onClick={() => notifyLocal("Lokal eksempelvisning · ingen endringer er lagret.")}>Lagre som kladd</button>
              <button type="button" className="footer-btn primary" onClick={() => notifyLocal("Lokal eksempelvisning · ingen publisering er utført.")}><Check size={14} />Publiser</button>
            </footer>
          </section>
          <aside className="compose-side">
            <section className="inspector-card">
              <header className="inspector-head"><div className="inspector-heading"><span className="step">02</span><strong>Nyhetsdetaljer</strong><small>Opplysninger som følger saken ut.</small></div></header>
              <div className="inspector-body">
                <label className="field"><span>Tittel</span><input value={title} maxLength={255} onChange={(event) => setTitle(event.target.value)} /></label>
                <div className="details-row">
                  <label className="field"><span>Type</span><select value={category} onChange={(event) => setCategory(event.target.value)}><option>Nyhet</option><option>Arrangement</option><option>Informasjon</option><option>Aktuelt</option><option>Viktig melding</option></select></label>
                  <label className="field"><span>Prioritet</span><input type="number" min="1" max="9" value={priority} onChange={(event) => setPriority(event.target.value)} /></label>
                </div>
                <small className="priority-help">1–9 · 1 er høyest.</small>
                <div className="cover-card">
                  {coverUrl && <img className="cover-image" src={coverUrl} alt="Forhåndsvisning av nyhetsbildet" />}
                  <div className="cover-control"><label htmlFor="cover-url">Forsidebilde · URL</label><div className="cover-input-wrap"><Link2 size={14} /><input id="cover-url" type="url" value={coverUrl} placeholder="https://…" onChange={(event) => setCoverUrl(event.target.value)} /></div><div className="cover-hint">Forhåndsvisning av forsidebildet</div></div>
                </div>
                <label className="check-row"><input type="checkbox" checked={pinned} onChange={(event) => setPinned(event.target.checked)} />Fest øverst</label>
                <label className="check-row"><input type="checkbox" checked={scheduled} onChange={(event) => setScheduled(event.target.checked)} />Planlagt publisering</label>
                <div className="schedule-fields" hidden={!scheduled}><div className="details-row">
                  <label className="field"><span>Dato · dd.mm.åå</span><input value={publishDate} placeholder="dd.mm.åå" onChange={(event) => setPublishDate(event.target.value)} /></label>
                  <label className="field"><span>Klokkeslett</span><input value={publishTime} placeholder="19:00" maxLength={5} onChange={(event) => setPublishTime(event.target.value)} /></label>
                </div></div>
                <label className="field expire-field"><span>Utløper</span><input type="datetime-local" value={expires} onChange={(event) => setExpires(event.target.value)} /></label>
                <div className="author-state"><strong>Forfatter følger innlogget ansatt</strong>Sofie Nilsen · eksempel på koblet tenant_staff-bruker</div>
                <label className="check-row"><input type="checkbox" checked={productOn} onChange={(event) => setProductOn(event.target.checked)} />Knytt til produkt</label>
                {productOn && <div className="product-fields">
                  <label className="field"><span>Produkt</span><select defaultValue=""><option value="">Velg produkt</option><option>Lokalt eksempelprodukt</option></select></label>
                  <div className="details-row"><input type="number" min="0" max="100" placeholder="Rabatt %" /><input type="number" min="1" placeholder="Maks antall" /><input type="date" aria-label="Produktets utløpsdato" /></div>
                </div>}
              </div>
            </section>
            <section className="inspector-card">
              <header className="inspector-head"><div className="inspector-heading"><span className="step">03</span><strong>Artikkelens elementer</strong><small>Dra håndtaket for å sortere.</small></div><span className="media-count">{blocks.length} elementer</span></header>
              <div className="inspector-body">
                <div className="block-list">{blocks.map((block, index) => (
                  <div
                    className={`block-item ${selectedId === block.id ? "active" : ""}`}
                    key={block.id}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={(event) => {
                      event.preventDefault();
                      reorderBlock(Number(event.dataTransfer.getData("text/plain")), block.id);
                    }}
                  >
                    <span
                      className="drag-hint"
                      aria-label={`Dra for å flytte element ${index + 1}`}
                      title="Dra for å sortere"
                      draggable
                      onDragStart={(event) => {
                        event.dataTransfer.effectAllowed = "move";
                        event.dataTransfer.setData("text/plain", String(block.id));
                      }}
                    ><GripVertical size={16} /></span>
                    <button type="button" className="block-item-main" onClick={() => setSelectedId(block.id)}><span className="block-order">{String(index + 1).padStart(2, "0")}</span><span className="block-icon"><BlockIcon type={block.type} /></span><span className="block-name">{typeNames[block.type]}</span></button>
                    <div className="block-actions">
                      <button type="button" className="icon-btn" aria-label="Flytt opp" disabled={index === 0} onClick={() => moveBlock(block.id, -1)}><ArrowUp size={14} /></button>
                      <button type="button" className="icon-btn" aria-label="Flytt ned" disabled={index === blocks.length - 1} onClick={() => moveBlock(block.id, 1)}><ArrowDown size={14} /></button>
                      <button type="button" className="icon-btn danger" aria-label="Fjern element" onClick={() => removeBlock(block.id)}><X size={14} /></button>
                    </div>
                  </div>
                ))}</div>
                {selected && ["image", "carousel", "video"].includes(selected.type) && (
                  <div className="media-panel">
                    <div className="media-panel-head"><span>Medieinnstillinger · {typeNames[selected.type]}</span><span className="media-count">Valgt element</span></div>
                    <div className="media-panel-body">
                      {selected.type === "image" && <>
                        {selected.url && <img className="media-thumb" src={selected.url} alt={selected.alt ?? ""} />}
                        <label>Bildeadresse<input type="url" value={selected.url ?? ""} placeholder="https://…" onChange={(event) => updateBlock(selected.id, { url: event.target.value })} /></label>
                        <label>Alternativ tekst<input value={selected.alt ?? ""} placeholder="Beskriv bildet for lesere" onChange={(event) => updateBlock(selected.id, { alt: event.target.value })} /></label>
                        <label>Bildetekst<input value={selected.caption ?? ""} onChange={(event) => updateBlock(selected.id, { caption: event.target.value })} /></label>
                      </>}
                      {selected.type === "carousel" && <>
                        <p style={{ margin: 0, color: "#718492", fontSize: 10 }}>Én bildeadresse per linje. Karusellen trenger minst to gyldige bilder.</p>
                        {(selected.urls ?? []).map((url, index) => <label key={index}>Bilde {index + 1} · URL<div style={{ display: "flex", gap: 5 }}><input type="url" value={url} placeholder="https://…" onChange={(event) => {
                          const urls = [...(selected.urls ?? [])]; urls[index] = event.target.value; updateBlock(selected.id, { urls });
                        }} /><button type="button" className="icon-btn danger" aria-label={`Fjern bilde ${index + 1}`} onClick={() => updateBlock(selected.id, { urls: (selected.urls ?? []).filter((_, i) => i !== index) })}><X size={14} /></button></div></label>)}
                        <button type="button" className="icon-btn" style={{ width: "auto", padding: "0 8px", display: "flex", gap: 5 }} onClick={() => updateBlock(selected.id, { urls: [...(selected.urls ?? []), ""] })}><Plus size={14} />Legg til bilde</button>
                        <label>Bildetekst<input value={selected.caption ?? ""} onChange={(event) => updateBlock(selected.id, { caption: event.target.value })} /></label>
                      </>}
                      {selected.type === "video" && <>
                        <label>YouTube-lenke<input type="url" value={selected.url ?? ""} placeholder="https://www.youtube.com/watch?v=…" onChange={(event) => updateBlock(selected.id, { url: event.target.value })} /></label>
                        <label>Bildetekst<input value={selected.caption ?? ""} onChange={(event) => updateBlock(selected.id, { caption: event.target.value })} /></label>
                        {videoIdFromUrl(selected.url ?? "") ? <span className="author-state"><Check size={13} style={{ verticalAlign: "middle" }} /> Gyldig YouTube-adresse</span> : <span className="author-state missing"><CircleAlert size={13} style={{ verticalAlign: "middle" }} /> Bruk en vanlig YouTube-videoadresse.</span>}
                      </>}
                    </div>
                  </div>
                )}
                {selected?.type === "list" && <div className="media-panel"><div className="media-panel-head">Listeinnstillinger</div><div className="media-panel-body">{(selected.items ?? []).map((item, index) => <label key={index}>Punkt {index + 1}<input value={item} onChange={(event) => {
                  const items = [...(selected.items ?? [])]; items[index] = event.target.value; updateBlock(selected.id, { items });
                }} /></label>)}<button type="button" className="icon-btn" style={{ width: "auto", padding: "0 8px", display: "flex", gap: 5 }} onClick={() => updateBlock(selected.id, { items: [...(selected.items ?? []), ""] })}><Plus size={14} />Legg til punkt</button></div></div>}
                {selected?.type === "sheet" && <div className="media-panel"><div className="media-panel-head">Regneark · innhold redigeres på arket</div><div className="media-panel-body"><span className="media-count">Velg en celle eller overskrift direkte i artikkelen.</span><button type="button" className="icon-btn" style={{ width: "auto", padding: "0 8px", display: "flex", gap: 5 }} onClick={() => updateBlock(selected.id, { rows: [...(selected.rows ?? []), (selected.headers ?? []).map(() => "")] })}><Plus size={14} />Ny rad</button></div></div>}
                {selected?.type === "text" && <div className="media-panel"><div className="media-panel-head">Tekst · redigeres direkte på arket</div><div className="media-panel-body"><span className="media-count">Teksten tilpasser seg innholdet automatisk.</span></div></div>}
              </div>
            </section>
          </aside>
        </div>
      </main>
      {toast && <div className="toast" role="status">{toast}</div>}
    </div>
  );
}
