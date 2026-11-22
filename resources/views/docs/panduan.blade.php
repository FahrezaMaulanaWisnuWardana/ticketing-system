@extends('layouts.app')

@section('title', __('common.user_guide'))

@push('styles')
<style>{!! $css !!}</style>
<style>
    /* The standalone file has its own top bar; inside the app the real header is used. */
    .doc-page > .topbar { display: none !important; }
    .doc-page body { margin: 0; }
    .doc-page .hero { padding: 44px 0 52px; }
    .doc-page section { padding: 56px 0; }
    /* Keep the introduction above the illustration in the narrow app content area. */
    .doc-page .hero-visual { order: 0; }

    /* The standalone guide is light-only; map its palette/surfaces to the app's dark theme. */
    html[data-theme="dark"] .doc-page {
        --brand:#a5b4fc; --brand-dark:#c7d2fe; --brand-soft:rgba(129,140,248,.18);
        --ink:#e2e8f0; --muted:#94a3b8; --line:#334155; --bg:#0b1220; --white:#1e293b;
        --shadow:0 10px 30px -12px rgba(0,0,0,.55);
        background:var(--bg); color:var(--ink); border-radius:16px;
    }
    html[data-theme="dark"] .doc-page section { background:#0b1220 !important; }
    html[data-theme="dark"] .doc-page .hero {
        background:
            radial-gradient(900px 460px at 85% -10%,rgba(99,102,241,.22),transparent 60%),
            radial-gradient(700px 400px at 0% 100%,rgba(13,148,136,.12),transparent 55%),
            linear-gradient(180deg,#131c30,#0b1220);
    }
    html[data-theme="dark"] .doc-page .eyebrow,
    html[data-theme="dark"] .doc-page .chip,
    html[data-theme="dark"] .doc-page .btn-ghost,
    html[data-theme="dark"] .doc-page .stats,
    html[data-theme="dark"] .doc-page .step,
    html[data-theme="dark"] .doc-page .role,
    html[data-theme="dark"] .doc-page .frame,
    html[data-theme="dark"] .doc-page .status-card,
    html[data-theme="dark"] .doc-page .lifecycle,
    html[data-theme="dark"] .doc-page .shortcut,
    html[data-theme="dark"] .doc-page .tip,
    html[data-theme="dark"] .doc-page .mock-window,
    html[data-theme="dark"] .doc-page .float-card,
    html[data-theme="dark"] .doc-page .login-card,
    html[data-theme="dark"] .doc-page .board-tabs,
    html[data-theme="dark"] .doc-page .card,
    html[data-theme="dark"] .doc-page .step:not(:last-child):after {
        background:#1e293b; border-color:#334155; color:#e2e8f0;
    }
    html[data-theme="dark"] .doc-page .frame-bar,
    html[data-theme="dark"] .doc-page .frame.cap figcaption,
    html[data-theme="dark"] .doc-page .mock-top,
    html[data-theme="dark"] .doc-page .board-mock,
    html[data-theme="dark"] .doc-page .col,
    html[data-theme="dark"] .doc-page .qm-head span,
    html[data-theme="dark"] .doc-page kbd { background:#0f172a; border-color:#334155; color:#cbd5e1; }
    html[data-theme="dark"] .doc-page .life { background:#1e293b; border-color:#334155; color:#cbd5e1; }
    html[data-theme="dark"] .doc-page .life.new { background:rgba(59,130,246,.2); color:#93c5fd; }
    html[data-theme="dark"] .doc-page .life.prog { background:rgba(245,158,11,.2); color:#fcd34d; }
    html[data-theme="dark"] .doc-page .life.rev { background:rgba(168,85,247,.2); color:#d8b4fe; }
    html[data-theme="dark"] .doc-page .life.wait { background:rgba(249,115,22,.2); color:#fdba74; }
    html[data-theme="dark"] .doc-page .life.done { background:rgba(16,185,129,.2); color:#6ee7b7; }
    html[data-theme="dark"] .doc-page .mock-row,
    html[data-theme="dark"] .doc-page .qrow { border-color:#334155; color:#cbd5e1; }
    html[data-theme="dark"] .doc-page .todo li,
    html[data-theme="dark"] .doc-page .fake-input,
    html[data-theme="dark"] .doc-page .fake-google,
    html[data-theme="dark"] .doc-page .card small,
    html[data-theme="dark"] .doc-page .qrow .urg.normal { color:#cbd5e1; }
    html[data-theme="dark"] .doc-page .float-card .ic {
        background:rgba(129,140,248,.2) !important; color:#c7d2fe !important;
    }
    html[data-theme="dark"] .doc-page .note { background:#422006; border-color:#92400e; color:#fde68a; }
    html[data-theme="dark"] .doc-page .note b { color:#fcd34d; }
    html[data-theme="dark"] .doc-page .twofa { background:#2e1065; border-color:#5b21b6; color:#ddd6fe; }
    html[data-theme="dark"] .doc-page .chip { background:rgba(30,41,59,.75); }
    html[data-theme="dark"] .doc-page .callout .btn-primary { background:#fff; color:#312e81; }
</style>
@endpush

@section('content')
<div class="doc-page">
    {!! $content !!}
</div>
@endsection
