import re
import os

filepath = "/Users/mizanurrahman/claude/affscash script/Affscash.net  latest script/project aapanel/cookie-policy.php"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

new_css = """<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --p:#7c3aed;--pl:#ec4899;--pd:#5b21b6;
  --cyan:#06b6d4;--green:#10b981;--red:#ef4444;
  --bg:#f9fafb; /* Very light gray background */
  --card:#ffffff;
  --border:#f3f4f6;
  --border2:#e5e7eb;
  --text:#111827;
  --muted:#4b5563;
  --muted2:#6b7280;
  --grad-primary: linear-gradient(135deg, #ec4899, #8b5cf6);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden}
a{text-decoration:none}

/* ── NAV ── */
nav{position:fixed;top:0;left:0;right:0;z-index:999;height:68px;
    display:flex;align-items:center;padding:0 6%;gap:32px;
    background:rgba(255,255,255,.9);backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);transition:.3s}
nav.scrolled{box-shadow:0 4px 20px rgba(0,0,0,.05)}
.nav-logo{font-size:21px;font-weight:900;letter-spacing:-.5px;
  background:var(--grad-primary);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex-shrink:0}
.nav-spacer{flex:1}
.nav-actions{display:flex;gap:8px;flex-shrink:0;align-items:center}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:10px;
     font-size:13.5px;font-weight:600;border:none;cursor:pointer;transition:all .2s;white-space:nowrap}
.btn-ghost{background:transparent;border:1px solid var(--border2);color:var(--muted)}
.btn-ghost:hover{border-color:var(--p);color:var(--p);background:#fff}
.btn-primary{background:var(--grad-primary);color:#fff;
  box-shadow:0 4px 20px rgba(124,58,237,.25)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(124,58,237,.35)}
.landing-logo-img{display:block}

/* ── HERO / PAGE HEADER ── */
.page-hero{padding:120px 6% 40px;position:relative;overflow:hidden;background:#fff;border-bottom:1px solid var(--border2)}
.page-hero-inner{max-width:860px;margin:0 auto;position:relative;z-index:1;text-align:center}
.page-hero-eyebrow{display:inline-flex;align-items:center;gap:8px;
  background:rgba(236,72,153,.1);border:1px solid rgba(236,72,153,.2);
  border-radius:24px;padding:5px 14px 5px 10px;font-size:12px;color:#db2777;
  margin-bottom:24px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
.page-hero-eyebrow::before{content:'';width:7px;height:7px;background:#db2777;border-radius:50%}
.page-hero h1{font-size:clamp(28px,4vw,44px);font-weight:800;line-height:1.2;
  letter-spacing:-1px;margin-bottom:16px;color:#111827}
.page-hero-meta{display:flex;flex-wrap:wrap;justify-content:center;gap:24px;margin-top:24px;padding-top:24px;
  border-top:1px solid var(--border2)}
.page-hero-meta-item{font-size:13px;color:var(--muted2);text-align:left}
.page-hero-meta-item strong{color:var(--text);display:block;font-weight:600;margin-bottom:2px}

/* ── LAYOUT ── */
.tos-layout{max-width:1120px;margin:0 auto;padding:40px 6% 80px;
  display:grid;grid-template-columns:260px 1fr;gap:40px;align-items:start}

/* ── SIDEBAR TOC ── */
.toc-wrap{position:sticky;top:88px;align-self:start;max-height:calc(100vh - 108px);display:flex;flex-direction:column}
.toc{
  background:var(--card);
  border:1px solid var(--border2);
  border-radius:12px;padding:24px;
  box-shadow:0 4px 12px rgba(0,0,0,.02);
  display:flex;flex-direction:column;min-height:0;overflow:hidden}
.toc-title{font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;
  letter-spacing:.1em;margin:0 0 16px;padding-bottom:16px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;cursor:default;flex-shrink:0}
.toc-title-icon{width:16px;height:16px;color:var(--pl)} /* Pink icon */
.toc-progress{display:none} /* Hidden in this design */
.toc-toggle{display:none}
.toc-scroll{overflow-y:auto;overflow-x:hidden;min-height:0;margin:0 -6px;padding:0 6px;
  scrollbar-width:none;}
.toc-scroll::-webkit-scrollbar{display:none}
.toc-list{list-style:none;display:flex;flex-direction:column;gap:8px;margin:0;padding:0}
.toc-list li{margin:0;padding:0}
.toc-list a{font-size:13px;color:var(--text);display:flex;align-items:center;gap:12px;
  padding:6px;border-radius:8px;transition:all .2s ease;font-weight:500;}
.toc-list a:hover{background:rgba(243,244,246,1)}
.toc-list a.active{font-weight:700;}
.toc-list a.active .toc-num{background:#f3f4f6;}
.toc-num{font-size:10px;color:var(--muted);font-weight:700;font-variant-numeric:tabular-nums;
  background:#f3f4f6;border-radius:12px;padding:4px 8px;flex-shrink:0;min-width:28px;
  text-align:center;}
.toc-text{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.4}
.toc-list a.active .toc-text{white-space:normal}
.toc-footer{display:none}

/* Sidebar Promo Box */
.sidebar-promo{background:var(--grad-primary);border-radius:12px;padding:24px 20px;text-align:center;margin-top:24px;box-shadow:0 8px 24px rgba(139,92,246,.25);position:relative;overflow:hidden}
.sidebar-promo p{color:#fff;font-size:13px;font-weight:600;line-height:1.5;margin:0 0 16px}
.sidebar-promo-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 16px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:all .2s;backdrop-filter:blur(5px)}
.sidebar-promo-btn:hover{background:#fff;color:var(--p)}
.sidebar-promo-btn svg{width:12px;height:12px}

/* ── CONTENT ── */
.tos-content{min-width:0;display:flex;flex-direction:column;gap:16px}

.notice-block{background:#fdfaff;border:1px solid #f3e8ff;
  border-radius:12px;padding:24px 32px;margin-bottom:16px;font-size:14px;color:var(--muted);
  line-height:1.8;position:relative;}
.notice-block strong{color:var(--text);font-weight:600;}

/* Accordion Section */
.tos-section{background:var(--card);border:1px solid var(--border2);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.02);transition:all .3s ease;opacity:0;transform:translateY(20px)}
.tos-section.visible{opacity:1;transform:translateY(0)}
.tos-section.is-open{border-color:rgba(139,92,246,.3);box-shadow:0 8px 24px rgba(139,92,246,.08)}

.accordion-header{display:flex;align-items:center;padding:20px 24px;cursor:pointer;user-select:none;gap:16px;transition:background .2s}
.accordion-header:hover{background:#fbfbfe}

.section-number{font-size:12px;font-weight:800;color:#fff;background:var(--grad-primary);border-radius:8px;padding:5px 12px;flex-shrink:0;letter-spacing:1px}
.tos-section h2{font-size:15px;font-weight:700;color:var(--text);margin:0;flex:1;letter-spacing:0}

.accordion-icon{width:18px;height:18px;color:var(--muted2);transition:transform .3s ease}
.tos-section.is-open .accordion-icon{transform:rotate(180deg);color:var(--p)}

.accordion-content{display:none;padding:0 24px 24px 72px;background:#fff;}
.tos-section.is-open .accordion-content{display:block}

.tos-section h3{font-size:13px;font-weight:700;color:var(--text);margin:24px 0 12px;text-transform:uppercase;letter-spacing:.06em}
.tos-section h3:first-child{margin-top:0}
.tos-section p{font-size:14px;color:var(--muted);line-height:1.75;margin-bottom:14px}
.tos-section p:last-child{margin-bottom:0}

.tos-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0}
.tos-list li{font-size:14px;color:var(--muted);line-height:1.65;padding-left:20px;position:relative}
.tos-list li::before{content:'•';position:absolute;left:0;color:var(--pl);font-weight:700;font-size:16px;line-height:1.5}
.tos-list li strong{color:var(--text)}

/* ── FOOTER ── */
footer{padding:40px 6% 28px;border-top:1px solid var(--border2);background:#fff}
.footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;max-width:1120px;margin:0 auto}
.footer-bottom p{font-size:13px;color:var(--muted2)}
.footer-links-row{display:flex;gap:20px;list-style:none;flex-wrap:wrap;justify-content:center}
.footer-links-row a{font-size:13px;color:var(--muted2);transition:.2s}
.footer-links-row a:hover{color:var(--p)}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .tos-layout{grid-template-columns:1fr;gap:24px;padding:40px 5% 60px}
  .toc-wrap{position:static;margin-bottom:0;max-height:none;display:block}
  .toc{padding:16px 20px;}
  .toc-title{cursor:pointer;margin-bottom:0;padding-bottom:0;border-bottom:none}
  .toc-toggle{display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;background:rgba(124,58,237,.1)}
  .toc[data-open="true"] .toc-title{margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2)}
  .toc[data-open="false"] .toc-scroll{display:none}
  .toc-list{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
  .sidebar-promo{display:none}
  .accordion-content{padding:0 20px 20px 20px}
}
@media(max-width:640px){
  .page-hero{padding:100px 5% 32px}
  .page-hero h1{font-size:28px}
  .page-hero-meta{flex-direction:column;gap:12px}
  .toc-list{grid-template-columns:1fr!important}
  .accordion-header{padding:16px 20px;gap:12px}
  .section-number{padding:4px 10px}
}
</style>"""

content = re.sub(r'<style>.*?</style>', new_css, content, flags=re.DOTALL)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
