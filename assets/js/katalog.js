// Katalog Buku — performa + UX
// Debounce, skeleton, history, fetch tanpa reload penuh
(() => {
  const form = document.querySelector('section form') || document.querySelector('form');
  if (!form || !form.querySelector('input[name="q"]')) return;
  const qInput = form.querySelector('input[name="q"]');
  const kategoriSelect = form.querySelector('select[name="kategori"]');
  const sortSelect = form.querySelector('select[name="sort"]');
  const tersediaCheckbox = form.querySelector('input[name="tersedia"]');
  const grid = document.getElementById('katalogGrid') || document.querySelector('.grid.grid-cols-2') || document.querySelector('#koleksi ~ .grid');
  if (!qInput || !grid) return;

  let debounceTimer = null;
  let activeController = null;
  let lastRequestId = 0;

  function showSkeleton() {
    const count = 18;
    let html = '';
    for (let i = 0; i < count; i++) {
      html += `
        <article class="bg-white rounded-lg border overflow-hidden" style="border-color: var(--border)">
          <div class="aspect-[2/3]" style="background: var(--surface-2)"></div>
          <div class="p-2 space-y-1.5">
            <div class="h-2 rounded" style="background: var(--surface-2); width: 45%"></div>
            <div class="h-2.5 rounded" style="background: var(--surface-2); width: 75%"></div>
            <div class="h-2 rounded" style="background: var(--surface-2); width: 60%"></div>
          </div>
        </article>`;
    }
    grid.innerHTML = html;
  }

  function buildUrl(withPageReset = true) {
    const params = new URLSearchParams(new FormData(form));
    // Hapus kategori 0 (semua)
    if (params.get('kategori') === '0') params.delete('kategori');
    if (!params.get('q')) params.delete('q');
    if (!params.get('sort')) params.delete('sort');
    if (!params.get('tersedia')) params.delete('tersedia');
    if (withPageReset) params.delete('halaman');
    const base = window.location.pathname;
    const qs = params.toString();
    return qs ? base + '?' + qs : base;
  }

  async function fetchAndUpdate(url, push = true) {
    const requestId = ++lastRequestId;
    // Batalkan request sebelumnya
    if (activeController) activeController.abort();
    activeController = new AbortController();
    showSkeleton();
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
        signal: activeController.signal
      });
      if (!res.ok) throw new Error('Network error');
      const html = await res.text();
      if (requestId !== lastRequestId) return; // hasil lama, abaikan
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const newGrid = doc.getElementById('katalogGrid') || doc.querySelector('.grid.grid-cols-2') || doc.querySelector('#koleksi ~ .grid');
      const newPagination = doc.querySelector('.flex.justify-center');
      const newCount = doc.querySelector('.rounded-full.bg-white.border');
      if (newGrid && grid) {
        grid.innerHTML = newGrid.innerHTML;

        // Re-apply lazy loading dan reveal
        grid.querySelectorAll('img').forEach(img => {
          if (!img.hasAttribute('loading')) img.setAttribute('loading', 'lazy');
          if (!img.hasAttribute('decoding')) img.setAttribute('decoding', 'async');
        });
      }
      // Update pagination
      const oldPag = document.getElementById('katalogPagination') || document.querySelector('.flex.justify-center.items-center');
      if (newPagination && oldPag) oldPag.replaceWith(newPagination);
      else if (!newPagination && oldPag) oldPag.remove();
      else if (newPagination && !oldPag) {
        grid?.after(newPagination);
      }
      // Update count & subtitle
      const newKoleksiHeader = doc.getElementById('koleksi');
      const oldKoleksiHeader = document.getElementById('koleksi');
      if (newKoleksiHeader && oldKoleksiHeader) {
        oldKoleksiHeader.innerHTML = newKoleksiHeader.innerHTML;
      }
      if (push) history.pushState(null, '', url);
      const targetElem = document.getElementById('koleksi');
      if (targetElem) {
        targetElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    } catch (e) {
      if (e.name === 'AbortError') return;
      console.warn('Katalog fetch gagal', e);
      window.location.href = url;
    } finally {
      if (requestId === lastRequestId) activeController = null;
    }
  }

  // Debounce untuk q
  if (qInput) {
    qInput.setAttribute('autocomplete', 'off');
    qInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const url = buildUrl(true);
        fetchAndUpdate(url, true);
      }, 350);
    });
    // Enter tetap submit biasa
  }

  // Kategori dan sort — langsung fetch tanpa debounce
  function onFilterChange() {
    const url = buildUrl(true);
    fetchAndUpdate(url, true);
  }
  kategoriSelect?.addEventListener('change', onFilterChange);
  sortSelect?.addEventListener('change', onFilterChange);
  tersediaCheckbox?.addEventListener('change', onFilterChange);

  // Intercept form submit untuk pakai fetch
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(debounceTimer);
    const url = buildUrl(true);
    fetchAndUpdate(url, true);
  });

  // Popstate
  window.addEventListener('popstate', () => {
    fetchAndUpdate(window.location.href, false);
  });
})();
