</main>

<?php $auth_footer = in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['login.php', 'register.php', 'lupa_password.php', 'reset_password.php'], true); ?>
<?php if (!$auth_footer): ?>
<footer class="mt-8 border-t bg-[#0c0f12] text-stone-300 <?= (isset($menu_aktif) && $menu_aktif !== '') ? 'admin-footer' : ((is_anggota() && isset($member_menu_aktif) && $member_menu_aktif !== '') ? 'member-footer' : '') ?>" style="border-color:#1e2326">
  <div class="<?= (isset($menu_aktif) && $menu_aktif !== '') ? 'admin-footer-inner' : ((is_anggota() && isset($member_menu_aktif) && $member_menu_aktif !== '') ? 'member-footer-inner' : 'max-w-7xl mx-auto') ?> px-4 sm:px-6 py-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
      <div class="flex items-center gap-2.5 font-display font-bold text-white text-[15px] mb-2">
        <span class="w-8 h-8 rounded-lg bg-white text-[#0c0f12] flex items-center justify-center"><i class="bi bi-book-half text-sm"></i></span>
        Perpustakaan Umum
      </div>
      <p class="text-sm leading-6 text-stone-400 max-w-sm">Katalog klasik, pengalaman modern — ruang yang tenang untuk menemukan buku.</p>
    </div>
    <div>
      <div class="font-semibold text-white text-sm mb-2">Jam Layanan</div>
      <ul class="text-sm text-stone-400 space-y-1.5">
        <li>Senin – Jumat · 08.00 – 17.00</li>
        <li>Sabtu · 09.00 – 14.00</li>
        <li>Minggu &amp; Hari Libur · Tutup</li>
      </ul>
    </div>
    <div>
      <div class="font-semibold text-white text-sm mb-2">Hubungi Kami</div>
      <ul class="text-sm text-stone-400 space-y-1.5">
        <li><i class="bi bi-geo-alt mr-1.5"></i>Jl. Pendidikan No. 5, Kotamu</li>
        <li><i class="bi bi-telephone mr-1.5"></i>(021) 555-0123</li>
        <li><i class="bi bi-envelope mr-1.5"></i>perpusumum@contoh.id</li>
      </ul>
    </div>
  </div>
  <div class="border-t text-center text-stone-500 text-xs py-3 px-4" style="border-color:#1e2326">
    &copy; <?= date('Y') ?> Perpustakaan Umum Sejahtera · Proyek UKK
  </div>
</footer>
<?php endif; ?>


<!-- PWA install -->
<div id="pwaInstallWrap" class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-auto z-50 hidden">
  <button id="pwaInstallBtn" type="button"
    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">
    <i class="bi bi-download"></i>
    Install aplikasi
  </button>
</div>

<!-- Global confirmation / notice modal -->
<div id="appModal" class="app-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="appModalTitle">
  <div class="app-modal-backdrop" data-modal-close></div>
  <div class="app-modal-panel" role="document">
    <div class="app-modal-icon" id="appModalIcon"><i class="bi bi-question-circle"></i></div>
    <div class="app-modal-copy">
      <h2 id="appModalTitle">Konfirmasi</h2>
      <p id="appModalMessage"></p>
    </div>
    <div class="app-modal-actions">
      <button type="button" id="appModalCancel" class="app-modal-btn app-modal-cancel">Batal</button>
      <button type="button" id="appModalConfirm" class="app-modal-btn app-modal-confirm">Oke</button>
    </div>
  </div>
</div>

