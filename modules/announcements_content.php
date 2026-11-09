<?php
require_once __DIR__ . '/../includes/announcement_helpers.php';
require_once __DIR__ . '/../config/database.php';

$newsTextPillBg = '#176b69';
$newsTextPillColor = '#ffffff';
try {
    if (!empty($_SESSION['company_id'])) {
        $newsThemeStmt = getDBConnection()->prepare(
            'SELECT header_bg_color FROM tenant_website_general WHERE company_id = ? LIMIT 1'
        );
        $newsThemeStmt->execute([$_SESSION['company_id']]);
        $storedHeaderColor = $newsThemeStmt->fetchColumn();
        if (is_string($storedHeaderColor) && preg_match('/^#[0-9a-fA-F]{6}$/', $storedHeaderColor)) {
            $newsTextPillBg = $storedHeaderColor;
            $red = hexdec(substr($storedHeaderColor, 1, 2));
            $green = hexdec(substr($storedHeaderColor, 3, 2));
            $blue = hexdec(substr($storedHeaderColor, 5, 2));
            $newsTextPillColor = (($red * 299 + $green * 587 + $blue * 114) / 1000) > 160
                ? '#24333b'
                : '#ffffff';
        }
    }
} catch (Throwable $e) {
    error_log('Could not load News header color: ' . $e->getMessage());
}
?>
<style>
@scope (.news-control-room) {
.news-control-room{--ink:#17324d;--muted:#60758a;--paper:#f5f8fa;--teal:#087f8c;--coral:#e56f54;--news-textpill-bg:<?= htmlspecialchars($newsTextPillBg, ENT_QUOTES, 'UTF-8') ?>;--news-textpill-color:<?= htmlspecialchars($newsTextPillColor, ENT_QUOTES, 'UTF-8') ?>;color:var(--ink);background:var(--paper);border:1px solid #d8e3ea;border-radius:18px;padding:1.25rem;box-shadow:0 8px 28px rgba(22,59,92,.07)}
.news-heading{display:flex;justify-content:space-between;gap:1rem;align-items:center}.news-heading h2{font-family:Georgia,serif;font-size:1.65rem;margin:0}.news-heading p{color:var(--muted);margin:.25rem 0 0;font-size:.86rem}.news-actions{display:flex;gap:.45rem;flex-wrap:wrap}.news-actions .btn{border-radius:999px}
.news-workspace{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(290px,.75fr);gap:1rem;margin-top:1rem}.preview-shell{background:#e9e5dc;border-radius:14px;padding:.8rem}.preview-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#596568}.preview-top strong{color:var(--teal)}.preview-frame{background:#fffdf9;border-radius:10px;padding:1.1rem;min-height:400px;overflow:hidden}.preview-frame.mobile{max-width:390px;margin:auto}.public-mast{border-bottom:2px solid var(--ink);padding-bottom:.55rem;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:end}.public-mast b{font-family:Georgia,serif;font-size:1.35rem}.public-mast small{color:var(--muted)}
.hero-story,.secondary-story,.archive-story{cursor:pointer;outline:0}.hero-story{display:grid;grid-template-columns:1.12fr .88fr;gap:1rem;padding-bottom:1rem;border-bottom:1px solid #ddd8ce}.hero-story.no-image{grid-template-columns:1fr}.story-image{width:100%;height:100%;min-height:130px;object-fit:cover;border-radius:5px;background:#e5e0d6}.hero-story h3{font-family:Georgia,serif;font-size:clamp(1.25rem,2.2vw,2rem);line-height:1.08;margin:.25rem 0}.story-kicker{font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--coral)}.story-excerpt{font-size:.82rem;line-height:1.5;color:#516064;white-space:pre-line}.story-meta{color:#7c8788;font-size:.68rem}.secondary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem;margin:1rem 0;border-bottom:1px solid #ddd8ce;padding-bottom:1rem}.secondary-story{padding:.25rem}.secondary-story.no-image{padding-top:.1rem}.secondary-story h4{font-family:Georgia,serif;font-size:1rem;line-height:1.15;margin:.25rem 0}.secondary-story .story-image{height:72px;min-height:0;margin-bottom:.4rem}.archive-label{font-size:.65rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:700}.archive-story{display:flex;gap:.65rem;padding:.65rem 0;border-bottom:1px solid #eee9e1}.archive-story.no-image{display:block}.archive-story h5{font-family:Georgia,serif;margin:0;font-size:.9rem}.archive-story .story-image{width:54px;height:44px;min-height:0;flex:none}.selected-story{box-shadow:inset 3px 0 var(--coral);padding-left:.55rem}.story-focus:focus-visible{box-shadow:0 0 0 3px #e5a28e!important}.reason{background:#eef4f1;color:#245553;border-radius:8px;padding:.65rem;font-size:.78rem;margin-top:.7rem}.management{background:#fff;border:1px solid #e3dfd5;border-radius:12px;padding:.8rem}.management h3{font-size:.9rem;margin:.1rem 0 .6rem}.manage-item{display:flex;align-items:center;flex-wrap:wrap;gap:.5rem;border-top:1px solid #eee;padding:.6rem .1rem;font-size:.78rem}.manage-item button{border:0;background:transparent;color:var(--teal)}.manage-item-title{flex:1 1 150px;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.badge-news{font-size:.65rem;border:1px solid #cbd8d2;padding:.18rem .4rem;border-radius:999px;color:#38605d;background:#f1f7f3}.news-textpill-bg{background:var(--news-textpill-bg);border-color:var(--news-textpill-bg);color:var(--news-textpill-color)}.news-empty{text-align:center;color:var(--muted);padding:3rem 1rem}.news-control-room button:focus-visible,.news-control-room input:focus-visible,.news-control-room select:focus-visible,.news-control-room textarea:focus-visible{outline:3px solid #e5a28e;outline-offset:2px}.news-control-room .modal-content{border:0;border-radius:14px}.news-control-room .form-label{font-size:.78rem;font-weight:700}.news-control-room textarea{min-height:130px}.news-control-room .modal-dialog{max-width:680px}
.preview-frame.mobile{width:100%;max-width:390px;overflow-x:hidden}.preview-frame.mobile .public-mast{align-items:flex-start;flex-direction:column;gap:.2rem}.preview-frame.mobile .hero-story{grid-template-columns:1fr;min-width:0}.preview-frame.mobile .secondary-grid{grid-template-columns:1fr;min-width:0}.preview-frame.mobile .story-focus,.preview-frame.mobile .story-focus>div{min-width:0;max-width:100%;overflow-wrap:anywhere}.preview-frame.mobile .story-meta{display:block;overflow-wrap:anywhere;word-break:break-word}.preview-frame.mobile .archive-story{gap:.5rem;padding:.55rem 0}.preview-frame.mobile .archive-story h5{overflow-wrap:anywhere}
 .story-body{min-width:0}.archive-story .story-body{flex:1}.story-content{cursor:pointer}.story-content:focus-visible{box-shadow:0 0 0 3px #e5a28e;outline:0;border-radius:3px}.announcement-image-editor{border:1px solid #e3dfd5;border-radius:12px;overflow:hidden;background:#f5f3ee}.announcement-image-preview{display:block;width:100%;height:230px;object-fit:cover;background:#e8e3da}.announcement-image-placeholder{height:150px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:.82rem}.announcement-image-url{padding:.8rem;background:#fff;border-top:1px solid #e3dfd5}.announcement-editor-toolbar{display:flex;gap:.25rem;flex-wrap:wrap;padding:.45rem;background:#f5f3ee;border:1px solid #d9d3c8;border-bottom:0;border-radius:9px 9px 0 0}.announcement-editor-toolbar .btn{min-width:2.1rem}.announcement-content-editor{min-height:190px;max-height:420px;overflow:auto;border:1px solid #d9d3c8;border-radius:0 0 9px 9px;padding:.75rem;background:#fff;line-height:1.6}.announcement-content-editor:focus{border-color:var(--teal);box-shadow:0 0 0 .2rem rgba(23,107,105,.13);outline:0}.announcement-content-editor p{margin:0 0 .75rem}.announcement-content-editor p:last-child{margin-bottom:0}.announcement-content-editor h2,.announcement-content-editor h3,.announcement-content-editor h4,.announcement-content-editor h5{margin:.4rem 0 .55rem}.announcement-content-editor blockquote{border-left:3px solid var(--coral);padding-left:.75rem;color:var(--muted)}.announcement-content-editor pre{white-space:pre-wrap;background:#f5f3ee;padding:.6rem;border-radius:6px}.announcement-rich-preview p{margin:0 0 .7rem}.announcement-rich-preview p:last-child{margin-bottom:0}
@media(max-width:800px){.news-workspace{grid-template-columns:1fr}.hero-story{grid-template-columns:1fr}.secondary-grid{grid-template-columns:1fr}.preview-frame{padding:.8rem}.news-heading{align-items:flex-start;flex-direction:column}}@media(prefers-reduced-motion:reduce){.news-control-room *{transition:none!important}}
}
.news-control-room-modal .modal-content{border:0;border-radius:14px}.news-control-room-modal .form-label{font-size:.78rem;font-weight:700}.news-control-room-modal textarea{min-height:130px}.news-control-room-modal .modal-dialog{max-width:680px}
/* Image previews are replaced elements: their intrinsic dimensions must never
   become the minimum size of the modal's flex/grid content. */
.news-control-room-modal .modal-dialog{max-height:calc(100dvh - 1rem);min-width:0}
.news-control-room-modal .modal-content{max-height:calc(100dvh - 1rem);min-width:0}
.news-control-room-modal .modal-body{flex:1 1 auto;min-width:0;min-height:0;max-height:max(8rem,calc(100dvh - 16rem));overflow-y:auto}
.news-control-room-modal .announcement-image-editor,
.news-control-room-modal .announcement-image-url,
.news-control-room-modal .shared-image-upload{box-sizing:border-box;min-width:0;max-width:100%}
.news-control-room-modal .announcement-image-preview{display:block;width:100%;max-width:100%;min-width:0;height:auto;max-height:230px;object-fit:contain}
.news-control-room-modal .announcement-image-preview[hidden],
.news-control-room-modal .announcement-image-placeholder[hidden]{display:none!important}
.news-control-room-modal .modal-footer{flex-wrap:wrap}
/* Keep the writing surface clearly distinct from the surrounding dialog. */
.news-control-room-modal #announcement-content-editor,
.news-control-room #announcement-content-editor{width:100%;min-height:clamp(17rem,36vh,24rem);max-height:65vh;padding:1rem 1.1rem;border:2px solid #78919b;border-radius:0 0 10px 10px;background:#fff;box-shadow:inset 0 1px 3px rgba(18,45,59,.07);font-size:1rem;line-height:1.65;overflow:auto;resize:vertical;overflow-wrap:anywhere}
.news-control-room-modal .announcement-editor-toolbar,
.news-control-room .announcement-editor-toolbar{border:2px solid #78919b;border-bottom:0;background:#edf3f4;padding:.6rem;border-radius:10px 10px 0 0}
.news-control-room-modal #announcement-content-editor:focus,
.news-control-room #announcement-content-editor:focus{border-color:#176b69;outline:3px solid rgba(23,107,105,.2);outline-offset:2px}
#announcement-content-editor:empty::before{content:"Skriv nyhetssaken her …";color:#627780;pointer-events:none}
@media(max-width:575px){.news-control-room-modal #announcement-content-editor,.news-control-room #announcement-content-editor{min-height:17rem;padding:.9rem;max-height:65svh}}
</style>
<style>
.news-subtabs{display:flex;gap:.35rem;border-bottom:1px solid #d8e3ea;padding:.4rem .5rem;margin:0 0 1rem;background:#f8fafb;border-radius:12px 12px 0 0}
.news-subtab{border:0;background:transparent;color:#60758a;border-radius:8px;padding:.65rem 1rem;font-weight:700}.news-subtab.active{background:#087f8c;color:#fff}.news-subtab:focus-visible{outline:3px solid #e5a28e;outline-offset:2px}.news-subpanel[hidden]{display:none!important}
.news-compose-heading{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.5rem .15rem 1rem}.news-compose-heading h2{font:2rem Georgia,serif;color:#17324d;margin:.15rem 0}.news-compose-heading p{margin:0;color:#60758a}.news-compose-kicker{color:#087f8c;font-size:.68rem;font-weight:800;letter-spacing:.12em}.news-compose-preview-badge,.news-compose-local{border:1px solid #c9ddd8;background:#eef6f3;color:#35655e;border-radius:999px;padding:.35rem .65rem;font-size:.68rem;font-weight:700;white-space:nowrap}
.news-compose-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(310px,.8fr);gap:1rem;align-items:start}.news-compose-main,.news-compose-side{display:grid;gap:1rem;min-width:0}.news-compose-card{min-width:0;background:#fff;border:1px solid #dce5e7;border-radius:14px;box-shadow:0 5px 20px rgba(23,50,77,.045);overflow:hidden}.news-compose-card-head{display:flex;justify-content:space-between;align-items:center;gap:.7rem;padding:.85rem 1rem;border-bottom:1px solid #e8edee}.news-compose-card-head>div:first-child{display:grid;grid-template-columns:auto 1fr;column-gap:.6rem;align-items:center}.news-compose-card-head strong{color:#17324d;font-size:.9rem}.news-compose-card-head small{grid-column:2;color:#728493;font-size:.72rem}.news-compose-step{grid-row:span 2;display:grid;place-items:center;width:1.6rem;height:1.6rem;background:#eaf3f1;color:#087f8c;border-radius:7px;font-size:.65rem;font-weight:800}.news-compose-card-body{padding:1rem}.news-compose-card-body .form-label{font-size:.75rem;font-weight:700;color:#334e62}.news-compose-card-body .form-control,.news-compose-card-body .form-select{border-color:#cad8dc;box-shadow:none}.news-compose-card-body .form-control:focus,.news-compose-card-body .form-select:focus{border-color:#087f8c;box-shadow:0 0 0 .18rem rgba(8,127,140,.13)}
.news-compose-element-list{display:grid;gap:.35rem}.news-compose-element{width:100%;display:flex;align-items:center;gap:.45rem;text-align:left;border:1px solid transparent;background:#fff;border-radius:8px;padding:.55rem;color:#334e62}.news-compose-element:hover,.news-compose-element.active{background:#eef6f3;border-color:#cbded8}.news-compose-element-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.78rem;font-weight:700}.news-compose-element-actions{display:flex;gap:.15rem}.news-compose-element-actions button{border:0;background:transparent;color:#60758a;padding:.25rem;border-radius:5px}.news-compose-element-actions button:hover{background:#dcebe6;color:#17324d}.news-compose-add-row{display:flex;gap:.4rem;margin-top:.55rem;padding-top:.6rem;border-top:1px solid #e8edee}.news-compose-add-row .form-select{min-width:0}.news-compose-selected{border:1px solid #dce5e7;border-radius:10px;overflow:hidden}.news-compose-selected-head{display:flex;justify-content:space-between;align-items:center;padding:.7rem .8rem;background:#edf4f2;border-bottom:1px solid #dce5e7;font-weight:700;font-size:.8rem}.news-compose-selected-body{padding:.85rem}.news-compose-selected-body textarea{min-height:150px}.news-compose-list-row{display:flex;gap:.45rem;margin-bottom:.4rem}.news-compose-list-row input{flex:1}.news-compose-table-wrap{overflow:auto}.news-compose-table-wrap input{min-width:100px}.news-compose-hint{font-size:.75rem;color:#718492;margin-bottom:.6rem}.news-compose-footer{display:flex;justify-content:flex-end;gap:.5rem;padding:1rem 0}.news-compose-reader-wrap{padding:1rem;background:#e9e5dc}.news-compose-reader{background:#fffdf9;max-width:860px;margin:auto;padding:1.25rem;border-radius:8px;color:#17324d}.news-compose-reader.mobile{max-width:390px}.news-compose-reader h2{font:clamp(1.35rem,3vw,2rem) Georgia,serif;line-height:1.1;margin:.5rem 0}.news-compose-reader-meta{font-size:.7rem;color:#7c8788;margin-bottom:.8rem}.news-compose-reader img{max-width:100%;max-height:300px;object-fit:cover;border-radius:5px}.news-compose-reader .nc-reader-table{width:100%;border-collapse:collapse;margin:.7rem 0;font-size:.78rem}.news-compose-reader .nc-reader-table th,.news-compose-reader .nc-reader-table td{padding:.5rem;border-bottom:1px solid #e2e6e2;text-align:left}.news-compose-reader .nc-reader-table th{background:#eef4f1}
.news-compose-footer{border-top:1px solid #dce5e7}.news-compose-selected-body [contenteditable=true]{min-height:180px;max-height:420px;overflow:auto;border:1px solid #cad8dc;border-radius:8px;background:#fff;padding:.75rem;line-height:1.6}.news-compose-selected-body [contenteditable=true]:focus{outline:3px solid rgba(8,127,140,.18);border-color:#087f8c}
.news-compose-card-body .announcement-image-editor{box-sizing:border-box;min-width:0;max-width:100%;overflow:hidden}.news-compose-card-body .announcement-image-preview{box-sizing:border-box;display:block;width:100%;min-width:0;max-width:100%;height:auto;max-height:230px;object-fit:contain}
.news-compose-card-body .announcement-image-preview[hidden],.news-compose-card-body .announcement-image-placeholder[hidden]{display:none!important}
@media(max-width:850px){.news-compose-grid{grid-template-columns:1fr}.news-compose-side{grid-row:1}.news-compose-main{grid-row:2}}
@media(max-width:575px){.news-compose-heading{align-items:flex-start;flex-direction:column}.news-compose-heading h2{font-size:1.6rem}.news-subtabs{overflow-x:auto}.news-subtab{white-space:nowrap}.news-compose-card-head{padding:.75rem}.news-compose-card-body{padding:.8rem}.news-compose-footer{position:sticky;bottom:0;background:#f5f8fa;padding:.7rem 0;z-index:2}.news-compose-reader-wrap{padding:.5rem}.news-compose-reader{padding:.8rem}}
</style>
<div class="container-fluid py-3">
<nav class="news-subtabs" role="tablist" aria-label="News">
 <button type="button" class="news-subtab active" id="news-overview-tab" data-news-panel="news-overview-panel" role="tab" aria-selected="true">Nyhetsoversikt</button>
 <button type="button" class="news-subtab" id="news-create-tab" data-news-panel="news-create-panel" role="tab" aria-selected="false">Opprett ny sak</button>
</nav>
<div id="news-overview-panel" class="news-subpanel">
 <section class="news-control-room" aria-labelledby="news-title">
  <div class="news-heading"><div><h2 id="news-title">Nyhetsrom</h2><p>Endringer vises med en gang i simuleringen. Prioritet 1 er høyest; høyere tall rangeres lavere.</p></div><div class="news-actions"><button class="btn btn-primary btn-sm" id="newAnnouncementButton">Opprett ny sak</button><button class="btn btn-outline-secondary btn-sm" id="refreshAnnouncements">Oppdater</button></div></div>
  <div class="news-workspace"><div class="preview-shell"><div class="preview-top"><strong>Forhåndsvisning av nyhetssiden</strong><div class="btn-group btn-group-sm" role="group" aria-label="Visning"><button class="btn btn-outline-secondary active" id="desktopView">Desktop</button><button class="btn btn-outline-secondary" id="mobileView">Mobil</button></div></div><div id="news-preview" class="preview-frame"></div></div><aside><div id="selected-reason" class="reason mb-2" hidden></div><div class="management"><div class="d-flex justify-content-between align-items-center"><h3>Administrer saker</h3><select id="news-filter" class="form-select form-select-sm w-auto"><option value="all">Alle</option><option value="published">Publisert</option><option value="scheduled">Planlagt</option><option value="draft">Kladder</option></select></div><div id="news-management"></div></div></aside></div>
 </section>
</div>
<div id="news-create-panel" class="news-subpanel" hidden>
 <header class="news-compose-heading"><div><span class="news-compose-kicker">NEWS · REDAKTØR</span><h2 id="announcementModalTitle">Opprett ny sak</h2><p>Bygg saken og se hvordan den vil vises før du lagrer.</p></div><span class="news-compose-preview-badge">Forhåndsvisning følger endringene</span></header>
 <div id="announcement-save-status" class="alert alert-success py-2" role="status" aria-live="polite" hidden></div>
 <form id="announcementForm">
  <input type="hidden" id="announcement-id">
  <div class="news-compose-grid">
   <section class="news-compose-main">
    <article class="news-compose-card">
     <header class="news-compose-card-head"><div><span class="news-compose-step">01</span><strong>Innholdselement</strong><small>Velg et element til høyre for å redigere det her.</small></div><span class="news-compose-local">LOKAL REDIGERING</span></header>
     <div class="news-compose-card-body"><div id="announcement-selected-element"></div></div>
    </article>
    <article class="news-compose-card news-compose-preview-card">
     <header class="news-compose-card-head"><div><span class="news-compose-step">04</span><strong>Leserens nyhetsside</strong><small>Slik ser saken ut for leserne.</small></div><div class="btn-group btn-group-sm" role="group" aria-label="Forhåndsvisning"><button type="button" class="btn btn-outline-secondary active" id="compose-preview-desktop">Desktop</button><button type="button" class="btn btn-outline-secondary" id="compose-preview-mobile">Mobil</button></div></header>
     <div class="news-compose-reader-wrap"><article id="news-create-preview" class="news-compose-reader"></article></div>
    </article>
   </section>
   <aside class="news-compose-side">
    <section class="news-compose-card">
     <header class="news-compose-card-head"><div><span class="news-compose-step">02</span><strong>Nyhetsdetaljer</strong><small>Opplysninger som følger saken ut.</small></div></header>
     <div class="news-compose-card-body">
      <label class="form-label" for="announcement-title">Tittel</label><input class="form-control mb-3" id="announcement-title" required maxlength="255">
      <div class="row g-2"><div class="col-7"><label class="form-label" for="announcement-type">Type</label><select class="form-select" id="announcement-type"><option value="news">Nyhet</option><option value="event">Arrangement</option><option value="info">Informasjon</option><option value="promotion">Aktuelt</option><option value="urgent">Viktig melding</option></select></div><div class="col-5"><label class="form-label" for="announcement-priority">Prioritet</label><input class="form-control" id="announcement-priority" type="number" min="1" max="9" step="1" value="5" required aria-describedby="announcement-priority-help"></div></div><small id="announcement-priority-help" class="text-muted">1–9 · 1 er høyest.</small>
      <div class="announcement-image-editor mt-3"><img id="announcement-image-preview" class="announcement-image-preview" alt="Forhåndsvisning av nyhetsbildet" hidden><div id="announcement-image-placeholder" class="announcement-image-placeholder">Ingen forsidebilde valgt</div><div class="announcement-image-url"><label class="form-label" for="announcement-image-url">Forsidebilde · URL</label><input type="url" class="form-control" id="announcement-image-url" placeholder="https://…"></div></div>
      <div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="announcement-is-pinned"><label class="form-check-label" for="announcement-is-pinned">Fest øverst</label></div>
      <div class="row g-2 mt-2"><div class="col-12"><label class="form-label" for="announcement-publish-at">Publiseres</label><input type="datetime-local" class="form-control" id="announcement-publish-at"></div><div class="col-12"><label class="form-label" for="announcement-expire-at">Utløper</label><input type="datetime-local" class="form-control" id="announcement-expire-at"></div></div>
      <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" id="announcement-product-toggle"><label class="form-check-label" for="announcement-product-toggle">Knytt til produkt</label></div><div id="product-section" class="mt-2" hidden><select class="form-select mb-2" id="announcement-product-id"><option value="">Velg produkt</option></select><div class="row g-2"><div class="col-4"><input type="number" class="form-control" id="announcement-discount" min="0" max="100" placeholder="Rabatt %"></div><div class="col-4"><input type="number" class="form-control" id="announcement-max-qty" min="1" placeholder="Maks antall"></div><div class="col-4"><input type="date" class="form-control" id="announcement-product-expiry"></div></div></div>
      <div id="announcement-priority-impact" class="small text-muted mt-2" role="status" aria-live="polite"></div>
     </div>
    </section>
    <section class="news-compose-card">
     <header class="news-compose-card-head"><div><span class="news-compose-step">03</span><strong>Artikkelens rekkefølge</strong><small>Velg og sorter elementene.</small></div><span id="news-create-element-count" class="text-muted small"></span></header>
     <div class="news-compose-card-body"><div id="announcement-elements-list" class="news-compose-element-list"></div>
      <div class="news-compose-add-row"><select id="announcement-element-type" class="form-select form-select-sm" aria-label="Elementtype"><option value="text">Ren tekst</option><option value="image">Bilde</option><option value="text_image">Tekst og bilde</option><option value="image_carousel">Bildekarusell</option><option value="youtube_video">YouTube-video</option><option value="list">Liste</option><option value="spreadsheet">Tabell</option></select><button type="button" class="btn btn-primary btn-sm" id="announcement-add-element">+ Legg til</button></div>
     </div>
    </section>
   </aside>
  </div>
  <details class="mt-3"><summary class="small text-muted">Reserveinnhold for eldre visninger</summary><div class="pt-2">
   <label class="form-label" for="announcement-content-editor">Eldre tekstinnhold</label><div class="announcement-editor-toolbar" role="toolbar" aria-label="Tekstformatering"><button type="button" class="btn btn-sm btn-outline-secondary" data-format="bold" title="Fet"><strong>B</strong></button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="italic" title="Kursiv"><em>I</em></button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="underline" title="Understreket"><u>U</u></button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="formatBlock" data-value="h3" title="Overskrift">H</button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="insertUnorderedList" title="Punktliste">•</button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="insertOrderedList" title="Nummerert liste">1.</button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="createLink" title="Sett inn lenke">↗</button></div><div id="announcement-content-editor" class="announcement-content-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-describedby="announcement-content-help announcement-content-feedback"></div><textarea id="announcement-content" hidden aria-hidden="true"></textarea><div id="announcement-content-feedback" class="invalid-feedback" role="alert" aria-live="polite" hidden>Innhold kan ikke være tomt.</div><small id="announcement-content-help" class="text-muted d-block mb-3">Beholdes for eldre visninger mens disse tas ut av bruk.</small></div></details>
  <div class="news-compose-footer"><button type="button" class="btn btn-outline-secondary" id="cancelAnnouncement">Til nyhetsoversikt</button><button type="button" class="btn btn-outline-secondary" id="saveDraftButton">Lagre som kladd</button><button type="button" class="btn btn-primary" id="publishButton">Publiser</button></div>
 </form>
</div>
</div>
<script src="/assets/announcement_ranking.js"></script>
<script>
(() => {
const types={event:'Arrangement',info:'Informasjon',news:'Nyhet',promotion:'Aktuelt',urgent:'Viktig melding'},priorityValue=value=>AnnouncementRanking.priorityValue(value),priorityDefault=5;let announcements=[],previewDraft=null,selectedId=null,previewMode='desktop',lastTrigger=null,publishIntent=false,simulatePublished=true,previewEffectivePublishAt=null,editRevision=0,loadRevision=0,saveBusy=false,imagePreviewUrl='',imagePreviewFailed=false;
 const $=id=>document.getElementById(id), esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])), norm=s=>String(s??'').replace(/\r\n?/g,'\n');
 const contentEditor=()=>$('announcement-content-editor');
   let editorElements=[],selectedElementIndex=0,composePreviewMode='desktop';
   const elementDefaults=type=>type==='text'?{html:''}:type==='image'?{url:'',alt:'',caption:''}:type==='text_image'?{html:'',url:'',alt:'',caption:'',layout:'image_top'}:type==='image_carousel'?{urls:[]}:type==='list'?{items:['Nytt punkt']}:type==='spreadsheet'?{headers:['Kolonne 1','Kolonne 2'],rows:[['','']]}:{url:'',caption:''};
   const elementLabel=type=>({text:'Ren tekst',image:'Bilde',text_image:'Tekst og bilde',image_carousel:'Bildekarusell',youtube_video:'YouTube-video',list:'Liste',spreadsheet:'Tabell'}[type]||'Innhold');
  const escAttr=value=>esc(value).replace(/`/g,'&#96;');
   const elementKind=el=>el?.editor_kind||el?.element_type||'text';
   const elementHtml=value=>String(value??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
   const listHtml=items=>`<ul>${items.map(item=>`<li>${elementHtml(item)}</li>`).join('')}</ul>`;
   const tableHtml=(headers,rows)=>`<table><thead><tr>${headers.map(value=>`<th>${elementHtml(value)}</th>`).join('')}</tr></thead><tbody>${rows.map(row=>`<tr>${headers.map((_,i)=>`<td>${elementHtml(row[i]||'')}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
   function kindFromSavedText(html){
    if(/<table\b/i.test(html))return 'spreadsheet';
    if(/<(?:ul|ol)\b/i.test(html))return 'list';
    return 'text';
   }
  function elementField(index,key,label,value,type='text'){
   const attrs=`data-element-index="${index}" data-element-key="${key}"`;
   if(type==='textarea')return `<label class="form-label small">${label}<textarea class="form-control form-control-sm" rows="3" ${attrs}>${esc(value)}</textarea></label>`;
   return `<label class="form-label small">${label}<input class="form-control form-control-sm" type="${type}" value="${escAttr(value)}" ${attrs}></label>`;
  }
  function renderElements(){
    const list=$('announcement-elements-list'),selected=$('announcement-selected-element');if(!list||!selected)return;
    $('news-create-element-count').textContent=`${editorElements.length} element${editorElements.length===1?'':'er'}`;
    list.innerHTML=editorElements.map((el,index)=>`<div class="news-compose-element ${index===selectedElementIndex?'active':''}"><button type="button" class="btn p-0 border-0 bg-transparent text-start flex-grow-1" data-element-select="${index}"><span class="news-compose-element-title">${String(index+1).padStart(2,'0')} · ${elementLabel(elementKind(el))}</span></button><span class="news-compose-element-actions"><button type="button" data-element-move="-1" data-element-index="${index}" aria-label="Flytt opp" ${index===0?'disabled':''}>↑</button><button type="button" data-element-move="1" data-element-index="${index}" aria-label="Flytt ned" ${index===editorElements.length-1?'disabled':''}>↓</button><button type="button" data-element-remove="${index}" aria-label="Fjern element">×</button></span></div>`).join('')||'<p class="text-muted small mb-0">Ingen elementer ennå.</p>';
    const el=editorElements[selectedElementIndex],c=el?.content||{},kind=elementKind(el);
    if(!el){selected.innerHTML='<p class="news-compose-hint mb-0">Legg til et element for å begynne.</p>';renderComposerPreview();return}
    let fields='';
    if(kind==='text')fields=`<label class="form-label" for="selected-text-${selectedElementIndex}">Tekstinnhold</label><div id="selected-text-${selectedElementIndex}" class="form-control" contenteditable="true" data-element-html aria-label="Tekstinnhold">${c.html||''}</div>`;
    if(kind==='image')fields=elementField(selectedElementIndex,'url','Bildefilens lenke',c.url||'','url')+elementField(selectedElementIndex,'alt','Alternativ tekst',c.alt||'')+elementField(selectedElementIndex,'caption','Bildetekst',c.caption||'');
    if(kind==='text_image')fields=elementField(selectedElementIndex,'html','Tekst',contentToText(c.html||''),'textarea')+elementField(selectedElementIndex,'url','Bildefilens lenke',c.url||'','url')+elementField(selectedElementIndex,'alt','Alternativ tekst',c.alt||'')+elementField(selectedElementIndex,'caption','Bildetekst',c.caption||'');
    if(kind==='image_carousel')fields=elementField(selectedElementIndex,'urls','Bilde-URL-er (én per linje)',(c.urls||[]).join('\n'),'textarea');
    if(kind==='youtube_video')fields=elementField(selectedElementIndex,'url','YouTube-lenke',c.url||'','url')+elementField(selectedElementIndex,'caption','Bildetekst',c.caption||'');
    if(kind==='list'){let items=c.items||[];fields=`<p class="news-compose-hint">Punktene vises som en liste i saken.</p>${items.map((item,i)=>`<div class="news-compose-list-row"><input class="form-control form-control-sm" data-list-index="${i}" value="${escAttr(item)}" aria-label="Listepunkt ${i+1}"><button type="button" class="btn btn-sm btn-outline-danger" data-list-remove="${i}" aria-label="Fjern punkt">×</button></div>`).join('')}<button type="button" class="btn btn-sm btn-outline-primary" data-list-add>+ Legg til punkt</button>`}
    if(kind==='spreadsheet'){let headers=c.headers||[],rows=c.rows||[];fields=`<p class="news-compose-hint">Rediger overskrifter, celler og rader. Tabellen lagres som trygg HTML i tekstelementet – ingen ny databasekolonne kreves.</p><div class="news-compose-table-wrap"><table class="table table-sm"><thead><tr>${headers.map((value,i)=>`<th><input class="form-control form-control-sm" data-table-head="${i}" value="${escAttr(value)}" aria-label="Kolonne ${i+1}"></th>`).join('')}<th></th></tr></thead><tbody>${rows.map((row,r)=>`<tr>${headers.map((_,col)=>`<td><input class="form-control form-control-sm" data-table-row="${r}" data-table-col="${col}" value="${escAttr(row[col]||'')}" aria-label="Rad ${r+1}, kolonne ${col+1}"></td>`).join('')}<td><button type="button" class="btn btn-sm btn-outline-danger" data-table-remove="${r}" aria-label="Fjern rad">×</button></td></tr>`).join('')}</tbody></table></div><div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-primary" data-table-row-add>+ Rad</button><button type="button" class="btn btn-sm btn-outline-secondary" data-table-col-add>+ Kolonne</button></div>`}
    selected.innerHTML=`<div class="news-compose-selected"><div class="news-compose-selected-head"><span>${selectedElementIndex+1}. ${elementLabel(kind)}</span><button type="button" class="btn btn-sm btn-outline-danger" data-selected-remove>Fjern</button></div><div class="news-compose-selected-body">${fields}</div></div>`;
  }
  function setEditorElements(elements){
    editorElements=(Array.isArray(elements)?elements:[]).map(el=>{let content={...(el.content||{})},editor_kind=el.element_type==='text'?kindFromSavedText(content.html||''):el.element_type;if(editor_kind==='list'){let doc=new DOMParser().parseFromString(content.html||'','text/html');content.items=Array.from(doc.querySelectorAll('li')).map(item=>item.textContent||'');if(!content.items.length)content.items=[''];}if(editor_kind==='spreadsheet'){let doc=new DOMParser().parseFromString(content.html||'','text/html'),table=doc.querySelector('table');content.headers=Array.from(table?.querySelectorAll('thead th')||[]).map(cell=>cell.textContent||'');content.rows=Array.from(table?.querySelectorAll('tbody tr')||[]).map(row=>Array.from(row.querySelectorAll('td')).map(cell=>cell.textContent||''));}return {element_type:el.element_type,editor_kind,content}});
    selectedElementIndex=Math.min(selectedElementIndex,Math.max(0,editorElements.length-1));
   renderElements();
  }
  function getEditorElements(){
    return editorElements.map(el=>{const kind=elementKind(el),content={...el.content};if(kind==='list')return {element_type:'text',content:{html:listHtml(content.items||[])}};if(kind==='spreadsheet')return {element_type:'text',content:{html:tableHtml(content.headers||[],content.rows||[])}};if(el.element_type==='image_carousel')content.urls=(content.urls||[]).map(v=>String(v).trim()).filter(Boolean);return {element_type:el.element_type,content}}).filter(el=>el.element_type!=='text'||contentToText(el.content?.html||'').trim()!=='');
  }
  function elementsLegacyText(){
   const parts=[];
   for(const el of getEditorElements()){
    const c=el.content||{};
    if(el.element_type==='text'||el.element_type==='text_image')parts.push(contentToText(c.html||''));
    if(c.caption)parts.push(c.caption);
   }
   return parts.map(s=>String(s||'').trim()).filter(Boolean).join('\n\n')||'Nyhetsinnhold';
  }
  function legacyContentForSave(existing,elements){
   if(!elements.length)return contentValue();
   const generated=elementsLegacyText();
   if(existing&&!existing.elements?.length&&contentToText(existing.content_html||existing.content||'').trim()===generated.trim())return existing.content||generated;
   return generated;
  }
  function elementsPreviewText(){
   const parts=[];
   for(const el of getEditorElements()){
    const c=el.content||{};
    if(el.element_type==='text'||el.element_type==='text_image')parts.push(contentToText(c.html||''));
    if(c.caption)parts.push(c.caption);
    if(el.element_type==='image'&&!c.caption)parts.push(c.alt||'Bilde');
    if(el.element_type==='youtube_video'&&!c.caption)parts.push('YouTube-video');
   }
   return parts.filter(Boolean).join(' ').trim();
  }
  function elementsFirstImage(){
   for(const el of getEditorElements()){
    if(el.element_type==='image'||el.element_type==='text_image')return el.content.url||'';
    if(el.element_type==='image_carousel')return el.content.urls?.[0]||'';
   }
   return '';
  }
  function renderComposerPreview(){
   const preview=$('news-create-preview');if(!preview)return;
   const safeImage=url=>/^https?:\/\//i.test(String(url||''))?String(url):'';
   const title=esc($('announcement-title')?.value||'Ny sak uten tittel');
   const cover=safeImage($('announcement-image-url')?.value);
   const body=editorElements.map(el=>{
    const c=el.content||{},kind=elementKind(el),text=esc(contentToText(c.html||'')).replace(/\n/g,'<br>');
    if(kind==='text')return text?`<p>${text}</p>`:'';
    if(kind==='list')return `<ul>${(c.items||[]).filter(Boolean).map(item=>`<li>${esc(item)}</li>`).join('')}</ul>`;
    if(kind==='spreadsheet')return `<table class="nc-reader-table"><thead><tr>${(c.headers||[]).map(v=>`<th>${esc(v)}</th>`).join('')}</tr></thead><tbody>${(c.rows||[]).map(row=>`<tr>${(c.headers||[]).map((_,i)=>`<td>${esc(row[i]||'')}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
    if(kind==='image'||kind==='text_image'){const img=safeImage(c.url);return `${img?`<p><img src="${escAttr(img)}" alt="${escAttr(c.alt||'')}"></p>`:''}${kind==='text_image'?`<p>${text}</p>`:''}${c.caption?`<small>${esc(c.caption)}</small>`:''}`}
    if(kind==='image_carousel')return `<div class="d-flex gap-2">${(c.urls||[]).filter(Boolean).map(url=>safeImage(url)).filter(Boolean).map(url=>`<img src="${escAttr(url)}" alt="" style="width:30%;height:100px;object-fit:cover">`).join('')}</div>`;
    return `<div class="p-4 text-center bg-dark text-white rounded">YouTube-video${c.caption?` · ${esc(c.caption)}`:''}</div>`;
   }).join('');
   preview.classList.toggle('mobile',composePreviewMode==='mobile');
   preview.innerHTML=`<div class="d-flex justify-content-between align-items-end border-bottom border-2 pb-2 mb-3"><strong style="font:1.25rem Georgia,serif">Nyheter</strong><small>Fjordvik Kulturhus</small></div><small class="text-uppercase text-muted">${esc(types[$('announcement-type')?.value]||'Nyhet')}</small><h2>${title}</h2><div class="news-compose-reader-meta">Av firmaet · ${$('announcement-publish-at')?.value?esc(new Date($('announcement-publish-at').value).toLocaleDateString('nb-NO')):'Publiseres nå'}</div>${cover?`<p><img src="${escAttr(cover)}" alt=""></p>`:''}<div>${body||'<p class="text-muted">Nyhetsinnholdet vises her mens du redigerer.</p>'}</div>`;
  }
  function setNewsPanel(panel,refresh=true){
   const create=panel==='create';
   $('news-overview-panel').hidden=create;$('news-create-panel').hidden=!create;
   $('news-overview-tab').classList.toggle('active',!create);$('news-create-tab').classList.toggle('active',create);
   $('news-overview-tab').setAttribute('aria-selected',String(!create));$('news-create-tab').setAttribute('aria-selected',String(create));
   if(!create){if($('announcement-id').value)resetComposer();else{previewDraft=null;selectedId=null;render()}if(refresh)load()}
  }
  function addElement(type){
   const kind=type==='list'||type==='spreadsheet'?type:type;
   const persistedType=kind==='list'||kind==='spreadsheet'?'text':kind;
   editorElements.push({element_type:persistedType,editor_kind:kind,content:elementDefaults(kind)});
   selectedElementIndex=editorElements.length-1;renderElements();syncDraft();
  }
  function updateSelectedField(field){
   const index=Number(field.dataset.elementIndex);if(!editorElements[index])return;
   const key=field.dataset.elementKey,content=editorElements[index].content||{};
   content[key]=key==='urls'?field.value.split(/\r?\n/).map(v=>v.trim()).filter(Boolean):field.value;
   editorElements[index].content=content;syncDraft();
  }
  $('announcement-add-element').addEventListener('click',()=>addElement($('announcement-element-type').value));
  $('announcement-elements-list').addEventListener('input',e=>{const f=e.target.closest('[data-element-index][data-element-key]');if(f)updateSelectedField(f)});
  $('announcement-elements-list').addEventListener('click',e=>{
   const select=e.target.closest('[data-element-select]');if(select){selectedElementIndex=Number(select.dataset.elementSelect);renderElements();return}
   const remove=e.target.closest('[data-element-remove]');if(remove){const i=Number(remove.dataset.elementRemove);editorElements.splice(i,1);selectedElementIndex=Math.min(selectedElementIndex,Math.max(0,editorElements.length-1));renderElements();syncDraft();return}
   const move=e.target.closest('[data-element-move]');if(move){const i=Number(move.dataset.elementIndex),j=i+Number(move.dataset.elementMove);if(j<0||j>=editorElements.length)return;[editorElements[i],editorElements[j]]=[editorElements[j],editorElements[i]];selectedElementIndex=j;renderElements();syncDraft()}
  });
  $('announcement-selected-element').addEventListener('input',e=>{
   const target=e.target;
   if(target.matches('[data-element-html]')){const el=editorElements[selectedElementIndex];if(el){el.content.html=target.innerHTML;syncDraft()}return}
   const f=target.closest('[data-element-index][data-element-key]');if(f){updateSelectedField(f);return}
   let el=editorElements[selectedElementIndex];if(!el)return;let c=el.content||{};
   if(target.matches('[data-list-index]')){c.items[Number(target.dataset.listIndex)]=target.value;c.html=listHtml(c.items)}
   else if(target.matches('[data-table-head]')){c.headers[Number(target.dataset.tableHead)]=target.value;c.html=tableHtml(c.headers,c.rows)}
   else if(target.matches('[data-table-row][data-table-col]')){c.rows[Number(target.dataset.tableRow)][Number(target.dataset.tableCol)]=target.value;c.html=tableHtml(c.headers,c.rows)}
   el.content=c;syncDraft();
  });
  $('announcement-selected-element').addEventListener('click',e=>{
   const el=editorElements[selectedElementIndex];if(!el)return;const c=el.content||{};
   if(e.target.closest('[data-selected-remove]')){editorElements.splice(selectedElementIndex,1);selectedElementIndex=Math.min(selectedElementIndex,Math.max(0,editorElements.length-1));renderElements();syncDraft()}
   else if(e.target.closest('[data-list-add]')){c.items.push('');c.html=listHtml(c.items);renderElements();syncDraft()}
   else if(e.target.closest('[data-list-remove]')){c.items.splice(Number(e.target.closest('[data-list-remove]').dataset.listRemove),1);c.html=listHtml(c.items);renderElements();syncDraft()}
   else if(e.target.closest('[data-table-row-add]')){c.rows.push(c.headers.map(()=>''));c.html=tableHtml(c.headers,c.rows);renderElements();syncDraft()}
   else if(e.target.closest('[data-table-col-add]')){c.headers.push(`Kolonne ${c.headers.length+1}`);c.rows.forEach(row=>row.push(''));c.html=tableHtml(c.headers,c.rows);renderElements();syncDraft()}
   else if(e.target.closest('[data-table-remove]')){c.rows.splice(Number(e.target.closest('[data-table-remove]').dataset.tableRemove),1);c.html=tableHtml(c.headers,c.rows);renderElements();syncDraft()}
  });
  const editorPriority=()=>{let input=$('announcement-priority'),priority=input.valueAsNumber,valid=AnnouncementRanking.isValidPriority(priority);input.setCustomValidity(valid?'':'Prioritet må være et heltall mellom 1 og 9.');return valid?priority:null};
 const contentToText=value=>{let node=document.createElement('div'),html=String(value??'').replace(/<\/(?:td|th)>/gi,'\t').replace(/<\/(?:tr|div|p|h[2-5]|li|blockquote|pre)>/gi,'\n').replace(/<br\s*\/?>/gi,'\n');node.innerHTML=html;return norm(node.textContent||'')};
 const contentValue=()=>contentEditor()?.innerHTML||'';
 const setContentHtml=value=>{if(contentEditor())contentEditor().innerHTML=typeof value==='string'?value:'';$('announcement-content').value=contentValue()};
 const val=id=>id==='announcement-content'?contentValue():$(id).value;
 const validateContentEditor=()=>{let editor=contentEditor(),feedback=$('announcement-content-feedback'),valid=contentToText(contentValue()).trim()!=='';editor.classList.toggle('is-invalid',!valid);editor.setAttribute('aria-invalid',String(!valid));feedback.hidden=valid;if(!valid)editor.focus();return valid};
const active=a=>!!a?.is_published&&(!a.publish_at||new Date(a.publish_at)<=new Date())&&(!a.expire_at||new Date(a.expire_at)>new Date());
 const excerpt=(s,n)=>{s=contentToText(s).trim();if(s.length<=n)return s;return s.slice(0,n).replace(/\s+\S*$/,'').trim()+'…'};
 function allRows(){let rows=announcements.map(a=>previewDraft&&String(a.id)===String(previewDraft.id)?previewDraft:a);if(previewDraft&&!announcements.some(a=>String(a.id)===String(previewDraft.id)))rows.push(previewDraft);return rows}
  function ranked(){let rows=allRows();if(previewDraft){if(!previewDraft.priorityInputValid&&previewDraft.originalPriority===null)rows=rows.filter(a=>String(a.id)!==String(previewDraft.id));rows=rows.map(a=>String(a.id)===String(previewDraft.id)?{...a,priority:previewDraft.originalPriority??priorityDefault}:a);if(previewDraft.priorityInputValid&&(previewDraft.originalPriority===null||previewDraft.originalPriority!==previewDraft.priority))rows=AnnouncementRanking.planPriorityAssignment(rows,previewDraft.id,previewDraft.priority,{force:true}).items}return AnnouncementRanking(rows.filter(active))}
 function renderPriorityImpact(){let panel=$('announcement-priority-impact');if(!panel)return;let id=val('announcement-id')||'draft-preview',desired=editorPriority();if(desired===null){panel.textContent='Skriv inn et heltall mellom 1 og 9 for å simulere prioriteten.';return}let base=allRows().map(a=>String(a.id)===String(id)?{...a,priority:previewDraft?.originalPriority||priorityDefault}:a),unchanged=previewDraft?.originalPriority===desired,plan=unchanged?{updates:{}}:AnnouncementRanking.planPriorityAssignment(base,id,desired,{force:true}),changed=Object.entries(plan.updates).filter(([rowId])=>String(rowId)!==String(id));if(!changed.length){panel.textContent='Ingen andre saker må flyttes.';return}let byId=new Map(base.map(a=>[String(a.id),a]));panel.textContent=`Ved lagring forskyves ${changed.length} ${changed.length===1?'sak':'saker'}: `+changed.slice(0,4).map(([rowId,next])=>`${byId.get(String(rowId))?.title||'Nyhet'} → ${next}`).join(', ')+(changed.length>4?`, og ${changed.length-4} til.`:'.')}
function date(s){return s?new Date(s).toLocaleDateString('nb-NO',{day:'numeric',month:'short',year:'numeric'}):'Ikke datert'}
function image(a){return a.image_url?`<img class="story-image" src="${esc(a.image_url)}" alt="" loading="lazy">`:''}
  function story(a,place){let tag=types[a.announcement_type]||'Kunngjøring',heading=place==='hero'?'h3':place==='secondary'?'h4':'h5',noImage=a.image_url?'':' no-image',id=esc(a.id);return `<article class="${place}-story${noImage}${String(a.id)===String(selectedId)?' selected-story':''}" data-story-id="${id}">${image(a)}<div class="story-body"><div class="story-content" tabindex="0" role="button" aria-label="Rediger ${esc(a.title)}"><span class="story-kicker">${esc(tag)}${a.is_pinned?' · Festet':''}</span><${heading}>${esc(a.title)}</${heading}>${place!=='archive'?`<p class="story-excerpt">${esc(excerpt(a.content_text||'',place==='hero'?210:105))}</p>`:''}<span class="story-meta">${esc(date(a.publish_at))}${a.product?.name?' · Produkt: '+esc(a.product.name):''}</span></div></div></article>`}
 function reasonFor(a,rows){if(!a)return '';if(!a.is_published)return 'Denne saken er en kladd og vises ikke for leserne.';if(a.publish_at&&new Date(a.publish_at)>new Date())return 'Denne saken er planlagt og vises ikke før publiseringstidspunktet.';if(a.expire_at&&new Date(a.expire_at)<=new Date())return 'Denne saken er utløpt og vises ikke lenger.';let i=rows.findIndex(x=>String(x.id)===String(a.id));return i===0?(a.is_pinned?'Denne saken blir hovedsak fordi den er festet.':`Denne saken blir hovedsak med prioritet ${priorityValue(a.priority)}.`):(i<5?`Denne saken blir sekundærsak med prioritet ${priorityValue(a.priority)}.`:`Denne saken ligger i arkivet fordi ${i} saker rangeres høyere.`)}
 function renderPreview(){let rows=ranked(),p=$('news-preview');p.innerHTML=rows.length?`<header class="public-mast"><b>Nyheter</b><small>Simulert offentlig visning · trykk på en sak for å redigere</small></header><div class="hero-wrap">${story(rows[0],'hero')}</div>${rows.length>1?`<div class="secondary-grid">${rows.slice(1,5).map(a=>story(a,'secondary')).join('')}</div>`:''}${rows.length>5?`<div class="archive-label">Arkiv</div>${rows.slice(5).map(a=>story(a,'archive')).join('')}`:''}`:'<div class="news-empty"><strong>Ingen aktive saker</strong><br><small>Publiserte saker innenfor publiseringstid vises her.</small></div>';p.querySelectorAll('img.story-image').forEach(img=>img.addEventListener('error',()=>{let card=img.closest('[data-story-id]');img.remove();card?.classList.add('no-image')}));p.querySelectorAll('.story-content').forEach(el=>{el.addEventListener('click',()=>edit(el.closest('[data-story-id]').dataset.storyId));el.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();edit(el.closest('[data-story-id]').dataset.storyId)}})});let selected=previewDraft&&String(previewDraft.id)===String(selectedId)?previewDraft:announcements.find(a=>String(a.id)===String(selectedId));let box=$('selected-reason');box.textContent=selected?reasonFor(selected,rows):'';box.hidden=!selected}
 function renderManagement(){let f=$('news-filter').value,now=new Date(),rows=announcements.filter(a=>f==='all'||f==='draft'&&!a.is_published||f==='scheduled'&&a.is_published&&a.publish_at&&new Date(a.publish_at)>now||f==='published'&&active(a));$('news-management').innerHTML=rows.length?rows.map(a=>`<div class="manage-item"><span class="manage-item-title" title="${esc(a.title)}">${a.is_pinned?'▸ ':''}${esc(a.title)}</span><span class="badge-news news-textpill-bg">${Number(a.clicks)||0} klikk</span><span class="badge-news">Prioritet ${priorityValue(a.priority)}</span><button type="button" data-action="edit" data-id="${esc(a.id)}">Rediger</button><button type="button" data-action="pin" data-id="${esc(a.id)}">${a.is_pinned?'Løsne':'Fest'}</button><button type="button" data-action="publish" data-id="${esc(a.id)}">${a.is_published?'Skjul':'Publiser'}</button><button type="button" data-action="delete" data-id="${esc(a.id)}">Slett</button></div>`).join(''):'<div class="news-empty py-4">Ingen saker i dette filteret.</div>'}
function render(){ $('news-preview').classList.toggle('mobile',previewMode==='mobile');$('desktopView').classList.toggle('active',previewMode==='desktop');$('mobileView').classList.toggle('active',previewMode==='mobile');renderPreview();renderManagement()}
function productFromForm(){if(!$('announcement-product-toggle').checked)return null;let id=val('announcement-product-id');if(!id)return null;let option=$('announcement-product-id').selectedOptions[0];return {output_product_id:id,name:option?.textContent||'',discount_percent:Number(val('announcement-discount'))||0,max_quantity:val('announcement-max-qty')?Number(val('announcement-max-qty')):null,expiry_date:val('announcement-product-expiry')||null}}
function localSqlNow(){const d=new Date();return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')+'T'+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')}
function effectivePublishAt(){return val('announcement-publish-at')||previewEffectivePublishAt}
 function updateImagePreview(){let url=val('announcement-image-url').trim(),img=$('announcement-image-preview'),placeholder=$('announcement-image-placeholder'),valid=false;try{let parsed=new URL(url);valid=parsed.protocol==='http:'||parsed.protocol==='https:'}catch(e){}if(!valid){imagePreviewUrl='';imagePreviewFailed=false;img.hidden=true;placeholder.hidden=false;img.removeAttribute('src');return}if(url!==imagePreviewUrl){imagePreviewUrl=url;imagePreviewFailed=false;img.src=url}img.hidden=imagePreviewFailed;placeholder.hidden=!imagePreviewFailed}
     function syncDraft(){let id=val('announcement-id')||'draft-preview',existing=announcements.find(a=>String(a.id)===String(id)),priority=editorPriority();previewDraft={id,originalPriority:existing?priorityValue(existing.priority):null,priorityInputValid:priority!==null,title:val('announcement-title'),content_text:elementsPreviewText()||contentToText(contentValue()),content_html:contentValue(),elements:getEditorElements(),image_url:val('announcement-image-url')||elementsFirstImage(),announcement_type:val('announcement-type'),priority,publish_at:effectivePublishAt(),expire_at:val('announcement-expire-at')||null,is_pinned:$('announcement-is-pinned').checked,is_published:simulatePublished,product:productFromForm()};if(!val('announcement-id')&&!previewDraft.title.trim()&&!previewDraft.content_text.trim()&&!previewDraft.image_url)previewDraft=null;updateImagePreview();renderPriorityImpact();renderComposerPreview();render()}
async function loadProducts(){let s=$('announcement-product-id');if(s.options.length>1)return;let r=await fetch('/api/get_output_products.php'),x=await r.json();s.innerHTML='<option value="">Velg produkt</option>'+(x.products||[]).map(p=>`<option value="${esc(p.id)}">${esc(p.name||'Ukjent')}</option>`).join('')}
async function toggleProduct(){let on=$('announcement-product-toggle').checked;$('product-section').hidden=!on;if(on)await loadProducts();syncDraft()}
function resetProduct(){let s=$('announcement-product-id');s.selectedIndex=0;$('announcement-product-toggle').checked=false;$('product-section').hidden=true;$('announcement-discount').value='';$('announcement-max-qty').value='';$('announcement-product-expiry').value=''}
  function resetComposer(){
   $('announcementForm').reset();$('announcement-id').value='';$('announcement-priority').value=String(priorityDefault);
   $('announcement-publish-at').value=localSqlNow();$('announcement-expire-at').value='';$('announcement-is-pinned').checked=false;
   setContentHtml('');setEditorElements([{element_type:'text',editor_kind:'text',content:{html:''}}]);selectedElementIndex=0;
   resetProduct();$('announcementModalTitle').textContent='Opprett ny sak';previewDraft=null;selectedId=null;previewEffectivePublishAt=$('announcement-publish-at').value;
   $('announcement-content-feedback').hidden=true;contentEditor().classList.remove('is-invalid');$('announcement-image-url').value='';updateImagePreview();syncDraft();
  }
  function openNew(){editRevision+=1;publishIntent=false;simulatePublished=true;previewEffectivePublishAt=localSqlNow();resetComposer();setNewsPanel('create')}
  async function edit(id){let revision=++editRevision,a=announcements.find(x=>String(x.id)===String(id));if(!a)return;if(a.product)await loadProducts();if(revision!==editRevision)return;selectedId=String(id);publishIntent=!!a.is_published;[['title',a.title],['image-url',a.image_url||''],['type',a.announcement_type||'info'],['priority',priorityValue(a.priority)],['publish-at',a.publish_at?.replace(' ','T').slice(0,16)||''],['expire-at',a.expire_at?.replace(' ','T').slice(0,16)||'']].forEach(([k,v])=>$('announcement-'+k).value=v);setContentHtml(a.content_html);setEditorElements(a.elements?.length?a.elements:[{element_type:'text',editor_kind:'text',content:{html:a.content_html||a.content||''}}]);previewEffectivePublishAt=a.publish_at?a.publish_at.replace(' ','T').slice(0,16):localSqlNow();$('announcement-id').value=a.id;$('announcement-is-pinned').checked=!!a.is_pinned;resetProduct();if(a.product){$('announcement-product-toggle').checked=true;$('product-section').hidden=false;$('announcement-product-id').value=a.product.output_product_id;$('announcement-discount').value=a.product.discount_percent||0;$('announcement-max-qty').value=a.product.max_quantity||'';$('announcement-product-expiry').value=a.product.expiry_date||''}$('announcementModalTitle').textContent='Rediger sak';previewDraft=null;simulatePublished=true;syncDraft();setNewsPanel('create')}
 function setSaveBusy(busy){if(saveBusy===busy)return;saveBusy=busy;let form=$('announcementForm');form.setAttribute('aria-busy',String(busy));form.querySelectorAll('input,select,textarea,button').forEach(control=>{if(busy){control.dataset.saveWasDisabled=control.disabled?'1':'0';control.disabled=true}else{control.disabled=control.dataset.saveWasDisabled==='1';delete control.dataset.saveWasDisabled}})}
 function applyPriorityMap(priorities){if(!priorities||typeof priorities!=='object')return false;announcements=announcements.map(a=>Object.prototype.hasOwnProperty.call(priorities,String(a.id))?{...a,priority:priorityValue(priorities[String(a.id)])}:a);render();return true}
 async function saveAnnouncement(publish){
  if(saveBusy)return;
  let priority=editorPriority();
  if(priority===null){$('announcement-priority').reportValidity();return}
  let elements=getEditorElements();
   if(!elements.length){alert('Legg til et innholdselement med tekst eller data før du lagrer.');return}
   if(!$('announcementForm').reportValidity())return;
  publishIntent=!!publish;syncDraft();
  let id=val('announcement-id'),existing=announcements.find(a=>String(a.id)===String(id)),data={id:id||undefined,title:val('announcement-title').trim(),content:legacyContentForSave(existing,elements),elements,image_url:val('announcement-image-url').trim()||elementsFirstImage()||null,announcement_type:val('announcement-type'),priority,is_published:publishIntent,is_pinned:$('announcement-is-pinned').checked,publish_at:effectivePublishAt(),expire_at:val('announcement-expire-at')||null,product:productFromForm()};
  setSaveBusy(true);
  try{
   let r=await fetch(id?'/api/update_announcement.php':'/api/create_announcement.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}),x;
   try{x=await r.json()}catch(e){x={success:false,message:'Ugyldig svar fra server'}}
   if(!r.ok||!x.success){alert(x.message||'Kunne ikke lagre');return}
   if(typeof x.content!=='string'||typeof x.content_html!=='string'||typeof x.content_text!=='string'){alert('Serveren returnerte ikke lagret innhold');return}
   loadRevision+=1;
   let saved={...(existing||{}),id:id||x.announcement_id,title:data.title,image_url:data.image_url,announcement_type:data.announcement_type,priority:data.priority,is_published:data.is_published,is_pinned:data.is_pinned,publish_at:data.publish_at,expire_at:data.expire_at,product:data.product,elements,content:x.content,content_html:x.content_html,content_text:x.content_text,clicks:Number(existing?.clicks)||0,created_at:existing?.created_at||new Date().toISOString(),updated_at:new Date().toISOString()};
   if(existing)announcements=announcements.map(a=>String(a.id)===String(id)?saved:a);else announcements.push(saved);
   previewDraft=null;
   if(!applyPriorityMap(x.priorities))await load();
   setSaveBusy(false);
   if(id){$('announcement-save-status').hidden=true;setNewsPanel('overview',false)}
   else{$('announcement-save-status').textContent='Nyhet opprettet. Skjemaet er nullstilt og klart for neste sak.';$('announcement-save-status').hidden=false;resetComposer();$('announcement-save-status').textContent='Nyhet opprettet. Skjemaet er nullstilt og klart for neste sak.';$('announcement-save-status').hidden=false;setNewsPanel('create')}
  }catch(e){alert('Kunne ikke lagre. Kontroller forbindelsen og prøv igjen.')}
  finally{setSaveBusy(false)}
 }
 async function load(){let revision=++loadRevision;try{let x=await (await fetch('/api/get_announcements.php?show_unpublished=true')).json();if(revision!==loadRevision)return false;if(x.success){announcements=x.announcements.map(a=>({...a,priority:priorityValue(a.priority),clicks:Number(a.clicks)||0}));render();return true}return false}catch(e){if(revision===loadRevision)$('news-preview').innerHTML='<div class="news-empty">Kunne ikke hente nyhetene. Prøv igjen.</div>';return false}}
async function action(action,id){let endpoint=action==='pin'?'/api/toggle_announcement_pin.php':action==='publish'?'/api/toggle_announcement_publish.php':'/api/delete_announcement.php';if(action==='delete'&&!confirm('Slette denne saken?'))return;try{let r=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}),x=await r.json();if(!r.ok||!x.success)return;loadRevision+=1;let item=announcements.find(a=>String(a.id)===String(id));if(action==='delete')announcements=announcements.filter(a=>String(a.id)!==String(id));else if(action==='pin'&&item)item.is_pinned=!item.is_pinned;else if(action==='publish'&&item){item.is_published=!item.is_published;if(item.is_published)item.publish_at=new Date().toISOString().slice(0,19).replace('T',' ')}render()}catch(e){alert('Endringen kunne ikke lagres. Prøv igjen.')}}
  ['announcement-title','announcement-image-url','announcement-type','announcement-priority','announcement-publish-at','announcement-expire-at','announcement-product-id','announcement-discount','announcement-max-qty','announcement-product-expiry'].forEach(id=>$(id).addEventListener('input',syncDraft));contentEditor().addEventListener('input',()=>{validateContentEditor();$('announcement-content').value=contentValue();syncDraft()});document.querySelectorAll('.announcement-editor-toolbar [data-format]').forEach(button=>button.addEventListener('click',()=>{contentEditor().focus();let format=button.dataset.format;if(format==='createLink'){let url=window.prompt('Lenkeadresse (https:// eller mailto:)');if(url)document.execCommand('createLink',false,url)}else if(format==='formatBlock')document.execCommand(format,false,button.dataset.value);else document.execCommand(format,false,null);$('announcement-content').value=contentValue();syncDraft()}));
  $('announcement-image-preview').addEventListener('error',()=>{imagePreviewFailed=true;$('announcement-image-preview').hidden=true;$('announcement-image-placeholder').hidden=false});
  $('newAnnouncementButton').addEventListener('click',openNew);
  $('news-overview-tab').addEventListener('click',()=>setNewsPanel('overview'));
  $('news-create-tab').addEventListener('click',()=>{if($('news-create-panel').hidden&&!$('announcement-id').value)openNew();else setNewsPanel('create')});
  $('cancelAnnouncement').addEventListener('click',()=>setNewsPanel('overview'));
  $('compose-preview-desktop').addEventListener('click',()=>{composePreviewMode='desktop';$('compose-preview-desktop').classList.add('active');$('compose-preview-mobile').classList.remove('active');renderComposerPreview()});
  $('compose-preview-mobile').addEventListener('click',()=>{composePreviewMode='mobile';$('compose-preview-mobile').classList.add('active');$('compose-preview-desktop').classList.remove('active');renderComposerPreview()});
  $('refreshAnnouncements').addEventListener('click',load);$('desktopView').addEventListener('click',()=>{previewMode='desktop';render()});$('mobileView').addEventListener('click',()=>{previewMode='mobile';render()});$('news-filter').addEventListener('change',renderManagement);$('saveDraftButton').addEventListener('click',()=>saveAnnouncement(false));$('publishButton').addEventListener('click',()=>saveAnnouncement(true));$('news-management').addEventListener('click',e=>{let b=e.target.closest('[data-action]');if(!b)return;if(b.dataset.action==='edit')edit(b.dataset.id);else action(b.dataset.action,b.dataset.id)});
  document.addEventListener('shown.bs.tab',e=>{if(e.target.dataset.bsTarget==='#content-announcements')load()});
  document.querySelectorAll('#announcementForm input,#announcementForm select').forEach(field=>field.addEventListener('input',syncDraft));
  document.querySelectorAll('#announcementForm select,#announcementForm input[type="checkbox"]').forEach(field=>field.addEventListener('change',()=>{if(field.id==='announcement-product-toggle')toggleProduct();else syncDraft()}));
  $('announcementForm').addEventListener('submit',e=>e.preventDefault());
  resetComposer();setNewsPanel('overview');
})();
</script>