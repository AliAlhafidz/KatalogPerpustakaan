// Buku ISBN — dipisah dari admin/buku/_form.php agar cacheable
// Dipanggil hanya di halaman tambah buku. Semua lookup dilakukan di server (cari_isbn.php multi-provider).
(() => {
  const tabIsbn = document.getElementById('tab-isbn');
  const tabManual = document.getElementById('tab-manual');
  const isbnPanel = document.getElementById('isbn-panel');
  const detailForm = document.getElementById('detail-form');
  const manualHint = document.getElementById('manual-hint');
  const searchInput = document.getElementById('isbn-search');
  const btn = document.getElementById('btn-cari-isbn');
  const status = document.getElementById('isbn-status');
  const coverApi = document.getElementById('cover_api_url');
  if (!tabIsbn || !btn) return;

  function showForm() { detailForm.classList.remove('hidden'); }
  function activate(tab) {
    const active = 'flex-1 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-brand-700 shadow-sm ring-1 ring-slate-200';
    const inactive = 'flex-1 rounded-xl px-4 py-3 text-sm font-semibold text-slate-500 hover:bg-white';
    tabIsbn.className = tab === 'isbn' ? active : inactive;
    tabManual.className = tab === 'manual' ? active : inactive;
    isbnPanel.classList.toggle('hidden', tab !== 'isbn');
    manualHint.classList.toggle('hidden', tab !== 'manual');
    const isbnField = document.getElementById('isbn');
    if (isbnField) isbnField.readOnly = tab === 'isbn';
    showForm();
  }
  tabIsbn.addEventListener('click', () => activate('isbn'));
  tabManual.addEventListener('click', () => activate('manual'));

  function setStatus(message, type) {
    status.className = 'mt-3 text-sm rounded-xl px-3 py-2 ' + (type === 'ok' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : type === 'warn' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-red-50 text-red-700 border border-red-200');
    status.textContent = message;
    status.classList.remove('hidden');
  }
  function fill(name, value) {
    const el = document.querySelector('[name="' + name + '"]');
    if (el && value !== undefined && value !== null) el.value = value;
  }

  // Enter key di input ISBN juga trigger pencarian
  if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        btn.click();
      }
    });
  }

  btn.addEventListener('click', async () => {
    const isbn = searchInput.value.replace(/[^0-9Xx]/g, '');
    if (![10,13].includes(isbn.length)) { setStatus('ISBN harus berupa 10 atau 13 digit.', 'error'); return; }
    btn.disabled = true;
    const originalBtnHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Mencari...';
    status.classList.add('hidden');
    try {
      const base = document.body.dataset.baseUrl || '/perpustakaan';
      const res = await fetch(base + '/admin/buku/cari_isbn.php?isbn=' + encodeURIComponent(isbn), {headers:{'Accept':'application/json'}});
      const json = await res.json();
      if (!json.success) {
        const msg = json.message || 'Data buku tidak ditemukan. Silakan lengkapi data buku secara manual.';
        setStatus(msg, 'error');
        // Tetap tampilkan form agar admin bisa input manual, tapi kosongkan cover preview
        activate('manual');
        return;
      }
      const d = json.data;
      fill('isbn', d.isbn || isbn);
      fill('judul', d.judul || '');
      fill('penulis', d.penulis || '');
      fill('penerbit', d.penerbit || '');
      fill('tahun_terbit', d.tahun_terbit || '');
      fill('deskripsi', d.deskripsi || '');

      if (d.kategori && d.kategori.id) {
        const sel = document.getElementById('id_kategori');
        if (sel) sel.value = d.kategori.id;
      }

      // Cover — simpan URL untuk download server-side, tampilkan preview aman via DOM
      if (coverApi) coverApi.value = d.cover_url || '';
      const previewWrap = document.getElementById('cover-preview-wrap');
      if (previewWrap) {
        previewWrap.textContent = '';
        if (d.cover_url) {
          const img = document.createElement('img');
          // Validasi sederhana: harus https
          let url = String(d.cover_url);
          if (url.startsWith('https://')) {
            img.src = url;
            img.alt = 'Cover buku';
            img.className = 'w-24 h-36 object-cover rounded-xl shadow-sm border border-slate-200';
            // Jika gagal load, sembunyikan
            img.onerror = () => { previewWrap.classList.add('hidden'); };
            previewWrap.appendChild(img);
            previewWrap.classList.remove('hidden');
          } else {
            previewWrap.classList.add('hidden');
          }
        } else {
          previewWrap.classList.add('hidden');
        }
      }

      activate('isbn');

      // Tampilkan sumber metadata untuk transparansi/debugging
      let sourceMsg = 'Data ditemukan. Silakan periksa stok & rak sebelum menyimpan.';
      if (d.sumber_detail && d.sumber_detail.primary) {
        const provs = (d.sumber_detail.providers || d.providers || []).join(', ');
        sourceMsg += provs ? ' (Sumber: ' + provs + ')' : ' (Sumber: ' + d.sumber_detail.primary + ')';
      } else if (d.sumber) {
        // backward compat string
        const provs = (d.providers || []).join(', ');
        if (provs && provs !== d.sumber) sourceMsg += ' (Sumber: ' + provs + ')';
        else sourceMsg += ' (Sumber: ' + d.sumber + ')';
      }
      if (!d.deskripsi) {
        sourceMsg += ' — Sinopsis tidak tersedia untuk ISBN ini, silakan isi manual jika perlu.';
        setStatus(sourceMsg, 'warn');
      } else {
        setStatus(sourceMsg, 'ok');
      }

      // Jika kategori otomatis terisi, beri hint
      if (d.kategori && d.kategori.nama) {
        // Sudah terpilih, tidak perlu status tambahan
      }

    } catch (e) {
      setStatus('Gagal menghubungi layanan ISBN. Periksa koneksi dan coba lagi, atau gunakan input manual.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalBtnHtml;
    }
  });
})();
