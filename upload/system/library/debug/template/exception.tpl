<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" />
    <link rel="dns-prefetch" href="https://fonts.googleapis.com" />
    <link rel="dns-prefetch" href="https://fonts.gstatic.com" />
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com" />
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com" />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js" defer></script>
    <style>
      :root {
        --color-bg: oklch(98% 0.005 260);
        --color-text: oklch(20% 0.01 260);

        --color-header-bg: oklch(70% 0.18 29);
        --color-header-text: oklch(20% 0.01 260);

        --color-frame-bg: oklch(96% 0.005 260);
        --color-frame-border: oklch(90% 0.01 260);

        --color-tech-info-bg: oklch(94% 0.01 120);
        --color-tech-info-border: oklch(86% 0.02 120);
        --color-tech-info-shadow: oklch(88% 0.015 260);

        --color-theme-toggle-bg: transparent;
        --color-theme-toggle-hover-bg: oklch(70% 0.18 29 / 0.12);
        --color-theme-toggle-color: oklch(20% 0.01 260);
        --color-theme-toggle-hover-color: oklch(20% 0.01 260);

        --error-line-bg: oklch(70% 0.15 340 / 0.15);
        --error-line-num-bg: oklch(70% 0.15 340 / 0.25);
        --error-line-num-color: oklch(60% 0.18 340);

        --color-suggestions-bg: oklch(94% 0.01 120);
        --color-suggestions-border: oklch(86% 0.02 120);
        --color-suggestions-shadow: oklch(88% 0.015 120);
      }

      body.theme_dark {
        --color-bg: oklch(18% 0.01 260);
        --color-text: oklch(95% 0.01 260);

        --color-header-bg: oklch(55% 0.18 29);
        --color-header-text: oklch(100% 0.01 29);

        --color-tech-info-bg: oklch(20% 0.015 120);
        --color-tech-info-border: oklch(28% 0.02 120);
        --color-tech-info-shadow: oklch(16% 0.01 260);

        --color-frame-bg: oklch(24% 0.01 260);
        --color-frame-border: oklch(35% 0.01 260);

        --color-theme-toggle-bg: transparent;
        --color-theme-toggle-hover-bg: oklch(55% 0.18 29 / 0.10);
        --color-theme-toggle-color: oklch(95% 0.01 260);
        --color-theme-toggle-hover-color: oklch(100% 0.01 29);

        --error-line-bg: oklch(40% 0.15 340 / 0.18);
        --error-line-num-bg: oklch(40% 0.15 340 / 0.32);
        --error-line-num-color: oklch(70% 0.18 340);

        --color-suggestions-bg: oklch(20% 0.015 120);
        --color-suggestions-border: oklch(28% 0.02 120);
        --color-suggestions-shadow: oklch(16% 0.01 120);
      }

      *,
      *::before,
      *::after {
        box-sizing: border-box;
      }

      html {
        -moz-text-size-adjust: none;
        -webkit-text-size-adjust: none;
        text-size-adjust: none;
      }

      .hljs-ln-numbers {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        text-align: center;
        color: var(--color-text);
        opacity: 0.5;
        border-right: 1px solid var(--color-frame-border);
        vertical-align: top;
        padding-right: 0.5rem !important;
        width: 3rem;
      }

      .hljs-ln-code {
        padding-left: 0.75rem !important;
      }

      .hljs-ln-line[data-error-line] {
        background-color: var(--error-line-bg) !important;
      }

      .hljs-ln-line[data-error-line] .hljs-ln-numbers {
        background-color: var(--error-line-num-bg) !important;
        color: var(--error-line-num-color) !important;
        font-weight: bold;
      }

      body {
        background-color: var(--color-bg);
        color: var(--color-text);
        font-family: 'JetBrains Mono', 'Consolas', 'Monaco', monospace;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding: 1.25rem 0;
        margin: 0;
      }

      .page__container {
        width: 100%;
        padding-inline: max(2rem, calc(50vw - 36rem + 32px));
      }

      .error-header__body {
        background-color: var(--color-header-bg);
        color: var(--color-header-text);
        padding: 1.25rem;
        border-radius: 0.375rem;
      }

      .error-header__content {
        display: flex;
        gap: 1.5rem;
        align-items: stretch;
      }

      .error-header__main {
        display: flex;
        flex-direction: column;
        gap: 20px;
        flex: 1;
        min-width: 0;
      }

      .error-header__title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
      }

      .error-header__message {
        margin: 0;
        color: inherit;
      }

      .tech-info {
        background: var(--color-tech-info-bg);
        padding: 1rem;
        border-radius: 0.375rem;
        border: 1px solid var(--color-tech-info-border);
        flex-shrink: 0;
        min-width: 280px;
        box-shadow:
          inset 0 3px 6px var(--color-tech-info-shadow),
          inset 0 1px 0 rgba(255, 255, 255, 0.15),
          0 2px 4px var(--color-frame-border);
      }

      .tech-info__list {
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }

      .tech-info__item {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
      }

      .tech-info__label {
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }

      .tech-info__value {
        font-size: 0.875rem;
        margin: 0;
        word-break: break-all;
        font-family: inherit;
        color: var(--color-text);
      }

      .theme-toggle--fixed {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 1000;
        background: var(--color-header-bg);
        color: var(--color-header-text);
        border: 2px solid var(--color-frame-border);
        border-radius: 50%;
        width: 3rem;
        height: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px var(--color-frame-border);
        transition: all 0.2s ease;
        cursor: pointer;
        outline: none;
      }

      .theme-toggle--fixed:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px var(--color-frame-border);
      }

      .theme-toggle--fixed:focus {
        box-shadow: 0 0 0 3px var(--color-header-bg);
      }

      .theme-toggle--fixed .theme-toggle__icon {
        font-size: 1.25rem;
        line-height: 1;
        display: block;
      }

      .theme-toggle:not(.theme-toggle--fixed) {
        display: none;
      }

      .error-main {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
      }

      .error-frame {
        background-color: var(--color-frame-bg);
        border-radius: 0.375rem;
        border: 1px solid var(--color-frame-border);
        overflow: hidden;
      }

      .error-frame[open] .error-frame__summary {
        border-radius: 0.375rem 0.375rem 0 0;
      }

      .error-frame__summary {
        padding: 1rem;
        border-bottom: 1px solid var(--color-frame-border);
        cursor: pointer;
        background-color: var(--color-frame-bg);
        border-radius: 0.375rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        list-style: none;
      }

      .error-frame__summary::-webkit-details-marker {
        display: none;
      }

      .error-frame__file {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
      }

      .error-frame__function {
        font-size: 0.95rem;
        opacity: 0.8;
      }

      .error-frame__body {
        padding: 0;
      }

      .error-frame__body pre {
        margin: 0;
        padding: 0;
        background: transparent !important;
        border-radius: 0;
      }

      pre,
      code,
      pre[class*="language-"] {
        font-family: 'JetBrains Mono', 'Consolas', 'Monaco', monospace !important;
      }

      .suggestions {
        background: var(--color-suggestions-bg);
        padding: 1rem;
        border-radius: 0.375rem;
        border: 1px solid var(--color-suggestions-border);
        margin-top: auto;
        box-shadow:
          inset 0 3px 6px var(--color-suggestions-shadow),
          inset 0 1px 0 rgba(255, 255, 255, 0.15),
          0 2px 4px var(--color-suggestions-border);
      }

      .suggestions__list {
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }

      .suggestions__item {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
      }

      .suggestions__label {
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }

      .suggestions__value {
        font-size: 0.875rem;
        margin: 0;
        word-break: break-all;
        font-family: inherit;
        color: var(--color-text);
      }

      @media (max-width: 768px) {
        .error-header__content {
          flex-direction: column;
          gap: 1rem;
        }

        .tech-info {
          min-width: auto;
          width: 100%;
        }

        .theme-toggle--fixed {
          top: 1rem;
          right: 1rem;
          width: 2.5rem;
          height: 2.5rem;
        }

        .theme-toggle--fixed .theme-toggle__icon {
          font-size: 1rem;
        }

        .page__container {
          padding-inline: 1rem;
        }
      }
    </style>
  </head>
  <body>
    <button class="theme-toggle theme-toggle--fixed" aria-label="Switch theme">
      <span class="theme-toggle__icon" id="theme-toggle-icon">☀️</span>
    </button>
    <header class="error-header page__container">
      <div class="error-header__body">
        <div class="error-header__content">
          <div class="error-header__main">
            <h1 class="error-header__title"><?= $heading_title ?></h1>
            <p class="error-header__message"><?= $message ?></p>
            <?php if (!empty($suggestions)): ?>
            <div class="suggestions">
              <dl class="suggestions__list">
                <?php foreach ($suggestions as $suggestion): ?>
                <div class="suggestions__item">
                  <dt class="suggestions__label"><?= $suggestion['icon'] ?> Hint</dt>
                  <dd class="suggestions__value"><?= $suggestion['text'] ?></dd>
                </div>
                <?php endforeach; ?>
              </dl>
            </div>
            <?php endif; ?>
          </div>
          <div class="tech-info">
            <dl class="tech-info__list">
              <?php foreach ($tech_info as $label => $value): ?>
              <div class="tech-info__item">
                <dt class="tech-info__label"><?= $label ?></dt>
                <dd class="tech-info__value"><?= $value ?></dd>
              </div>
              <?php endforeach; ?>
            </dl>
          </div>
        </div>
      </div>
    </header>
    <main class="error-main page__container">
      <?php foreach ($frames as $index => $frame): ?>
      <details class="error-frame" <?= $index === 0 ? 'open' : '' ?> data-error-line="<?= $frame['line'] ?>" data-start-line="<?= $frame['start_line'] ?>">
      <summary class="error-frame__summary">
        <span class="error-frame__file"><?= $frame['file'] ?>:<?= $frame['line'] ?></span>
        <span class="error-frame__function"><?= $frame['function'] ?>()</span>
      </summary>
      <div class="error-frame__body">
        <pre><code class="language-php"><?= $frame['code'] ?></code></pre>
      </div>
      </details>
      <?php endforeach; ?>
    </main>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js"></script>
    <script>
      const body = document.body;
      const icon = document.getElementById('theme-toggle-icon');
      const themeToggle = document.querySelector('.theme-toggle--fixed');
      const THEME_KEY = 'opencart-exception-page-theme';

      const setHighlightTheme = (isDark) => {
        const lightLink = document.getElementById('hljs-theme-light');
        const darkLink = document.getElementById('hljs-theme-dark');

        if (isDark) {
          lightLink?.remove();

          if (!darkLink) {
            const link = document.createElement('link');
            link.id = 'hljs-theme-dark';
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/atom-one-dark-reasonable.min.css';
            document.head.appendChild(link);
          }
        } else {
          darkLink?.remove();

          if (!lightLink) {
            const link = document.createElement('link');
            link.id = 'hljs-theme-light';
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/atom-one-light.min.css';
            document.head.appendChild(link);
          }
      }
      }

      const updateHighlightTheme = () => {
        setHighlightTheme(document.body.classList.contains('theme_dark'));
      }

      const applyTheme = (theme) => {
        if (theme === 'dark') {
          body.classList.add('theme_dark');
          body.classList.remove('theme_light');
          icon.textContent = '☀️';
        } else {
          body.classList.add('theme_light');
          body.classList.remove('theme_dark');
          icon.textContent = '🌙';
        }

        updateHighlightTheme();
      }

      const getSystemTheme = () => {
        return window.matchMedia('(prefers-color-scheme: dark)').matches
          ? 'dark'
          : 'light';
      }

      const initTheme = () => {
        const savedTheme = localStorage.getItem(THEME_KEY);

        if (savedTheme === 'light' || savedTheme === 'dark') {
          applyTheme(savedTheme);
        } else {
          applyTheme(getSystemTheme());
        }
      }

      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      const handleSystemThemeChange = (e) => {
        if (!localStorage.getItem(THEME_KEY)) {
          applyTheme(e.matches ? 'dark' : 'light');
        }
      }
      mediaQuery.addEventListener('change', handleSystemThemeChange);

      themeToggle.addEventListener('click', () => {
        const currentTheme = body.classList.contains('theme_light') ? 'light' : 'dark';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        localStorage.setItem(THEME_KEY, newTheme);
        applyTheme(newTheme);
      });

      initTheme();
    </script>
    <script>
      window.addEventListener('DOMContentLoaded', () => {
        hljs.highlightAll();
        updateHighlightTheme();

        document.querySelectorAll('.error-frame').forEach((frame) => {
          const startLine = parseInt(frame.dataset.startLine, 10) || 1;
          const codeBlock = frame.querySelector('pre code');

          if (codeBlock) {
            hljs.lineNumbersBlock(codeBlock, { startFrom: startLine });
          }
        });

        setTimeout(() => {
          document.querySelectorAll('.error-frame').forEach((frame) => {
            const errorLine = parseInt(frame.dataset.errorLine, 10);
            if (errorLine) {
              const codeTd = frame.querySelector('.hljs-ln-code[data-line-number="' + errorLine + '"]');
              const numTd = frame.querySelector('.hljs-ln-numbers[data-line-number="' + errorLine + '"]');

              if (codeTd) {
                codeTd.setAttribute('data-error-line', 'true');
              }

              if (numTd) {
                numTd.setAttribute('data-error-line', 'true');
              }
            }
          });
        }, 0);
      });
    </script>
  </body>
</html>
