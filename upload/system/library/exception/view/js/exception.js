// Debug JavaScript for OpenCart
(() => {
  'use strict';

  // Theme management
  const body = document.body;
  const themeSwitcher = document.querySelector('.theme-switcher')
  const themeRadios = document.querySelectorAll('.theme-switcher__radio');
  const THEME_KEY = 'opencart-debug-theme';

  /**
   * Set highlight.js theme based on current theme
   */
  const setHighlightTheme = (isDark) => {
    // CSS variables handle theme switching automatically
  };

  /**
   * Get current effective theme (what's actually being displayed)
   */
  const getEffectiveTheme = () => {
    if (body.classList.contains('theme_light')) {
      return 'light';
    } else if (body.classList.contains('theme_dark')) {
      return 'dark';
    } else {
      // No forced class, using system theme
      return getSystemTheme();
    }
  };

  /**
   * Apply theme to body and update radio buttons
   */
  const applyTheme = (theme) => {
    // Remove all theme classes first
    body.classList.remove('theme_light', 'theme_dark');

    if (theme === 'light') {
      body.classList.add('theme_light');
    } else if (theme === 'dark') {
      body.classList.add('theme_dark');
    }
    // For 'system', we don't add any class - let CSS prefers-color-scheme handle it

    // Update radio button selection
    const targetRadio = document.querySelector(`.theme-switcher__radio--${theme}`);
    if (targetRadio) {
      targetRadio.checked = true;
    }

    setHighlightTheme(getEffectiveTheme() === 'dark');
  };

  /**
   * Get system preferred theme
   */
  const getSystemTheme = () => {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  };

  /**
   * Get current theme setting from radio buttons
   */
  const getCurrentThemeSetting = () => {
    const checkedRadio = document.querySelector('.theme-switcher__radio:checked');
    return checkedRadio ? checkedRadio.value : 'system';
  };

  /**
   * Initialize theme on page load
   */
  const initTheme = () => {
    const savedTheme = localStorage.getItem(THEME_KEY);

    if (savedTheme === 'light' || savedTheme === 'dark' || savedTheme === 'system') {
      applyTheme(savedTheme);
    } else {
      // Default to system theme
      applyTheme('system');
    }
  };

  /**
   * Handle system theme changes
   */
  const handleSystemThemeChange = (e) => {
    // Only update if we're currently using system theme
    const currentSetting = getCurrentThemeSetting();
    if (currentSetting === 'system') {
      // The CSS will handle the visual change automatically
      // We just need to update the highlight.js theme
      setHighlightTheme(e.matches);
    }
  };

  /**
   * Handle theme radio button changes
   */
  const handleThemeChange = (e) => {
    const newTheme = e.target.value;
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
      // Ctrl/Cmd + D to cycle themes
      if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();

        const current = getCurrentThemeSetting();
        const themes = ['light', 'system', 'dark'];
        const currentIndex = themes.indexOf(current);
        const nextIndex = (currentIndex + 1) % themes.length;
        const nextTheme = themes[nextIndex];

        localStorage.setItem(THEME_KEY, nextTheme);
        applyTheme(nextTheme);
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

    // Add theme radio listeners
    themeRadios.forEach(radio => {
      radio.addEventListener('change', handleThemeChange);
    });

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
    applyTheme,
    initCodeHighlighting,
    getCurrentThemeSetting,
    getEffectiveTheme
  };
})();
