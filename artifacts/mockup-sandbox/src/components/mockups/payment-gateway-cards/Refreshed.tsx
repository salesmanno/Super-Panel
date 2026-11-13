import "./_group.css";
import { ArrowUpRight, CheckCircle2, CircleAlert, CreditCard, FlaskConical, RefreshCw, ShieldCheck, ToggleLeft, Zap } from "lucide-react";

function GatewayCard({ active, isVipps }: { active: boolean; isVipps: boolean }) {
  return (
    <article style={{
      overflow: "hidden", border: `1px solid ${active ? "#b8e0ca" : "#e3e9f0"}`, borderRadius: 18,
      background: "linear-gradient(180deg,#fff 0%,#fbfcfe 100%)", boxShadow: "0 8px 24px rgba(24,39,75,.08)",
    }}>
      <div style={{ padding: 20 }}>
        <div style={{ display: "flex", alignItems: "center", gap: 13 }}>
          <span style={{ display: "grid", placeItems: "center", width: 48, height: 48, borderRadius: 15, background: active ? "#e8f6ee" : "#eef2f7", color: active ? "#16834d" : "#465a73" }}>
            {isVipps ? <span style={{ fontWeight: 850, fontSize: 18 }}>V</span> : <CreditCard size={22} />}
          </span>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ color: "#6b7787", fontSize: 10, fontWeight: 800, letterSpacing: ".09em" }}>BETALINGSMÅTE</div>
            <h2 style={{ margin: "3px 0 0", color: "#1c2938", fontSize: 19 }}>Vipps</h2>
          </div>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 5, padding: "6px 10px", border: "1px solid #c5e8d2", borderRadius: 999, color: "#167445", background: "#edf8f1", fontSize: 11, fontWeight: 750 }}>
            <CheckCircle2 size={13} /> Aktiv
          </span>
        </div>
        <p style={{ margin: "15px 0 13px", color: "#6b7787", fontSize: 13 }}>Norsk mobilbetaling</p>
        <div style={{ display: "flex", gap: 7, marginBottom: 14 }}>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 5, padding: "5px 9px", border: "1px solid #f4dfaa", borderRadius: 999, color: "#8a5b00", background: "#fff8e8", fontSize: 11, fontWeight: 700 }}><FlaskConical size={13} /> Testmiljø</span>
        </div>
        <button style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: 7, width: "100%", minHeight: 42, border: "1px solid #e4b9bc", borderRadius: 11, background: "white", color: "#a83e47", fontSize: 13, fontWeight: 700 }}>
          <ToggleLeft size={17} /> Deaktiver betalingsmåten
        </button>
        {isVipps && (
          <section style={{ marginTop: 16, padding: 14, border: "1px solid #dce7f4", borderRadius: 14, background: "linear-gradient(145deg,#f5f9ff 0%,#f7f8fc 100%)" }}>
            <div style={{ display: "flex", justifyContent: "space-between", gap: 10 }}>
              <div>
                <div style={{ display: "flex", alignItems: "center", gap: 5, color: "#4d627b", fontSize: 10, fontWeight: 800, letterSpacing: ".07em" }}><Zap size={13} /> AUTOMATISK BETALINGSBEKREFTELSE</div>
                <h3 style={{ margin: "5px 0 0", color: "#22354d", fontSize: 15 }}>Vipps webhook</h3>
              </div>
              <span style={{ height: 25, padding: "5px 9px", border: "1px solid #f4dfaa", borderRadius: 99, color: "#8a5b00", background: "#fff8e8", fontSize: 11, fontWeight: 700 }}>Test</span>
            </div>
            <div style={{ margin: "12px 0", padding: 11, border: "1px solid #f0d8aa", borderRadius: 10, color: "#78571b", background: "#fffaf0", fontSize: 12, lineHeight: 1.5 }}>
              <strong style={{ display: "block", marginBottom: 3 }}>Krever oppfølging · levering ikke bekreftet</strong>
              Mottakeren må klargjøres før webhooken kan registreres.
              <div style={{ marginTop: 6, color: "#65768a", fontSize: 10, overflowWrap: "anywhere" }}>https://bergsøy.salesman.no/api/vipps_callback.php</div>
            </div>
            <div style={{ display: "flex", gap: 8 }}>
              <button style={{ display: "flex", flex: 1, justifyContent: "center", alignItems: "center", gap: 6, minWidth: 0, minHeight: 42, padding: "7px 10px", border: 0, borderRadius: 10, background: "#174e91", color: "white", fontSize: 12, fontWeight: 750 }}>
                <ShieldCheck size={15} /> Sjekk / fullfør webhook-oppsett
              </button>
              <button aria-label="Oppdater status" style={{ display: "flex", alignItems: "center", gap: 5, minHeight: 42, padding: "7px 10px", border: "1px solid #d6deea", borderRadius: 10, background: "white", color: "#40556e", fontSize: 12, fontWeight: 700 }}>
                <RefreshCw size={14} /> Oppdater status
              </button>
            </div>
            <div style={{ marginTop: 9, color: "#78879a", fontSize: 11 }}>Registrert status og faktisk levering vises separat.</div>
          </section>
        )}
      </div>
    </article>
  );
}

export function Refreshed() {
  return (
    <main style={{ minHeight: "100vh", padding: 20, background: "#f4f7fb", color: "#22354d" }}>
      <div style={{ maxWidth: 500, margin: "0 auto" }}>
        <div style={{ marginBottom: 12, color: "#6b7787", fontSize: 12 }}>ECONOMY <span style={{ margin: "0 5px" }}>›</span> BETALINGSLØSNINGER</div>
        <GatewayCard active isVipps />
        <div style={{ display: "flex", gap: 8, alignItems: "center", marginTop: 11, color: "#68778a", fontSize: 11 }}>
          <CircleAlert size={14} />
          <span>«Deaktiver» styrer bare betalingsmåten. Webhook-oppsettet har egen, tydelig knapp.</span>
          <ArrowUpRight size={13} />
        </div>
      </div>
    </main>
  );
}
