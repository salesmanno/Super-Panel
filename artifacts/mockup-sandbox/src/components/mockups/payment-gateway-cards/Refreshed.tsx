import "./_group.css";
import { useState } from "react";
import { CheckCircle2, ChevronDown, CircleAlert, CreditCard, RotateCcw } from "lucide-react";

type Gateway = {
  id: string;
  name: string;
  tone: "green" | "yellow" | "red";
  state: string;
  logo: "vipps" | "stripe" | "klarna" | "invoice";
  active: boolean;
};

const gateways: Gateway[] = [
  { id: "vipps", name: "Vipps", tone: "yellow", state: "Sjekk", logo: "vipps", active: true },
  { id: "stripe", name: "Stripe", tone: "green", state: "Aktiv", logo: "stripe", active: true },
  { id: "klarna", name: "Klarna", tone: "yellow", state: "Sjekk oppsett", logo: "klarna", active: true },
  { id: "invoice", name: "Faktura", tone: "red", state: "Av", logo: "invoice", active: false },
];

function Brand({ logo, name }: { logo: Gateway["logo"]; name: string }) {
  if (logo === "vipps") return <img src="/__mockup/images/vipps.webp" alt="Vipps" style={{ width: "100%", height: "100%", objectFit: "cover" }} />;
  const styles: Record<Gateway["logo"], React.CSSProperties> = {
    vipps: {},
    stripe: { color: "#635bff", background: "#f5f4ff", fontSize: 25, fontWeight: 850, letterSpacing: "-.07em" },
    klarna: { color: "#111", background: "#ffdf00", fontSize: 23, fontWeight: 850, letterSpacing: "-.06em" },
    invoice: { color: "#40556e", background: "#eef2f6", fontSize: 15, fontWeight: 750 },
  };
  return <div style={{ display: "grid", placeItems: "center", width: "100%", height: "100%", ...styles[logo] }}>{logo === "invoice" ? <CreditCard size={23} /> : name.toLowerCase()}</div>;
}

function GatewayCard({ gateway, expanded, onToggle }: { gateway: Gateway; expanded: boolean; onToggle: () => void }) {
  const titleId = `gateway-title-${gateway.id}`;
  return (
    <article style={{
      overflow: "hidden", border: `1px solid ${expanded ? "#b7c8dc" : "#e1e7ef"}`, borderRadius: 14,
      background: "#fff", boxShadow: expanded ? "0 8px 22px rgba(22,38,61,.1)" : "0 4px 14px rgba(22,38,61,.055)",
    }}>
      <button type="button" aria-expanded={expanded} aria-controls={`gateway-details-${gateway.id}`} aria-labelledby={titleId} onClick={onToggle}
        style={{ display: "block", width: "100%", padding: 0, border: 0, background: "white", textAlign: "left", cursor: "pointer" }}>
        <div style={{ height: 88, overflow: "hidden", background: "#f1f4f8" }}><Brand logo={gateway.logo} name={gateway.name} /></div>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 7, minHeight: 49, padding: "9px 10px" }}>
          <span id={titleId} style={{ minWidth: 0, overflow: "hidden", color: "#202b3a", fontSize: 13, fontWeight: 750, textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{gateway.name}</span>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 5, flex: "0 0 auto", color: "#566273", fontSize: 10, fontWeight: 700 }}>
            <span aria-hidden="true" style={{ width: 9, height: 9, borderRadius: "50%", background: gateway.tone === "green" ? "#1b9a5a" : gateway.tone === "yellow" ? "#dfa700" : "#d3424f", boxShadow: `0 0 0 3px ${gateway.tone === "green" ? "rgba(27,154,90,.13)" : gateway.tone === "yellow" ? "rgba(223,167,0,.15)" : "rgba(211,66,79,.13)"}` }} />
            {gateway.state}<ChevronDown size={14} />
          </span>
        </div>
      </button>
      {expanded && (
        <section id={`gateway-details-${gateway.id}`} style={{ padding: 11, borderTop: "1px solid #e9edf2", background: "#fbfcfe" }}>
          <p style={{ margin: "0 0 9px", color: "#6b7787", fontSize: 11 }}>{gateway.logo === "vipps" ? "Testmiljø · norsk mobilbetaling" : "Betalingsoppsett"}</p>
          {gateway.logo === "vipps" && <div style={{ marginBottom: 10, padding: 9, border: "1px solid #c7e8d2", borderRadius: 9, color: "#245c3e", background: "#f3fbf5", fontSize: 11, lineHeight: 1.45 }}>
            <strong style={{ display: "block" }}>Webhook registrert</strong>Faktisk levering er ikke bekreftet ennå.
          </div>}
          {(gateway.logo === "stripe" || gateway.logo === "vipps") && <div style={{ marginBottom: 10, padding: 9, borderRadius: 9, background: "white", border: "1px solid #e1e7ef" }}>
            <div style={{ marginBottom: 6, color: "#536276", fontSize: 11, fontWeight: 700 }}>Transaksjonsgebyr</div>
            <div style={{ display: "flex", gap: 7 }}><span style={{ flex: 1, padding: 7, border: "1px solid #d9e0e8", borderRadius: 7, color: "#667085", fontSize: 10 }}>Fast: 0,00 kr</span><span style={{ flex: 1, padding: 7, border: "1px solid #d9e0e8", borderRadius: 7, color: "#667085", fontSize: 10 }}>Prosent: 0,00 %</span></div>
          </div>}
          <button type="button" style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: 6, width: "100%", minHeight: 38, border: `1px solid ${gateway.active ? "#e3b8bc" : "#b7d9c3"}`, borderRadius: 9, color: gateway.active ? "#a83e47" : "#167445", background: "white", fontSize: 12, fontWeight: 700 }}>
            {gateway.active ? <><RotateCcw size={14} /> Deaktiver</> : <><CheckCircle2 size={14} /> Aktiver</>}
          </button>
          {gateway.logo === "vipps" && <div style={{ marginTop: 7, color: "#778496", fontSize: 10 }}>Deaktivering fjerner ikke webhooken; den kan brukes for betalinger som allerede pågår.</div>}
        </section>
      )}
    </article>
  );
}

export function Refreshed() {
  const [expanded, setExpanded] = useState<string | null>(null);
  return (
    <main style={{ minHeight: "100vh", padding: 18, background: "#f4f7fb", color: "#22354d" }}>
      <div style={{ maxWidth: 650, margin: "0 auto" }}>
        <div style={{ marginBottom: 15 }}>
          <div style={{ color: "#6b7787", fontSize: 10, fontWeight: 750, letterSpacing: ".08em" }}>ECONOMY <span style={{ margin: "0 5px" }}>›</span> BETALINGSLØSNINGER</div>
          <h1 style={{ margin: "5px 0 0", color: "#203149", fontSize: 21 }}>Betalingsmåter</h1>
          <p style={{ margin: "4px 0 0", color: "#738094", fontSize: 12 }}>Trykk på et kort for detaljer og innstillinger.</p>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(2,minmax(0,1fr))", gap: 11 }}>
          {gateways.map((gateway) => <GatewayCard key={gateway.id} gateway={gateway} expanded={expanded === gateway.id} onToggle={() => setExpanded(current => current === gateway.id ? null : gateway.id)} />)}
        </div>
        <div style={{ display: "flex", alignItems: "flex-start", gap: 7, marginTop: 12, color: "#778496", fontSize: 10, lineHeight: 1.45 }}>
          <CircleAlert size={13} />
          <span>Webhook registreres ved behov første gang Vipps aktiveres. Statusen skiller registrering fra faktisk levering.</span>
        </div>
      </div>
    </main>
  );
}
