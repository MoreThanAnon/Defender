@extends('layouts.app')

@section('content')
<style>
    /* ========= Design Tokens ========= */
    :root{
        /* Emerald + cream + soft orange palette */
        --bg: #f6f7f5;
        --text: #16352b;
        --muted: #ecf3ef;
        --primary: #0f7b5c;       /* deep emerald */
        --primary-600:#0e6f54;
        --primary-700:#0c624a;
        --accent: #ff7a45;        /* softened saffron */
        --accent-600:#ef6d39;
        --accent-700:#d65f31;
        --white: #ffffff;
        --shadow: 0 10px 30px rgba(15, 123, 92, .12);
        --radius: 16px;
        --radius-lg: 22px;
        --border: 1px solid rgba(8, 40, 27, .08);
    }

    @media (prefers-color-scheme: dark){
        :root{
            --bg:#0e1714;
            --text:#e9f3ef;
            --muted:#0f201a;
            --white:#0f1412;
            --shadow: 0 14px 34px rgba(0,0,0,.45);
            --border: 1px solid rgba(255,255,255,.06);
        }
    }

    /* ========= Page Base ========= */
    body{
        background:
            radial-gradient(1200px 600px at 80% -10%, rgba(255,122,69,.10), transparent 60%),
            radial-gradient(1100px 650px at -10% 20%, rgba(15,123,92,.12), transparent 55%),
            var(--bg);
        color: var(--text);
    }

    /* ========= Hero ========= */
    .hero-section{
        color:#fff;
        min-height: 360px;
        border: 0;
        position: relative;
        overflow: clip;
        /* layered gradient with soft vignette */
        background:
            radial-gradient(900px 300px at 20% 10%, rgba(255,255,255,.08), transparent 60%),
            linear-gradient(135deg, var(--primary) 0%, #129d75 35%, #14a37a 60%, #1ab086 100%);
        box-shadow: var(--shadow);
    }
    .hero-section:after{
        /* subtle saffron underline */
        content:"";
        position:absolute; left:0; right:0; bottom:-1px; height:5px;
        background: linear-gradient(90deg, var(--primary) 0 33%, #fff 33% 66%, var(--accent) 66% 100%);
        opacity:.9;
    }

    .display-4{
        letter-spacing:.2px;
        text-shadow: 0 2px 18px rgba(0,0,0,.15);
    }

    /* ========= Buttons ========= */
    .btn{
        border-radius: 999px;
        padding: .8rem 1.35rem;
        font-weight: 700;
        transition: transform .16s ease, box-shadow .2s ease, background .2s ease, color .2s ease, border-color .2s ease;
    }
    .btn:active{ transform: translateY(1px) scale(.99); }

    .btn-primary, .btn-primary:focus{
        background: #fff !important;
        border-color: #fff !important;
        color: var(--primary) !important;
        box-shadow: 0 8px 20px rgba(255,255,255,.15);
    }
    .btn-primary:hover{
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        color:#fff !important;
        box-shadow: 0 10px 26px rgba(255,122,69,.35);
    }

    .btn-outline-light{
        color: var(--primary) !important;
        border-color: rgba(255,255,255,.85) !important;
        background: #fff !important;
    }
    .btn-outline-light:hover{
        background: var(--accent) !important;
        color:#fff !important;
        border-color: var(--accent) !important;
        box-shadow: 0 10px 24px rgba(255,122,69,.35);
    }

    /* ========= Section Headings ========= */
    .section-title{
        color: var(--primary);
        font-weight: 800;
        letter-spacing:.3px;
    }
    .section-sub{ color: rgba(22,53,43,.7); }

    /* ========= Feature Cards (glass look) ========= */
    .feature-card{
        background: linear-gradient(180deg, rgba(255,255,255,.75), rgba(255,255,255,.6));
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border-left: 6px solid var(--primary);
        border-right: var(--border);
        border-top: var(--border);
        border-bottom: var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    .feature-card h5{ color: var(--primary-700); }

    /* ========= Process Steps ========= */
    .step-number{
        display:inline-grid;
        place-items:center;
        width:42px; height:42px;
        border-radius:50%;
        color:#fff;
        font-size:1.25rem; font-weight:800;
        background: linear-gradient(180deg, var(--primary), var(--primary-600));
        box-shadow: 0 8px 18px rgba(15,123,92,.25);
        border: 2px solid rgba(255,255,255,.5);
    }

    /* ========= Light Section ========= */
    .bg-light{ background: var(--muted) !important; }

    /* ========= CTA ========= */
    .cta{
        background: linear-gradient(135deg, var(--primary-700), var(--primary));
        color:#fff;
    }
    .cta .btn-outline-light{
        background: transparent !important;
        border-color:#fff !important;
        color:#fff !important;
    }
    .cta .btn-outline-light:hover{
        background:#fff !important;
        color: var(--primary) !important;
    }

    /* ========= Footer ========= */
    .footer-irish{
        background: var(--primary);
        color:#fff;
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        padding: 18px 0;
        text-align:center;
        margin-top: 32px;
        font-size: 15px;
        letter-spacing:.6px;
        box-shadow: var(--shadow);
    }
    .irish-flag{
        display:inline-block; width:36px; height:18px; vertical-align:middle;
        margin-left:8px; border-radius:3px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
        background: linear-gradient(90deg, var(--primary) 0 33%, #fff 33% 66%, var(--accent) 66% 100%);
        border: 1px solid rgba(0,0,0,.06);
    }

    /* ========= Minor tweaks ========= */
    .shadow-soft{ box-shadow: var(--shadow); }
    .lead{ color: rgba(255,255,255,.92); }

    /* Reduce spacing on very small screens */
    @media (max-width: 576px){
        .display-4{ font-size: 2rem; }
        .hero-section{ min-height: 300px; }
    }
</style>

{{-- Hide navigation links for guests --}}
@auth
    {{-- Navigation for logged-in users (if needed) --}}
@else
    <style>.navbar-nav .nav-item{ display:none !important; }</style>
@endauth

<div class="hero-section text-center d-flex flex-column justify-content-center align-items-center">
    <div class="container py-5">
        <h1 class="display-4 fw-bold">Protect Local Wealth & Culture</h1>
        <p class="lead mb-4">Invest in Irish property to safeguard your community’s future, keep profits at home, and strengthen our local traditions.</p>
        <div>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg me-2">Get Started</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Login</a>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center mb-5">
        <h2 class="section-title">Why Choose Us</h2>
        <p class="section-sub">Defend your local interests—our platform empowers you to invest in Irish property, protect your heritage, and ensure that financial benefits stay within our communities.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card p-4 shadow-soft h-100">
                <h5 class="fw-bold mb-2">Keep Ownership Local</h5>
                <p class="mb-0">Investors from the area receive real equity, helping to preserve Irish ownership and support your neighbours.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card p-4 shadow-soft h-100">
                <h5 class="fw-bold mb-2">Protect Your Interests</h5>
                <p class="mb-0">Choose where your money goes and ensure your investments reflect your values and priorities for Ireland’s future.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card p-4 shadow-soft h-100">
                <h5 class="fw-bold mb-2">Transparency & Control</h5>
                <p class="mb-0">Track your investments, monitor local impact, and stay informed with clear reporting and regular updates.</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row text-center mb-4">
            <h2 class="fw-bold" style="color:var(--accent);">How It Works</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <span class="step-number">1</span>
                <h5 class="fw-bold mt-2">Join the Community</h5>
                <p>Register to become part of a network dedicated to protecting Irish assets and culture.</p>
            </div>
            <div class="col-md-4 text-center">
                <span class="step-number">2</span>
                <h5 class="fw-bold mt-2">Invest in Local Projects</h5>
                <p>Direct your funds into Irish properties and developments that matter to you and your community.</p>
            </div>
            <div class="col-md-4 text-center">
                <span class="step-number">3</span>
                <h5 class="fw-bold mt-2">Strengthen Irish Prosperity</h5>
                <p>Watch your investment grow while supporting local jobs, culture, and economic independence.</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center py-5 cta">
    <h2 class="fw-bold mb-3">Ready to Defend Local Prosperity?</h2>
    <p class="mb-4">Take action to protect your culture, your money, and your community. Join today and invest in Ireland’s future.</p>
    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">Create Your Account</a>
</div>

<div class="footer-irish">
    Made with <span style="color:var(--accent);">&#x2764;</span> in Ireland
    <span class="irish-flag"></span>
</div>
@endsection
