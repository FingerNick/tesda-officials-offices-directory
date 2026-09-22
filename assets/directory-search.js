(() => {
  const form = document.getElementById('directory-search');
  const results = document.getElementById('directory-results');
  const status = document.getElementById('search-status');
  if (!form || !results || !window.fetch) return;

  let controller;
  let inputTimer;

  const searchUrl = () => {
    const url = new URL(form.action, window.location.href);
    const params = new URLSearchParams(new FormData(form));
    for (const [key, value] of params) {
      if (value) url.searchParams.set(key, value);
    }
    return url;
  };

  const loadResults = async (url, { syncForm = false, historyMode = 'push' } = {}) => {
    if (controller) controller.abort();
    controller = new AbortController();
    const request = controller;
    results.setAttribute('aria-busy', 'true');
    status.className = 'sr-only';
    status.textContent = 'Searching…';

    try {
      const response = await fetch(url, { signal: request.signal });
      if (!response.ok) throw new Error('Search failed');
      const page = new DOMParser().parseFromString(await response.text(), 'text/html');
      const nextResults = page.getElementById('directory-results');
      const nextForm = page.getElementById('directory-search');
      if (!nextResults || !nextForm) throw new Error('Search response is incomplete');
      if (request !== controller) return;

      results.innerHTML = nextResults.innerHTML;
      if (syncForm) form.innerHTML = nextForm.innerHTML;
      document.dispatchEvent(new Event('directory:updated'));
      if (historyMode === 'push') history.pushState(null, '', url);
      if (historyMode === 'replace') history.replaceState(null, '', url);
      status.textContent = results.querySelector('.directory-result-count')?.textContent || 'Search updated.';
    } catch (error) {
      if (error.name === 'AbortError' || request !== controller) return;
      status.className = 'mt-2 text-sm font-semibold text-white';
      status.textContent = 'Search could not update. Please try again.';
    } finally {
      if (request === controller) results.removeAttribute('aria-busy');
    }
  };

  const searchFromForm = (options) => {
    window.clearTimeout(inputTimer);
    loadResults(searchUrl(), options);
  };

  form.addEventListener('submit', event => {
    event.preventDefault();
    searchFromForm();
  });

  form.addEventListener('input', event => {
    if (event.target.name !== 'q') return;
    window.clearTimeout(inputTimer);
    inputTimer = window.setTimeout(() => loadResults(searchUrl(), { historyMode: 'replace' }), 350);
  });

  form.addEventListener('change', event => {
    if (event.target.name === 'q') return;
    searchFromForm({ syncForm: event.target.name === 'type' });
  });

  document.addEventListener('click', event => {
    const link = event.target.closest('.filter-tile, #clear-filters');
    if (!link) return;
    event.preventDefault();
    window.clearTimeout(inputTimer);
    loadResults(new URL(link.href), { syncForm: true });
  });

  window.addEventListener('popstate', () => {
    window.clearTimeout(inputTimer);
    loadResults(new URL(window.location.href), { syncForm: true, historyMode: 'none' });
  });
})();
