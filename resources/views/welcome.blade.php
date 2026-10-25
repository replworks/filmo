<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Filmo — 배우 필모그래피 카탈로그</title>
        <meta name="description" content="한국 웹드라마와 숏코미디 영상에서 배우 후보를 찾고, 근거 자료와 함께 검토해 필모그래피로 정리하는 운영자 도구.">
        <style>
            :root {
                --ink: #0b0908;
                --ink-raised: #14110f;
                --paper: #f7f2e8;
                --muted: #aaa29a;
                --line: rgba(247, 242, 232, 0.12);
                --accent: #f5b544;
                --accent-bright: #ffd27a;
                --accent-soft: rgba(245, 181, 68, 0.12);
                --font-sans: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
                    "Apple SD Gothic Neo", "Pretendard", "Noto Sans KR", sans-serif;
                --font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                min-width: 320px;
                background: var(--ink);
                color: var(--paper);
                font-family: var(--font-sans);
                -webkit-font-smoothing: antialiased;
                text-rendering: optimizeLegibility;
            }

            body::before {
                position: fixed;
                inset: 0;
                z-index: -1;
                content: "";
                pointer-events: none;
                background:
                    radial-gradient(circle at 82% 12%, rgba(245, 181, 68, 0.16), transparent 32rem),
                    radial-gradient(circle at 8% 88%, rgba(120, 83, 40, 0.14), transparent 30rem),
                    repeating-linear-gradient(0deg, transparent 0 79px, rgba(247, 242, 232, 0.025) 79px 80px),
                    repeating-linear-gradient(90deg, transparent 0 79px, rgba(247, 242, 232, 0.025) 79px 80px);
            }

            ::selection {
                background: var(--accent);
                color: var(--ink);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            a:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 4px;
            }

            .shell {
                display: flex;
                flex-direction: column;
                width: min(1180px, 100%);
                min-height: 100svh;
                margin: 0 auto;
                padding: 28px 32px 30px;
            }

            .site-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                padding-bottom: 22px;
                border-bottom: 1px solid var(--line);
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 12px;
            }

            .brand-mark {
                display: grid;
                width: 38px;
                height: 38px;
                place-items: center;
                border: 1px solid rgba(245, 181, 68, 0.5);
                border-radius: 10px;
                background: var(--accent-soft);
                color: var(--accent);
                font-family: var(--font-mono);
                font-size: 17px;
                font-weight: 700;
            }

            .brand-name {
                font-size: 13px;
                font-weight: 600;
                letter-spacing: 0.22em;
                text-transform: uppercase;
            }

            .admin-link {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 9px 14px;
                border: 1px solid var(--line);
                border-radius: 999px;
                color: var(--muted);
                font-size: 12px;
                letter-spacing: 0.04em;
                transition:
                    border-color 180ms ease,
                    color 180ms ease,
                    background-color 180ms ease;
            }

            .admin-link:hover {
                border-color: rgba(245, 181, 68, 0.55);
                background: var(--accent-soft);
                color: var(--paper);
            }

            main {
                flex: 1;
            }

            .hero {
                display: grid;
                grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
                gap: 64px;
                align-items: center;
                padding: 78px 0 68px;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin: 0;
                color: var(--accent);
                font-family: var(--font-mono);
                font-size: 10px;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .eyebrow .dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: var(--accent);
                box-shadow: 0 0 0 5px var(--accent-soft);
            }

            h1 {
                max-width: 14ch;
                margin: 24px 0 0;
                font-size: clamp(2.65rem, 6.2vw, 5.2rem);
                font-weight: 600;
                letter-spacing: -0.05em;
                line-height: 1.04;
            }

            h1 em {
                color: var(--accent);
                font-style: normal;
            }

            .lede {
                max-width: 54ch;
                margin: 28px 0 0;
                color: var(--muted);
                font-size: clamp(0.98rem, 1.2vw, 1.08rem);
                line-height: 1.8;
                word-break: keep-all;
            }

            .cta-row {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                align-items: center;
                margin-top: 36px;
            }

            .button {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 14px 20px;
                border-radius: 999px;
                background: var(--accent);
                color: #17120a;
                font-size: 13.5px;
                font-weight: 700;
                letter-spacing: -0.01em;
                box-shadow: 0 14px 38px rgba(245, 181, 68, 0.16);
                transition:
                    background-color 180ms ease,
                    transform 180ms ease,
                    box-shadow 180ms ease;
            }

            .button:hover {
                background: var(--accent-bright);
                box-shadow: 0 18px 44px rgba(245, 181, 68, 0.22);
                transform: translateY(-2px);
            }

            .button span {
                transition: transform 180ms ease;
            }

            .button:hover span {
                transform: translateX(3px);
            }

            .note {
                color: var(--muted);
                font-size: 11.5px;
                letter-spacing: 0.02em;
            }

            .hero-art {
                position: relative;
                display: grid;
                min-height: 400px;
                place-items: center;
            }

            .orbit {
                position: absolute;
                width: min(430px, 92%);
                aspect-ratio: 1;
                border: 1px solid var(--line);
                border-radius: 50%;
            }

            .orbit::before {
                position: absolute;
                inset: 14%;
                content: "";
                border: 1px solid rgba(245, 181, 68, 0.18);
                border-radius: 50%;
            }

            .orbit::after {
                position: absolute;
                top: 5.5%;
                left: 50%;
                width: 8px;
                height: 8px;
                content: "";
                border-radius: 50%;
                background: var(--accent);
                box-shadow: 0 0 18px rgba(245, 181, 68, 0.8);
                transform: translateX(-50%);
            }

            .film {
                position: relative;
                z-index: 1;
                width: min(390px, 100%);
                padding: 14px 16px;
                border: 1px solid rgba(247, 242, 232, 0.16);
                border-radius: 18px;
                background:
                    linear-gradient(155deg, rgba(255, 255, 255, 0.045), transparent 42%),
                    linear-gradient(160deg, #1d1813, #0d0b09 72%);
                box-shadow:
                    0 34px 90px rgba(0, 0, 0, 0.55),
                    inset 0 1px 0 rgba(255, 255, 255, 0.05);
                transform: rotate(-3.5deg);
            }

            .film::before,
            .film::after {
                display: block;
                height: 16px;
                content: "";
                border-radius: 3px;
                background: repeating-linear-gradient(
                    90deg,
                    transparent 0 12px,
                    rgba(247, 242, 232, 0.16) 12px 20px
                );
            }

            .film-meta {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 2px;
                color: rgba(247, 242, 232, 0.46);
                font-family: var(--font-mono);
                font-size: 8.5px;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            .frames {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 9px;
                padding: 6px 0 12px;
            }

            .frame {
                position: relative;
                display: flex;
                align-items: flex-end;
                aspect-ratio: 16 / 10;
                padding: 10px;
                overflow: hidden;
                border: 1px solid rgba(247, 242, 232, 0.1);
                border-radius: 7px;
                background: #191410;
            }

            .frame span {
                position: relative;
                z-index: 1;
                color: rgba(247, 242, 232, 0.38);
                font-family: var(--font-mono);
                font-size: 8px;
                letter-spacing: 0.14em;
            }

            .frame::before {
                position: absolute;
                inset: 0;
                content: "";
                background: linear-gradient(135deg, transparent 20%, rgba(0, 0, 0, 0.5));
            }

            .frame::after {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                content: "";
                border-top: 7px solid transparent;
                border-bottom: 7px solid transparent;
                border-left: 11px solid rgba(247, 242, 232, 0.22);
                transform: translate(-40%, -50%);
            }

            .frame-a {
                background:
                    radial-gradient(circle at 30% 30%, rgba(245, 181, 68, 0.42), transparent 55%),
                    #2a1d10;
            }

            .frame-b {
                background:
                    radial-gradient(circle at 70% 25%, rgba(207, 126, 85, 0.32), transparent 55%),
                    #241611;
            }

            .frame-c {
                background:
                    radial-gradient(circle at 35% 65%, rgba(154, 126, 83, 0.3), transparent 58%),
                    #1a1712;
            }

            .frame-d {
                background:
                    radial-gradient(circle at 65% 60%, rgba(245, 181, 68, 0.25), transparent 58%),
                    #1b1510;
            }

            .steps {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                border-top: 1px solid var(--line);
            }

            .step {
                padding: 26px 28px 6px 0;
                border-right: 1px solid var(--line);
            }

            .step + .step {
                padding-left: 28px;
            }

            .step:last-child {
                border-right: 0;
            }

            .step-number {
                color: var(--accent);
                font-family: var(--font-mono);
                font-size: 10px;
                letter-spacing: 0.16em;
            }

            .step h2 {
                margin: 12px 0 8px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .step p {
                max-width: 32ch;
                margin: 0;
                color: var(--muted);
                font-size: 13px;
                line-height: 1.7;
                word-break: keep-all;
            }

            .site-footer {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 10px;
                padding-top: 22px;
                margin-top: 56px;
                border-top: 1px solid var(--line);
                color: rgba(247, 242, 232, 0.42);
                font-family: var(--font-mono);
                font-size: 9px;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .site-footer strong {
                color: var(--paper);
                font-weight: 600;
            }

            @media (max-width: 900px) {
                .hero {
                    grid-template-columns: minmax(0, 1fr);
                    gap: 42px;
                    padding: 56px 0 52px;
                }

                .hero-art {
                    min-height: 330px;
                }

                .steps {
                    grid-template-columns: minmax(0, 1fr);
                }

                .step,
                .step + .step {
                    padding: 22px 0;
                    border-right: 0;
                    border-bottom: 1px solid var(--line);
                }

                .step:last-child {
                    border-bottom: 0;
                }
            }

            @media (max-width: 560px) {
                .shell {
                    padding: 20px 20px 24px;
                }

                .admin-link {
                    padding: 8px 11px;
                    font-size: 11px;
                }

                h1 {
                    max-width: none;
                }

                .cta-row {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .button {
                    justify-content: center;
                    width: 100%;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                html {
                    scroll-behavior: auto;
                }

                *,
                *::before,
                *::after {
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="site-header">
                <a class="brand" href="{{ url('/') }}" aria-label="Filmo 홈">
                    <span class="brand-mark" aria-hidden="true">F</span>
                    <span class="brand-name">Filmo</span>
                </a>

                <a class="admin-link" href="{{ url('/admin') }}">
                    운영자 페이지
                    <span aria-hidden="true">↗</span>
                </a>
            </header>

            <main>
                <section class="hero">
                    <div class="hero-copy">
                        <p class="eyebrow">
                            <span class="dot" aria-hidden="true"></span>
                            배우 필모그래피 카탈로그
                        </p>

                        <h1>
                            영상에서 찾고,<br>
                            <em>근거로 완성하는</em><br>
                            배우 필모그래피.
                        </h1>

                        <p class="lede">
                            한국 웹드라마와 숏코미디 채널의 영상을 모아 배우 후보를 발견합니다.
                            Gemini로 후보를 추출하고, NamuWiki·Naver 자료로 검토해
                            하나의 필모그래피로 정리하세요.
                        </p>

                        <div class="cta-row">
                            <a class="button" href="{{ url('/admin') }}">
                                운영자 페이지로 이동
                                <span aria-hidden="true">→</span>
                            </a>
                            <span class="note">운영자 전용 · 로그인 필요</span>
                        </div>
                    </div>

                    <div class="hero-art" aria-hidden="true">
                        <div class="orbit"></div>
                        <div class="film">
                            <div class="film-meta">
                                <span>FILMO / 001</span>
                                <span>Korean web drama</span>
                            </div>

                            <div class="frames">
                                <div class="frame frame-a"><span>01</span></div>
                                <div class="frame frame-b"><span>02</span></div>
                                <div class="frame frame-c"><span>03</span></div>
                                <div class="frame frame-d"><span>04</span></div>
                            </div>

                            <div class="film-meta">
                                <span>Collect</span>
                                <span>Extract</span>
                                <span>Review</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="steps" aria-label="Filmo 작업 흐름">
                    <article class="step">
                        <span class="step-number">01</span>
                        <h2>수집</h2>
                        <p>YouTube 채널을 등록하고, 채널의 업로드 영상을 빠짐없이 모읍니다.</p>
                    </article>

                    <article class="step">
                        <span class="step-number">02</span>
                        <h2>추출</h2>
                        <p>Gemini로 영상 속 배우와 필모그래피 후보를 추출합니다.</p>
                    </article>

                    <article class="step">
                        <span class="step-number">03</span>
                        <h2>검토</h2>
                        <p>NamuWiki·Naver 근거를 확인하고, 후보를 승인하거나 거절합니다.</p>
                    </article>
                </section>
            </main>

            <footer class="site-footer">
                <span><strong>Filmo</strong> — 배우 필모그래피 카탈로그</span>
                <span>Operator-only backoffice</span>
            </footer>
        </div>
    </body>
</html>
