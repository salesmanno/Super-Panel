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
.news-compose-grid{grid-template-columns:minmax(0,1.65fr) minmax(285px,.75fr);align-items:start}
.news-compose-main{display:block}
.news-compose-preview-card{border:0;background:transparent;box-shadow:none;overflow:visible}
.news-compose-preview-card>.news-compose-card-head{border:0;padding:.2rem 0 .8rem}
.news-compose-reader-wrap{padding:clamp(.6rem,2vw,1.25rem);background:#e6e3dc;border-radius:14px}
.news-compose-reader{box-sizing:border-box;display:flex;flex-direction:column;width:100%;max-width:820px;min-height:1120px;margin:0 auto;padding:clamp(1.4rem,5vw,4rem);border-radius:2px;background:#fffefa;color:#263b43;box-shadow:0 10px 30px rgba(40,48,48,.12);font-family:Georgia,"Times New Roman",serif;overflow-wrap:anywhere}.news-compose-reader>*{flex-shrink:0}
.news-compose-reader.mobile{max-width:390px;min-height:0;padding:1.35rem}
.nc-paper-header{display:flex;align-items:end;justify-content:space-between;gap:1rem;padding-bottom:.8rem;margin-bottom:1.8rem;border-bottom:1px solid #cad4cf;color:#46615d;font:700 .69rem/1.4 system-ui,sans-serif;letter-spacing:.11em;text-transform:uppercase}
.nc-paper-title{width:100%;margin:.45rem 0 1rem;border:0;background:transparent;color:#173b42;font:700 clamp(2rem,4.4vw,3.5rem)/1.08 Georgia,serif;outline:0}
.nc-paper-title:focus{box-shadow:0 2px #287b72}
.nc-paper-byline{margin:0 0 2rem;color:#74817d;font:.78rem/1.5 system-ui,sans-serif}
.nc-paper-block{position:relative;margin:1.3rem 0 2rem;padding:.2rem .2rem .2rem 1rem;border-left:2px solid transparent;transition:border-color .16s}
.nc-paper-block.selected,.nc-paper-block:focus-within{border-left-color:#287b72}
.nc-paper-block [contenteditable=true]{outline:0;overflow-wrap:anywhere}
.nc-paper-block [contenteditable=true]:focus{background:rgba(40,123,114,.035)}
.nc-paper-text{min-height:1.5em;font:1.02rem/1.75 Georgia,serif}
.nc-paper-text p{margin:0 0 .8em}.nc-paper-text p:last-child{margin-bottom:0}
.nc-paper-list{padding-left:1.3rem;font:1rem/1.8 Georgia,serif}
.nc-paper-table{width:100%;border-collapse:collapse;font:.88rem/1.5 system-ui,sans-serif}
.nc-paper-table th,.nc-paper-table td{min-width:3rem;padding:.65rem .55rem;border-bottom:1px solid #d9e0dc;text-align:left}
.nc-paper-table th{background:#edf3ef;color:#355650;font-weight:700}
.nc-paper-image,.nc-paper-carousel{position:relative;margin:0}
.nc-paper-image img,.nc-paper-carousel img{display:block;width:100%;max-height:510px;object-fit:contain;background:#eef0ed}
.nc-paper-caption{display:block;margin-top:.5rem;color:#73817c;font:italic .83rem/1.5 Georgia,serif}
.nc-paper-empty{display:grid;min-height:145px;place-items:center;padding:1rem;border:1px dashed #b9c9c2;background:#f4f6f1;color:#687b75;font:.84rem/1.5 system-ui,sans-serif;text-align:center}
.nc-paper-error{color:#9a5141}
.nc-paper-carousel-controls{display:flex;justify-content:space-between;align-items:center;margin-top:.6rem;font:.78rem system-ui,sans-serif;color:#556a64}
.nc-paper-carousel-controls button{border:1px solid #d2ddd7;border-radius:999px;background:#fff;color:#315c55;padding:.3rem .7rem}
.nc-paper-video{position:relative;width:100%;aspect-ratio:16/9;background:#e8ece9}
.nc-paper-video iframe,.nc-paper-video video{position:absolute;inset:0;width:100%;height:100%;border:0}
.nc-paper-insert{margin-top:3rem;padding-top:1.1rem;border-top:1px solid #dce4df}
.nc-paper-insert>button{width:100%;min-height:48px;border:1px dashed #86a69a;border-radius:8px;background:#f4f7f2;color:#315f56;font:700 .9rem system-ui,sans-serif}
.nc-type-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem;margin-top:.6rem}
.nc-type-picker button{min-height:42px;border:1px solid #d5e0da;border-radius:7px;background:#fff;color:#37544d;font:600 .82rem system-ui,sans-serif}
.nc-type-picker button:hover{background:#edf4ef;border-color:#8cad9f}
.news-compose-element{touch-action:pan-y}
.news-compose-drag{display:grid;place-items:center;width:26px;height:32px;border:0;background:transparent;color:#71827c;cursor:grab;touch-action:none}
.news-compose-element[aria-grabbed=true]{opacity:.45}
.news-compose-media-editor{display:grid;gap:.65rem}
.news-compose-media-preview{width:100%;max-height:170px;object-fit:contain;background:#f0f2ee;border-radius:6px}
.news-compose-author{padding:.65rem .75rem;border-radius:8px;background:#eff4f0;color:#42645a;font-size:.78rem}
.news-compose-schedule[hidden],.nc-type-picker[hidden]{display:none!important}
@media(max-width:850px){.news-compose-grid{grid-template-columns:1fr}.news-compose-main{grid-row:1}.news-compose-side{grid-row:2}.news-compose-reader{min-height:0}}
@media(max-width:520px){.nc-type-picker{grid-template-columns:repeat(2,minmax(0,1fr))}.news-compose-element-actions{gap:0}.news-compose-element-actions button{padding:.35rem}}
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
.news-compose-grid{grid-template-columns:minmax(0,1.65fr) minmax(285px,.75fr);align-items:start}.news-compose-main{display:block}
.news-compose-preview-card{border:0;background:transparent;box-shadow:none;overflow:visible}.news-compose-preview-card>.news-compose-card-head{border:0;padding:.2rem 0 .8rem}
.news-compose-reader-wrap{padding:clamp(.6rem,2vw,1.25rem);background:#e6e3dc;border-radius:14px}
.news-compose-reader{box-sizing:border-box;width:100%;max-width:820px;min-height:1120px;margin:0 auto;padding:clamp(1.4rem,5vw,4rem);border-radius:2px;background:#fffefa;color:#263b43;box-shadow:0 10px 30px rgba(40,48,48,.12);font-family:Georgia,"Times New Roman",serif;overflow-wrap:anywhere}
.news-compose-reader.mobile{max-width:390px;min-height:0;padding:1.35rem}
.nc-paper-header{display:flex;align-items:end;justify-content:space-between;gap:1rem;padding-bottom:.8rem;margin-bottom:1.8rem;border-bottom:1px solid #cad4cf;color:#46615d;font:700 .69rem/1.4 system-ui,sans-serif;letter-spacing:.11em;text-transform:uppercase}
.nc-paper-title{display:block;width:100%;min-height:1.1em;resize:none;overflow:hidden;margin:.45rem 0 1rem;padding:0;border:0;background:transparent;color:#173b42;font:700 clamp(2rem,4.4vw,3.5rem)/1.08 Georgia,serif;outline:0}.nc-paper-title:focus{box-shadow:0 2px #287b72}
.nc-inline-tools{display:flex;gap:.25rem;margin:0 0 .5rem;font:600 .75rem system-ui,sans-serif}.nc-inline-tools button{min-width:30px;min-height:28px;border:1px solid #d7e0db;border-radius:5px;background:#f8faf7;color:#45655b}.nc-inline-tools button:hover{background:#eaf2ec}
.nc-paper-byline{margin:0 0 2rem;color:#74817d;font:.78rem/1.5 system-ui,sans-serif}
.nc-paper-block{position:relative;margin:1.3rem 0 2rem;padding:.2rem .2rem .2rem 1rem;border-left:2px solid transparent;transition:border-color .16s}.nc-paper-block.selected,.nc-paper-block:focus-within{border-left-color:#287b72}
.nc-paper-block [contenteditable=true]{outline:0;overflow-wrap:anywhere}.nc-paper-block [contenteditable=true]:focus{background:rgba(40,123,114,.035)}
.nc-paper-text{height:auto;max-height:none;overflow:visible;min-height:1.5em;font:1.02rem/1.75 Georgia,serif}.nc-paper-text:empty:before{content:"Skriv teksten din her …";color:#91a09a;pointer-events:none}.nc-paper-text p{margin:0 0 .8em}.nc-paper-text p:last-child{margin-bottom:0}
.nc-paper-list{padding-left:1.3rem;font:1rem/1.8 Georgia,serif}
.nc-paper-table{width:100%;border-collapse:collapse;font:.88rem/1.5 system-ui,sans-serif}.nc-paper-table th,.nc-paper-table td{min-width:3rem;padding:.65rem .55rem;border-bottom:1px solid #d9e0dc;text-align:left}.nc-paper-table th{background:#edf3ef;color:#355650;font-weight:700}
.nc-paper-image,.nc-paper-carousel{position:relative;margin:0}.nc-paper-image img,.nc-paper-carousel img{display:block;width:100%;max-height:510px;object-fit:contain;background:#eef0ed}
.nc-paper-caption{display:block;margin-top:.5rem;color:#73817c;font:italic .83rem/1.5 Georgia,serif}
.nc-paper-empty{display:grid;min-height:145px;place-items:center;padding:1rem;border:1px dashed #b9c9c2;background:#f4f6f1;color:#687b75;font:.84rem/1.5 system-ui,sans-serif;text-align:center}.nc-paper-error{color:#9a5141}
.nc-paper-carousel-controls{display:flex;justify-content:space-between;align-items:center;margin-top:.6rem;font:.78rem system-ui,sans-serif;color:#556a64}.nc-paper-carousel-controls button{border:1px solid #d2ddd7;border-radius:999px;background:#fff;color:#315c55;padding:.3rem .7rem}
.nc-paper-video{position:relative;width:100%;aspect-ratio:16/9;background:#e8ece9}.nc-paper-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.nc-paper-insert{margin-top:auto;padding-top:2rem}.nc-paper-insert>button{width:100%;min-height:48px;border:1px dashed #86a69a;border-radius:8px;background:#f4f7f2;color:#315f56;font:700 .9rem system-ui,sans-serif}
.nc-type-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem;margin-top:.6rem}.nc-type-picker button{min-height:42px;border:1px solid #d5e0da;border-radius:7px;background:#fff;color:#37544d;font:600 .82rem system-ui,sans-serif}.nc-type-picker button:hover{background:#edf4ef;border-color:#8cad9f}
.news-compose-element{touch-action:pan-y}.news-compose-drag{display:grid;place-items:center;width:26px;height:32px;border:0;background:transparent;color:#71827c;cursor:grab;touch-action:none}.news-compose-element[aria-grabbed=true]{opacity:.45}
.news-compose-media-editor{display:grid;gap:.65rem}.news-compose-media-preview{width:100%;max-height:170px;object-fit:contain;background:#f0f2ee;border-radius:6px}.news-compose-author{padding:.65rem .75rem;border-radius:8px;background:#eff4f0;color:#42645a;font-size:.78rem}
.news-compose-schedule[hidden],.nc-type-picker[hidden]{display:none!important}
@media(max-width:850px){.news-compose-grid{grid-template-columns:1fr}.news-compose-main{grid-row:1}.news-compose-side{grid-row:2}.news-compose-reader{min-height:0}}
@media(max-width:520px){.nc-type-picker{grid-template-columns:repeat(2,minmax(0,1fr))}.news-compose-element-actions{gap:0}.news-compose-element-actions button{padding:.35rem}}
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
<style>
.news-compose-grid{grid-template-columns:minmax(0,1.65fr) minmax(285px,.75fr);align-items:start}
.news-compose-main{display:block;min-width:0}
.news-compose-side{display:grid;align-content:start;gap:1rem;min-width:0}
.news-compose-preview-card{border:0;background:transparent;box-shadow:none;overflow:visible}
.news-compose-preview-card>.news-compose-card-head{border:0;padding:.2rem 0 .8rem}
.news-compose-reader-wrap{padding:clamp(.6rem,2vw,1.25rem);background:#e6e3dc;border-radius:14px}
.news-compose-reader{box-sizing:border-box;width:100%;max-width:820px;min-height:1120px;margin:0 auto;padding:clamp(1.4rem,5vw,4rem);border-radius:2px;background:#fffefa;color:#263b43;box-shadow:0 10px 30px rgba(40,48,48,.12);font-family:Georgia,"Times New Roman",serif;overflow-wrap:anywhere}
.news-compose-reader.mobile{max-width:390px;min-height:0;padding:1.35rem}
.news-compose-reader img{max-width:100%;max-height:510px;object-fit:contain;border-radius:0}
.nc-paper-header{display:flex;align-items:end;justify-content:space-between;gap:1rem;padding-bottom:.8rem;margin-bottom:1.8rem;border-bottom:1px solid #cad4cf;color:#46615d;font:700 .69rem/1.4 system-ui,sans-serif;letter-spacing:.11em;text-transform:uppercase}
.nc-paper-title{width:100%;margin:.45rem 0 1rem;border:0;background:transparent;color:#173b42;font:700 clamp(2rem,4.4vw,3.5rem)/1.08 Georgia,serif;outline:0}.nc-paper-title:focus{box-shadow:0 2px #287b72}
.nc-paper-byline{margin:0 0 2rem;color:#74817d;font:.78rem/1.5 system-ui,sans-serif}
.nc-paper-block{position:relative;margin:1.3rem 0 2rem;padding:.2rem .2rem .2rem 1rem;border-left:2px solid transparent;transition:border-color .16s}.nc-paper-block.selected,.nc-paper-block:focus-within{border-left-color:#287b72}
.nc-paper-block [contenteditable=true]{outline:0;overflow-wrap:anywhere}.nc-paper-block [contenteditable=true]:focus{background:rgba(40,123,114,.035)}
.nc-paper-text{min-height:1.5em;font:1.02rem/1.75 Georgia,serif}.nc-paper-text p{margin:0 0 .8em}.nc-paper-text p:last-child{margin-bottom:0}
.nc-paper-list{padding-left:1.3rem;font:1rem/1.8 Georgia,serif}
.nc-paper-table{width:100%;border-collapse:collapse;font:.88rem/1.5 system-ui,sans-serif}.nc-paper-table th,.nc-paper-table td{min-width:3rem;padding:.65rem .55rem;border-bottom:1px solid #d9e0dc;text-align:left}.nc-paper-table th{background:#edf3ef;color:#355650;font-weight:700}
.nc-paper-image,.nc-paper-carousel{position:relative;margin:0}.nc-paper-image img,.nc-paper-carousel img{display:block;width:100%;max-height:510px;object-fit:contain;background:#eef0ed}
.nc-paper-caption{display:block;margin-top:.5rem;color:#73817c;font:italic .83rem/1.5 Georgia,serif}
.nc-paper-empty{display:grid;min-height:145px;place-items:center;padding:1rem;border:1px dashed #b9c9c2;background:#f4f6f1;color:#687b75;font:.84rem/1.5 system-ui,sans-serif;text-align:center}.nc-paper-error{color:#9a5141}
.nc-paper-carousel-controls{display:flex;justify-content:space-between;align-items:center;margin-top:.6rem;font:.78rem system-ui,sans-serif;color:#556a64}.nc-paper-carousel-controls button{border:1px solid #d2ddd7;border-radius:999px;background:#fff;color:#315c55;padding:.3rem .7rem}
.nc-paper-video{position:relative;width:100%;aspect-ratio:16/9;background:#e8ece9}.nc-paper-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.nc-paper-insert{margin-top:3rem;padding-top:1.1rem;border-top:1px solid #dce4df}.nc-paper-insert>button{width:100%;min-height:48px;border:1px dashed #86a69a;border-radius:8px;background:#f4f7f2;color:#315f56;font:700 .9rem system-ui,sans-serif}
.nc-type-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem;margin-top:.6rem}.nc-type-picker button{min-height:42px;border:1px solid #d5e0da;border-radius:7px;background:#fff;color:#37544d;font:600 .82rem system-ui,sans-serif}.nc-type-picker button:hover{background:#edf4ef;border-color:#8cad9f}
.news-compose-element{touch-action:pan-y}.news-compose-drag{display:grid;place-items:center;width:26px;height:32px;border:0;background:transparent;color:#71827c;cursor:grab;touch-action:none}.news-compose-element[aria-grabbed=true]{opacity:.45}
.news-compose-media-editor{display:grid;gap:.65rem}.news-compose-media-preview{width:100%;max-height:170px;object-fit:contain;background:#f0f2ee;border-radius:6px}.news-compose-author{padding:.65rem .75rem;border-radius:8px;background:#eff4f0;color:#42645a;font-size:.78rem}
.news-compose-schedule[hidden],.nc-type-picker[hidden]{display:none!important}
.nc-mobile-inspector{display:none}
@media(max-width:850px){.news-compose-grid{grid-template-columns:1fr}.news-compose-side{grid-row:2}.news-compose-main{grid-row:1}.news-compose-reader{min-height:0}.nc-mobile-inspector{display:inline-block;margin:.5rem 0}.news-compose-reader-wrap{padding:.5rem}}
@media(max-width:575px){.nc-type-picker{grid-template-columns:repeat(2,minmax(0,1fr))}.news-compose-reader{padding:.9rem}.news-compose-footer{position:sticky;bottom:0;background:#f5f8fa;padding:.7rem 0;z-index:2}}
</style>
<style>
 .news-edit-cards{display:grid;gap:.45rem;margin:.55rem 0 0}
.news-edit-card{display:grid;grid-template-columns:minmax(110px,.72fr) minmax(0,1.28fr);min-width:0;overflow:hidden;border:1px solid #dce5e7;border-radius:12px;background:#fff;color:#173b42;box-shadow:0 4px 15px rgba(23,50,77,.04);transition:box-shadow .15s,transform .15s}
.news-edit-card:hover{transform:translateY(-2px);box-shadow:0 5px 16px #173b4218}.news-edit-card:focus-within{outline:3px solid #087f8c;outline-offset:2px}
.news-edit-card-open{display:flex;min-width:0;flex-direction:column;align-items:stretch;padding:0;border:0;background:transparent;text-align:left;color:inherit}
.news-edit-card-open>img,.news-edit-card-image{width:100%;height:100%;min-height:148px;max-height:220px;object-fit:cover;background:#e8efeb}
.news-edit-card-image{display:grid;place-items:center;color:#6b8d82;font-size:.78rem}
.news-edit-card-body{display:grid;align-content:start;gap:.48rem;padding:.85rem;min-width:0}
.news-edit-card small{color:#60758a}.news-edit-card p{margin:0;color:#526963;overflow-wrap:anywhere}
.news-edit-card-title{font-weight:750;line-height:1.25}.news-edit-card-excerpt{display:-webkit-box;overflow:hidden;color:#526963;font-size:.82rem;line-height:1.4;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.news-edit-card-meta{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.2rem}
.news-edit-card-meta .badge-news{display:inline-flex;align-items:center;gap:.25rem}
.news-edit-card-actions{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:.35rem;padding:.55rem .7rem;border-top:1px solid #e8edee;background:#f8fafb}
.news-edit-card-actions button{border:1px solid #d6e1e4;border-radius:7px;background:#fff;color:#385565;padding:.32rem .55rem;font-size:.74rem;font-weight:650}
.news-edit-card-actions button:hover{border-color:#8cad9f;background:#eef6f3}
.news-edit-card-actions button[data-action=delete]{margin-left:auto;color:#a34343}
.news-home-preview{--site-blue:#d9eafb;--site-ink:#183b70;--site-head:#2d5598;margin:1.1rem 0 1.4rem;border:1px solid #c7d8e5;border-radius:13px;overflow:hidden;background:var(--site-blue);box-shadow:0 5px 18px rgba(30,62,97,.07)}
.news-home-top{min-height:64px;display:flex;align-items:center;gap:.8rem;padding:.8rem clamp(1rem,3vw,2.2rem);background:var(--site-head);color:#fff}
.news-home-logo{width:40px;height:40px;flex:none;object-fit:contain;border-radius:50%}.news-home-name{font-weight:750;font-size:1.06rem;letter-spacing:-.015em}.news-home-nav{display:flex;gap:1.7rem;align-items:center;min-height:43px;padding:0 clamp(1rem,3vw,2.2rem);background:#fff;color:#56616b;border-bottom:1px solid #e5e8e9;font-size:.76rem}
.news-home-nav span:nth-child(2){color:#243c62;font-weight:750;position:relative}.news-home-nav span:nth-child(2):after{position:absolute;content:"";height:2px;background:#345a9c;bottom:-.62rem;left:0;right:0}
.news-home-content{padding:clamp(.8rem,2.5vw,1.5rem)}.news-home-hero{display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:clamp(1rem,3vw,2.5rem);padding:.4rem 0 1.35rem;border-bottom:1px solid #c2d6e7;cursor:pointer}
.news-home-hero.no-image{grid-template-columns:1fr}.news-home-hero-copy{min-width:0}.news-home-hero h3{margin:.25rem 0 .5rem;color:#171e25;font-size:clamp(1.3rem,2.8vw,2.05rem);line-height:1.08;letter-spacing:-.035em}.news-home-hero .story-excerpt{font-size:.87rem;color:#30475c}.news-home-hero img{width:100%;height:clamp(155px,25vw,250px);object-fit:cover;background:#c9dcec}.news-home-rest{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;padding:1rem 0}.news-home-card{min-width:0;cursor:pointer}.news-home-card img{display:block;width:100%;height:clamp(100px,14vw,155px);object-fit:cover;background:#c9dcec;margin-bottom:.55rem}.news-home-card h4{margin:.2rem 0;color:#18242d;font-size:.98rem;line-height:1.2}.news-home-card .story-meta{display:block;margin-top:.35rem}.news-home-footer{min-height:70px;display:flex;align-items:center;padding:.8rem clamp(1rem,3vw,2.2rem);background:var(--site-head);color:#fff;font-size:.8rem;font-weight:650}.news-home-empty{padding:2.8rem 1rem;text-align:center;color:#52697d;font-size:.88rem}.news-home-tools{display:flex;align-items:center;justify-content:space-between;gap:.6rem;margin:1.15rem 0 .35rem}.news-home-tools strong{font-size:.78rem;letter-spacing:.07em;text-transform:uppercase;color:#536b7a}.news-management-note{font-size:.74rem;color:#728493;margin:.2rem 0 .5rem}.news-edit-card-actions button[data-priority-move]{font-size:.9rem;padding:.25rem .5rem}.news-edit-card-actions button:disabled{opacity:.38;cursor:not-allowed}
.news-home-preview .story-kicker{color:#426da9}.news-home-preview .story-meta{color:#586d7e}
.news-home-preview .story-meta{display:block;margin-top:.5rem;font-size:.76rem}
.news-home-top,.news-home-footer{color:var(--site-head-ink,#fff)}
.news-home-top{justify-content:var(--site-header-position,flex-start);text-align:var(--site-header-align,left)}
.news-home-name{min-width:0;overflow-wrap:anywhere}.news-home-nav{justify-content:var(--site-nav-position,center)}
.news-home-top[data-layout="centered"]{flex-direction:column;align-items:var(--site-header-position,center)}
.news-home-footer small{display:block;margin-top:.35rem;font-weight:400}
.news-admin-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.65rem;flex-wrap:wrap;margin-top:1rem}
.news-admin-toolbar input{flex:1 1 220px;max-width:420px}
.news-admin-heading{display:flex;gap:1rem;justify-content:space-between;align-items:center;flex-wrap:wrap}
.news-subtabs{box-sizing:border-box;width:100%;min-width:0;max-width:100%;overflow-x:auto}
#news-edit-back{margin-bottom:1rem}
@media(max-width:575px){.news-edit-cards{grid-template-columns:1fr}.news-edit-card{grid-template-columns:100px minmax(0,1fr)}.news-edit-card-open>img,.news-edit-card-image{min-height:118px;max-height:160px}.news-edit-card-body{padding:.65rem;gap:.35rem}.news-edit-card-actions{gap:.3rem;padding:.45rem}.news-edit-card-actions button{padding:.3rem .42rem}}
@media(max-width:700px){.news-home-hero{grid-template-columns:1fr;gap:.8rem}.news-home-hero img{height:auto;max-height:250px;aspect-ratio:16/9}.news-home-rest{grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.news-home-card img{height:auto;aspect-ratio:1.55}.news-home-top{min-height:58px}.news-home-nav{gap:1.2rem}.news-home-tools{align-items:flex-start;flex-direction:column}}
@media(max-width:390px){.news-home-rest{grid-template-columns:1fr}.news-home-card{display:grid;grid-template-columns:105px minmax(0,1fr);gap:.6rem;align-items:start}.news-home-card img{grid-row:span 2;margin:0}.news-home-card h4{font-size:.9rem}.news-home-nav{gap:.9rem}}
.news-preview-actions{display:flex;align-items:center;flex-wrap:wrap;gap:.35rem;margin-top:.7rem;padding-top:.55rem;border-top:1px solid #bfd2e3}.news-preview-actions .preview-clicks{margin-right:auto;color:#536b7a;font-size:.72rem}.news-preview-actions button{border:1px solid #b9cddd;border-radius:6px;background:#f8fbfd;color:#244e70;padding:.28rem .5rem;font-size:.72rem;font-weight:650;cursor:pointer}.news-preview-actions button:hover{background:#fff;border-color:#6d95b3}.news-preview-actions button:disabled{opacity:.45;cursor:not-allowed}.news-home-preview [data-edit-story]:focus-visible{outline:3px solid #e5a28e;outline-offset:2px}.news-home-hero .news-preview-actions{grid-column:1/-1}.news-home-card .news-preview-actions{grid-column:1/-1}.news-priority-live{min-height:1.1rem;margin:.25rem 0;color:#375e57;font-size:.75rem}
 .news-admin-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,330px);align-items:start;gap:1rem;margin-top:1rem}
 .news-admin-preview-column{min-width:0}.news-admin-sidebar{position:sticky;top:.75rem;min-width:0;padding:.8rem;border:1px solid #d8e3e5;border-radius:12px;background:#f8faf8}
 .news-admin-sidebar-head{display:flex;justify-content:space-between;align-items:baseline;gap:.5rem;margin:0 0 .35rem}
 .news-admin-sidebar-head strong{font-size:.9rem;color:#173b42}.news-admin-sidebar-head span{font-size:.7rem;color:#718492;white-space:nowrap}
 .news-admin-sidebar .news-admin-toolbar{margin:.5rem 0;justify-content:flex-start}.news-admin-sidebar .news-admin-toolbar select{flex:1;min-width:0}
 .news-edit-card{display:grid;grid-template-columns:minmax(0,1fr);gap:0;border-radius:9px;box-shadow:none}
 .news-edit-card:hover{transform:none;box-shadow:0 3px 10px rgba(23,50,77,.08)}
 .news-edit-card-open{display:block;min-width:0;padding:.58rem .62rem .3rem}
 .news-edit-card-open>img,.news-edit-card-image{display:none}
 .news-edit-card-body{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:start;gap:.25rem .45rem;padding:0}
 .news-edit-card-body>small:first-child,.news-edit-card-title,.news-edit-card-excerpt,.news-edit-card-meta{grid-column:1/-1}
 .news-edit-card-body>small:first-child{font-size:.65rem;color:#718492}
 .news-edit-card-title{font-size:.79rem;line-height:1.25}
 .news-edit-card-excerpt{font-size:.7rem;-webkit-line-clamp:1}
 .news-edit-card-body>small:nth-of-type(2){font-size:.66rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
 .news-edit-card-meta{gap:.25rem;margin-top:.1rem}
 .news-edit-card-meta .badge-news{padding:.12rem .34rem;font-size:.62rem}
 .news-edit-card-actions{gap:.25rem;padding:.35rem .45rem}
 .news-edit-card-actions button{padding:.24rem .38rem;font-size:.67rem}
 .news-edit-card-actions button[data-card-action=delete]{margin-left:auto}
 .news-home-top{box-sizing:border-box;height:var(--site-header-height,64px);min-height:0;padding:0 clamp(.7rem,3vw,2.2rem);overflow:hidden}
 .news-home-top[data-layout="centered"]{gap:.15rem}
 .news-home-logo{width:auto;height:var(--site-logo-height,40px);max-width:min(34%,180px);max-height:calc(var(--site-header-height,64px) - 8px);object-fit:contain;border-radius:var(--site-radius,10px)}
 .news-home-name{max-width:100%;font-size:var(--site-header-font-size,1.06rem);line-height:1.15;max-height:1.15em;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical}
 .news-home-top[data-layout="centered"] .news-home-logo{height:var(--site-logo-height,40px);max-height:calc(var(--site-header-height,80px) - var(--site-header-font-size,20px) * 1.15 - 4px)}
 .news-home-preview{font-family:var(--site-font-family,Georgia,serif);border-radius:var(--site-radius,13px)}
 .news-home-preview .news-home-top,.news-home-preview .news-home-footer{border-radius:0}
 .news-home-preview img{border-radius:var(--site-radius,5px)}
 .news-home-preview .news-home-nav,.news-home-preview .news-home-content{font-family:inherit}
 .news-admin-sidebar .news-edit-cards .alert{margin:.35rem 0}
 .news-sidebar-disclosure{min-width:0;position:sticky;top:.75rem}.news-sidebar-disclosure>summary{display:none}
 @media(max-width:900px){.news-admin-layout{grid-template-columns:minmax(0,1fr) minmax(230px,285px);gap:.7rem}}
 @media(max-width:700px){.news-admin-layout{grid-template-columns:1fr}.news-sidebar-disclosure{position:static;grid-row:1}.news-sidebar-disclosure>summary{display:list-item;cursor:pointer;padding:.8rem 1rem;background:#edf5f3;border:1px solid #c8dcd6;border-radius:9px;color:#173b42;font-weight:650}.news-admin-sidebar{position:static;margin-top:.5rem}.news-admin-preview-column{grid-row:2}.news-edit-card{grid-template-columns:minmax(0,1fr)}}
 @media(max-width:575px){.news-admin-sidebar{padding:.65rem}.news-edit-card-open{padding:.52rem}.news-edit-card-actions{gap:.25rem}.news-edit-card-actions button{padding:.3rem .42rem}}
 @media(max-width:700px){.news-home-top{height:var(--site-header-height,58px)}}
</style>
<div class="container-fluid py-3">
<nav class="news-subtabs" role="tablist" aria-label="News">
 <button type="button" class="news-subtab active" id="news-edit-tab" data-news-panel="news-edit-panel" role="tab" aria-selected="true">Nyhetsadministrasjon</button>
 <button type="button" class="news-subtab" id="news-create-tab" data-news-panel="news-create-panel" role="tab" aria-selected="false">Opprett ny sak</button>
</nav>
<div id="news-edit-panel" class="news-subpanel" hidden>
   <div id="news-edit-list">
  <header class="news-admin-heading"><div><h2>Nyhetsadministrasjon</h2><p class="text-muted mb-0">Saker, klikk og publiseringsstatus samlet på ett sted.</p></div><button type="button" class="btn btn-outline-secondary btn-sm" id="news-edit-refresh">Oppdater</button></header>
    <div class="news-admin-layout">
     <div class="news-admin-preview-column"><section class="news-home-preview" id="news-home-preview" aria-label="Forhåndsvis offentlig hjemmeside"></section></div>
     <details class="news-sidebar-disclosure" id="news-sidebar-disclosure" open><summary>Administrer nyhetssaker</summary><aside class="news-admin-sidebar" aria-label="Administrer alle nyhetssaker">
      <div class="news-admin-sidebar-head"><strong>Alle nyhetssaker</strong><span id="news-sidebar-count"></span></div>
      <p class="news-management-note">Bare publiserte saker som er innenfor dato vises på forsiden. Velg en sak for å redigere.</p>
      <div id="news-priority-live" class="news-priority-live" role="status" aria-live="polite"></div>
      <div class="news-admin-toolbar"><label for="news-admin-filter">Vis</label><select id="news-admin-filter" class="form-select form-select-sm"><option value="all">Alle saker</option><option value="visible">Synlige saker</option><option value="hidden">Skjulte saker</option></select></div>
      <div id="news-edit-cards" class="news-edit-cards" aria-live="polite">Henter saker …</div>
     </aside></details>
    </div>
 </div>
 <div id="news-edit-workspace" hidden><button type="button" class="btn btn-outline-secondary btn-sm" id="news-edit-back">← Tilbake til nyhetsadministrasjon</button><div id="news-edit-editor-host"></div></div>
</div>
<div id="news-create-host">
<div id="news-create-panel" class="news-subpanel" hidden>
  <header class="news-compose-heading"><div><span class="news-compose-kicker">NEWS · REDAKTØR</span><h2 id="announcementModalTitle">Opprett ny sak</h2><p>Rediger ferdig artikkel direkte på arket.</p></div><span class="news-compose-preview-badge">Én sammenhengende redigeringsflate</span></header>
 <div id="announcement-save-status" class="alert alert-success py-2" role="status" aria-live="polite" hidden></div>
 <form id="announcementForm">
  <input type="hidden" id="announcement-id">
  <div class="news-compose-grid">
   <section class="news-compose-main">
     <a class="nc-mobile-inspector" href="#news-compose-inspector">Innstillinger og elementliste ↓</a>
     <article class="news-compose-card news-compose-preview-card">
      <header class="news-compose-card-head"><div><span class="news-compose-step">01</span><strong>Artikkelark</strong><small>Skriv og rediger direkte i saken.</small></div><div class="btn-group btn-group-sm" role="group" aria-label="Visning"><button type="button" class="btn btn-outline-secondary active" id="compose-preview-desktop">Desktop</button><button type="button" class="btn btn-outline-secondary" id="compose-preview-mobile">Mobil</button></div></header>
     <div class="news-compose-reader-wrap"><article id="news-create-preview" class="news-compose-reader"></article></div>
    </article>
   </section>
   <aside class="news-compose-side" id="news-compose-inspector">
    <a class="nc-mobile-inspector" href="#news-create-preview">Til artikkelen ↑</a>
    <section class="news-compose-card">
     <header class="news-compose-card-head"><div><span class="news-compose-step">02</span><strong>Nyhetsdetaljer</strong><small>Opplysninger som følger saken ut.</small></div></header>
     <div class="news-compose-card-body">
      <label class="form-label" for="announcement-title">Tittel</label><input class="form-control mb-3" id="announcement-title" required maxlength="255">
      <input type="hidden" id="announcement-type" value="news">
      <label class="form-label" for="announcement-priority">Prioritet</label><input class="form-control" id="announcement-priority" type="number" min="1" max="9" step="1" value="5" required aria-describedby="announcement-priority-help"><small id="announcement-priority-help" class="text-muted">1–9 · 1 er høyest.</small>
      <div class="announcement-image-editor mt-3"><img id="announcement-image-preview" class="announcement-image-preview" alt="Forhåndsvisning av nyhetsbildet" hidden><div id="announcement-image-placeholder" class="announcement-image-placeholder">Ingen forsidebilde valgt</div><div class="announcement-image-url"><label class="form-label" for="announcement-image-url">Forsidebilde · URL</label><input type="url" class="form-control" id="announcement-image-url" placeholder="https://…"></div></div>
      <div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="announcement-is-pinned"><label class="form-check-label" for="announcement-is-pinned">Fest øverst</label></div>
        <div class="form-check mt-3" id="announcement-schedule-toggle-wrap" hidden><input class="form-check-input" type="checkbox" id="announcement-scheduled"><label class="form-check-label" for="announcement-scheduled">Planlagt publisering</label></div><div id="announcement-schedule-fields" class="news-compose-schedule mt-2" hidden><div class="row g-2"><div class="col-7"><label class="form-label" for="announcement-publish-date">Dato · dd.mm.åå</label><input class="form-control" id="announcement-publish-date" inputmode="numeric" placeholder="dd.mm.åå"></div><div class="col-5"><label class="form-label" for="announcement-publish-time">Klokkeslett</label><input class="form-control" id="announcement-publish-time" inputmode="numeric" placeholder="19:00" maxlength="5"></div></div></div><input type="hidden" id="announcement-publish-at"><div class="mt-3"><label class="form-label" for="announcement-expire-at">Utløpsdato</label><input type="date" class="form-control" id="announcement-expire-at"><small class="form-text text-muted">Utløper kl. 08:00. Eldre klokkeslett beholdes ved redigering.</small></div><div class="nc-author-slot mt-3"></div>
      <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" id="announcement-product-toggle"><label class="form-check-label" for="announcement-product-toggle">Knytt til produkt</label></div><div id="product-section" class="mt-2" hidden><select class="form-select mb-2" id="announcement-product-id"><option value="">Velg produkt</option></select><div class="row g-2"><div class="col-4"><input type="number" class="form-control" id="announcement-discount" min="0" max="100" placeholder="Rabatt %"></div><div class="col-4"><input type="number" class="form-control" id="announcement-max-qty" min="1" placeholder="Maks antall"></div><div class="col-4"><input type="date" class="form-control" id="announcement-product-expiry"></div></div></div>
      <div id="announcement-priority-impact" class="small text-muted mt-2" role="status" aria-live="polite"></div>
     </div>
    </section>
    <section class="news-compose-card">
       <header class="news-compose-card-head"><div><span class="news-compose-step">03</span><strong>Artikkelens elementer</strong><small>Dra håndtaket for å sortere.</small></div><span id="news-create-element-count" class="text-muted small"></span></header>
       <div class="news-compose-card-body"><div id="announcement-elements-list" class="news-compose-element-list"></div><div id="announcement-selected-element"></div>
     </div>
    </section>
   </aside>
  </div>
   <details class="mt-3" hidden><summary class="small text-muted">Reserveinnhold for eldre visninger</summary><div class="pt-2">
   <label class="form-label" for="announcement-content-editor">Eldre tekstinnhold</label><div class="announcement-editor-toolbar" role="toolbar" aria-label="Tekstformatering"><button type="button" class="btn btn-sm btn-outline-secondary" data-format="bold" title="Fet"><strong>B</strong></button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="italic" title="Kursiv"><em>I</em></button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="underline" title="Understreket"><u>U</u></button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="formatBlock" data-value="h3" title="Overskrift">H</button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="insertUnorderedList" title="Punktliste">•</button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="insertOrderedList" title="Nummerert liste">1.</button><button type="button" class="btn btn-sm btn-outline-secondary" data-format="createLink" title="Sett inn lenke">↗</button></div><div id="announcement-content-editor" class="announcement-content-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-describedby="announcement-content-help announcement-content-feedback"></div><textarea id="announcement-content" hidden aria-hidden="true"></textarea><div id="announcement-content-feedback" class="invalid-feedback" role="alert" aria-live="polite" hidden>Innhold kan ikke være tomt.</div><small id="announcement-content-help" class="text-muted d-block mb-3">Beholdes for eldre visninger mens disse tas ut av bruk.</small></div></details>
  <div class="news-compose-footer"><button type="button" class="btn btn-outline-secondary" id="cancelAnnouncement">Til nyhetsoversikt</button><button type="button" class="btn btn-outline-secondary" id="saveDraftButton">Lagre som kladd</button><button type="button" class="btn btn-primary" id="publishButton">Publiser</button></div>
 </form>
</div>
</div>
</div>
<script src="/assets/announcement_ranking.js"></script>
<script src="/assets/news_video.js"></script>
<script>
(() => {
const types={event:'Arrangement',info:'Informasjon',news:'Nyhet',promotion:'Aktuelt',urgent:'Viktig melding'},priorityValue=value=>AnnouncementRanking.priorityValue(value),priorityDefault=5;let announcements=[],priorityDraft=null,publishIntent=false,editRevision=0,loadRevision=0,saveBusy=false,priorityBusy=false,loadError='',priorityStatus='',imagePreviewUrl='',imagePreviewFailed=false,editorAuthor=null,companyName='',companyInfo={},selectedAuthorName='',editingExpireTime='08:00:00',insertionOpen=false,carouselIndexes={};
 const $=id=>document.getElementById(id), esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])), norm=s=>String(s??'').replace(/\r\n?/g,'\n');
 const contentEditor=()=>$('announcement-content-editor');
    let editorElements=[],selectedElementIndex=0,composePreviewMode='desktop',newStoryDraft=null,editBaseline=null;
   function editorFingerprint(){return JSON.stringify({fields:Array.from($('announcementForm').querySelectorAll('input[id],select[id]')).map(el=>[el.id,el.value,el.checked]),elements:editorElements,legacy:contentValue()})}
   function canReplaceEditor(){return !saveBusy&&(!val('announcement-id')||editBaseline===editorFingerprint()||confirm('Du har ulagrede endringer. Forkaste dem og åpne en annen sak?'))}
   function rememberNewStory(){
    if(val('announcement-id'))return;
    newStoryDraft={fields:Array.from($('announcementForm').querySelectorAll('input[id],select[id]')).map(el=>({id:el.id,value:el.value,checked:el.checked})),elements:JSON.parse(JSON.stringify(editorElements)),legacy:contentValue(),index:selectedElementIndex};
   }
    function restoreNewStory(){
    if(!newStoryDraft)return;
    for(const field of newStoryDraft.fields){const el=$(field.id);el.value=field.value;el.checked=field.checked}
    setContentHtml(newStoryDraft.legacy);editorElements=JSON.parse(JSON.stringify(newStoryDraft.elements));selectedElementIndex=newStoryDraft.index;
    newStoryDraft=null;renderElements();
     $('announcement-schedule-fields').hidden=!$('announcement-scheduled').checked;$('announcement-schedule-toggle-wrap').hidden=true;$('product-section').hidden=!$('announcement-product-toggle').checked;syncDraft();
   }
   const elementDefaults=type=>type==='text'?{html:''}:type==='image'?{url:'',alt:'',caption:''}:type==='text_image'?{html:'',url:'',alt:'',caption:'',layout:'image_top'}:type==='image_carousel'?{urls:['','']}:type==='list'?{items:['']}:type==='spreadsheet'?{headers:['Kolonne 1'],rows:[['']]}:{url:'',caption:''};
   const elementLabel=type=>({text:'Tekst',image:'Bilde',text_image:'Tekst og bilde',image_carousel:'Bildekarusell',youtube_video:'Video',list:'Liste',spreadsheet:'Regneark'}[type]||'Innhold');
  const escAttr=value=>esc(value).replace(/`/g,'&#96;');
   const elementKind=el=>el?.editor_kind||el?.element_type||'text';
   const elementHtml=value=>String(value??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
   const listHtml=items=>`<ul>${items.map(item=>`<li>${elementHtml(item)}</li>`).join('')}</ul>`;
   const tableHtml=(headers,rows)=>`<table><thead><tr>${headers.map(value=>`<th>${elementHtml(value)}</th>`).join('')}</tr></thead><tbody>${rows.map(row=>`<tr>${headers.map((_,i)=>`<td>${elementHtml(row[i]||'')}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
  function elementField(index,key,label,value,type='text'){
   const attrs=`data-element-index="${index}" data-element-key="${key}"`;
   if(type==='textarea')return `<label class="form-label small">${label}<textarea class="form-control form-control-sm" rows="3" ${attrs}>${esc(value)}</textarea></label>`;
   return `<label class="form-label small">${label}<input class="form-control form-control-sm" type="${type}" value="${escAttr(value)}" ${attrs}></label>`;
  }
  function mediaSettings(el,index){
   const c=el.content||{},kind=elementKind(el),safeUrl=v=>/^https?:\/\//i.test(String(v||''))?String(v):'';
   if(!['image','text_image','image_carousel','youtube_video'].includes(kind))return '';
   let markup='';
   if(kind==='image'||kind==='text_image')markup=`<div class="news-compose-media-editor">${safeUrl(c.url)?`<img class="news-compose-media-preview" src="${escAttr(safeUrl(c.url))}" alt="">`:'<div class="nc-paper-empty">Legg til en gyldig bildeadresse for å vise bildet.</div>'}${elementField(index,'url','Bildeadresse',c.url||'','url')}${elementField(index,'alt','Alternativ tekst',c.alt||'')}${elementField(index,'caption','Bildetekst',c.caption||'')}</div>`;
   if(kind==='image_carousel')markup=`<div class="news-compose-media-editor"><p class="news-compose-hint">Én bildeadresse per linje. Karusellen trenger minst to gyldige bilder.</p>${elementField(index,'urls','Bildeadresser',(c.urls||[]).join('\n'),'textarea')}</div>`;
   if(kind==='youtube_video')markup=`<div class="news-compose-media-editor">${elementField(index,'url','Videolenke',c.url||'','url')}<small class="text-muted">YouTube, Vimeo eller direkte lenke til .mp4 / .webm. Videoen må tillate avspilling her.</small>${elementField(index,'caption','Bildetekst',c.caption||'')}</div>`;
   return `<div class="news-compose-selected mt-2"><div class="news-compose-selected-head"><span>Medieinnstillinger · ${elementLabel(kind)}</span><button type="button" class="btn btn-sm btn-outline-danger" data-selected-remove>Fjern</button></div><div class="news-compose-selected-body">${markup}</div></div>`;
  }
  function renderElements(renderDocument=true){
    const list=$('announcement-elements-list');if(!list)return;
    $('news-create-element-count').textContent=`${editorElements.length} element${editorElements.length===1?'':'er'}`;
    list.innerHTML=editorElements.map((el,index)=>`<div class="news-compose-element ${index===selectedElementIndex?'active':''}" data-sort-index="${index}" draggable="true"><button type="button" class="news-compose-drag" data-drag-index="${index}" aria-label="Dra for å flytte element ${index+1}" title="Dra for å sortere">⠿</button><button type="button" class="btn p-0 border-0 bg-transparent text-start flex-grow-1" data-element-select="${index}"><span class="news-compose-element-title">${String(index+1).padStart(2,'0')} · ${elementLabel(elementKind(el))}</span></button><span class="news-compose-element-actions"><button type="button" data-element-move="-1" data-element-index="${index}" aria-label="Flytt opp" ${index===0?'disabled':''}>↑</button><button type="button" data-element-move="1" data-element-index="${index}" aria-label="Flytt ned" ${index===editorElements.length-1?'disabled':''}>↓</button><button type="button" data-element-remove="${index}" aria-label="Fjern element">×</button></span></div>`).join('')||'<p class="text-muted small mb-0">Elementene vises her når du legger dem til.</p>';
    const el=editorElements[selectedElementIndex];
    const old=$('announcement-selected-element');if(old)old.innerHTML=el?mediaSettings(el,selectedElementIndex):'';
    if(renderDocument)renderPaper();else $('news-create-preview').querySelectorAll('[data-paper-index]').forEach(block=>block.classList.toggle('selected',Number(block.dataset.paperIndex)===selectedElementIndex));
  }
  function videoId(url){
    return NewsVideo.parse(url);
  }
  function isWebUrl(value){try{const u=new URL(String(value||''));return ['http:','https:'].includes(u.protocol)&&!!u.hostname}catch(e){return false}}
   function currentAuthorLabel(){if(val('announcement-id'))return selectedAuthorName||'Opprinnelig forfatter ukjent';return editorAuthor?.name||(editorAuthor?.staff_id?'Navn ikke tilgjengelig':'Mangler ansattkobling')}
  function inlineTools(index){return `<div class="nc-inline-tools" role="toolbar" aria-label="Tekstformatering"><button type="button" data-paper-format="bold" data-paper-format-index="${index}" aria-label="Fet tekst"><strong>B</strong></button><button type="button" data-paper-format="italic" data-paper-format-index="${index}" aria-label="Kursiv tekst"><em>I</em></button><button type="button" data-paper-format="underline" data-paper-format-index="${index}" aria-label="Understreket tekst"><u>U</u></button><button type="button" data-paper-format="insertUnorderedList" data-paper-format-index="${index}" aria-label="Punktliste">•</button></div>`}
   function resizePaperTitle(){const title=$('nc-paper-title');if(title&&title.offsetWidth){title.style.height='auto';title.style.height=title.scrollHeight+'px'}}
  function renderPaper(){
   const paper=$('news-create-preview');if(!paper)return;
   const heading=companyName||'Nyheter',author=currentAuthorLabel();
   const blocks=editorElements.map((el,index)=>{
    const c=el.content||{},kind=elementKind(el),selected=index===selectedElementIndex;
     if(kind==='pending')return `<section class="nc-paper-block selected" data-paper-index="${index}"><div class="nc-paper-empty"><div><strong>Hva vil du legge til?</strong><div class="nc-type-picker">${[['text','Tekst'],['image','Bilde'],['image_carousel','Bildekarusell'],['list','Liste'],['spreadsheet','Regneark'],['youtube_video','Video']].map(([k,label])=>`<button type="button" data-add-kind="${k}" data-pending-index="${index}">${label}</button>`).join('')}</div></div></div></section>`;
     if(kind==='text')return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}">${inlineTools(index)}<div class="nc-paper-text" contenteditable="true" data-paper-text="${index}" role="textbox" aria-multiline="true" aria-label="Tekstelement ${index+1}">${c.html||''}</div></section>`;
    if(kind==='text_image')return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}"><figure class="nc-paper-image">${/^https?:\/\//i.test(c.url||'')?`<img src="${escAttr(c.url)}" alt="${escAttr(c.alt||'')}" data-paper-image>`:'<div class="nc-paper-empty">Bilde mangler · legg til adresse i elementlisten</div>'}${c.caption?`<figcaption class="nc-paper-caption">${esc(c.caption)}</figcaption>`:''}</figure><div class="mt-3">${inlineTools(index)}<div class="nc-paper-text" contenteditable="true" data-paper-text="${index}" role="textbox" aria-label="Tekst og bilde ${index+1}">${c.html||''}</div></div></section>`;
    if(kind==='list')return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}"><ul class="nc-paper-list" contenteditable="true" data-paper-list="${index}" aria-label="Rediger liste">${(c.items||['']).map(v=>`<li>${esc(v)}</li>`).join('')}</ul></section>`;
    if(kind==='spreadsheet')return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}"><div style="overflow-x:auto"><table class="nc-paper-table"><thead><tr>${(c.headers||[]).map((v,col)=>`<th contenteditable="true" data-paper-head="${col}" data-paper-index="${index}" aria-label="Kolonne ${col+1}">${esc(v)}</th>`).join('')}</tr></thead><tbody>${(c.rows||[]).map((row,r)=>`<tr>${(c.headers||[]).map((_,col)=>`<td contenteditable="true" data-paper-cell="${r}:${col}" data-paper-index="${index}" aria-label="Rad ${r+1}, kolonne ${col+1}">${esc(row[col]||'')}</td>`).join('')}</tr>`).join('')}</tbody></table></div><div class="mt-2 d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-paper-row-add="${index}">Legg til rad</button><button type="button" class="btn btn-sm btn-outline-secondary" data-paper-col-add="${index}">Legg til kolonne</button></div></section>`;
    if(kind==='image_carousel'){
     const urls=(c.urls||[]).filter(u=>/^https?:\/\//i.test(u)),pos=Math.min(carouselIndexes[index]||0,Math.max(0,urls.length-1)),src=urls[pos];
     return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}"><figure class="nc-paper-carousel">${src?`<img src="${escAttr(src)}" alt="Bilde ${pos+1} av ${urls.length}" data-paper-image><div class="nc-paper-carousel-controls"><button type="button" data-carousel-step="-1" data-carousel-index="${index}" ${pos===0?'disabled':''}>Forrige</button><span>${pos+1} av ${urls.length}</span><button type="button" data-carousel-step="1" data-carousel-index="${index}" ${pos===urls.length-1?'disabled':''}>Neste</button></div>`:`<div class="nc-paper-empty ${c.urls?.some(Boolean)?'nc-paper-error':''}">${c.urls?.some(Boolean)?'Bildene kunne ikke lastes. Kontroller adressene.':'Legg til minst to bildeadresser i elementlisten.'}</div>`}</figure></section>`;
    }
    if(kind==='image')return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}"><figure class="nc-paper-image">${/^https?:\/\//i.test(c.url||'')?`<img src="${escAttr(c.url)}" alt="${escAttr(c.alt||'')}" data-paper-image>`:`<div class="nc-paper-empty">Legg til en bildeadresse i elementlisten.</div>`}${c.caption?`<figcaption class="nc-paper-caption">${esc(c.caption)}</figcaption>`:''}</figure></section>`;
    const video=videoId(c.url||'');return `<section class="nc-paper-block ${selected?'selected':''}" data-paper-index="${index}">${video?`<div class="nc-paper-video">${video.provider==='file'?`<video controls playsinline preload="metadata" src="${escAttr(video.src)}" aria-label="${escAttr(c.caption||'Video')}"></video>`:`<iframe src="${escAttr(video.src)}" title="${escAttr(c.caption||'Video')}" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`}</div>${c.caption?`<span class="nc-paper-caption">${esc(c.caption)}</span>`:''}`:`<div class="nc-paper-empty ${c.url?'nc-paper-error':''}">${c.url?'Ugyldig videolenke. Bruk YouTube, Vimeo eller .mp4 / .webm.':'Legg til en videolenke i elementlisten.'}</div>`}</section>`;
   }).join('');
   paper.classList.toggle('mobile',composePreviewMode==='mobile');
   const schedule=$('announcement-scheduled')?.checked,dateText=$('announcement-publish-date')?.value;
    let cover=$('announcement-image-url')?.value.trim();
    if(editorElements.some(el=>['image','text_image'].includes(elementKind(el))&&el.content?.url===cover))cover='';
    paper.innerHTML=`<header class="nc-paper-header"><span>${esc(heading)}</span><span>${esc(types[$('announcement-type')?.value]||'Nyhet')}</span></header><textarea rows="1" id="nc-paper-title" class="nc-paper-title" aria-label="Nyhetstittel" maxlength="255" placeholder="Skriv inn tittel …">${esc($('announcement-title')?.value||'')}</textarea><p class="nc-paper-byline">Av ${esc(author)}${schedule&&dateText?` · Planlagt ${esc(dateText)} kl. ${esc($('announcement-publish-time')?.value||'')}`:''}</p>${/^https?:\/\//i.test(cover||'')?`<figure class="nc-paper-image mb-4"><img src="${escAttr(cover)}" alt="Forsidebilde" data-paper-image></figure>`:''}${blocks}<div class="nc-paper-insert"><button type="button" id="paper-add-trigger">+ Opprett nytt element</button></div>`;
    resizePaperTitle();
   paper.querySelectorAll('[data-paper-image]').forEach(img=>img.addEventListener('error',()=>{const figure=img.closest('figure');figure.innerHTML='<div class="nc-paper-empty nc-paper-error">Bildet kunne ikke lastes. Kontroller bildeadressen.</div>'}));
  }
  function legacyEditorKind(html){
   const doc=new DOMParser().parseFromString(String(html||''),'text/html'),nodes=Array.from(doc.body.childNodes).filter(node=>node.nodeType!==3||node.textContent.trim());
   if(nodes.length!==1||nodes[0].nodeType!==1)return 'text';
   const root=nodes[0];
    if(root.tagName==='UL'&&Array.from(root.children).every(child=>child.tagName==='LI'&&!child.children.length))return 'list';
    if(root.tagName==='TABLE'&&root.querySelector('thead')&&root.querySelector('tbody')&&Array.from(root.querySelectorAll('td,th')).every(cell=>!cell.children.length))return 'spreadsheet';
   return 'text';
  }
  function setEditorElements(elements){
    editorElements=(Array.isArray(elements)?elements:[]).map(el=>{let content={...(el.content||{})},editor_kind=el.element_type||'text';
     if(editor_kind==='text'){editor_kind=legacyEditorKind(content.html||'');if(editor_kind==='list'){const doc=new DOMParser().parseFromString(content.html,'text/html');content.items=Array.from(doc.querySelectorAll('li')).map(item=>item.textContent||'')}if(editor_kind==='spreadsheet'){const doc=new DOMParser().parseFromString(content.html,'text/html'),table=doc.querySelector('table');content.headers=Array.from(table?.querySelectorAll('thead th')||[]).map(cell=>cell.textContent||'');content.rows=Array.from(table?.querySelectorAll('tbody tr')||[]).map(row=>Array.from(row.querySelectorAll('td')).map(cell=>cell.textContent||''))}}
     if(editor_kind==='image_carousel'){content.urls=Array.isArray(content.urls)?content.urls:(Array.isArray(content.images)?content.images.map(image=>typeof image==='string'?image:image?.url||''):[])}
     if(editor_kind==='youtube_video')content.url=content.url||(content.video_id?`https://www.youtube.com/watch?v=${content.video_id}`:'');
     return {element_type:el.element_type,editor_kind,content}});
    selectedElementIndex=Math.min(selectedElementIndex,Math.max(0,editorElements.length-1));
   renderElements();
  }
  function getEditorElements(){
    return editorElements.map(el=>{const kind=elementKind(el),content={...el.content};
     if(kind==='list')return {element_type:'text',content:{html:listHtml(content.items||[])}};
     if(kind==='spreadsheet')return {element_type:'text',content:{html:tableHtml(content.headers||[],content.rows||[])}};
     if(el.element_type==='image_carousel')content.urls=(content.urls||[]).map(v=>String(v).trim()).filter(Boolean);
     return {element_type:el.element_type,content}});
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
    if(el.element_type==='youtube_video'&&!c.caption)parts.push('Video');
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
  function renderComposerPreview(){renderPaper()}
  function setNewsPanel(panel,refresh=true){
    if(saveBusy)return;
    const editing=panel==='edit'&&!!val('announcement-id');
    $('news-edit-panel').hidden=panel!=='edit';
    $('news-edit-list').hidden=editing;
    $('news-edit-workspace').hidden=!editing;
    $(editing?'news-edit-editor-host':'news-create-host').appendChild($('news-create-panel'));
    $('news-create-panel').hidden=panel!=='create'&&!editing;
     for(const name of ['edit','create']){
     $('news-'+name+'-tab').classList.toggle('active',panel===name);
     $('news-'+name+'-tab').setAttribute('aria-selected',String(panel===name));
    }
    renderEditCards();
     if(panel==='edit'&&refresh)load();
    else{renderPaper();requestAnimationFrame(resizePaperTitle)}
  }
   function addElement(type,pendingIndex=null){
   const kind=type==='list'||type==='spreadsheet'?type:type,persistedType=kind==='list'||kind==='spreadsheet'?'text':kind;
    const item={element_type:persistedType,editor_kind:kind,content:elementDefaults(kind)};
    if(pendingIndex!==null&&elementKind(editorElements[pendingIndex])==='pending'){editorElements[pendingIndex]=item;selectedElementIndex=pendingIndex}
    else{editorElements.push(item);selectedElementIndex=editorElements.length-1}
    renderElements();syncDraft();
   const editor=$('news-create-preview').querySelector(`[data-paper-text="${selectedElementIndex}"],[data-paper-list="${selectedElementIndex}"]`);if(editor)editor.focus();
    else if(window.matchMedia('(max-width:850px)').matches&&['image','image_carousel','youtube_video'].includes(kind))$('announcement-selected-element').scrollIntoView({block:'center',behavior:'smooth'});
  }
  function updateSelectedField(field){
   const index=Number(field.dataset.elementIndex);if(!editorElements[index])return;
   const key=field.dataset.elementKey,content=editorElements[index].content||{};
   content[key]=key==='urls'?field.value.split(/\r?\n/).map(v=>v.trim()).filter(Boolean):field.value;
   editorElements[index].content=content;renderPaper();syncDraft();
  }
  function moveElement(from,to){
   if(from<0||to<0||from>=editorElements.length||to>=editorElements.length||from===to)return;
   const [item]=editorElements.splice(from,1);editorElements.splice(to,0,item);
    selectedElementIndex=to;carouselIndexes={};renderElements();syncDraft();
  }
  const listNode=$('announcement-elements-list'),paperNode=$('news-create-preview'),mediaNode=$('announcement-selected-element');
  listNode.addEventListener('click',e=>{
   const select=e.target.closest('[data-element-select]');if(select){selectedElementIndex=Number(select.dataset.elementSelect);renderElements();return}
   const remove=e.target.closest('[data-element-remove]');if(remove){const i=Number(remove.dataset.elementRemove);editorElements.splice(i,1);selectedElementIndex=Math.min(selectedElementIndex,Math.max(0,editorElements.length-1));renderElements();syncDraft();return}
   const move=e.target.closest('[data-element-move]');if(move)moveElement(Number(move.dataset.elementIndex),Number(move.dataset.elementIndex)+Number(move.dataset.elementMove));
  });
  let draggedIndex=null,pointerDragIndex=null;
  listNode.addEventListener('dragstart',e=>{const row=e.target.closest('[data-sort-index]');if(!row)return;draggedIndex=Number(row.dataset.sortIndex);e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',String(draggedIndex))});
  listNode.addEventListener('dragover',e=>{if(e.target.closest('[data-sort-index]'))e.preventDefault()});
  listNode.addEventListener('drop',e=>{const row=e.target.closest('[data-sort-index]');if(!row)return;e.preventDefault();moveElement(Number(e.dataTransfer.getData('text/plain')||draggedIndex),Number(row.dataset.sortIndex));draggedIndex=null});
  listNode.addEventListener('pointerdown',e=>{const handle=e.target.closest('[data-drag-index]');if(handle&&e.pointerType!=='mouse'){pointerDragIndex=Number(handle.dataset.dragIndex);handle.setPointerCapture?.(e.pointerId)}});
  listNode.addEventListener('pointerup',e=>{if(pointerDragIndex===null)return;const under=document.elementFromPoint(e.clientX,e.clientY),row=under?.closest('[data-sort-index]');if(row)moveElement(pointerDragIndex,Number(row.dataset.sortIndex));pointerDragIndex=null});
  listNode.addEventListener('pointercancel',()=>{pointerDragIndex=null});
  mediaNode.addEventListener('input',e=>{const field=e.target.closest('[data-element-index][data-element-key]');if(field)updateSelectedField(field)});
  mediaNode.addEventListener('click',e=>{if(e.target.closest('[data-selected-remove]')){editorElements.splice(selectedElementIndex,1);selectedElementIndex=Math.min(selectedElementIndex,Math.max(0,editorElements.length-1));renderElements();syncDraft()}});
  paperNode.addEventListener('mousedown',e=>{if(e.target.closest('[data-paper-format]'))e.preventDefault()});
   paperNode.addEventListener('paste',e=>{if(!e.target.closest('[contenteditable="true"]'))return;e.preventDefault();document.execCommand('insertText',false,e.clipboardData?.getData('text/plain')||'')});
  paperNode.addEventListener('input',e=>{
    const title=e.target.closest('#nc-paper-title');if(title){$('announcement-title').value=title.value;resizePaperTitle();syncDraft();return}
   const block=e.target.closest('[data-paper-index]');if(!block)return;
   const index=Number(block.dataset.paperIndex),el=editorElements[index];if(!el)return;
   const text=e.target.closest('[data-paper-text]');if(text)el.content.html=text.innerHTML;
   const list=e.target.closest('[data-paper-list]');if(list)el.content.items=Array.from(list.querySelectorAll('li')).map(li=>li.textContent||'');
   const head=e.target.closest('[data-paper-head]');if(head)el.content.headers[Number(head.dataset.paperHead)]=head.textContent||'';
   const cell=e.target.closest('[data-paper-cell]');if(cell){const [r,c]=cell.dataset.paperCell.split(':').map(Number);el.content.rows[r][c]=cell.textContent||''}
   selectedElementIndex=index;syncDraft();
  });
  paperNode.addEventListener('click',e=>{
   const format=e.target.closest('[data-paper-format]');if(format){const i=Number(format.dataset.paperFormatIndex);selectedElementIndex=i;renderElements(false);document.execCommand(format.dataset.paperFormat,false,null);const editor=paperNode.querySelector(`[data-paper-text="${i}"]`);if(editor)editorElements[i].content.html=editor.innerHTML;syncDraft();return}
    if(e.target.closest('#paper-add-trigger')){editorElements.push({element_type:'pending',editor_kind:'pending',content:{}});selectedElementIndex=editorElements.length-1;renderElements();syncDraft();paperNode.querySelector(`[data-paper-index="${selectedElementIndex}"] button`)?.focus();return}
    const add=e.target.closest('[data-add-kind]');if(add){addElement(add.dataset.addKind,Number(add.dataset.pendingIndex));return}
   const step=e.target.closest('[data-carousel-step]');if(step){const i=Number(step.dataset.carouselIndex),len=(editorElements[i]?.content?.urls||[]).filter(Boolean).length;carouselIndexes[i]=Math.max(0,Math.min(len-1,(carouselIndexes[i]||0)+Number(step.dataset.carouselStep)));selectedElementIndex=i;renderElements();return}
   const rowAdd=e.target.closest('[data-paper-row-add]');if(rowAdd){const el=editorElements[Number(rowAdd.dataset.paperRowAdd)],c=el.content;c.rows.push(c.headers.map(()=>''));renderElements();syncDraft();return}
   const colAdd=e.target.closest('[data-paper-col-add]');if(colAdd){const el=editorElements[Number(colAdd.dataset.paperColAdd)],c=el.content;c.headers.push(`Kolonne ${c.headers.length+1}`);c.rows.forEach(row=>row.push(''));renderElements();syncDraft();return}
   const block=e.target.closest('[data-paper-index]');if(block){const i=Number(block.dataset.paperIndex);if(i!==selectedElementIndex){selectedElementIndex=i;renderElements(false)}}
  });
  const editorPriority=()=>{let input=$('announcement-priority'),priority=input.valueAsNumber,valid=AnnouncementRanking.isValidPriority(priority);input.setCustomValidity(valid?'':'Prioritet må være et heltall mellom 1 og 9.');return valid?priority:null};
 const contentToText=value=>{let node=document.createElement('div'),html=String(value??'').replace(/<\/(?:td|th)>/gi,'\t').replace(/<\/(?:tr|div|p|h[2-5]|li|blockquote|pre)>/gi,'\n').replace(/<br\s*\/?>/gi,'\n');node.innerHTML=html;return norm(node.textContent||'')};
 const contentValue=()=>contentEditor()?.innerHTML||'';
 const setContentHtml=value=>{if(contentEditor())contentEditor().innerHTML=typeof value==='string'?value:'';$('announcement-content').value=contentValue()};
 const val=id=>id==='announcement-content'?contentValue():$(id).value;
 const validateContentEditor=()=>{let editor=contentEditor(),feedback=$('announcement-content-feedback'),valid=contentToText(contentValue()).trim()!=='';editor.classList.toggle('is-invalid',!valid);editor.setAttribute('aria-invalid',String(!valid));feedback.hidden=valid;if(!valid)editor.focus();return valid};
 const excerpt=(s,n)=>{s=contentToText(s).trim();if(s.length<=n)return s;return s.slice(0,n).replace(/\s+\S*$/,'').trim()+'…'};
 function renderPriorityImpact(){let panel=$('announcement-priority-impact');if(!panel)return;let id=val('announcement-id')||'draft-preview',desired=editorPriority();if(desired===null){panel.textContent='Skriv inn et heltall mellom 1 og 9 for å se prioritetseffekten.';return}let base=announcements.map(a=>String(a.id)===String(id)?{...a,priority:priorityDraft?.originalPriority||priorityDefault}:a);if(!announcements.some(a=>String(a.id)===String(id)))base.push({id,title:val('announcement-title')||'Nyhet',priority:priorityDefault});let unchanged=priorityDraft?.originalPriority===desired,plan=unchanged?{updates:{}}:AnnouncementRanking.planPriorityAssignment(base,id,desired,{force:true}),changed=Object.entries(plan.updates).filter(([rowId])=>String(rowId)!==String(id));if(!changed.length){panel.textContent='Ingen andre saker må flyttes.';return}let byId=new Map(base.map(a=>[String(a.id),a]));panel.textContent=`Ved lagring forskyves ${changed.length} ${changed.length===1?'sak':'saker'}: `+changed.slice(0,4).map(([rowId,next])=>`${byId.get(String(rowId))?.title||'Nyhet'} → ${next}`).join(', ')+(changed.length>4?`, og ${changed.length-4} til.`:'.')}
  function date(s){return s?new Date(String(s).replace(' ','T')).toLocaleDateString('nb-NO',{day:'numeric',month:'short',year:'numeric'}):'Ikke datert'}
  function cardStatus(a,now=new Date()){
   if(!a.is_published)return 'Skjult / kladd';
   if(a.publish_at&&new Date(String(a.publish_at).replace(' ','T'))>now)return 'Planlagt';
   if(a.expire_at&&new Date(String(a.expire_at).replace(' ','T'))<=now)return 'Utløpt';
   return 'Publisert';
  }
  function isPublicNow(a,now=new Date()){
   if(!a.is_published)return false;
    const parseDate=value=>{if(!value||String(value).startsWith('0000-00-00'))return null;const parsed=new Date(String(value).replace(' ','T'));return Number.isNaN(parsed.getTime())?false:parsed};
    const published=parseDate(a.publish_at),expires=parseDate(a.expire_at);
    if(published===false||expires===false)return false;
    return !(published&&published>now)&&!(expires&&expires<=now);
  }
  function safeImage(value){
   const raw=String(value||'').trim();
   if(/^https?:\/\//i.test(raw))return raw;
   if(/^\/(?!\/)/.test(raw))return raw;
   return '';
  }
  function rankedAnnouncements(){return AnnouncementRanking(announcements)}
  function samePinNeighbor(id,direction,sorted=rankedAnnouncements()){
   const index=sorted.findIndex(item=>String(item.id)===String(id));if(index<0)return null;
   const target=sorted[index],step=Number(direction)<0?-1:1,candidate=sorted[index+step];
   return candidate&&Boolean(candidate.is_pinned)===Boolean(target.is_pinned)?candidate:null;
  }
  function priorityMoveButton(item,direction,sorted=rankedAnnouncements()){
   const up=Number(direction)<0,neighbor=samePinNeighbor(item.id,direction,sorted),word=up?'opp':'ned';
   let reason='';
   if(!neighbor)reason=up?'Ingen tidligere sak med samme festestatus.':'Ingen neste sak med samme festestatus.';
   else if(!priorityMovePlan(item.id,direction,sorted))reason='Felles prioritet kan ikke flyttes videre i denne retningen.';
   const label=reason?`Kan ikke flytte ${item.title||'saken'} ${word}: ${reason}`:`Flytt ${item.title||'saken'} ${word}`;
   return `<button type="button" data-priority-move="${up?-1:1}" data-id="${esc(item.id)}" aria-label="${esc(label)}" title="${esc(reason||`Flytt ${word}`)}" ${reason||priorityBusy?'disabled':''}>${up?'↑':'↓'}</button>`;
  }
  function previewActions(item,sorted){
   return `<span class="story-meta">${Number(item.clicks)||0} klikk</span>`;
  }
  function renderPublicPreview(){
   const host=$('news-home-preview');if(!host)return;
   if(loadError){host.innerHTML=`<div class="alert alert-danger" role="alert">${esc(loadError)} Forhåndsvisningen kunne ikke lastes.</div>`;return}
   if(loadRevision===0){host.innerHTML='<p role="status">Henter saker …</p>';return}
   const color=/^#[0-9a-f]{6}$/i.test(String(companyInfo.header_bg_color||''))?companyInfo.header_bg_color:'#2d5598';
   const logo=safeImage(companyInfo.img_url);
   const name=companyInfo.name||companyName||'';
   const positions={left:'flex-start',center:'center',right:'flex-end'};
   const headerPosition=Object.hasOwn(positions,companyInfo.logo_position)?companyInfo.logo_position:(companyInfo.header_layout==='centered'?'center':'left');
   const navPosition=Object.hasOwn(positions,companyInfo.nav_position)?companyInfo.nav_position:'center';
   const safeSize=(value,fallback)=>{const match=String(value??'').trim().match(/^(\d+(?:\.\d+)?)(px|rem|em|vh|vw|%)?$/);return match&&Number(match[1])<=10000?match[1]+(match[2]||'px'):fallback};
   const headerHeight=safeSize(companyInfo.header_height,'80px');
   const logoHeight=safeSize(companyInfo.logo_height,'50px');
   const headerFontSize=safeSize(companyInfo.company_header_font_size,'20px');
   const radius=safeSize(companyInfo.border_radius,'16px');
   const safeFontFamily=value=>{const raw=String(value??'').trim();return raw&&raw.length<=150&&/^[A-Za-z0-9 ,'"_-]+$/.test(raw)?raw:'Inter, sans-serif'};
   const textColor=/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(String(companyInfo.header_text_color||''))?companyInfo.header_text_color:'';
   host.style.setProperty('--site-header-position',positions[headerPosition]);
   host.style.setProperty('--site-header-align',headerPosition);
   host.style.setProperty('--site-nav-position',positions[navPosition]);
     const allRanked=rankedAnnouncements(),stories=AnnouncementRanking(announcements.filter(a=>isPublicNow(a)));
    const contact=[companyInfo.contact_phone,companyInfo.address_street,[companyInfo.address_zip,companyInfo.address_city].filter(Boolean).join(' ')].filter(Boolean);
   const storyCard=(a,hero=false)=>{
    const title=esc(a.title||'Uten tittel'),image=safeImage(a.image_url),summary=esc(excerpt(a.content_text||a.content_html||'',hero?230:150));
    const id=esc(a.id);
     if(hero)return `<article tabindex="0" role="group" class="news-home-hero story-focus ${image?'':'no-image'}" data-edit-story="${id}" aria-label="Åpne og rediger: ${title}"><span class="news-home-hero-copy"><span class="story-kicker">Siste nytt</span><h3>${title}</h3>${summary?`<span class="story-excerpt">${summary}</span>`:''}<span class="story-meta">${esc(a.author_name||'')} · ${esc(date(a.publish_at))}</span></span>${image?`<img src="${esc(image)}" alt="" loading="lazy">`:''}${previewActions(a,allRanked)}</article>`;
     return `<article tabindex="0" role="group" class="news-home-card story-focus" data-edit-story="${id}" aria-label="Åpne og rediger: ${title}">${image?`<img src="${esc(image)}" alt="" loading="lazy">`:''}<h4>${title}</h4><span class="story-meta">${esc(date(a.publish_at))}</span>${previewActions(a,allRanked)}</article>`;
   };
   host.style.setProperty('--site-head',color);
   const rgb=[1,3,5].map(start=>parseInt(color.slice(start,start+2),16));
    host.style.setProperty('--site-head-ink',textColor||((rgb[0]*299+rgb[1]*587+rgb[2]*114)/1000>160?'#183b45':'#ffffff'));
    host.style.setProperty('--site-header-height',headerHeight);
    host.style.setProperty('--site-logo-height',logoHeight);
    host.style.setProperty('--site-header-font-size',headerFontSize);
    host.style.setProperty('--site-radius',radius);
    host.style.setProperty('--site-font-family',safeFontFamily(companyInfo.font_family));
    host.style.setProperty('--site-logo-position',headerPosition);
   host.innerHTML=`<header class="news-home-top">${logo?`<img class="news-home-logo" src="${esc(logo)}" alt="">`:''}${name?`<span class="news-home-name">${esc(name)}</span>`:''}</header><nav class="news-home-nav" aria-label="Navigasjon"><span>Om oss</span><span>Hjem</span><span>Tjenester</span></nav><div class="news-home-content">${stories.length?`${storyCard(stories[0],true)}${stories.length>1?`<div class="news-home-rest">${stories.slice(1).map(a=>storyCard(a)).join('')}</div>`:''}`:`<div class="news-home-empty">${announcements.length?'Ingen publiserte saker innenfor gyldige publiseringsdatoer.':'Ingen nyhetssaker er registrert for denne bedriften.'}</div>`}</div><footer class="news-home-footer"><span>${name?esc(name):''}${contact.length?`<small>${contact.map(esc).join(' · ')}</small>`:''}</span></footer>`;
   host.querySelector('.news-home-top').dataset.layout=['standard','centered','minimal'].includes(companyInfo.header_layout)?companyInfo.header_layout:'standard';
   if(Array.isArray(companyInfo.preview_warnings)&&companyInfo.preview_warnings.length){
    const warning=document.createElement('div');warning.className='alert alert-warning';warning.setAttribute('role','status');
    warning.textContent=companyInfo.preview_warnings.join(' ');host.prepend(warning);
   }
  }
  function renderEditCards(){
   renderPublicPreview();
   $('news-priority-live').textContent=priorityStatus;
   if(loadError){$('news-edit-cards').innerHTML=`<div class="alert alert-danger" role="alert">${esc(loadError)} <button type="button" class="btn btn-sm btn-outline-danger" id="news-edit-retry">Prøv igjen</button></div>`;return}
   const sorted=rankedAnnouncements();
   const filter=$('news-admin-filter').value,now=new Date();
    const rows=sorted.filter(a=>{
    return filter==='all'||(filter==='visible'?isPublicNow(a):!isPublicNow(a));
     });
    const count=$('news-sidebar-count');if(count)count.textContent=`${rows.length} av ${sorted.length}`;
   $('news-edit-cards').innerHTML=rows.map(a=>{
    const id=esc(a.id),title=esc(a.title),status=cardStatus(a,now),clicks=Number(a.clicks)||0;
      const image=safeImage(a.image_url),position=sorted.findIndex(item=>String(item.id)===String(a.id)),ranked=position>=0;
     const priority=priorityValue(a.priority);
       return `<article class="news-edit-card"><button type="button" class="news-edit-card-open" data-edit-story="${id}" aria-label="Åpne og rediger ${title}"><span class="news-edit-card-body"><small>${esc(types[a.announcement_type]||'Nyhet')}${a.is_pinned?' · Festet':''}</small><strong class="news-edit-card-title">${title}</strong><span class="news-edit-card-excerpt">${esc(excerpt(a.content_text||'',130))||'Ingen tekstutdrag'}</span><small>${esc(a.author_name||'Forfatter ukjent')} · ${esc(date(a.publish_at))}</small><span class="news-edit-card-meta"><span class="badge-news">${esc(status)}</span><span class="badge-news news-textpill-bg">${clicks} klikk</span><span class="badge-news">Prioritet ${priority}</span></span></span></button><div class="news-edit-card-actions" aria-label="Handlinger for ${title}">${ranked?priorityMoveButton(a,-1,sorted)+priorityMoveButton(a,1,sorted):''}<button type="button" data-card-action="pin" data-id="${id}">${a.is_pinned?'Løsne':'Fest øverst'}</button><button type="button" data-card-action="publish" data-id="${id}">${a.is_published?'Skjul':'Publiser'}</button><button type="button" data-card-action="delete" data-id="${id}">Slett</button></div></article>`;
    }).join('')||'<p class="text-muted small mb-0">Ingen saker i dette utvalget.</p>';
 }
 function render(){renderEditCards()}
function productFromForm(){if(!$('announcement-product-toggle').checked)return null;let id=val('announcement-product-id');if(!id)return null;let option=$('announcement-product-id').selectedOptions[0];return {output_product_id:id,name:option?.textContent||'',discount_percent:Number(val('announcement-discount'))||0,max_quantity:val('announcement-max-qty')?Number(val('announcement-max-qty')):null,expiry_date:val('announcement-product-expiry')||null}}
function localSqlNow(){const d=new Date();return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')+'T'+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')}
function scheduleSqlValue(){
 if(!$('announcement-scheduled').checked)return null;
 const m=$('announcement-publish-date').value.trim().match(/^(\d{2})\.(\d{2})\.(\d{2}|\d{4})$/),time=$('announcement-publish-time').value;
  if(!m||!/^([01]\d|2[0-3]):[0-5]\d$/.test(time))return '';
 let year=Number(m[3]);if(year<100)year+=2000;
 const date=new Date(year,Number(m[2])-1,Number(m[1]));
 if(date.getFullYear()!==year||date.getMonth()!==Number(m[2])-1||date.getDate()!==Number(m[1]))return '';
 return `${year}-${m[2]}-${m[1]} ${time}:00`;
}
function setScheduleFields(value,enabled){
 $('announcement-scheduled').checked=!!enabled;$('announcement-schedule-fields').hidden=!enabled;
 if(enabled&&value){let d=new Date(String(value).replace(' ','T'));if(!Number.isNaN(d.getTime())){$('announcement-publish-date').value=`${String(d.getDate()).padStart(2,'0')}.${String(d.getMonth()+1).padStart(2,'0')}.${String(d.getFullYear()).slice(-2)}`;$('announcement-publish-time').value=`${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`}}
 if(!enabled){$('announcement-publish-date').value='';$('announcement-publish-time').value=''}
}
function effectivePublishAt(){return scheduleSqlValue()}
 function updateImagePreview(){let url=val('announcement-image-url').trim(),img=$('announcement-image-preview'),placeholder=$('announcement-image-placeholder'),valid=false;try{let parsed=new URL(url);valid=parsed.protocol==='http:'||parsed.protocol==='https:'}catch(e){}if(!valid){imagePreviewUrl='';imagePreviewFailed=false;img.hidden=true;placeholder.hidden=false;img.removeAttribute('src');return}if(url!==imagePreviewUrl){imagePreviewUrl=url;imagePreviewFailed=false;img.src=url}img.hidden=imagePreviewFailed;placeholder.hidden=!imagePreviewFailed}
      function syncDraft(){let id=val('announcement-id')||'draft-preview',existing=announcements.find(a=>String(a.id)===String(id)),priority=editorPriority();priorityDraft={id,originalPriority:existing?priorityValue(existing.priority):null,priorityInputValid:priority!==null,priority};if(!val('announcement-id')&&!val('announcement-title').trim()&&!getEditorElements().length)priorityDraft=null;updateImagePreview();renderPriorityImpact();render()}
async function loadProducts(){let s=$('announcement-product-id');if(s.options.length>1)return;let r=await fetch('/api/get_output_products.php'),x=await r.json();s.innerHTML='<option value="">Velg produkt</option>'+(x.products||[]).map(p=>`<option value="${esc(p.id)}">${esc(p.name||'Ukjent')}</option>`).join('')}
async function toggleProduct(){let on=$('announcement-product-toggle').checked;$('product-section').hidden=!on;if(on)await loadProducts();syncDraft()}
function resetProduct(){let s=$('announcement-product-id');s.selectedIndex=0;$('announcement-product-toggle').checked=false;$('product-section').hidden=true;$('announcement-discount').value='';$('announcement-max-qty').value='';$('announcement-product-expiry').value=''}
  function resetComposer(){
   $('announcementForm').reset();$('announcement-id').value='';$('announcement-type').value='news';selectedAuthorName='';$('announcement-priority').value=String(priorityDefault);
    $('announcement-publish-at').value='';setScheduleFields('',false);$('announcement-schedule-toggle-wrap').hidden=true;$('announcement-expire-at').value='';editingExpireTime='08:00:00';$('announcement-is-pinned').checked=false;
    setContentHtml('');setEditorElements([]);selectedElementIndex=0;
   resetProduct();$('announcementModalTitle').textContent='Opprett ny sak';priorityDraft=null;insertionOpen=false;carouselIndexes={};
    const authorSlot=document.querySelector('.nc-author-slot');if(authorSlot)authorSlot.innerHTML=editorAuthor?.staff_id?`<div class="news-compose-author">Forfatter · ${esc(currentAuthorLabel())}</div>`:'<div class="news-compose-author">Ny sak krever at brukeren er knyttet til en ansatt i bedriften.</div>';
   $('announcement-content-feedback').hidden=true;contentEditor().classList.remove('is-invalid');$('announcement-image-url').value='';updateImagePreview();syncDraft();
  }
   function openNew(){
    if(!canReplaceEditor())return;
    if(!val('announcement-id')){setNewsPanel('create');return}
     editRevision+=1;publishIntent=false;$('announcement-save-status').hidden=true;resetComposer();
    restoreNewStory();
    setNewsPanel('create');
   }
  async function edit(id){
   if(!canReplaceEditor())return;
   let revision=++editRevision,a=announcements.find(x=>String(x.id)===String(id));if(!a)return;
   try{if(a.product)await loadProducts()}catch(e){alert('Kunne ikke hente produktet. Prøv igjen.');return}
   if(revision!==editRevision)return;
   rememberNewStory();
    publishIntent=!!a.is_published;selectedAuthorName=a.author_name||'';
   $('announcement-id').value=a.id;
    const expireMatch=String(a.expire_at||'').match(/^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}:\d{2}(?::\d{2})?))?/);
    editingExpireTime=expireMatch?.[2]?(expireMatch[2].length===5?expireMatch[2]+':00':expireMatch[2]):'08:00:00';
    [['title',a.title],['image-url',a.image_url||''],['type',a.announcement_type||'news'],['priority',priorityValue(a.priority)],['expire-at',expireMatch?.[1]||'']].forEach(([k,v])=>$('announcement-'+k).value=v);
    const scheduledEdit=!!(a.publish_at&&new Date(String(a.publish_at).replace(' ','T'))>new Date());
    setScheduleFields(a.publish_at,scheduledEdit);$('announcement-schedule-toggle-wrap').hidden=!scheduledEdit;
   setContentHtml(a.content_html);carouselIndexes={};setEditorElements(a.elements?.length?a.elements:[{element_type:'text',content:{html:a.content_html||a.content||''}}]);
   $('announcement-is-pinned').checked=!!a.is_pinned;resetProduct();
   if(a.product){$('announcement-product-toggle').checked=true;$('product-section').hidden=false;$('announcement-product-id').value=a.product.output_product_id;$('announcement-discount').value=a.product.discount_percent||0;$('announcement-max-qty').value=a.product.max_quantity||'';$('announcement-product-expiry').value=a.product.expiry_date||''}
   const authorSlot=document.querySelector('.nc-author-slot');if(authorSlot)authorSlot.innerHTML=`<div class="news-compose-author">Opprinnelig forfatter · ${esc(selectedAuthorName||'Navn ikke tilgjengelig')}</div>`;
   $('announcementModalTitle').textContent='Rediger sak';$('announcement-save-status').hidden=true;
    priorityDraft=null;syncDraft();editBaseline=editorFingerprint();setNewsPanel('edit');
  }
  function setSaveBusy(busy){if(saveBusy===busy)return;saveBusy=busy;let form=$('announcementForm');form.setAttribute('aria-busy',String(busy));form.querySelectorAll('input,select,textarea,button').forEach(control=>{if(busy){control.dataset.saveWasDisabled=control.disabled?'1':'0';control.disabled=true}else{control.disabled=control.dataset.saveWasDisabled==='1';delete control.dataset.saveWasDisabled}});form.querySelectorAll('[contenteditable]').forEach(control=>{if(busy){control.dataset.saveEditable=control.getAttribute('contenteditable');control.setAttribute('contenteditable','false')}else if('saveEditable' in control.dataset){control.setAttribute('contenteditable',control.dataset.saveEditable);delete control.dataset.saveEditable}})}
 function applyPriorityMap(priorities){if(!priorities||typeof priorities!=='object')return false;announcements=announcements.map(a=>Object.prototype.hasOwnProperty.call(priorities,String(a.id))?{...a,priority:priorityValue(priorities[String(a.id)])}:a);render();return true}
 function validateElementBlocks(){
  for(let i=0;i<editorElements.length;i++){
   const el=editorElements[i],kind=elementKind(el),c=el.content||{};
    let invalid=kind==='pending';
   if(kind==='text')invalid=!contentToText(c.html||'').trim();
   if(kind==='text_image')invalid=!contentToText(c.html||'').trim()||!isWebUrl(c.url);
   if(kind==='image')invalid=!isWebUrl(c.url);
   if(kind==='image_carousel')invalid=(c.urls||[]).filter(isWebUrl).length<2;
   if(kind==='youtube_video')invalid=!videoId(c.url||'');
   if(kind==='list')invalid=!(c.items||[]).some(item=>String(item).trim());
   if(kind==='spreadsheet')invalid=!(c.headers||[]).some(v=>String(v).trim())&&!(c.rows||[]).some(row=>row.some(v=>String(v).trim()));
   if(invalid){selectedElementIndex=i;renderElements();alert(`${elementLabel(kind)} ${i+1} mangler innhold eller en gyldig medielenke.`);return false}
  }
  return true;
 }
 async function saveAnnouncement(publish){
  if(saveBusy)return;
  let priority=editorPriority();
  if(priority===null){$('announcement-priority').reportValidity();return}
  let elements=getEditorElements();
   if(!elements.length){alert('Legg til et innholdselement før du lagrer.');return}
   if(!validateElementBlocks())return;
     if(val('announcement-id')&&$('announcement-scheduled').checked){const scheduled=scheduleSqlValue();if(!scheduled){alert('Fyll inn en gyldig dato i dd.mm.åå-format og et klokkeslett.');$('announcement-publish-date').focus();return}if(new Date(scheduled.replace(' ','T'))<=new Date()){alert('Planlagt publisering må være et framtidig tidspunkt.');$('announcement-publish-date').focus();return}}
    if((publish||!val('announcement-id'))&&!editorAuthor?.staff_id){alert('Brukeren må være knyttet til en ansatt i bedriften før en ny sak kan opprettes eller publiseres.');return}
   if(!$('announcementForm').reportValidity())return;
  publishIntent=!!publish;syncDraft();
   let id=val('announcement-id'),existing=announcements.find(a=>String(a.id)===String(id)),scheduled=!!id&&$('announcement-scheduled').checked,expireDate=val('announcement-expire-at'),expireAt=expireDate?`${expireDate} ${id?editingExpireTime:'08:00:00'}`:null,data={id:id||undefined,title:val('announcement-title').trim(),content:legacyContentForSave(existing,elements),elements,image_url:val('announcement-image-url').trim()||elementsFirstImage()||null,announcement_type:val('announcement-type'),priority,is_published:publishIntent,is_pinned:$('announcement-is-pinned').checked,scheduled_publish:scheduled,publish_at:scheduled?scheduleSqlValue():null,expire_at:expireAt,product:productFromForm()};
  setSaveBusy(true);
  try{
   let r=await fetch(id?'/api/update_announcement.php':'/api/create_announcement.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}),x;
   try{x=await r.json()}catch(e){x={success:false,message:'Ugyldig svar fra server'}}
   if(!r.ok||!x.success){alert(x.message||'Kunne ikke lagre');return}
   if(typeof x.content!=='string'||typeof x.content_html!=='string'||typeof x.content_text!=='string'){alert('Serveren returnerte ikke lagret innhold');return}
   loadRevision+=1;
    let saved={...(existing||{}),id:id||x.announcement_id,title:data.title,image_url:data.image_url,announcement_type:data.announcement_type,priority:data.priority,is_published:data.is_published,is_pinned:data.is_pinned,publish_at:Object.prototype.hasOwnProperty.call(x,'publish_at')?x.publish_at:(scheduled?data.publish_at:(existing?.publish_at??null)),expire_at:data.expire_at,product:data.product,elements,content:x.content,content_html:x.content_html,content_text:x.content_text,author_name:existing?existing.author_name:editorAuthor?.name,clicks:Number(existing?.clicks)||0,created_at:existing?.created_at||new Date().toISOString(),updated_at:new Date().toISOString()};
   if(existing)announcements=announcements.map(a=>String(a.id)===String(id)?saved:a);else announcements.push(saved);
    priorityDraft=null;
   if(!applyPriorityMap(x.priorities))await load();
   setSaveBusy(false);
   if(id){editBaseline=editorFingerprint();resetComposer();restoreNewStory();setNewsPanel('edit',false)}
   else{resetComposer();$('announcement-save-status').textContent=publishIntent?(scheduled?'Nyheten er lagret for planlagt publisering.':'Nyheten er publisert.'):'Nyheten er lagret som kladd.';$('announcement-save-status').textContent+=' Skjemaet er nullstilt og klart for neste sak.';$('announcement-save-status').hidden=false;setNewsPanel('create')}
  }catch(e){alert('Kunne ikke lagre. Kontroller forbindelsen og prøv igjen.')}
  finally{setSaveBusy(false)}
 }
  async function load(){
   const revision=++loadRevision;
   let response,x;
   try{
    response=await fetch('/api/get_announcements.php?show_unpublished=true',{cache:'no-store'});
    try{x=await response.json()}catch(error){throw new Error(`Serveren returnerte ikke et gyldig nyhetssvar (HTTP ${response.status||'ukjent'}).`)}
    if(!response.ok||!x.success)throw new Error(x.message||`Kunne ikke hente nyhetene (HTTP ${response.status||'ukjent'}).`);
    if(!Array.isArray(x.announcements))throw new Error('Nyhetssvaret mangler listen over saker.');
   }catch(error){
    if(revision===loadRevision){loadError=error.message||'Kunne ikke kontakte nyhetstjenesten.';render()}
    return false;
   }
   if(revision!==loadRevision)return false;
   loadError='';editorAuthor=x.editor_author||null;companyName=typeof x.company_name==='string'?x.company_name:'';
   companyInfo={...(x.company&&typeof x.company==='object'?x.company:{}),preview_warnings:x.warnings||[]};
   announcements=x.announcements.map(a=>({...a,is_published:a.is_published===true||a.is_published===1||a.is_published==='1',is_pinned:a.is_pinned===true||a.is_pinned===1||a.is_pinned==='1',priority:priorityValue(a.priority),clicks:Number(a.clicks)||0}));
   const slot=document.querySelector('.nc-author-slot');
   if(slot&&!val('announcement-id'))slot.innerHTML=editorAuthor?.staff_id?`<div class="news-compose-author">Forfatter · ${esc(currentAuthorLabel())}</div>`:'<div class="news-compose-author">Ny sak krever at brukeren er knyttet til en ansatt i bedriften.</div>';
   try{renderPaper();render()}catch(error){
    loadError='Sakene ble hentet, men visningen feilet. Last siden på nytt.';console.error('News rendering failed',error);renderPublicPreview();return false;
   }
   return true;
  }
 async function action(action,id){if(priorityBusy)return;let endpoint=action==='pin'?'/api/toggle_announcement_pin.php':action==='publish'?'/api/toggle_announcement_publish.php':'/api/delete_announcement.php';if(action==='delete'&&!confirm('Slette denne saken?'))return;priorityBusy=true;render();try{let r=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}),x=await r.json();if(!r.ok||!x.success){alert(x.message||'Endringen kunne ikke lagres.');return}await load()}catch(e){alert('Endringen kunne ikke lagres. Prøv igjen.')}finally{priorityBusy=false;render()}}
  function priorityMovePlan(id,direction,rows=announcements){
   const sorted=AnnouncementRanking(rows),target=sorted.find(row=>String(row.id)===String(id));
   const neighbor=samePinNeighbor(id,direction,sorted);if(!target||!neighbor)return null;
   const targetPriority=priorityValue(target.priority),neighborPriority=priorityValue(neighbor.priority),up=Number(direction)<0;
   if(up){
    const desiredPriority=neighborPriority===targetPriority?targetPriority-1:neighborPriority;
    if(!AnnouncementRanking.isValidPriority(desiredPriority))return null;
    const plan=AnnouncementRanking.planPriorityAssignment(rows,id,desiredPriority);
    return Object.keys(plan.updates).length?{...plan,mutationId:id,desiredPriority,story:target,neighbor,up}:null;
   }
   if(neighborPriority>targetPriority){
    const desiredPriority=targetPriority,plan=AnnouncementRanking.planPriorityAssignment(rows,neighbor.id,desiredPriority);
    return Object.keys(plan.updates).length?{...plan,mutationId:neighbor.id,desiredPriority,story:target,neighbor,up}:null;
   }
   const desiredPriority=targetPriority+1;
   if(!AnnouncementRanking.isValidPriority(desiredPriority))return null;
   const plan=AnnouncementRanking.planPriorityAssignment(rows,id,desiredPriority);
   return Object.keys(plan.updates).length?{...plan,mutationId:id,desiredPriority,story:target,neighbor,up}:null;
  }
  async function moveStoryPriority(id,direction){
   if(priorityBusy)return;
   const previous=announcements,move=priorityMovePlan(id,direction,announcements);if(!move)return;
   priorityBusy=true;announcements=move.items;priorityStatus=`Flytter «${move.story.title||'saken'}» ${move.up?'opp':'ned'} …`;render();
   try{
    const response=await fetch('/api/update_announcement_priority.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:move.mutationId,priority:move.desiredPriority})});
    let result;try{result=await response.json()}catch(e){result={success:false,message:'Ugyldig svar fra server'}}
    if(!response.ok||!result.success)throw new Error(result.message||'Kunne ikke lagre prioritet.');
    if(!await load())throw new Error('Prioriteten ble lagret, men den oppdaterte rekkefølgen kunne ikke hentes.');
    priorityStatus=`«${move.story.title||'Saken'}» flyttet ${move.up?'opp':'ned'} forbi «${move.neighbor.title||'neste sak'}».`;
   }catch(error){
    const refreshed=await load();
    if(!refreshed){announcements=previous;priorityStatus='Prioriteten ble ikke lagret. Vist rekkefølge er tilbakestilt.';render();alert('Prioriteten ble ikke lagret. Kunne heller ikke hente oppdatert rekkefølge.')}
    else{priorityStatus=error.message||'Prioriteten ble ikke lagret. Listen er hentet på nytt.';render();alert(priorityStatus)}
   }finally{priorityBusy=false;render()}
  }
  ['announcement-title','announcement-image-url','announcement-type','announcement-priority','announcement-publish-date','announcement-publish-time','announcement-expire-at','announcement-product-id','announcement-discount','announcement-max-qty','announcement-product-expiry'].forEach(id=>$(id).addEventListener('input',()=>{if(id==='announcement-title'){const title=$('nc-paper-title');if(title)title.value=$('announcement-title').value}if(id==='announcement-image-url'||id==='announcement-type'||id==='announcement-publish-date'||id==='announcement-publish-time')renderPaper();syncDraft()}));contentEditor().addEventListener('input',()=>{validateContentEditor();$('announcement-content').value=contentValue();syncDraft()});document.querySelectorAll('.announcement-editor-toolbar [data-format]').forEach(button=>button.addEventListener('click',()=>{contentEditor().focus();let format=button.dataset.format;if(format==='createLink'){let url=window.prompt('Lenkeadresse (https:// eller mailto:)');if(url)document.execCommand('createLink',false,url)}else if(format==='formatBlock')document.execCommand(format,false,button.dataset.value);else document.execCommand(format,false,null);$('announcement-content').value=contentValue();syncDraft()}));
  $('announcement-image-preview').addEventListener('error',()=>{imagePreviewFailed=true;$('announcement-image-preview').hidden=true;$('announcement-image-placeholder').hidden=false});
   $('news-create-tab').addEventListener('click',openNew);
   $('news-edit-tab').addEventListener('click',()=>setNewsPanel('edit'));
  $('news-edit-back').addEventListener('click',()=>{
   if(!canReplaceEditor())return;
   editRevision+=1;resetComposer();restoreNewStory();setNewsPanel('edit',false);
  });
    $('news-edit-cards').addEventListener('click',e=>{const retry=e.target.closest('#news-edit-retry');if(retry){load();return}const move=e.target.closest('[data-priority-move]');if(move){moveStoryPriority(move.dataset.id,move.dataset.priorityMove);return}const actionButton=e.target.closest('[data-card-action]');if(actionButton){action(actionButton.dataset.cardAction,actionButton.dataset.id);return}const card=e.target.closest('[data-edit-story]');if(card)edit(card.dataset.editStory)});
    $('news-home-preview').addEventListener('click',e=>{const move=e.target.closest('[data-priority-move]');if(move){moveStoryPriority(move.dataset.id,move.dataset.priorityMove);return}const actionButton=e.target.closest('[data-card-action]');if(actionButton){action(actionButton.dataset.cardAction,actionButton.dataset.id);return}if(e.target.closest('button'))return;const card=e.target.closest('[data-edit-story]');if(card)edit(card.dataset.editStory)});
    $('news-home-preview').addEventListener('keydown',e=>{if(e.target.closest('button'))return;const card=e.target.closest('[data-edit-story]');if(card&&(e.key==='Enter'||e.key===' ')){e.preventDefault();edit(card.dataset.editStory)}});
   $('news-admin-filter').addEventListener('change',renderEditCards);
  $('news-edit-refresh').addEventListener('click',load);
  $('cancelAnnouncement').addEventListener('click',()=>{
    if(val('announcement-id'))$('news-edit-back').click();else setNewsPanel('edit');
  });
  $('compose-preview-desktop').addEventListener('click',()=>{composePreviewMode='desktop';$('compose-preview-desktop').classList.add('active');$('compose-preview-mobile').classList.remove('active');renderComposerPreview()});
  $('compose-preview-mobile').addEventListener('click',()=>{composePreviewMode='mobile';$('compose-preview-mobile').classList.add('active');$('compose-preview-desktop').classList.remove('active');renderComposerPreview()});
   $('saveDraftButton').addEventListener('click',()=>saveAnnouncement(false));$('publishButton').addEventListener('click',()=>saveAnnouncement(true));
  document.addEventListener('shown.bs.tab',e=>{if(e.target.dataset.bsTarget==='#content-announcements')load()});
  document.querySelectorAll('#announcementForm input,#announcementForm select').forEach(field=>field.addEventListener('input',syncDraft));
  document.querySelectorAll('#announcementForm select,#announcementForm input[type="checkbox"]').forEach(field=>field.addEventListener('change',()=>{if(field.id==='announcement-product-toggle')toggleProduct();else if(field.id==='announcement-scheduled'){ $('announcement-schedule-fields').hidden=!field.checked;renderPaper();syncDraft()}else syncDraft()}));
  $('announcementForm').addEventListener('submit',e=>e.preventDefault());
   $('announcement-title').addEventListener('input',resizePaperTitle);
   window.addEventListener('resize',resizePaperTitle);
   const sidebarMedia=window.matchMedia('(max-width:700px)');
   const syncSidebarLayout=()=>{$('news-sidebar-disclosure').open=!sidebarMedia.matches};
   syncSidebarLayout();sidebarMedia.addEventListener('change',syncSidebarLayout);
   resetComposer();setNewsPanel('edit');
})();
</script>