/*
 * GREATER FMS file preview modal.
 * PDFs, images and text files open in the browser's own viewer. Word, Excel and
 * PowerPoint files are rendered by file-preview/viewer.html, in a sandboxed frame
 * that cannot reach FMS or the network; this page fetches the file and hands it over.
 *
 * Any element with data-preview-url, data-preview-name and data-preview-ext opens it.
 */
(function () {
  'use strict';

  var OFFICE = { docx: 'docx', xlsx: 'xlsx', xls: 'xlsx', pptx: 'pptx' };
  var IMAGES = { png: 'image/png', jpg: 'image/jpeg', jpeg: 'image/jpeg' };
  var MAX_OFFICE_BYTES = 25 * 1024 * 1024;
  var modalEl, body, title, download, objectUrl, frame, timer, current = 0;

  function setup() {
    modalEl = document.getElementById('filePreviewModal');
    if (!modalEl) { return false; }
    body = document.getElementById('filePreviewBody');
    title = document.getElementById('filePreviewTitle');
    download = document.getElementById('filePreviewDownload');
    modalEl.addEventListener('hidden.bs.modal', reset);
    window.addEventListener('message', onFrameMessage);
    return true;
  }

  function reset() {
    current++;
    clearTimeout(timer);
    if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    frame = null;
    body.innerHTML = '';
  }

  function notice(icon, text, tone) {
    body.innerHTML = '';
    var box = document.createElement('div');
    box.className = 'd-flex flex-column align-items-center justify-content-center text-center h-100 p-4 ' + (tone || 'text-muted');
    var i = document.createElement('i');
    i.className = 'ti ' + icon + ' mb-3';
    i.style.fontSize = '3rem';
    var p = document.createElement('p');
    p.className = 'mb-0';
    p.textContent = text;
    box.appendChild(i);
    box.appendChild(p);
    body.appendChild(box);
  }

  function fill(el) {
    el.style.width = '100%';
    el.style.height = '100%';
    el.style.border = '0';
    el.style.display = 'block';
    body.innerHTML = '';
    body.appendChild(el);
  }

  function open(url, name, ext) {
    // Bootstrap does not stack modals: close any open one first (e.g. the version history)
    var other = document.querySelector('.modal.show');
    if (other && other !== modalEl) {
      other.addEventListener('hidden.bs.modal', function () { open(url, name, ext); }, { once: true });
      bootstrap.Modal.getOrCreateInstance(other).hide();
      return;
    }
    reset();
    var token = current;
    ext = String(ext || '').toLowerCase();
    title.textContent = name;
    download.href = url;
    notice('ti-loader ti-spin', 'Loading preview…');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();

    if (ext !== 'pdf' && ext !== 'txt' && !IMAGES[ext] && !OFFICE[ext]) {
      notice('ti-file-off', 'Preview is not available for .' + ext + ' files. Use Download to open it.');
      return;
    }

    fetch(url, { credentials: 'same-origin' })
      .then(function (res) {
        var type = res.headers.get('Content-Type') || '';
        if (res.status === 403) { throw new Error('You do not have access to this file.'); }
        if (!res.ok) { throw new Error('The file could not be loaded.'); }
        if (type.indexOf('text/html') === 0) { throw new Error('Your session may have expired. Reload the page and try again.'); }
        if (OFFICE[ext] && Number(res.headers.get('Content-Length')) > MAX_OFFICE_BYTES) {
          throw new Error('This file is too large to preview. Use Download to open it.');
        }
        return ext === 'txt' ? res.text() : res.arrayBuffer();
      })
      .then(function (data) {
        if (token !== current) { return; }
        if (ext === 'txt') {
          var pre = document.createElement('pre');
          pre.className = 'm-0 p-3 bg-white';
          pre.style.whiteSpace = 'pre-wrap';
          pre.textContent = data;
          fill(pre);
          pre.style.overflow = 'auto';
        } else if (ext === 'pdf' || IMAGES[ext]) {
          objectUrl = URL.createObjectURL(new Blob([data], { type: ext === 'pdf' ? 'application/pdf' : IMAGES[ext] }));
          if (ext === 'pdf') {
            var pdf = document.createElement('iframe');
            pdf.title = name;
            pdf.src = objectUrl;
            fill(pdf);
          } else {
            var wrap = document.createElement('div');
            wrap.className = 'd-flex align-items-center justify-content-center h-100 p-3';
            var img = document.createElement('img');
            img.alt = name;
            img.src = objectUrl;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100%';
            wrap.appendChild(img);
            fill(wrap);
          }
        } else {
          renderOffice(token, OFFICE[ext], data);
        }
      })
      .catch(function (err) {
        if (token === current) { notice('ti-alert-triangle', err.message, 'text-danger'); }
      });
  }

  function renderOffice(token, kind, buffer) {
    frame = document.createElement('iframe');
    frame.title = title.textContent;
    frame.setAttribute('sandbox', 'allow-scripts');   // no allow-same-origin: no access to FMS
    frame.setAttribute('referrerpolicy', 'no-referrer');
    frame.dataset.kind = kind;
    frame.fmsBuffer = buffer;
    frame.fmsToken = token;
    frame.src = modalEl.dataset.viewer;
    fill(frame);
    frame.style.visibility = 'hidden';
    timer = setTimeout(function () {
      if (token === current) { notice('ti-alert-triangle', 'The preview took too long. Use Download to open the file.', 'text-danger'); }
    }, 60000);
  }

  function onFrameMessage(event) {
    if (!frame || event.source !== frame.contentWindow || !event.data || event.data.source !== 'fms-preview-frame') {
      return;
    }
    var f = frame;
    if (event.data.type === 'ready' && f.fmsBuffer) {
      var buffer = f.fmsBuffer;
      f.fmsBuffer = null;
      f.contentWindow.postMessage({ source: 'fms-preview-page', kind: f.dataset.kind, buffer: buffer }, '*', [buffer]);
    } else if (event.data.type === 'rendered') {
      clearTimeout(timer);
      f.style.visibility = 'visible';
    } else if (event.data.type === 'error') {
      clearTimeout(timer);
      notice('ti-alert-triangle', 'This file could not be previewed. Use Download to open it.', 'text-danger');
    }
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-preview-url]');
    if (!trigger || (!modalEl && !setup())) { return; }
    event.preventDefault();
    open(trigger.dataset.previewUrl, trigger.dataset.previewName, trigger.dataset.previewExt);
  });

  window.FmsFilePreview = { open: function (url, name, ext) { if (modalEl || setup()) { open(url, name, ext); } } };
})();
