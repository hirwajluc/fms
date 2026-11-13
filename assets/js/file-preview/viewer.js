/*
 * GREATER FMS file preview: renders Word, Excel and PowerPoint files.
 * Runs inside a sandboxed frame with no access to FMS and no network access;
 * the opening page sends the file's bytes with postMessage.
 */
(function () {
  'use strict';

  var LIBS = {
    docx: ['../../vendor/libs/jszip/jszip.min.js', '../../vendor/libs/docx-preview/docx-preview.min.js'],
    xlsx: ['../../vendor/libs/sheetjs/xlsx.full.min.js'],
    pptx: ['../../vendor/libs/jszip/jszip.min.js', 'pptx.js?v=3']   // bump ?v= when pptx.js changes
  };
  var MAX_ROWS = 2000, MAX_COLS = 100;
  var status = document.getElementById('status');
  var output = document.getElementById('output');
  var started = false;
  var fitters = [];

  window.addEventListener('resize', function () {
    fitters.forEach(function (fit) { fit(); });
  });

  function reply(message) {
    message.source = 'fms-preview-frame';
    parent.postMessage(message, '*');
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = function () { reject(new Error('The previewer could not be loaded.')); };
      document.head.appendChild(s);
    });
  }

  function loadLibs(kind) {
    return (LIBS[kind] || []).reduce(function (chain, src) {
      return chain.then(function () { return loadScript(src); });
    }, Promise.resolve());
  }

  function renderDocx(buffer) {
    return window.docx.renderAsync(buffer, output, null, {
      inWrapper: true,
      breakPages: true,
      ignoreLastRenderedPageBreak: true,
      renderHeaders: true,
      renderFooters: true,
      renderFootnotes: true,
      useBase64URL: true
    }).then(function () {
      fitters.push(fitDocx);
      fitDocx();
    });
  }

  // Shrink the pages to the frame's width on small screens
  function fitDocx() {
    var page = output.querySelector('section.docx');
    var wrapper = output.querySelector('.docx-wrapper');
    var avail = document.documentElement.clientWidth;
    if (!page || !wrapper || avail <= 0) { return; }
    wrapper.style.zoom = '';
    var needed = page.offsetWidth + 32;   // the page plus the wrapper's padding
    wrapper.style.zoom = needed > avail ? (avail / needed).toFixed(3) : '';
  }

  function renderXlsx(buffer) {
    var XLSX = window.XLSX;
    var book = XLSX.read(buffer, { type: 'array', cellStyles: false });
    var tabs = document.createElement('div');
    var sheet = document.createElement('div');
    var note = document.createElement('div');
    tabs.className = 'sheet-tabs';
    sheet.className = 'sheet';
    note.className = 'sheet-note';

    function show(name, button) {
      Array.prototype.forEach.call(tabs.children, function (b) { b.classList.remove('active'); });
      button.classList.add('active');
      var ws = book.Sheets[name];
      note.textContent = '';
      if (!ws || !ws['!ref']) {
        sheet.textContent = 'This sheet is empty.';
        return;
      }
      var range = XLSX.utils.decode_range(ws['!ref']);
      var cut = [];
      if (range.e.r - range.s.r + 1 > MAX_ROWS) { range.e.r = range.s.r + MAX_ROWS - 1; cut.push('the first ' + MAX_ROWS + ' rows'); }
      if (range.e.c - range.s.c + 1 > MAX_COLS) { range.e.c = range.s.c + MAX_COLS - 1; cut.push('the first ' + MAX_COLS + ' columns'); }
      var view = Object.assign({}, ws, { '!ref': XLSX.utils.encode_range(range) });
      sheet.innerHTML = XLSX.utils.sheet_to_html(view, { header: '', footer: '' });
      if (cut.length) { note.textContent = 'Showing ' + cut.join(' and ') + '. Download the file to see everything.'; }
    }

    book.SheetNames.forEach(function (name, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = name;
      b.addEventListener('click', function () { show(name, b); });
      tabs.appendChild(b);
      if (i === 0) { setTimeout(function () { show(name, b); }, 0); }
    });
    output.appendChild(tabs);
    output.appendChild(note);
    output.appendChild(sheet);
  }

  function renderPptx(buffer) {
    return window.FmsPptx.render(buffer, output);
  }

  window.addEventListener('message', function (event) {
    if (event.source !== parent || started || !event.data || event.data.source !== 'fms-preview-page') {
      return;
    }
    started = true;
    var kind = event.data.kind;
    var render = { docx: renderDocx, xlsx: renderXlsx, pptx: renderPptx }[kind];
    if (!render) {
      reply({ type: 'error', message: 'This file type cannot be previewed.' });
      return;
    }
    loadLibs(kind)
      .then(function () { return render(event.data.buffer); })
      .then(function () {
        status.remove();
        reply({ type: 'rendered' });
      })
      .catch(function (err) {
        status.className = 'error';
        status.textContent = 'This file could not be previewed. Download it to open it.';
        reply({ type: 'error', message: String((err && err.message) || err) });
      });
  });

  reply({ type: 'ready' });
})();
