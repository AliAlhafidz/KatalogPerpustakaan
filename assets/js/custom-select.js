// Custom Select / Modal Picker — lightweight, single global handlers, event delegation
(() => {
  const SELECTOR = 'select';
  let idCounter = 0;
  const modals = new Set();
  let globalEscapeBound = false;

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function lockScroll() {
    if (modals.size > 0) document.documentElement.classList.add('modal-open');
  }
  function unlockScroll() {
    if (modals.size === 0) document.documentElement.classList.remove('modal-open');
    // also clear body overflow fallback
    if (!document.querySelector('.custom-select-modal.is-open') && !document.querySelector('.picker-modal.is-open') && !document.querySelector('.app-modal.is-open')) {
      document.body.style.overflow = '';
      document.documentElement.classList.remove('modal-open');
    }
  }

  function buildModal(select, opts) {
    const selectId = 'cs-' + (++idCounter);
    const placeholder = select.getAttribute('data-placeholder') || opts.placeholder || 'Pilih...';
    const selectedOption = select.options[select.selectedIndex];
    const selectedText = selectedOption ? selectedOption.textContent.trim() : placeholder;
    const selectedValue = select.value;

    const wrapper = document.createElement('div');
    wrapper.className = 'custom-select-wrapper';
    wrapper.dataset.customSelect = '';

    select.style.display = 'none';
    select.setAttribute('data-custom-original', '1');
    if (!select.id) select.id = selectId + '-orig';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-select-trigger w-full flex items-center justify-between gap-2 border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 text-left';
    trigger.style.cssText = 'border-color: var(--border); background: var(--surface); color: var(--text)';
    if (select.classList.contains('h-12')) trigger.classList.add('h-12');
    if (select.classList.contains('h-10')) trigger.classList.add('h-10');
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', selectId + '-modal');
    trigger.innerHTML = `<span class="custom-select-value truncate">${escapeHtml(selectedText)}</span><i class="bi bi-chevron-down shrink-0" style="color: var(--text-faint)"></i>`;

    const modal = document.createElement('div');
    modal.id = selectId + '-modal';
    modal.className = 'custom-select-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', selectId + '-title');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <div class="custom-select-backdrop" data-close></div>
      <div class="custom-select-panel" role="document">
        <div class="custom-select-header">
          <h2 id="${selectId}-title" class="custom-select-title">${escapeHtml(opts.title || placeholder)}</h2>
          <button type="button" class="custom-select-close" aria-label="Tutup" data-close><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="custom-select-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" class="custom-select-search" placeholder="Cari..." autocomplete="off" aria-label="Cari pilihan">
        </div>
        <div class="custom-select-options" role="listbox"></div>
        <div class="custom-select-empty hidden">Tidak ada hasil.</div>
      </div>
    `;

    const optionsContainer = modal.querySelector('.custom-select-options');
    const searchInput = modal.querySelector('.custom-select-search');
    const emptyEl = modal.querySelector('.custom-select-empty');

    // Delegated click for options — single listener
    optionsContainer.addEventListener('click', (e) => {
      const item = e.target.closest('.custom-select-option');
      if (!item) return;
      const val = item.dataset.value;
      const text = item.querySelector('span')?.textContent?.trim() ?? val;
      select.value = val;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      select.dispatchEvent(new Event('input', { bubbles: true }));
      trigger.querySelector('.custom-select-value').textContent = text;
      trigger.querySelector('.custom-select-value').classList.remove('text-slate-400');
      trigger.querySelector('.custom-select-value').style.color = '';
      // update visual selected state without full re-render
      optionsContainer.querySelectorAll('.custom-select-option').forEach(o => {
        const isSel = o.dataset.value === val;
        o.classList.toggle('is-selected', isSel);
        o.setAttribute('aria-selected', isSel ? 'true' : 'false');
        const icon = o.querySelector('i');
        if (icon) icon.className = isSel ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        if (icon) icon.style.color = isSel ? 'var(--accent)' : 'var(--text-faint-2)';
      });
      close();
      if (select.getAttribute('onchange')?.includes('submit')) {
        select.form?.requestSubmit();
      }
    });

    function renderOptions(filter = '') {
      const term = filter.toLowerCase().trim();
      // Build HTML string once, single DOM write
      let html = '';
      let visible = 0;
      const currentVal = select.value;
      for (const opt of Array.from(select.options)) {
        const text = opt.textContent.trim();
        const val = opt.value;
        if (term && !text.toLowerCase().includes(term) && !val.toLowerCase().includes(term)) continue;
        visible++;
        const isSel = val === currentVal;
        const cls = isSel ? 'custom-select-option is-selected' : 'custom-select-option';
        const icon = isSel ? '<i class="bi bi-check-circle-fill" style="color: var(--accent)"></i>' : '<i class="bi bi-circle" style="color: var(--text-faint-2)"></i>';
        html += `<button type="button" class="${cls}" role="option" aria-selected="${isSel ? 'true' : 'false'}" data-value="${escapeHtml(val)}"><span class="truncate">${escapeHtml(text)}</span><span class="ml-auto">${icon}</span></button>`;
      }
      optionsContainer.innerHTML = html;
      emptyEl.classList.toggle('hidden', visible !== 0);
      optionsContainer.classList.toggle('hidden', visible === 0);
    }

    let searchTimer = null;
    function debounceSearch(val) {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => renderOptions(val), 70);
    }

    function open() {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      trigger.setAttribute('aria-expanded', 'true');
      modals.add(modal);
      lockScroll();
      searchInput.value = '';
      renderOptions('');
      // focus after paint
      requestAnimationFrame(() => searchInput.focus());
    }
    function close() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      trigger.setAttribute('aria-expanded', 'false');
      modals.delete(modal);
      unlockScroll();
      trigger.focus();
    }

    trigger.addEventListener('click', open);
    // single delegated close for backdrop + close button
    modal.addEventListener('click', (e) => {
      if (e.target.closest('[data-close]')) close();
      else if (e.target === modal) close();
    });
    searchInput.addEventListener('input', () => debounceSearch(searchInput.value));

    trigger.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        open();
      }
    });

    if (!selectedValue) {
      const v = trigger.querySelector('.custom-select-value');
      v.classList.add('text-slate-400');
      v.style.color = 'var(--text-faint-2)';
    }

    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(trigger);
    wrapper.appendChild(select);
    document.body.appendChild(modal);

    // sync external changes
    const observer = new MutationObserver(() => {
      const opt = select.options[select.selectedIndex];
      if (opt) {
        trigger.querySelector('.custom-select-value').textContent = opt.textContent.trim();
        trigger.querySelector('.custom-select-value').classList.remove('text-slate-400');
        trigger.querySelector('.custom-select-value').style.color = '';
      }
    });
    observer.observe(select, { attributes: true, childList: true, subtree: true });

    // expose close for global escape
    modal._csClose = close;
    return { wrapper, trigger, modal, select };
  }

  function bindGlobalEscape() {
    if (globalEscapeBound) return;
    globalEscapeBound = true;
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      // close topmost open modal
      for (const m of Array.from(modals).reverse()) {
        if (m.classList.contains('is-open') && m._csClose) {
          e.preventDefault();
          m._csClose();
          break;
        }
      }
    });
  }

  function init() {
    bindGlobalEscape();
    const selects = document.querySelectorAll(SELECTOR);
    selects.forEach(sel => {
      if (sel.dataset.customOriginal || sel.dataset.noCustom) return;
      if (sel.type === 'hidden') return;
      if (sel.closest('.custom-select-wrapper')) return;

      let title = sel.getAttribute('data-title') || '';
      if (!title) {
        const label = document.querySelector(`label[for="${sel.id}"]`);
        if (label) title = label.textContent.trim();
        else title = sel.getAttribute('name') || 'Pilih';
        title = title.replace(/\*$/, '').trim();
        if (title.toLowerCase().includes('kategori')) title = 'Pilih Kategori';
        else if (title.toLowerCase().includes('sort') || title === 'sort') title = 'Urutkan';
        else if (!title) title = 'Pilih';
        else title = 'Pilih ' + title.charAt(0).toUpperCase() + title.slice(1);
      }
      const placeholder = sel.querySelector('option[value=""]')?.textContent?.trim() || 'Pilih...';
      buildModal(sel, { title, placeholder });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  window.initCustomSelect = init;
})();
