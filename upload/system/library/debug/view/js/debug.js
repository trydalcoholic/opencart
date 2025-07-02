// Debug JavaScript for OpenCart
(() => {
  'use strict';

  // Theme management
  const body = document.body;
  const themeToggle = document.querySelector('.theme-toggle--fixed');
  const themeIcon = document.getElementById('theme-toggle-icon');
  const THEME_KEY = 'opencart-debug-theme';

  /**
   * Set highlight.js theme based on current theme
   */
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
  };

  /**
   * Apply theme to body and update icon
   */
  const applyTheme = (theme) => {
    if (theme === 'dark') {
      body.classList.add('theme_dark');
      body.classList.remove('theme_light');
      if (themeIcon) themeIcon.textContent = '☀️';
    } else {
      body.classList.add('theme_light');
      body.classList.remove('theme_dark');
      if (themeIcon) themeIcon.textContent = '🌙';
    }

    setHighlightTheme(theme === 'dark');
  };

  /**
   * Get system preferred theme
   */
  const getSystemTheme = () => {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  };

  /**
   * Initialize theme on page load
   */
  const initTheme = () => {
    const savedTheme = localStorage.getItem(THEME_KEY);

    if (savedTheme === 'light' || savedTheme === 'dark') {
      applyTheme(savedTheme);
    } else {
      applyTheme(getSystemTheme());
    }
  };

  /**
   * Handle system theme changes
   */
  const handleSystemThemeChange = (e) => {
    // Only apply system theme if user hasn't set a preference
    if (!localStorage.getItem(THEME_KEY)) {
      applyTheme(e.matches ? 'dark' : 'light');
    }
  };

  /**
   * Toggle theme manually
   */
  const toggleTheme = () => {
    const currentTheme = body.classList.contains('theme_light') ? 'light' : 'dark';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    localStorage.setItem(THEME_KEY, newTheme);
    applyTheme(newTheme);
  };

  /**
   * Initialize code highlighting
   */
  const initCodeHighlighting = () => {
    // Wait for highlight.js to load
    if (typeof hljs === 'undefined') {
      setTimeout(initCodeHighlighting, 100);
      return;
    }

    // Highlight all code blocks
    hljs.highlightAll();

    // Add line numbers to error frames
    document.querySelectorAll('.error-frame').forEach((frame) => {
      const startLine = parseInt(frame.dataset.startLine, 10) || 1;
      const errorLine = parseInt(frame.dataset.errorLine, 10);
      const codeBlock = frame.querySelector('pre code');

      if (codeBlock && typeof hljs.lineNumbersBlock === 'function') {
        hljs.lineNumbersBlock(codeBlock, { startFrom: startLine });

        // Mark error line after line numbers are added
        if (errorLine) {
          setTimeout(() => {
            const codeTd = frame.querySelector(`.hljs-ln-code[data-line-number="${errorLine}"]`);
            const numTd = frame.querySelector(`.hljs-ln-numbers[data-line-number="${errorLine}"]`);

            if (codeTd) codeTd.setAttribute('data-error-line', 'true');
            if (numTd) numTd.setAttribute('data-error-line', 'true');
          }, 50);
        }
      }
    });
  };

  /**
   * Initialize dump variable interactions
   */
  const initDumpInteractions = () => {
    // Add click-to-expand for large objects/arrays
    document.querySelectorAll('.dump-variable').forEach((variable) => {
      const content = variable.querySelector('.dump-content');
      if (content && content.scrollHeight > 200) {
        variable.classList.add('dump-collapsed');

        const expandBtn = document.createElement('button');
        expandBtn.className = 'dump-expand-btn';
        expandBtn.textContent = 'Show more';
        expandBtn.onclick = () => {
          variable.classList.toggle('dump-collapsed');
          expandBtn.textContent = variable.classList.contains('dump-collapsed') ? 'Show more' : 'Show less';
        };

        variable.appendChild(expandBtn);
      }
    });
  };

  /**
   * Add keyboard shortcuts
   */
  const initKeyboardShortcuts = () => {
    document.addEventListener('keydown', (e) => {
      // Ctrl/Cmd + D to toggle theme
      if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        toggleTheme();
      }

      // Escape to close expanded frames
      if (e.key === 'Escape') {
        document.querySelectorAll('.error-frame[open]').forEach(frame => {
          frame.removeAttribute('open');
        });
      }
    });
  };

  /**
   * Initialize everything when DOM is ready
   */
  const init = () => {
    // Initialize theme
    initTheme();

    // Listen for system theme changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', handleSystemThemeChange);

    // Add theme toggle listener
    if (themeToggle) {
      themeToggle.addEventListener('click', toggleTheme);
    }

    // Initialize code highlighting
    initCodeHighlighting();

    // Initialize dump interactions
    initDumpInteractions();

    // Initialize keyboard shortcuts
    initKeyboardShortcuts();

    // Open first error frame by default
    const firstFrame = document.querySelector('.error-frame');
    if (firstFrame) {
      firstFrame.setAttribute('open', '');
    }
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose some functions globally for debugging
  window.debugUtils = {
    toggleTheme,
    applyTheme,
    initCodeHighlighting
  };
})();
