import "./_group.css";

export function Current() {
  return (
    <main style={{ minHeight: "100vh", padding: 24, background: "#f5f7fa", color: "#212529" }}>
      <div style={{ maxWidth: 460, margin: "0 auto" }}>
        <div style={{ background: "#fff", border: "1px solid #198754", borderRadius: 8, padding: 20 }}>
          <div style={{ display: "flex", alignItems: "center", marginBottom: 8 }}>
            <span style={{ color: "#198754", fontSize: 25, marginRight: 10 }}>◉</span>
            <h2 style={{ margin: 0, fontSize: 17 }}>Vipps</h2>
          </div>
          <p style={{ margin: "0 0 10px", color: "#6c757d", fontSize: 13 }}>Norsk mobilbetaling</p>
          <div style={{ marginBottom: 10 }}>
            <span style={{ display: "inline-block", padding: "4px 8px", borderRadius: 4, background: "#198754", color: "white", fontSize: 12 }}>Aktiv</span>
            <span style={{ display: "inline-block", marginLeft: 6, padding: "4px 8px", borderRadius: 4, background: "#fff3cd", fontSize: 12 }}>Testmodus</span>
          </div>
          <button style={{ width: "100%", padding: 9, border: "1px solid #dc3545", borderRadius: 5, background: "#fff", color: "#dc3545" }}>
            Deaktiver
          </button>
          <div style={{ marginTop: 18, paddingTop: 10, borderTop: "1px solid #ddd" }}>
            <div style={{ display: "flex", justifyContent: "space-between", fontSize: 13 }}>
              <strong>Automatisk betalingsbekreftelse</strong>
              <span style={{ color: "#6c757d" }}>Test</span>
            </div>
            <p style={{ color: "#6c757d", fontSize: 12, lineHeight: 1.5 }}>På kontrollerer og registrerer webhook før Vipps aktiveres. Av beholder oppfølging av påbegynte betalinger.</p>
            <p style={{ color: "#6c757d", fontSize: 12 }}>Status ikke kontrollert.</p>
            <button style={{ width: "100%", padding: 8, border: "1px solid #adb5bd", borderRadius: 5, background: "white" }}>Oppdater status</button>
            <button style={{ width: "100%", marginTop: 8, padding: 8, border: "1px solid #0d6efd", borderRadius: 5, background: "white", color: "#0d6efd" }}>Fullfør webhook-oppsett</button>
          </div>
        </div>
      </div>
    </main>
  );
}