<style>
  .app-modal { position:fixed; inset:0; z-index:100; display:flex; align-items:center; justify-content:center; padding:1rem; visibility:hidden; opacity:0; pointer-events:none; transition:opacity .16s ease, visibility .16s ease; }
  .app-modal.is-open { visibility:visible; opacity:1; pointer-events:auto; }
  .app-modal-backdrop { position:absolute; inset:0; z-index:0; background:rgba(12,16,20,.38); }
  .app-modal-panel { position:relative; z-index:1; width:min(100%, 420px); border:1px solid var(--border); border-radius:12px; background: var(--surface); padding:20px; box-shadow:0 12px 40px rgba(16,24,40,.12); transform:translateY(6px); transition:transform .16s ease; }
  .app-modal.is-open .app-modal-panel { transform:none; }
  .app-modal-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:var(--accent-soft); color:var(--accent-text); font-size:1.1rem; margin-bottom:10px; }
  .app-modal-icon.is-danger { background:#fef2f2; color:#991b1b; }
  .app-modal-icon.is-info { background:var(--accent-soft); color:var(--accent-text); }
  .app-modal-copy h2 { margin:0; color:var(--text); font-size:1rem; font-weight:700; font-family:var(--font-display); }
  .app-modal-copy p { margin:6px 0 0; color:var(--text-muted); font-size:.875rem; line-height:1.55; white-space:pre-wrap; }
  .app-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:18px; }
  .app-modal-btn { position:relative; z-index:2; min-width:5rem; border-radius:8px; padding:8px 14px; font-size:.85rem; font-weight:600; border:1px solid transparent; cursor:pointer; }
  .app-modal-cancel { background:var(--surface); color:var(--text-muted); border-color:var(--border); }
  .app-modal-confirm { background:var(--accent); color:#fff; border-color:var(--accent); box-shadow:none; }
  .app-modal-confirm:hover { background:var(--accent-hover); border-color:var(--accent-hover); }
  .app-modal-confirm.is-danger { background:#b42318; border-color:#b42318; box-shadow:none; }
  .app-modal-btn:active { transform:none; }
  @media (max-width:520px) {
    .app-modal { align-items:flex-end; padding:12px; }
    .app-modal-panel { width:100%; border-radius:12px; padding:16px; }
    .app-modal-actions { display:grid; grid-template-columns:1fr 1fr; }
    .app-modal-btn { width:100%; }
  }
</style>

<script>
  const appModal = document.getElementById('appModal');
  const appModalTitle = document.getElementById('appModalTitle');
  const appModalMessage = document.getElementById('appModalMessage');
  const appModalIcon = document.getElementById('appModalIcon');
  const appModalCancel = document.getElementById('appModalCancel');
  const appModalConfirm = document.getElementById('appModalConfirm');
  let appModalResolve = null;
  let appModalPreviousFocus = null;

  function closeAppModal(result = false) {
    if (!appModal) return;
    appModal.classList.remove('is-open');
    appModal.setAttribute('aria-hidden', 'true');
    // unlock only if no other modal is open
    if (!document.querySelector('.picker-modal.is-open') && !document.querySelector('.custom-select-modal.is-open')) {
      document.documentElement.classList.remove('modal-open');
      document.body.classList.remove('overflow-hidden');
    }
    if (appModalResolve) { const resolve = appModalResolve; appModalResolve = null; resolve(result); }
    appModalPreviousFocus?.focus?.();
    appModalPreviousFocus = null;
  }

  function openAppModal(message, options = {}) {
    if (!appModal) return Promise.resolve(options.notice ? true : false);
    if (appModalResolve) closeAppModal(false);
    appModalPreviousFocus = document.activeElement;
    appModalTitle.textContent = options.title || (options.notice ? 'Pemberitahuan' : 'Konfirmasi');
    appModalMessage.textContent = message || '';
    appModalIcon.classList.toggle('is-danger', !!options.danger);
    appModalIcon.classList.toggle('is-info', !!options.notice);
    appModalIcon.innerHTML = options.notice
      ? '<i class="bi bi-info-circle"></i>'
      : (options.danger ? '<i class="bi bi-exclamation-triangle"></i>' : '<i class="bi bi-question-circle"></i>');
    appModalConfirm.textContent = options.confirmText || 'Oke';
    appModalConfirm.classList.toggle('is-danger', !!options.danger);
    appModalCancel.textContent = options.cancelText || 'Batal';
    appModalCancel.classList.toggle('hidden', !!options.notice);
    appModal.classList.add('is-open');
    appModal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('modal-open');
    document.body.classList.add('overflow-hidden');
    window.setTimeout(() => (options.notice ? appModalConfirm : appModalCancel).focus(), 30);
    return new Promise(resolve => { appModalResolve = resolve; });
  }

  function showConfirmModal(message, options = {}) { return openAppModal(message, { ...options, notice:false }); }
  function showNoticeModal(message, options = {}) { return openAppModal(message, { ...options, notice:true }); }

  appModalCancel?.addEventListener('click', () => closeAppModal(false));
  appModalConfirm?.addEventListener('click', () => closeAppModal(true));
  appModal?.querySelector('[data-modal-close]')?.addEventListener('click', () => closeAppModal(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && appModal?.classList.contains('is-open')) closeAppModal(false); });

  // Confirmation untuk link/tombol yang bukan form.
  // Form ditangani oleh listener submit di bawah agar tidak terjadi double-intercept.
  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-confirm]');
    if (!trigger || trigger.tagName === 'FORM' || trigger.closest('form[data-confirm]') || trigger.dataset.modalBusy === '1') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    trigger.dataset.modalBusy = '1';
    showConfirmModal(trigger.dataset.confirm, {
      title: 'Konfirmasi Tindakan',
      confirmText: trigger.dataset.confirmText || 'Oke',
      danger: trigger.dataset.confirmDanger === 'true'
    }).then(ok => {
      trigger.dataset.modalBusy = '0';
      if (!ok) return;
      if (trigger.tagName === 'A' && trigger.href) {
        window.location.href = trigger.href;
      } else if (trigger.tagName === 'BUTTON' && trigger.type === 'submit') {
        trigger.form?.requestSubmit(trigger);
      }
    });
  }, true);

  // Form-level confirmations (dipakai oleh hapus buku, anggota, kategori, dll).
  document.addEventListener('submit', event => {
    const form = event.target.closest('form[data-confirm]');
    if (!form || form.dataset.modalBusy === '1' || form.dataset.confirmed === '1') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    form.dataset.modalBusy = '1';
    showConfirmModal(form.dataset.confirm, {
      title: 'Konfirmasi Tindakan',
      confirmText: form.dataset.confirmText || 'Oke',
      danger: form.dataset.confirmDanger === 'true'
    }).then(ok => {
      form.dataset.modalBusy = '0';
      if (!ok) return;
      // Submit native agar listener konfirmasi tidak menangkap submit kedua kalinya.
      form.dataset.confirmed = '1';
      HTMLFormElement.prototype.submit.call(form);
    });
  }, true);

  // Light/Dark theme: apply immediately and persist. Migrate old accent values to light.
  function applyPerpusTheme(theme) {
    const allowed = ['light','dark'];
    if (!allowed.includes(theme)) {
      if (['ocean','emerald','violet','rose','amber'].includes(theme)) theme = 'light';
      else theme = 'light';
    }
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('perpus-theme', theme); } catch (e) {}
    const isDark = theme === 'dark';
    document.querySelectorAll('[data-theme-toggle], #themeToggle').forEach(btn => {
      btn.setAttribute('aria-label', isDark ? 'Aktifkan Light Mode' : 'Aktifkan Dark Mode');
      btn.setAttribute('title', isDark ? 'Aktifkan Light Mode' : 'Aktifkan Dark Mode');
      const icon = btn.querySelector('i');
      if (icon) icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    });
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', isDark ? '#14181b' : '#243a5e');
  }

  document.addEventListener('click', (event) => {
    const t = event.target.closest('[data-theme-toggle], #themeToggle');
    if (!t) return;
    event.preventDefault();
    const cur = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
    applyPerpusTheme(cur === 'dark' ? 'light' : 'dark');
  });
  applyPerpusTheme(document.documentElement.dataset.theme || 'light');

  const navToggle = document.getElementById('navToggle');
  const navClose = document.getElementById('navClose');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const drawerBackdrop = document.getElementById('drawerBackdrop');

  function setDrawer(open) {
    if (!mobileDrawer || !drawerBackdrop) return;
    mobileDrawer.classList.toggle('open', open);
    drawerBackdrop.classList.toggle('open', open);
    mobileDrawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (navToggle) navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('overflow-hidden', open);
    document.body.classList.toggle('drawer-is-open', open);
  }
  navToggle?.addEventListener('click', () => setDrawer(true));
  navClose?.addEventListener('click', () => setDrawer(false));
  drawerBackdrop?.addEventListener('click', () => setDrawer(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') setDrawer(false); });

  function togglePasswordVisibility(btn) {
    const input = btn.previousElementSibling;
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
    } else {
      input.type = 'password';
      if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
    }
  }

  // Turn every data table into labeled cards on mobile without changing the PHP templates.
  document.querySelectorAll('table:not(.no-responsive)').forEach((table) => {
    const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
    table.querySelectorAll('tbody tr').forEach((row) => {
      Array.from(row.children).forEach((cell, index) => {
        if (cell.tagName === 'TD' && !cell.hasAttribute('data-label') && !cell.hasAttribute('colspan')) {
          cell.setAttribute('data-label', headers[index] || 'Detail');
        }
      });
    });
  });

  // Reveal animations disabled — quiet library: content appears immediately, no motion.
  document.querySelectorAll('.reveal').forEach((el) => el.classList.add('is-visible'));

  // Progressive Web App (PWA)
  let deferredInstallPrompt = null;
  const pwaWrap = document.getElementById('pwaInstallWrap');
  const pwaBtn = document.getElementById('pwaInstallBtn');

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    pwaWrap?.classList.remove('hidden');
  });

  pwaBtn?.addEventListener('click', async () => {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    pwaWrap?.classList.add('hidden');
  });

  window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    pwaWrap?.classList.add('hidden');
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js', { scope: '<?= BASE_URL ?>/' })
        .catch(err => console.warn('PWA service worker gagal:', err));
    });
  }

</script>
<script src="<?= BASE_URL ?>/assets/js/custom-select.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/katalog.js" defer></script>
</body>
</html>
