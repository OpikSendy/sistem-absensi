// assets/js/main.js
// FIXED: ReferenceError btnOpen & Mobile Optimization

(function(window, document){
  'use strict';

  /* ---------- 1. CONFIG & HELPER ---------- */
  // Safe Base URL
  try {
    window.BASE_URL = typeof BASE_URL !== 'undefined' ? BASE_URL : '/';
  } catch(e) {
    window.BASE_URL = '/';
  }

  // Helper selectors
  const $ = sel => document.querySelector(sel);

  /* ---------- 2. JAM DIGITAL ---------- */
  (function clock(){
    const el = document.getElementById('liveClock') || document.getElementById('clock');
    if (!el) return;
    function tick() {
      const d = new Date();
      el.textContent = d.toLocaleTimeString('id-ID', {hour12:false});
    }
    tick();
    setInterval(tick, 1000);
  })();

  /* ---------- 3. CHART.JS HANDLER ---------- */
  window.globalDonutChart = null;
  window.globalBarChart = null;

  // Donut Chart (Ringkas & Modern)
  window.buildDonut = function(id, labels, values){
    const ctx = document.getElementById(id)?.getContext('2d');
    if (!ctx || typeof Chart === 'undefined') return;
    if (window.globalDonutChart) try { window.globalDonutChart.destroy(); } catch(e) {}
    
    window.globalDonutChart = new Chart(ctx, {
      type: 'doughnut',
      data: { 
          labels, 
          datasets:[{ 
              data: values, 
              backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#6366f1'], // Emerald, Red, Amber, Indigo
              borderWidth: 0,
              hoverOffset: 4
          }] 
      },
      options: { 
          responsive:true, 
          maintainAspectRatio: false, 
          plugins:{ 
              legend:{ position:'bottom', labels: { color: '#64748b', usePointStyle: true, padding: 20, font: {size: 11} } } 
          },
          cutout: '75%' 
      }
    });
  }

  // Bar Chart (Grid Halus)
  window.buildBar = function(id, labels, datasets, stacked = false){
    const ctx = document.getElementById(id)?.getContext('2d');
    if (!ctx || typeof Chart === 'undefined') return;
    if (window.globalBarChart) try { window.globalBarChart.destroy(); } catch(e) {}
    
    const gridColor = '#f1f5f9';
    const textColor = '#94a3b8';

    window.globalBarChart = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets },
      options: {
        responsive:true,
        maintainAspectRatio: false,
        plugins:{ 
            legend:{ display: datasets && datasets.length > 1, labels: { color: '#64748b', usePointStyle: true } } 
        },
        scales:{
          x: { 
              stacked: stacked, 
              ticks: { color: textColor, font: {size: 11} }, 
              grid: { display: false } 
          },
          y: { 
              beginAtZero:true, 
              stacked: stacked, 
              ticks: { color: textColor, font: {size: 11} }, 
              grid: { color: gridColor, borderDash: [4, 4] }, 
              border: { display: false } 
          }
        },
        borderRadius: 4, 
        barPercentage: 0.6
      }
    });
  }

  // Safe Resize (Debounce)
  window.resizeChartsDebounced = (function(){
    let t;
    return function(delay){
      clearTimeout(t);
      t = setTimeout(() => {
        if (window.globalDonutChart) window.globalDonutChart.resize();
        if (window.globalBarChart) window.globalBarChart.resize();
      }, typeof delay === 'number' ? delay : 60);
    };
  })();

  /* ---------- 4. TODO LIST LOGIC ---------- */
  window.renderSubSelect = function(masterId, nameAttr = "todo_sub[]"){
    const list = (window.SUB_OPTIONS && window.SUB_OPTIONS[String(masterId)]) || [];
    // Buat elemen select baru
    const div = document.createElement('div');
    const opts = ['<option value="">— Pilih Sub —</option>']
      .concat(list.map(s=>`<option value="${String(s).replace(/"/g,'"')}">${s}</option>`))
      .join('');
    div.innerHTML = `<select name="${nameAttr}" class="form-select sub-select">${opts}</select>`;
    return div.firstElementChild;
  }

  window.applyRowMode = function(row, namePrefix = "todo_"){
    if (!row) return;
    const masterSel = row.querySelector('.master-select');
    const manualInputCol = row.querySelector('.manual-input-col');
    const subCol = row.querySelector('.sub-col');
    const sumberInp = row.querySelector('.sumber-input');
    const manualJudulInput = row.querySelector(`input[name*="manual_judul"]`); 

    // Reset required attributes
    if (manualJudulInput) manualJudulInput.required = false;
    if (masterSel) masterSel.required = false;

    // Reset visibility
    if(manualInputCol) manualInputCol.classList.add('d-none');
    if(subCol) subCol.innerHTML = '';

    const mval = (masterSel && masterSel.value) || '';

    if (mval === 'lainnya') {
        // Mode Manual
        if (manualInputCol) manualInputCol.classList.remove('d-none');
        if (sumberInp) sumberInp.value = 'manual';
        if (manualJudulInput) manualJudulInput.required = true;
    } else if (mval && window.SUB_OPTIONS && (window.SUB_OPTIONS[String(mval)] || []).length) {
        // Mode Sub-Task Dropdown
        if (subCol) subCol.appendChild(window.renderSubSelect(mval, `${namePrefix}sub[]`));
        if (sumberInp) sumberInp.value = 'dropdown';
        if (masterSel) masterSel.required = true;
    } else {
        // Mode Master Only
        if (sumberInp) sumberInp.value = 'dropdown';
        if (masterSel) masterSel.required = true;
    }
  }

  window.createTodoRow = function(tpl, namePrefix = "todo_"){
    if (!tpl) return document.createElement('div');
    const row = tpl.content ? tpl.content.firstElementChild.cloneNode(true) : tpl.cloneNode(true);
    
    // Rename inputs untuk menghindari konflik
    row.querySelectorAll('[name^="todo_"], [name^="extra_"]').forEach(el => {
      const oldName = el.getAttribute('name');
      if (!oldName) return;
      const newName = oldName.replace(/^(todo|extra)_/, namePrefix);
      el.setAttribute('name', newName);
    });

    const masterSel = row.querySelector('.master-select');
    const btnDel = row.querySelector('.btn-del');

    if(masterSel) masterSel.addEventListener('change', ()=> window.applyRowMode(row, namePrefix));
    if(btnDel) btnDel.addEventListener('click', ()=> row.remove());

    window.applyRowMode(row, namePrefix);
    return row;
  }

  /* ---------- 5. PHOTO PREVIEW (FIXED ERROR) ---------- */
  function setupPhotoPreview() {
    const fileInput = document.getElementById('foto');
    const btnRetake = document.getElementById('btnRetake');
    const previewBox = document.getElementById('previewBox');
    const previewImg = document.getElementById('previewImg');
    
    // FIX: Definisikan btnOpen di sini sebelum dipakai
    const btnOpen = document.getElementById('btnOpenCam'); 

    // Event Listener dengan pengecekan null (Safety Check)
    if (btnOpen && fileInput) {
        btnOpen.addEventListener('click', function(e){
            e.preventDefault(); // Mencegah form submit tidak sengaja
            fileInput.click();
        });
    }
    
    if (btnRetake && fileInput) {
      btnRetake.addEventListener('click', function(){
        fileInput.value = '';
        if (previewImg) previewImg.src = '';
        if (previewBox) previewBox.style.display = 'none';
        btnRetake.classList.add('d-none');
        fileInput.required = true;
      });
    }
    
    if (fileInput) {
      fileInput.addEventListener('change', function(e){
        const f = e.target.files[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = ev => {
          if (previewImg) previewImg.src = ev.target.result;
          if (previewBox) previewBox.style.display = 'block';
          if (btnRetake) btnRetake.classList.remove('d-none');
          fileInput.required = false;
        };
        r.readAsDataURL(f);
      });
    }
  }

  /* ---------- 6. INITIALIZATION ---------- */
  document.addEventListener('DOMContentLoaded', function() {

    // Init Sidebar Toggle (Chart Resize Handler)
    const sidebarEl = document.getElementById('ocSidebar');
    if (sidebarEl) {
      sidebarEl.addEventListener('hidden.bs.offcanvas', function () {
        window.resizeChartsDebounced(60);
      });
    }

    // Init Tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    // Init Auto-hide Alerts
    setTimeout(function() {
      document.querySelectorAll('.alert-dismissible').forEach(alert => {
        try { new bootstrap.Alert(alert).close(); } catch(e){}
      });
    }, 5000);

    // Jalankan Photo Preview Logic
    setupPhotoPreview();

  }); 

})(window, document);