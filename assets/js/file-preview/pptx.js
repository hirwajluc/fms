/*
 * GREATER FMS: PowerPoint (.pptx) preview, used by the sandboxed viewer (viewer.html).
 * Draws each slide with HTML and SVG from the file's own XML: text, shapes,
 * pictures, tables, groups, SmartArt and backgrounds, with what they inherit
 * from the slide layout, master and theme. Charts and embedded objects show a
 * placeholder. Text from the file is only ever set with textContent.
 */
(function () {
  'use strict';

  var EMU = 9525;            // EMU per CSS pixel (96 dpi)
  var PT = 96 / 72;          // CSS pixels per point
  var MAX_SLIDES = 200;
  var SVG_NS = 'http://www.w3.org/2000/svg';
  var R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
  var FONT_FALLBACK = 'Calibri, Carlito, "Segoe UI", Roboto, Arial, sans-serif';
  var SERIF_FALLBACK = 'Cambria, Caladea, Georgia, "Times New Roman", serif';
  var MONO_FALLBACK = 'Consolas, "Courier New", monospace';
  var IMAGE_TYPES = { png: 'image/png', jpg: 'image/jpeg', jpeg: 'image/jpeg', jpe: 'image/jpeg', gif: 'image/gif', bmp: 'image/bmp', svg: 'image/svg+xml', webp: 'image/webp' };
  var DEFAULT_COLORS = { dk1: '000000', lt1: 'FFFFFF', dk2: '44546A', lt2: 'E7E6E6', accent1: '4472C4', accent2: 'ED7D31', accent3: 'A5A5A5', accent4: 'FFC000', accent5: '5B9BD5', accent6: '70AD47', hlink: '0563C1', folHlink: '954F72' };
  var DEFAULT_MAP = { bg1: 'lt1', tx1: 'dk1', bg2: 'lt2', tx2: 'dk2' };
  var PRESET_COLORS = { black: '000000', white: 'FFFFFF', red: 'FF0000', green: '008000', blue: '0000FF', yellow: 'FFFF00', cyan: '00FFFF', magenta: 'FF00FF', gray: '808080', grey: '808080', darkGray: 'A9A9A9', lightGray: 'D3D3D3', orange: 'FFA500', purple: '800080', navy: '000080' };
  var COLOR_TAGS = { srgbClr: 1, schemeClr: 1, sysClr: 1, prstClr: 1, scrgbClr: 1, hslClr: 1 };
  var ALIGN = { l: 'left', ctr: 'center', r: 'right', just: 'justify', dist: 'justify', justLow: 'justify', thaiDist: 'justify' };
  var ANCHOR = { t: 'flex-start', ctr: 'center', b: 'flex-end', just: 'center', dist: 'center' };
  var CELL_ANCHOR = { t: 'top', ctr: 'middle', b: 'bottom' };
  var PH_ALIAS = { ctrTitle: 'title', subTitle: 'body', obj: 'body', pic: 'body', tbl: 'body', chart: 'body', dgm: 'body', media: 'body', clipArt: 'body' };
  // Bullets drawn with symbol fonts (Wingdings, Symbol) that the viewer may not have
  var SYMBOL_BULLETS = { '§': '▪', 'Ø': '➢', 'ü': '✓', 'Ü': '✓', 'q': '❑', 'v': '❖', 'u': '◆', 'w': '⬥', 'n': '■', 'l': '●', 'Ÿ': '•', '·': '•', 'ð': '➔', 'è': '➔', 'à': '→', 'ß': '←' };
  var DASHES = { dash: [4, 3], sysDash: [3, 1], dot: [1, 1], sysDot: [1, 1], lgDash: [8, 3], dashDot: [4, 3, 1, 3], sysDashDot: [3, 1, 1, 1], lgDashDot: [8, 3, 1, 3], lgDashDotDot: [8, 3, 1, 3, 1, 3], sysDashDotDot: [3, 1, 1, 1, 1, 1] };
  var TABLE_PARTS = ['wholeTbl', 'band1H', 'band2H', 'band1V', 'band2V', 'firstRow', 'lastRow', 'firstCol', 'lastCol'];
  var IDENTITY = { sx: 1, sy: 1, dx: 0, dy: 0 };
  var uid = 0;

  /* ---------- small helpers ---------- */

  // Elements are matched by local name: producers use different namespace prefixes
  function kids(el, name) {
    var out = [];
    for (var c = el ? el.firstElementChild : null; c; c = c.nextElementSibling) {
      if (!name || c.localName === name) { out.push(c); }
    }
    return out;
  }
  function kid(el, name) {
    for (var c = el ? el.firstElementChild : null; c; c = c.nextElementSibling) {
      if (c.localName === name) { return c; }
    }
    return null;
  }
  function path(el) {
    for (var i = 1; i < arguments.length && el; i++) { el = kid(el, arguments[i]); }
    return el || null;
  }
  function attr(el, name) { return el ? el.getAttribute(name) : null; }
  function num(el, name, def) {
    var v = el ? parseFloat(el.getAttribute(name)) : NaN;
    return isFinite(v) ? v : def;
  }
  function bool(v) { return v === '1' || v === 'true' || v === 'on'; }
  function rid(el, name) { return el ? (el.getAttributeNS(R_NS, name) || el.getAttribute('r:' + name)) : null; }
  function first(list, fn) {
    for (var i = 0; i < list.length; i++) {
      var v = list[i] ? fn(list[i]) : null;
      if (v) { return v; }
    }
    return null;
  }
  function clamp(v, lo, hi) { return v < lo ? lo : v > hi ? hi : v; }
  function r(n) { return Math.round(n * 100) / 100; }
  function make(tag, cls) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    return e;
  }
  function svg(tag, attrs) {
    var e = document.createElementNS(SVG_NS, tag);
    for (var k in attrs) { e.setAttribute(k, attrs[k]); }
    return e;
  }

  /* ---------- package ---------- */

  function relsPath(name) {
    var i = name.lastIndexOf('/');
    return name.slice(0, i + 1) + '_rels/' + name.slice(i + 1) + '.rels';
  }
  function resolve(base, target) {
    try { target = decodeURIComponent(target); } catch (e) { /* keep as is */ }
    if (target.charAt(0) === '/') { return target.slice(1); }
    var parts = base.split('/');
    parts.pop();
    target.split('/').forEach(function (p) {
      if (p === '..') { parts.pop(); } else if (p && p !== '.') { parts.push(p); }
    });
    return parts.join('/');
  }

  function Package(zip) {
    this.zip = zip;
    this.cache = {};
  }
  Package.prototype.xml = function (name) {
    var key = 'xml:' + name;
    if (!this.cache[key]) {
      var file = name ? this.zip.file(name) : null;
      this.cache[key] = file ? file.async('string').then(function (text) {
        var doc = new DOMParser().parseFromString(text, 'application/xml');
        return doc.getElementsByTagName('parsererror').length ? null : doc.documentElement;
      }) : Promise.resolve(null);
    }
    return this.cache[key];
  };
  Package.prototype.rels = function (name) {
    return this.xml(relsPath(name)).then(function (root) {
      var rels = {};
      kids(root, 'Relationship').forEach(function (rel) {
        if (attr(rel, 'TargetMode') === 'External') { return; }
        rels[attr(rel, 'Id')] = { type: String(attr(rel, 'Type') || '').split('/').pop(), target: resolve(name, attr(rel, 'Target') || '') };
      });
      return rels;
    });
  };
  Package.prototype.part = function (name) {
    if (!name) { return Promise.resolve(null); }
    var key = 'part:' + name;
    if (!this.cache[key]) {
      this.cache[key] = Promise.all([this.xml(name), this.rels(name)]).then(function (res) {
        return res[0] ? { name: name, root: res[0], rels: res[1] } : null;
      });
    }
    return this.cache[key];
  };
  // Blob URL for a picture the part refers to; null for formats browsers cannot show (EMF, WMF, TIFF)
  Package.prototype.image = function (part, id) {
    var rel = part && id ? part.rels[id] : null;
    if (!rel) { return Promise.resolve(null); }
    var type = IMAGE_TYPES[rel.target.split('.').pop().toLowerCase()];
    var file = this.zip.file(rel.target);
    if (!type || !file) { return Promise.resolve(null); }
    var key = 'img:' + rel.target;
    if (!this.cache[key]) {
      this.cache[key] = file.async('arraybuffer').then(function (buf) {
        return URL.createObjectURL(new Blob([buf], { type: type }));
      });
    }
    return this.cache[key];
  };
  function relOfType(part, type) {
    for (var id in (part ? part.rels : {})) {
      if (part.rels[id].type === type) { return part.rels[id].target; }
    }
    return null;
  }

  /* ---------- theme and colours ---------- */

  function readTheme(root) {
    var t = { colors: {}, major: 'Calibri Light', minor: 'Calibri', fills: [], lines: [], bgFills: [] };
    var els = path(root, 'themeElements');
    kids(path(els, 'clrScheme')).forEach(function (c) {
      var s = kid(c, 'srgbClr'), y = kid(c, 'sysClr');
      var hex = s ? attr(s, 'val') : y ? (attr(y, 'lastClr') || (attr(y, 'val') === 'window' ? 'FFFFFF' : '000000')) : null;
      if (hex && /^[0-9a-f]{6}$/i.test(hex)) { t.colors[c.localName] = hex; }
    });
    var fonts = path(els, 'fontScheme');
    t.major = attr(path(fonts, 'majorFont', 'latin'), 'typeface') || t.major;
    t.minor = attr(path(fonts, 'minorFont', 'latin'), 'typeface') || t.minor;
    var fmt = path(els, 'fmtScheme');
    t.fills = kids(path(fmt, 'fillStyleLst'));
    t.lines = kids(path(fmt, 'lnStyleLst'));
    t.bgFills = kids(path(fmt, 'bgFillStyleLst'));
    return t;
  }

  function colorMap(master, layout, slide) {
    var map = {};
    [master && kid(master.root, 'clrMap'),
      layout && path(layout.root, 'clrMapOvr', 'overrideClrMapping'),
      slide && path(slide.root, 'clrMapOvr', 'overrideClrMapping')].forEach(function (e) {
      for (var i = 0; e && i < e.attributes.length; i++) { map[e.attributes[i].localName] = e.attributes[i].value; }
    });
    return map;
  }

  function hexRgb(hex) {
    return /^[0-9a-f]{6}$/i.test(hex || '') ? { r: parseInt(hex.slice(0, 2), 16), g: parseInt(hex.slice(2, 4), 16), b: parseInt(hex.slice(4, 6), 16), a: 1 } : null;
  }
  function toHsl(c) {
    var R = c.r / 255, G = c.g / 255, B = c.b / 255;
    var max = Math.max(R, G, B), min = Math.min(R, G, B), d = max - min, l = (max + min) / 2, h = 0, s = 0;
    if (d) {
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      h = (max === R ? (G - B) / d + (G < B ? 6 : 0) : max === G ? (B - R) / d + 2 : (R - G) / d + 4) / 6;
    }
    return { h: h, s: s, l: l, a: c.a };
  }
  function fromHsl(x) {
    var l = clamp(x.l, 0, 1), s = clamp(x.s, 0, 1), h = x.h;
    function hue(p, q, t) {
      if (t < 0) { t += 1; }
      if (t > 1) { t -= 1; }
      return t < 1 / 6 ? p + (q - p) * 6 * t : t < 1 / 2 ? q : t < 2 / 3 ? p + (q - p) * (2 / 3 - t) * 6 : p;
    }
    if (!s) { return { r: l * 255, g: l * 255, b: l * 255, a: x.a }; }
    var q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
    return { r: hue(p, q, h + 1 / 3) * 255, g: hue(p, q, h) * 255, b: hue(p, q, h - 1 / 3) * 255, a: x.a };
  }
  function linear(v) {
    v = clamp(v / 100000, 0, 1);
    return 255 * (v <= 0.0031308 ? 12.92 * v : 1.055 * Math.pow(v, 1 / 2.4) - 0.055);
  }
  function modify(c, op, v) {
    var x;
    switch (op) {
      case 'alpha': c.a = v; break;
      case 'alphaMod': c.a *= v; break;
      case 'alphaOff': c.a += v; break;
      case 'lumMod': x = toHsl(c); x.l *= v; return fromHsl(x);
      case 'lumOff': x = toHsl(c); x.l += v; return fromHsl(x);
      case 'satMod': x = toHsl(c); x.s *= v; return fromHsl(x);
      case 'tint': c.r = c.r * v + 255 * (1 - v); c.g = c.g * v + 255 * (1 - v); c.b = c.b * v + 255 * (1 - v); break;
      case 'shade': c.r *= v; c.g *= v; c.b *= v; break;
      case 'inv': c.r = 255 - c.r; c.g = 255 - c.g; c.b = 255 - c.b; break;
    }
    return c;
  }
  // Resolve one colour element (srgbClr, schemeClr, ...) with its modifiers to {r, g, b, a}
  function color(c, S) {
    if (!c) { return null; }
    var name = c.localName, rgb = null, hex = null, v;
    if (name === 'srgbClr') { hex = attr(c, 'val'); }
    else if (name === 'sysClr') { hex = attr(c, 'lastClr') || (attr(c, 'val') === 'window' ? 'FFFFFF' : '000000'); }
    else if (name === 'prstClr') { hex = PRESET_COLORS[attr(c, 'val')] || '000000'; }
    else if (name === 'schemeClr') {
      v = attr(c, 'val');
      if (v === 'phClr') {
        if (!S.phClr) { return null; }
        rgb = Object.assign({}, S.phClr);
      } else {
        v = S.map[v] || DEFAULT_MAP[v] || v;
        hex = S.theme.colors[v] || DEFAULT_COLORS[v];
      }
    } else if (name === 'scrgbClr') {
      rgb = { r: linear(num(c, 'r', 0)), g: linear(num(c, 'g', 0)), b: linear(num(c, 'b', 0)), a: 1 };
    } else if (name === 'hslClr') {
      rgb = fromHsl({ h: num(c, 'hue', 0) / 21600000, s: num(c, 'sat', 0) / 100000, l: num(c, 'lum', 0) / 100000, a: 1 });
    } else {
      return null;
    }
    rgb = rgb || hexRgb(hex);
    for (var m = rgb ? c.firstElementChild : null; m; m = m.nextElementSibling) {
      rgb = modify(rgb, m.localName, num(m, 'val', 0) / 100000);
    }
    return rgb;
  }
  function findColor(container) {
    for (var c = container ? container.firstElementChild : null; c; c = c.nextElementSibling) {
      if (COLOR_TAGS[c.localName]) { return c; }
    }
    return null;
  }
  function colorOf(container, S) { return color(findColor(container), S); }
  function css(c) {
    return c ? 'rgba(' + Math.round(clamp(c.r, 0, 255)) + ',' + Math.round(clamp(c.g, 0, 255)) + ',' + Math.round(clamp(c.b, 0, 255)) + ',' + r(clamp(c.a, 0, 1)) + ')' : null;
  }

  /* ---------- fills and lines ---------- */

  // A fill element -> {none} | {css, solid[, stops, angle, radial]} | {blip}; undefined if not a fill
  function fillFrom(f, S) {
    var c;
    switch (f.localName) {
      case 'noFill': return { none: true };
      case 'solidFill': c = css(colorOf(f, S)); return c ? { css: c, solid: c } : null;
      case 'gradFill': return gradient(f, S);
      case 'pattFill': c = css(colorOf(kid(f, 'fgClr'), S)); return c ? { css: c, solid: c } : null;
      case 'blipFill': return { blip: rid(kid(f, 'blip'), 'embed') };
      case 'grpFill': return S.groupFill || null;
    }
    return undefined;
  }
  function fillOf(container, S) {
    for (var c = container ? container.firstElementChild : null; c; c = c.nextElementSibling) {
      var f = fillFrom(c, S);
      if (f !== undefined) { return f; }
    }
    return null;
  }
  function gradient(g, S) {
    var stops = kids(path(g, 'gsLst'), 'gs').map(function (s) {
      var c = css(colorOf(s, S));
      return c ? { pos: clamp(num(s, 'pos', 0) / 100000, 0, 1), color: c } : null;
    }).filter(Boolean).sort(function (a, b) { return a.pos - b.pos; });
    if (!stops.length) { return null; }
    var lin = kid(g, 'lin'), angle = lin ? num(lin, 'ang', 0) / 60000 : 90, radial = !!kid(g, 'path');
    var list = stops.map(function (s) { return s.color + ' ' + r(s.pos * 100) + '%'; });
    if (list.length === 1) { list.push(list[0]); }
    return {
      css: radial ? 'radial-gradient(' + list.join(', ') + ')' : 'linear-gradient(' + r(angle + 90) + 'deg, ' + list.join(', ') + ')',
      solid: stops[0].color, stops: stops, angle: angle, radial: radial
    };
  }
  function lineOf(ln, S) {
    var f = fillOf(ln, S);
    return {
      none: !!(f && f.none), color: f && !f.none ? f.solid || null : null,
      width: attr(ln, 'w') !== null ? num(ln, 'w', 0) / EMU : null,
      dash: attr(kid(ln, 'prstDash'), 'val'), head: attr(kid(ln, 'headEnd'), 'type'), tail: attr(kid(ln, 'tailEnd'), 'type')
    };
  }
  function withPh(S, c) {
    var o = Object.create(S);
    o.phClr = c;
    return o;
  }
  // Theme styles that a shape's <p:style> refers to
  function refFill(ref, S) {
    if (!ref) { return null; }
    var idx = num(ref, 'idx', 0);
    if (!idx) { return { none: true }; }
    var c = colorOf(ref, S);
    var tpl = idx > 1000 ? S.theme.bgFills[idx - 1001] : S.theme.fills[idx - 1];
    var f = tpl ? fillFrom(tpl, withPh(S, c)) : null;
    return f && !f.blip ? f : c ? { css: css(c), solid: css(c) } : null;
  }
  function refLine(ref, S) {
    if (!ref) { return null; }
    var idx = num(ref, 'idx', 0);
    if (!idx) { return { none: true }; }
    var c = colorOf(ref, S), tpl = S.theme.lines[idx - 1];
    var l = tpl ? lineOf(tpl, withPh(S, c)) : { none: false, color: null, width: null };
    if (!l.color && c) { l.color = css(c); }
    return l;
  }
  function resolveLine(ln, ref, S) {
    var base = refLine(ref, S), own = ln ? lineOf(ln, S) : null;
    if (own && own.none) { return null; }
    var l = own ? {
      color: own.color || (base && !base.none ? base.color : null),
      width: own.width !== null ? own.width : base && base.width,
      dash: own.dash || (base && base.dash), head: own.head, tail: own.tail
    } : base && !base.none ? base : null;
    if (!l || !l.color) { return null; }
    l.width = Math.max(l.width || 1, 0.75);
    return l;
  }
  function borderCss(l) {
    return !l || l.none || !l.color ? 'none' : r(Math.max(l.width || 1, 0.75)) + 'px ' + (DASHES[l.dash] ? 'dashed' : 'solid') + ' ' + l.color;
  }

  /* ---------- geometry ---------- */

  function poly(pts) {
    return 'M' + pts.map(function (p) { return r(p[0]) + ' ' + r(p[1]); }).join(' L') + ' Z';
  }
  function roundedRect(w, h, tl, tr, br, bl) {
    var d = 'M' + r(tl) + ' 0 H' + r(w - tr);
    if (tr) { d += ' A' + r(tr) + ' ' + r(tr) + ' 0 0 1 ' + r(w) + ' ' + r(tr); }
    d += ' V' + r(h - br);
    if (br) { d += ' A' + r(br) + ' ' + r(br) + ' 0 0 1 ' + r(w - br) + ' ' + r(h); }
    d += ' H' + r(bl);
    if (bl) { d += ' A' + r(bl) + ' ' + r(bl) + ' 0 0 1 0 ' + r(h - bl); }
    d += ' V' + r(tl);
    if (tl) { d += ' A' + r(tl) + ' ' + r(tl) + ' 0 0 1 ' + r(tl) + ' 0'; }
    return d + ' Z';
  }
  function ellipse(cx, cy, rx, ry) {
    return 'M' + r(cx - rx) + ' ' + r(cy) + ' A' + r(rx) + ' ' + r(ry) + ' 0 1 0 ' + r(cx + rx) + ' ' + r(cy) +
      ' A' + r(rx) + ' ' + r(ry) + ' 0 1 0 ' + r(cx - rx) + ' ' + r(cy) + ' Z';
  }
  // Point on an ellipse at a DrawingML (visual) angle in radians
  function onEllipse(cx, cy, rx, ry, ang) {
    var t = Math.atan2(rx * Math.sin(ang), ry * Math.cos(ang));
    return [cx + rx * Math.cos(t), cy + ry * Math.sin(t)];
  }
  function arcTo(rx, ry, p, sweep) {
    return ' A' + r(rx) + ' ' + r(ry) + ' 0 ' + (Math.abs(sweep) > Math.PI ? 1 : 0) + ' ' + (sweep > 0 ? 1 : 0) + ' ' + r(p[0]) + ' ' + r(p[1]);
  }
  function star(w, h, n, ratio) {
    var pts = [];
    for (var i = 0; i < n * 2; i++) {
      var a = -Math.PI / 2 + i * Math.PI / n, k = i % 2 ? ratio : 1;
      pts.push([w / 2 + w / 2 * k * Math.cos(a), h / 2 + h / 2 * k * Math.sin(a)]);
    }
    return poly(pts);
  }
  function adjusts(geom) {
    var av = {};
    kids(kid(geom, 'avLst'), 'gd').forEach(function (g) {
      var m = /^val\s+(-?[\d.]+)/.exec(attr(g, 'fmla') || '');
      if (m) { av[attr(g, 'name')] = parseFloat(m[1]); }
    });
    return av;
  }

  // SVG paths for a preset shape: [{d, fill, stroke, open, evenodd}]
  function presetPaths(prst, w, h, av) {
    var ss = Math.min(w, h), a, b, t, hl;
    function A(name, def) { return (name in av ? av[name] : def) / 100000; }
    function F(d, evenodd) { return [{ d: d, fill: true, stroke: true, evenodd: !!evenodd }]; }
    function L(d) { return [{ d: d, fill: false, stroke: true, open: true }]; }
    switch (prst) {
      case 'roundRect': case 'flowChartAlternateProcess': case 'wedgeRoundRectCallout':
        a = Math.min(ss * A('adj', 16667), ss / 2); return F(roundedRect(w, h, a, a, a, a));
      case 'round1Rect': a = Math.min(ss * A('adj', 16667), ss / 2); return F(roundedRect(w, h, 0, a, 0, 0));
      case 'round2SameRect':
        a = Math.min(ss * A('adj1', 16667), ss / 2); b = Math.min(ss * A('adj2', 0), ss / 2); return F(roundedRect(w, h, a, a, b, b));
      case 'round2DiagRect':
        a = Math.min(ss * A('adj1', 16667), ss / 2); b = Math.min(ss * A('adj2', 0), ss / 2); return F(roundedRect(w, h, a, b, a, b));
      case 'flowChartTerminator': a = Math.min(w, h) / 2; return F(roundedRect(w, h, a, a, a, a));
      case 'snip1Rect': a = ss * A('adj', 16667); return F(poly([[0, 0], [w - a, 0], [w, a], [w, h], [0, h]]));
      case 'snip2SameRect':
        a = ss * A('adj1', 16667); b = ss * A('adj2', 0);
        return F(poly([[a, 0], [w - a, 0], [w, a], [w, h - b], [w - b, h], [b, h], [0, h - b], [0, a]]));
      case 'ellipse': case 'flowChartConnector': case 'wedgeEllipseCallout': case 'flowChartOr': case 'flowChartSummingJunction':
      case 'cloud': case 'cloudCallout': case 'teardrop':
        return F(ellipse(w / 2, h / 2, w / 2, h / 2));
      case 'flowChartManualOperation': return F(poly([[0, 0], [w, 0], [w * 0.8, h], [w * 0.2, h]]));
      case 'flowChartManualInput': return F(poly([[0, h * 0.2], [w, 0], [w, h], [0, h]]));
      case 'flowChartOffpageConnector': return F(poly([[0, 0], [w, 0], [w, h * 0.8], [w / 2, h], [0, h * 0.8]]));
      case 'flowChartPunchedCard': return F(poly([[w * 0.2, 0], [w, 0], [w, h], [0, h], [0, h * 0.2]]));
      case 'flowChartDelay': return F('M0 0 H' + r(w / 2) + ' A' + r(w / 2) + ' ' + r(h / 2) + ' 0 0 1 ' + r(w / 2) + ' ' + r(h) + ' H0 Z');
      case 'flowChartDocument':
        return F('M0 0 H' + r(w) + ' V' + r(h * 0.83) + ' C' + r(w * 0.72) + ' ' + r(h * 0.62) + ' ' + r(w * 0.28) + ' ' + r(h * 1.04) + ' 0 ' + r(h * 0.83) + ' Z');
      case 'triangle': case 'flowChartExtract': a = w * A('adj', 50000); return F(poly([[a, 0], [w, h], [0, h]]));
      case 'flowChartMerge': return F(poly([[0, 0], [w, 0], [w / 2, h]]));
      case 'rtTriangle': return F(poly([[0, 0], [w, h], [0, h]]));
      case 'diamond': case 'flowChartDecision': return F(poly([[w / 2, 0], [w, h / 2], [w / 2, h], [0, h / 2]]));
      case 'parallelogram': case 'flowChartInputOutput':
        a = prst === 'parallelogram' ? ss * A('adj', 25000) : w / 5; return F(poly([[a, 0], [w, 0], [w - a, h], [0, h]]));
      case 'trapezoid': a = ss * A('adj', 25000); return F(poly([[0, h], [a, 0], [w - a, 0], [w, h]]));
      case 'pentagon': return F(poly([[w / 2, 0], [w, h * 0.382], [w * 0.809, h], [w * 0.191, h], [0, h * 0.382]]));
      case 'hexagon': case 'flowChartPreparation':
        a = ss * A('adj', 25000); return F(poly([[a, 0], [w - a, 0], [w, h / 2], [w - a, h], [a, h], [0, h / 2]]));
      case 'octagon':
        a = ss * A('adj', 29289); return F(poly([[a, 0], [w - a, 0], [w, a], [w, h - a], [w - a, h], [a, h], [0, h - a], [0, a]]));
      case 'homePlate': a = ss * A('adj', 50000); return F(poly([[0, 0], [w - a, 0], [w, h / 2], [w - a, h], [0, h]]));
      case 'chevron': a = ss * A('adj', 50000); return F(poly([[0, 0], [w - a, 0], [w, h / 2], [w - a, h], [0, h], [a, h / 2]]));
      case 'rightArrow': case 'leftArrow': case 'leftRightArrow':
        t = (h - h * A('adj1', 50000)) / 2; hl = Math.min(ss * A('adj2', 50000), prst === 'leftRightArrow' ? w / 2 : w);
        if (prst === 'rightArrow') { return F(poly([[0, t], [w - hl, t], [w - hl, 0], [w, h / 2], [w - hl, h], [w - hl, h - t], [0, h - t]])); }
        if (prst === 'leftArrow') { return F(poly([[w, t], [hl, t], [hl, 0], [0, h / 2], [hl, h], [hl, h - t], [w, h - t]])); }
        return F(poly([[0, h / 2], [hl, 0], [hl, t], [w - hl, t], [w - hl, 0], [w, h / 2], [w - hl, h], [w - hl, h - t], [hl, h - t], [hl, h]]));
      case 'upArrow': case 'downArrow':
        t = (w - w * A('adj1', 50000)) / 2; hl = Math.min(ss * A('adj2', 50000), h);
        if (prst === 'upArrow') { return F(poly([[t, h], [t, hl], [0, hl], [w / 2, 0], [w, hl], [w - t, hl], [w - t, h]])); }
        return F(poly([[t, 0], [t, h - hl], [0, h - hl], [w / 2, h], [w, h - hl], [w - t, h - hl], [w - t, 0]]));
      case 'plus': case 'mathPlus':
        a = ss * A('adj', 25000);
        return F(poly([[a, 0], [w - a, 0], [w - a, a], [w, a], [w, h - a], [w - a, h - a], [w - a, h], [a, h], [a, h - a], [0, h - a], [0, a], [a, a]]));
      case 'star4': return F(star(w, h, 4, A('adj', 12500) * 2));
      case 'star5': return F(star(w, h, 5, A('adj', 19098) * 2));
      case 'star6': return F(star(w, h, 6, A('adj', 28868) * 2));
      case 'star8': return F(star(w, h, 8, A('adj', 38250) * 2));
      case 'star10': return F(star(w, h, 10, A('adj', 42533) * 2));
      case 'star12': return F(star(w, h, 12, A('adj', 37500) * 2));
      case 'donut':
        a = ss * A('adj', 25000);
        return F(ellipse(w / 2, h / 2, w / 2, h / 2) + ' ' + ellipse(w / 2, h / 2, Math.max(w / 2 - a, 0), Math.max(h / 2 - a, 0)), true);
      case 'frame':
        a = ss * A('adj1', 12500);
        return F(poly([[0, 0], [w, 0], [w, h], [0, h]]) + ' ' + poly([[a, a], [a, h - a], [w - a, h - a], [w - a, a]]), true);
      case 'pie': case 'arc': case 'chord': case 'blockArc': {
        // Angles are in 60000ths of a degree
        var def = { pie: [0, 16200000], arc: [16200000, 0], chord: [2700000, 16200000], blockArc: [10800000, 0] }[prst];
        var st = ('adj1' in av ? av.adj1 : def[0]) / 60000 * Math.PI / 180;
        var en = ('adj2' in av ? av.adj2 : def[1]) / 60000 * Math.PI / 180;
        var sw = en - st;
        while (sw <= 0) { sw += 2 * Math.PI; }
        var p1 = onEllipse(w / 2, h / 2, w / 2, h / 2, st), p2 = onEllipse(w / 2, h / 2, w / 2, h / 2, st + sw);
        var arc = 'M' + r(p1[0]) + ' ' + r(p1[1]) + arcTo(w / 2, h / 2, p2, sw);
        if (prst === 'arc') { return L(arc); }
        if (prst === 'chord') { return F(arc + ' Z'); }
        if (prst === 'pie') { return F('M' + r(w / 2) + ' ' + r(h / 2) + ' L' + arc.slice(1) + ' Z'); }
        a = ss * A('adj3', 25000);
        var q1 = onEllipse(w / 2, h / 2, w / 2 - a, h / 2 - a, st + sw), q2 = onEllipse(w / 2, h / 2, w / 2 - a, h / 2 - a, st);
        return F(arc + ' L' + r(q1[0]) + ' ' + r(q1[1]) + arcTo(w / 2 - a, h / 2 - a, q2, -sw) + ' Z');
      }
      case 'line': case 'straightConnector1': return L('M0 0 L' + r(w) + ' ' + r(h));
      case 'bentConnector2': return L('M0 0 H' + r(w) + ' V' + r(h));
      case 'bentConnector3': case 'bentConnector4': case 'bentConnector5':
        a = w * A('adj1', 50000); return L('M0 0 H' + r(a) + ' V' + r(h) + ' H' + r(w));
      case 'curvedConnector2': case 'curvedConnector3': case 'curvedConnector4': case 'curvedConnector5':
        return L('M0 0 C' + r(w / 2) + ' 0 ' + r(w / 2) + ' ' + r(h) + ' ' + r(w) + ' ' + r(h));
      case 'leftBracket': case 'leftBrace': return L('M' + r(w) + ' 0 Q0 0 0 ' + r(h / 8) + ' V' + r(h * 7 / 8) + ' Q0 ' + r(h) + ' ' + r(w) + ' ' + r(h));
      case 'rightBracket': case 'rightBrace': return L('M0 0 Q' + r(w) + ' 0 ' + r(w) + ' ' + r(h / 8) + ' V' + r(h * 7 / 8) + ' Q' + r(w) + ' ' + r(h) + ' 0 ' + r(h));
      case 'bracketPair': case 'bracePair':
        a = Math.min(w / 8, ss / 4);
        return L('M' + r(a) + ' 0 H0 V' + r(h) + ' H' + r(a) + ' M' + r(w - a) + ' 0 H' + r(w) + ' V' + r(h) + ' H' + r(w - a));
    }
    return F(poly([[0, 0], [w, 0], [w, h], [0, h]]));
  }

  // Custom geometry (freeforms, edited points, icons)
  function evalGuides(list, vars) {
    kids(list, 'gd').forEach(function (g) {
      var f = (attr(g, 'fmla') || '').trim().split(/\s+/), op = f[0];
      var a = f.slice(1).map(function (t) { return t in vars ? vars[t] : parseFloat(t) || 0; });
      var x = a[0] || 0, y = a[1] || 0, z = a[2] || 0, deg = Math.PI / 180 / 60000, v = 0;
      switch (op) {
        case 'val': v = x; break;
        case '*/': v = z ? x * y / z : 0; break;
        case '+-': v = x + y - z; break;
        case '+/': v = z ? (x + y) / z : 0; break;
        case '?:': v = x > 0 ? y : z; break;
        case 'abs': v = Math.abs(x); break;
        case 'at2': v = Math.atan2(y, x) / deg; break;
        case 'cat2': v = x * Math.cos(Math.atan2(z, y)); break;
        case 'sat2': v = x * Math.sin(Math.atan2(z, y)); break;
        case 'cos': v = x * Math.cos(y * deg); break;
        case 'sin': v = x * Math.sin(y * deg); break;
        case 'tan': v = x * Math.tan(y * deg); break;
        case 'max': v = Math.max(x, y); break;
        case 'min': v = Math.min(x, y); break;
        case 'mod': v = Math.sqrt(x * x + y * y + z * z); break;
        case 'pin': v = y < x ? x : y > z ? z : y; break;
        case 'sqrt': v = Math.sqrt(Math.max(x, 0)); break;
      }
      vars[attr(g, 'name')] = v;
    });
  }
  function customPaths(geom, w, h) {
    var W = w * EMU, H = h * EMU, ss = Math.min(W, H);
    var vars = { w: W, h: H, l: 0, t: 0, r: W, b: H, hc: W / 2, vc: H / 2, wd2: W / 2, hd2: H / 2, wd4: W / 4, hd4: H / 4, wd8: W / 8, hd8: H / 8,
      ss: ss, ls: Math.max(W, H), ssd2: ss / 2, ssd4: ss / 4, ssd8: ss / 8, cd2: 10800000, cd4: 5400000, cd8: 2700000, '3cd4': 16200000, '3cd8': 8100000, '5cd8': 13500000, '7cd8': 18900000 };
    evalGuides(kid(geom, 'avLst'), vars);
    evalGuides(kid(geom, 'gdLst'), vars);
    function V(t) { return t in vars ? vars[t] : parseFloat(t) || 0; }
    return kids(path(geom, 'pathLst'), 'path').map(function (p) {
      var pw = num(p, 'w', 0), ph = num(p, 'h', 0);
      var sx = pw ? w / pw : 1 / EMU, sy = ph ? h / ph : 1 / EMU;
      var d = [], cur = [0, 0], start = cur, closed = false;
      kids(p).forEach(function (c) {
        var pts = kids(c, 'pt').map(function (pt) { return [V(attr(pt, 'x')) * sx, V(attr(pt, 'y')) * sy]; });
        switch (c.localName) {
          case 'moveTo': if (pts[0]) { cur = start = pts[0]; d.push('M' + r(cur[0]) + ' ' + r(cur[1])); } break;
          case 'lnTo': if (pts[0]) { cur = pts[0]; d.push('L' + r(cur[0]) + ' ' + r(cur[1])); } break;
          case 'cubicBezTo': if (pts.length === 3) { cur = pts[2]; d.push('C' + pts.map(function (q) { return r(q[0]) + ' ' + r(q[1]); }).join(' ')); } break;
          case 'quadBezTo': if (pts.length === 2) { cur = pts[1]; d.push('Q' + pts.map(function (q) { return r(q[0]) + ' ' + r(q[1]); }).join(' ')); } break;
          case 'arcTo': {
            var rx = V(attr(c, 'wR')) * sx, ry = V(attr(c, 'hR')) * sy;
            var st = V(attr(c, 'stAng')) / 60000 * Math.PI / 180, sw = V(attr(c, 'swAng')) / 60000 * Math.PI / 180;
            if (!rx || !ry || !sw) { break; }
            var s0 = onEllipse(0, 0, rx, ry, st), cx = cur[0] - s0[0], cy = cur[1] - s0[1];
            if (Math.abs(sw) >= 2 * Math.PI - 1e-6) {
              var half = onEllipse(cx, cy, rx, ry, st + sw / 2);
              d.push(arcTo(rx, ry, half, sw / 2));
            }
            cur = onEllipse(cx, cy, rx, ry, st + sw);
            d.push(arcTo(rx, ry, cur, Math.abs(sw) >= 2 * Math.PI - 1e-6 ? sw / 2 : sw));
            break;
          }
          case 'close': d.push('Z'); cur = start; closed = true; break;
        }
      });
      var fill = attr(p, 'fill') !== 'none', stroke = attr(p, 'stroke') !== '0' && attr(p, 'stroke') !== 'false';
      return { d: d.join(' '), fill: fill, stroke: stroke, open: !closed };
    }).filter(function (p) { return p.d; });
  }
  function geometry(geom, w, h) {
    if (geom && geom.localName === 'custGeom') { return customPaths(geom, w, h); }
    return presetPaths(geom ? attr(geom, 'prst') : 'rect', w, h, geom ? adjusts(geom) : {});
  }

  /* ---------- painting ---------- */

  function defs(s) {
    var d = s.firstChild && s.firstChild.localName === 'defs' ? s.firstChild : null;
    if (!d) { d = svg('defs', {}); s.insertBefore(d, s.firstChild); }
    return d;
  }
  function gradientPaint(s, fill) {
    var id = 'pg' + (++uid), g;
    if (fill.radial) {
      g = svg('radialGradient', { id: id });
    } else {
      var a = fill.angle * Math.PI / 180, cx = Math.cos(a) / 2, cy = Math.sin(a) / 2;
      g = svg('linearGradient', { id: id, x1: r(0.5 - cx), y1: r(0.5 - cy), x2: r(0.5 + cx), y2: r(0.5 + cy) });
    }
    fill.stops.forEach(function (st) { g.appendChild(svg('stop', { offset: st.pos, 'stop-color': st.color })); });
    defs(s).appendChild(g);
    return 'url(#' + id + ')';
  }
  function imagePaint(s, url, b) {
    var id = 'pi' + (++uid);
    var p = svg('pattern', { id: id, patternUnits: 'userSpaceOnUse', width: r(b.w), height: r(b.h) });
    p.appendChild(svg('image', { href: url, width: r(b.w), height: r(b.h), preserveAspectRatio: 'none' }));
    defs(s).appendChild(p);
    return 'url(#' + id + ')';
  }
  function marker(s, line, type, start) {
    var id = 'pm' + (++uid), size = Math.max(6, line.width * 3);
    var m = svg('marker', { id: id, viewBox: '0 0 10 10', refX: type === 'oval' || type === 'diamond' ? 5 : 8, refY: 5,
      markerWidth: r(size), markerHeight: r(size), markerUnits: 'userSpaceOnUse', orient: start ? 'auto-start-reverse' : 'auto' });
    var shape = type === 'oval' ? svg('circle', { cx: 5, cy: 5, r: 4 })
      : svg('path', { d: type === 'diamond' ? 'M5 0 L10 5 L5 10 L0 5 Z' : type === 'arrow' ? 'M0 0 L10 5 L0 10' : 'M0 0 L10 5 L0 10 Z' });
    if (type === 'arrow') {
      shape.setAttribute('fill', 'none');
      shape.setAttribute('stroke', line.color);
      shape.setAttribute('stroke-width', '1.5');
    } else {
      shape.setAttribute('fill', line.color);
    }
    m.appendChild(shape);
    defs(s).appendChild(m);
    return 'url(#' + id + ')';
  }
  function paint(holder, paths, fill, line, b) {
    var filled = fill && !fill.none && (fill.css || fill.url);
    if (!paths.length || (!filled && !line)) { return; }
    var s = svg('svg', { 'class': 'pptx-geom', width: r(Math.max(b.w, 1)), height: r(Math.max(b.h, 1)), overflow: 'visible', 'aria-hidden': 'true' });
    var g = svg('g', {}), flips = [];
    if (b.flipH) { flips.push('translate(' + r(b.w) + ' 0) scale(-1 1)'); }
    if (b.flipV) { flips.push('translate(0 ' + r(b.h) + ') scale(1 -1)'); }
    if (flips.length) { g.setAttribute('transform', flips.join(' ')); }
    var paintFill = !filled ? 'none' : fill.stops ? gradientPaint(s, fill) : fill.url ? imagePaint(s, fill.url, b) : fill.css;
    paths.forEach(function (p) {
      var e = svg('path', { d: p.d, fill: p.fill ? paintFill : 'none' });
      if (p.evenodd) { e.setAttribute('fill-rule', 'evenodd'); }
      if (line && p.stroke) {
        e.setAttribute('stroke', line.color);
        e.setAttribute('stroke-width', r(line.width));
        e.setAttribute('stroke-linejoin', 'round');
        var dash = DASHES[line.dash];
        if (dash) { e.setAttribute('stroke-dasharray', dash.map(function (x) { return r(x * Math.max(line.width, 1)); }).join(' ')); }
        if (p.open && line.head && line.head !== 'none') { e.setAttribute('marker-start', marker(s, line, line.head, true)); }
        if (p.open && line.tail && line.tail !== 'none') { e.setAttribute('marker-end', marker(s, line, line.tail, false)); }
      }
      g.appendChild(e);
    });
    s.appendChild(g);
    holder.appendChild(s);
  }

  /* ---------- placeholders and inheritance ---------- */

  function phOf(node) {
    var nv = node ? node.firstElementChild : null;
    return nv && /^nv/.test(nv.localName) ? path(nv, 'nvPr', 'ph') : null;
  }
  function placeholders(part) {
    if (!part.phs) {
      part.phs = [];
      kids(path(part.root, 'cSld', 'spTree')).forEach(function (n) {
        var ph = phOf(n);
        if (ph) { part.phs.push({ node: n, type: attr(ph, 'type') || 'obj', idx: attr(ph, 'idx') }); }
      });
    }
    return part.phs;
  }
  // Slide placeholders match the layout by index, then type; layout placeholders match the master by type
  function findPh(part, ph, byIdx) {
    if (!part || !ph) { return null; }
    var list = placeholders(part), type = attr(ph, 'type') || 'obj', idx = attr(ph, 'idx'), i;
    if (byIdx && idx !== null) {
      for (i = 0; i < list.length; i++) { if (list[i].idx === idx) { return list[i].node; } }
    }
    for (i = 0; i < list.length; i++) { if (list[i].type === type) { return list[i].node; } }
    var alias = PH_ALIAS[type];
    for (i = 0; alias && i < list.length; i++) { if (list[i].type === alias) { return list[i].node; } }
    return null;
  }
  function inherit(node, S, part) {
    var ph = phOf(node);
    if (!ph || part !== S.slide) { return [node]; }
    var lay = findPh(S.layout, ph, true);
    var mas = findPh(S.master, lay ? phOf(lay) : ph, false);
    return [node, lay, mas].filter(Boolean);
  }
  function xfrmOf(n) { return kid(n, 'xfrm') || path(n, 'spPr', 'xfrm'); }
  function box(xf, tf) {
    var off = kid(xf, 'off'), ext = kid(xf, 'ext');
    return {
      x: num(off, 'x', 0) / EMU * tf.sx + tf.dx, y: num(off, 'y', 0) / EMU * tf.sy + tf.dy,
      w: num(ext, 'cx', 0) / EMU * tf.sx, h: num(ext, 'cy', 0) / EMU * tf.sy,
      rot: num(xf, 'rot', 0) / 60000, flipH: bool(attr(xf, 'flipH')), flipV: bool(attr(xf, 'flipV'))
    };
  }
  function place(parentEl, b) {
    var d = make('div', 'pptx-shape');
    d.style.left = r(b.x) + 'px';
    d.style.top = r(b.y) + 'px';
    d.style.width = r(Math.max(b.w, 0)) + 'px';
    d.style.height = r(Math.max(b.h, 0)) + 'px';
    if (b.rot) { d.style.transform = 'rotate(' + r(b.rot) + 'deg)'; }
    parentEl.appendChild(d);
    return d;
  }
  function note(holder, text) {
    var n = make('div', 'pptx-note');
    n.textContent = text;
    holder.appendChild(n);
  }

  /* ---------- text ---------- */

  function fontStack(name, S) {
    name = name || '+mn-lt';
    if (name.charAt(0) === '+') { name = name.indexOf('+mj') === 0 ? S.theme.major : S.theme.minor; }
    name = String(name || '').replace(/["\\;{}<>]/g, '').trim();
    var fallback = /sans/i.test(name) ? FONT_FALLBACK
      : /cambria|georgia|times|garamond|serif|antiqua|palatino|merriweather|playfair|lora|baskerville|didot|constantia|bodoni|caslon|minion/i.test(name) ? SERIF_FALLBACK
      : /consolas|courier|mono|lucida console|menlo/i.test(name) ? MONO_FALLBACK : FONT_FALLBACK;
    return name ? '"' + name + '", ' + fallback : fallback;
  }
  function spacing(e) {
    var p = kid(e, 'spcPct'), t = kid(e, 'spcPts');
    return p ? { pct: num(p, 'val', 100000) / 100000 } : t ? { pts: num(t, 'val', 0) / 100 } : null;
  }
  function applyRPr(rs, rPr, S) {
    if (!rPr) { return; }
    var a;
    if (attr(rPr, 'sz') !== null) { rs.sz = num(rPr, 'sz', rs.sz); }
    if ((a = attr(rPr, 'b')) !== null) { rs.b = bool(a); }
    if ((a = attr(rPr, 'i')) !== null) { rs.i = bool(a); }
    if ((a = attr(rPr, 'u')) !== null) { rs.u = a !== 'none'; }
    if ((a = attr(rPr, 'strike')) !== null) { rs.strike = a !== 'noStrike'; }
    if (attr(rPr, 'baseline') !== null) { rs.baseline = num(rPr, 'baseline', 0); }
    if ((a = attr(rPr, 'cap')) !== null) { rs.cap = a; }
    if (attr(rPr, 'spc') !== null) { rs.spc = num(rPr, 'spc', 0) / 100; }
    var f = fillOf(rPr, S);
    if (f && (f.none || f.solid)) {
      rs.color = f.none ? 'transparent' : f.solid;
    } else if (kid(rPr, 'hlinkClick')) {
      rs.color = css(hexRgb(S.theme.colors.hlink || DEFAULT_COLORS.hlink));
      rs.u = true;
    }
    var latin = kid(rPr, 'latin');
    if (latin && attr(latin, 'typeface')) { rs.font = attr(latin, 'typeface'); }
    var hl = colorOf(kid(rPr, 'highlight'), S);
    if (hl) { rs.highlight = css(hl); }
  }
  function applyPPr(st, pPr, S) {
    if (!pPr) { return; }
    if (attr(pPr, 'algn')) { st.algn = attr(pPr, 'algn'); }
    if (attr(pPr, 'marL') !== null) { st.marL = num(pPr, 'marL', 0) / EMU; }
    if (attr(pPr, 'indent') !== null) { st.indent = num(pPr, 'indent', 0) / EMU; }
    for (var c = pPr.firstElementChild; c; c = c.nextElementSibling) {
      switch (c.localName) {
        case 'lnSpc': st.lnSpc = spacing(c); break;
        case 'spcBef': st.spcBef = spacing(c); break;
        case 'spcAft': st.spcAft = spacing(c); break;
        case 'buNone': st.bullet = null; break;
        case 'buChar': st.bullet = { ch: attr(c, 'char') || '•' }; break;
        case 'buAutoNum': st.bullet = { auto: attr(c, 'type') || 'arabicPeriod', start: num(c, 'startAt', 1) }; break;
        case 'buBlip': st.bullet = { ch: '•' }; break;
        case 'buClrTx': st.buColor = null; break;
        case 'buClr': st.buColor = css(colorOf(c, S)); break;
        case 'buSzTx': st.buSize = null; break;
        case 'buSzPct': st.buSize = { pct: num(c, 'val', 100000) / 100000 }; break;
        case 'buSzPts': st.buSize = { pts: num(c, 'val', 1800) }; break;
        case 'buFontTx': st.buFont = null; break;
        case 'buFont': st.buFont = attr(c, 'typeface'); break;
        case 'defRPr': applyRPr(st.run, c, S); break;
      }
    }
  }
  function alpha(n) {
    var s = '';
    for (; n > 0; n = Math.floor((n - 1) / 26)) { s = String.fromCharCode(97 + (n - 1) % 26) + s; }
    return s;
  }
  function roman(n) {
    var map = [[1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'], [100, 'C'], [90, 'XC'], [50, 'L'], [40, 'XL'], [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']], s = '';
    map.forEach(function (m) { while (n >= m[0]) { s += m[1]; n -= m[0]; } });
    return s;
  }
  function autoNumber(type, n) {
    var m = /^(arabic|alphaLc|alphaUc|romanLc|romanUc)(.*)$/.exec(type) || [0, 'arabic', 'Period'];
    var s = m[1] === 'arabic' ? String(n) : m[1] === 'alphaLc' ? alpha(n) : m[1] === 'alphaUc' ? alpha(n).toUpperCase() : m[1] === 'romanLc' ? roman(n).toLowerCase() : roman(n);
    return m[2] === 'ParenR' ? s + ')' : m[2] === 'ParenBoth' ? '(' + s + ')' : m[2] === 'Plain' ? s : s + '.';
  }
  function runSpan(text, rs, S, scale) {
    var e = make('span'), size = rs.sz / 100 * PT * scale;
    e.textContent = text;
    if (rs.baseline) {
      size *= 0.66;
      e.style.verticalAlign = rs.baseline > 0 ? 'super' : 'sub';
    }
    e.style.fontSize = r(size) + 'px';
    e.style.fontFamily = fontStack(rs.font, S);
    if (rs.b) { e.style.fontWeight = '700'; }
    if (rs.i) { e.style.fontStyle = 'italic'; }
    if (rs.u || rs.strike) { e.style.textDecoration = ((rs.u ? 'underline ' : '') + (rs.strike ? 'line-through' : '')).trim(); }
    if (rs.color) { e.style.color = rs.color; }
    if (rs.cap === 'all') { e.style.textTransform = 'uppercase'; } else if (rs.cap === 'small') { e.style.fontVariant = 'small-caps'; }
    if (rs.spc) { e.style.letterSpacing = r(rs.spc * PT) + 'px'; }
    if (rs.highlight) { e.style.backgroundColor = rs.highlight; }
    return e;
  }
  function hasText(p) {
    return kids(p).some(function (c) {
      return (c.localName === 'r' || c.localName === 'fld') && (kid(c, 't') || {}).textContent;
    });
  }

  // layers: list styles from the most general (master text styles) to the shape's own;
  // opts: {color, bold} from the shape or table style, {scale, reduce} from autofit
  function renderParagraphs(container, tx, layers, S, opts) {
    var counters = [], firstPara = true, scale = opts.scale || 1, reduce = 1 - (opts.reduce || 0);
    kids(tx, 'p').forEach(function (p) {
      var pPr = kid(p, 'pPr'), lvl = clamp(Math.round(num(pPr, 'lvl', 0)), 0, 8);
      var st = { algn: null, marL: 0, indent: 0, lnSpc: null, spcBef: null, spcAft: null, bullet: null, buColor: null, buSize: null, buFont: null, run: { sz: 1800 } };
      layers.forEach(function (layer, i) {
        if (layer) {
          applyPPr(st, kid(layer, 'defPPr'), S);
          applyPPr(st, kid(layer, 'lvl' + (lvl + 1) + 'pPr'), S);
        }
        if (i === 0 && opts.color) { st.run.color = opts.color; }
        if (i === 0 && opts.bold) { st.run.b = true; }
      });
      applyPPr(st, pPr, S);

      var pEl = make('div', 'pptx-p'), runs = [], text = '', lead = null;
      kids(p).forEach(function (c) {
        if (c.localName === 'r' || c.localName === 'fld') {
          var rs = Object.assign({}, st.run), t = kid(c, 't'), s = t ? t.textContent : '';
          applyRPr(rs, kid(c, 'rPr'), S);
          if (c.localName === 'fld' && /^slidenum$/i.test(attr(c, 'type') || '')) { s = String(S.number); }
          if (s) {
            runs.push(runSpan(s, rs, S, scale));
            text += s;
            lead = lead || rs;
          }
        } else if (c.localName === 'br') {
          runs.push(make('br'));
        }
      });
      if (!text) {
        lead = Object.assign({}, st.run);
        applyRPr(lead, kid(p, 'endParaRPr'), S);
        runs.push(runSpan('\u200b', lead, S, scale));
      }

      pEl.style.textAlign = ALIGN[st.algn] || 'left';
      if (st.marL) { pEl.style.paddingLeft = r(st.marL) + 'px'; }
      if (st.lnSpc && st.lnSpc.pts) { pEl.style.lineHeight = r(st.lnSpc.pts * PT * reduce) + 'px'; }
      else { pEl.style.lineHeight = String(r(1.2 * (st.lnSpc ? st.lnSpc.pct : 1) * reduce)); }
      var lineH = lead.sz / 100 * PT * scale * 1.2;
      // PowerPoint ignores the space before the first paragraph of a text box
      if (st.spcBef && !firstPara) { pEl.style.paddingTop = r(st.spcBef.pts !== undefined ? st.spcBef.pts * PT : st.spcBef.pct * lineH) + 'px'; }
      if (st.spcAft) { pEl.style.paddingBottom = r(st.spcAft.pts !== undefined ? st.spcAft.pts * PT : st.spcAft.pct * lineH) + 'px'; }

      if (text && st.bullet) {
        var bu = make('span', 'pptx-bu'), bt = st.bullet.ch, font = st.buFont || lead.font;
        if (st.bullet.auto) {
          counters[lvl] = (counters[lvl] || st.bullet.start - 1) + 1;
          bt = autoNumber(st.bullet.auto, counters[lvl]);
          font = lead.font;
        } else if (st.buFont && /wingdings|symbol|webdings/i.test(st.buFont)) {
          bt = SYMBOL_BULLETS[bt] || '•';
          font = lead.font;
        }
        bu.textContent = bt;
        var bsz = st.buSize && st.buSize.pts ? st.buSize.pts : lead.sz * (st.buSize ? st.buSize.pct : 1);
        bu.style.fontSize = r(bsz / 100 * PT * scale) + 'px';
        bu.style.fontFamily = fontStack(font, S);
        if (st.buColor || lead.color) { bu.style.color = st.buColor || lead.color; }
        if (st.indent < 0) {
          bu.style.width = r(-st.indent) + 'px';
          bu.style.marginLeft = r(st.indent) + 'px';
        } else {
          bu.style.paddingRight = '0.5em';
        }
        pEl.appendChild(bu);
      } else if (st.indent && text) {
        pEl.style.textIndent = r(st.indent) + 'px';
      }
      if (text) {
        if (!st.bullet || !st.bullet.auto) { counters[lvl] = 0; }
        counters.length = lvl + 1;
      }
      runs.forEach(function (x) { pEl.appendChild(x); });
      container.appendChild(pEl);
      firstPara = false;
    });
  }

  function textLayers(node, chain, S) {
    var ph = phOf(node), base;
    if (!ph) {
      base = S.pres.defaultText;
    } else {
      var type = attr(ph, 'type') || (chain[1] && attr(phOf(chain[1]), 'type')) || 'obj';
      base = type === 'title' || type === 'ctrTitle' ? S.txStyles.title : /^(dt|ftr|sldNum|hdr)$/.test(type) ? S.txStyles.other : S.txStyles.body;
    }
    var layers = [base];
    for (var i = chain.length - 1; i >= 0; i--) { layers.push(path(chain[i], 'txBody', 'lstStyle')); }
    return layers;
  }

  // Shapes whose text area is smaller than their box (from PowerPoint's preset definitions): [top, right, bottom, left]
  function textArea(geom, w, h) {
    var prst = geom && geom.localName === 'prstGeom' ? attr(geom, 'prst') : null, av = geom ? adjusts(geom) : {}, ss = Math.min(w, h), a, m;
    function A(name, def) { return (name in av ? av[name] : def) / 100000; }
    switch (prst) {
      case 'ellipse': case 'flowChartConnector': m = [h * 0.1464, w * 0.1464, h * 0.1464, w * 0.1464]; break;
      case 'roundRect': a = Math.min(ss * A('adj', 16667), ss / 2) * 0.29289; m = [a, a, a, a]; break;
      case 'diamond': case 'flowChartDecision': m = [h / 4, w / 4, h / 4, w / 4]; break;
      case 'triangle': m = [h / 2, w / 4, 0, w / 4]; break;
      case 'hexagon': a = ss * A('adj', 25000); m = [0, a, 0, a]; break;
      case 'octagon': a = ss * A('adj', 29289) / 2; m = [a, a, a, a]; break;
      case 'homePlate': m = [0, ss * A('adj', 50000) / 2, 0, 0]; break;
      case 'chevron': a = ss * A('adj', 50000); m = [0, a, 0, a]; break;
      default: return [0, 0, 0, 0];
    }
    if (w - m[1] - m[3] < w * 0.2) { m[1] = m[3] = 0; }   // keep some room for the text
    if (h - m[0] - m[2] < h * 0.2) { m[0] = m[2] = 0; }
    return m;
  }

  function renderText(holder, node, chain, S, b, tf, parentEl, geom) {
    var tx = kid(node, 'txBody');
    if (!tx || !kids(tx, 'p').some(hasText)) { return; }
    var bp = {}, fit = null;
    for (var i = chain.length - 1; i >= 0; i--) {
      var e = path(chain[i], 'txBody', 'bodyPr');
      if (!e) { continue; }
      for (var k = 0; k < e.attributes.length; k++) { bp[e.attributes[k].localName] = e.attributes[k].value; }
      if (kid(e, 'normAutofit')) { fit = kid(e, 'normAutofit'); } else if (kid(e, 'noAutofit') || kid(e, 'spAutoFit')) { fit = null; }
    }
    var area = kid(node, 'txXfrm') ? [0, 0, 0, 0] : textArea(geom, b.w, b.h);
    function ins(name, def, extra) { var v = parseFloat(bp[name]); return r((isFinite(v) ? v : def) / EMU + extra) + 'px'; }

    var tbox = make('div', 'pptx-text'), host = holder;
    var txX = kid(node, 'txXfrm');
    if (txX && parentEl) {
      // SmartArt: the text has its own box, turned by the shape's rotation plus its own
      var tb = box(txX, tf);
      tb.rot = ((b.rot + tb.rot) % 360 + 360) % 360;
      host = place(parentEl, tb);
    }
    tbox.style.padding = [ins('tIns', 45720, area[0]), ins('rIns', 91440, area[1]), ins('bIns', 45720, area[2]), ins('lIns', 91440, area[3])].join(' ');
    tbox.style.justifyContent = ANCHOR[bp.anchor] || 'flex-start';
    if (bp.wrap === 'none') { tbox.style.whiteSpace = 'pre'; }
    if (/^(vert|eaVert|wordArtVert|mongolianVert|wordArtVertRtl)$/.test(bp.vert || '')) {
      tbox.style.writingMode = 'vertical-rl';
    } else if (bp.vert === 'vert270') {
      tbox.style.writingMode = 'vertical-rl';
      tbox.style.transform = 'rotate(180deg)';
    }
    var target = tbox;
    if (parseInt(bp.numCol, 10) > 1) {
      target = make('div');
      target.style.columnCount = String(Math.min(parseInt(bp.numCol, 10), 16));
      target.style.columnGap = r(parseFloat(bp.spcCol || 0) / EMU) + 'px';
      tbox.appendChild(target);
    }
    var fc = colorOf(path(node, 'style', 'fontRef'), S);
    renderParagraphs(target, tx, textLayers(node, chain, S), S, {
      color: css(fc),
      scale: fit ? num(fit, 'fontScale', 100000) / 100000 : 1,
      reduce: fit ? num(fit, 'lnSpcReduction', 0) / 100000 : 0
    });
    host.appendChild(tbox);
  }

  /* ---------- slide elements ---------- */

  async function renderShape(node, parentEl, S, part, tf) {
    var chain = inherit(node, S, part);
    var spPrs = chain.map(function (n) { return kid(n, 'spPr'); });
    var xf = first(spPrs, function (sp) { return kid(sp, 'xfrm'); });
    if (!xf) { return; }
    var b = box(xf, tf), holder = place(parentEl, b), style = kid(node, 'style');
    var fill = first(spPrs, function (sp) { return fillOf(sp, S); }) || refFill(kid(style, 'fillRef'), S);
    if (fill && fill.blip) { fill = { url: await S.pkg.image(part, fill.blip) }; }
    var line = resolveLine(first(spPrs, function (sp) { return kid(sp, 'ln'); }), kid(style, 'lnRef'), S);
    var geom = first(spPrs, function (sp) { return kid(sp, 'prstGeom') || kid(sp, 'custGeom'); });
    paint(holder, geometry(geom, b.w, b.h), fill, line, b);
    renderText(holder, node, chain, S, b, tf, parentEl, geom);
  }

  async function renderPicture(node, parentEl, S, part, tf, frameBox) {
    var chain = frameBox ? [node] : inherit(node, S, part);
    var spPrs = chain.map(function (n) { return kid(n, 'spPr'); });
    var b = frameBox;
    if (!b) {
      var xf = first(spPrs, function (sp) { return kid(sp, 'xfrm'); });
      if (!xf) { return; }
      b = box(xf, tf);
    }
    var holder = place(parentEl, b), clip = make('div', 'pptx-clip');
    var blipFill = kid(node, 'blipFill'), blip = kid(blipFill, 'blip');
    var geom = first(spPrs, function (sp) { return kid(sp, 'prstGeom') || kid(sp, 'custGeom'); });
    var own = first(spPrs, function (sp) { return fillOf(sp, S); });
    if (own && !own.none && !own.blip) { paint(holder, geometry(geom, b.w, b.h), own, null, b); }
    var prst = !geom ? 'rect' : geom.localName === 'custGeom' ? 'custom' : attr(geom, 'prst');
    if (prst === 'ellipse') {
      clip.style.borderRadius = '50%';
    } else if (prst !== 'rect') {
      var shape = geometry(geom, b.w, b.h)[0];
      if (shape && shape.fill && !shape.open) { clip.style.clipPath = 'path("' + shape.d + '")'; }
    }
    if (b.flipH || b.flipV) { clip.style.transform = 'scale(' + (b.flipH ? -1 : 1) + ',' + (b.flipV ? -1 : 1) + ')'; }
    var url = await S.pkg.image(part, rid(blip, 'embed'));
    if (url) {
      var img = make('img'), src = kid(blipFill, 'srcRect');
      var L = num(src, 'l', 0) / 100000, T = num(src, 't', 0) / 100000, R = num(src, 'r', 0) / 100000, B = num(src, 'b', 0) / 100000;
      var iw = b.w / Math.max(0.01, 1 - L - R), ih = b.h / Math.max(0.01, 1 - T - B);
      img.alt = attr(path(node, 'nvPicPr', 'cNvPr'), 'descr') || '';
      img.draggable = false;
      img.style.left = r(-L * iw) + 'px';
      img.style.top = r(-T * ih) + 'px';
      img.style.width = r(iw) + 'px';
      img.style.height = r(ih) + 'px';
      var amt = kid(blip, 'alphaModFix');
      if (amt) { img.style.opacity = String(num(amt, 'amt', 100000) / 100000); }
      img.onerror = function () { img.remove(); clip.classList.add('pptx-missing'); };
      img.src = url;
      clip.appendChild(img);
    } else {
      clip.classList.add('pptx-missing');
    }
    holder.appendChild(clip);
    var line = resolveLine(first(spPrs, function (sp) { return kid(sp, 'ln'); }), path(node, 'style', 'lnRef'), S);
    if (line) { paint(holder, geometry(geom, b.w, b.h), null, line, b); }
  }

  async function renderGroup(node, parentEl, S, part, tf, template) {
    var gp = kid(node, 'grpSpPr'), xf = kid(gp, 'xfrm'), holder = parentEl, inner = tf;
    if (xf) {
      var b = box(xf, tf), ch = kid(xf, 'chOff'), ce = kid(xf, 'chExt');
      var cw = num(ce, 'cx', 0), chh = num(ce, 'cy', 0);
      holder = place(parentEl, b);
      inner = { sx: cw ? b.w * EMU / cw : 1, sy: chh ? b.h * EMU / chh : 1 };
      inner.dx = -num(ch, 'x', 0) / EMU * inner.sx;
      inner.dy = -num(ch, 'y', 0) / EMU * inner.sy;
    }
    var GS = Object.create(S);
    GS.groupFill = fillOf(gp, S);
    var children = kids(node);
    for (var i = 0; i < children.length; i++) {
      try { await renderNode(children[i], holder, GS, part, inner, template); } catch (e) { /* skip what cannot be drawn */ }
    }
  }

  async function renderDiagram(data, parentEl, S, part, b) {
    var ids = kid(data, 'relIds'), dm = ids ? part.rels[rid(ids, 'dm')] : null;
    var dmRoot = dm ? await S.pkg.xml(dm.target) : null;
    var ext = dmRoot ? dmRoot.getElementsByTagNameNS('*', 'dataModelExt')[0] : null;
    var rel = ext ? part.rels[attr(ext, 'relId')] : null;
    var drawing = rel ? await S.pkg.part(rel.target) : null;
    var tree = drawing ? kid(drawing.root, 'spTree') : null;
    if (!tree) { return false; }
    var holder = place(parentEl, b), nodes = kids(tree), whole = path(dmRoot, 'whole', 'ln');
    paint(holder, presetPaths('rect', b.w, b.h, {}), fillOf(kid(dmRoot, 'bg'), S), whole ? resolveLine(whole, null, S) : null, { w: b.w, h: b.h });
    for (var i = 0; i < nodes.length; i++) {
      try { await renderNode(nodes[i], holder, S, drawing, IDENTITY, false); } catch (e) { /* skip */ }
    }
    return true;
  }

  async function renderFrame(node, parentEl, S, part, tf) {
    var xf = first(inherit(node, S, part), xfrmOf);
    if (!xf) { return; }
    var b = box(xf, tf), data = path(node, 'graphic', 'graphicData'), uri = attr(data, 'uri') || '';
    if (kid(data, 'tbl')) { return renderTable(place(parentEl, b), kid(data, 'tbl'), S); }
    if (/diagram$/.test(uri)) {
      if (!(await renderDiagram(data, parentEl, S, part, b))) { note(place(parentEl, b), 'SmartArt graphic'); }
      return;
    }
    if (/chart$/.test(uri)) {
      var ref = kid(data, 'chart'), crel = ref ? part.rels[rid(ref, 'id')] : null;
      var croot = crel ? await S.pkg.xml(crel.target) : null, ch = place(parentEl, b), drawn = false;
      try { drawn = !!croot && renderChart(ch, b, croot, S); } catch (e) { drawn = false; }
      if (!drawn) {
        ch.textContent = '';
        note(ch, 'Chart (not shown in the preview)');
      }
      return;
    }
    if (/chartex$/.test(uri)) { return note(place(parentEl, b), 'Chart (not shown in the preview)'); }
    var pic = data ? data.getElementsByTagNameNS('*', 'pic')[0] : null;
    if (pic) { return renderPicture(pic, parentEl, S, part, tf, b); }
    note(place(parentEl, b), 'Embedded object');
  }

  async function renderNode(node, parentEl, S, part, tf, template) {
    if (template && phOf(node)) { return; }   // layout and master placeholders are only templates
    switch (node.localName) {
      case 'sp': case 'cxnSp': return renderShape(node, parentEl, S, part, tf);
      case 'pic': return renderPicture(node, parentEl, S, part, tf, null);
      case 'grpSp': return renderGroup(node, parentEl, S, part, tf, template);
      case 'graphicFrame': return renderFrame(node, parentEl, S, part, tf);
      case 'AlternateContent': {
        var fallback = kid(node, 'Fallback'), use = fallback && fallback.firstElementChild ? fallback : kid(node, 'Choice');
        var list = kids(use);
        for (var i = 0; i < list.length; i++) { await renderNode(list[i], parentEl, S, part, tf, template); }
      }
    }
  }

  /* ---------- charts, drawn from the values cached in the file ---------- */

  var CHART_TYPES = { barChart: 'bar', bar3DChart: 'bar', lineChart: 'line', line3DChart: 'line', areaChart: 'area', area3DChart: 'area', pieChart: 'pie', pie3DChart: 'pie', ofPieChart: 'pie', doughnutChart: 'doughnut' };

  function all(el, name) { return el ? Array.prototype.slice.call(el.getElementsByTagNameNS('*', name)) : []; }
  function valOf(el, name, def) { var e = kid(el, name); return e && attr(e, 'val') !== null ? attr(e, 'val') : def; }
  function cacheValues(ref) {
    var out = [], lvl = all(ref, 'lvl')[0];
    all(lvl || ref, 'pt').forEach(function (pt) {
      var i = num(pt, 'idx', -1), v = kid(pt, 'v');
      if (i >= 0 && i < 5000) { out[i] = v ? v.textContent : ''; }
    });
    var count = Math.min(num(all(ref, 'ptCount')[0], 'val', out.length), 5000);
    for (var i = 0; i < count; i++) { if (out[i] === undefined) { out[i] = ''; } }
    return out;
  }
  function chartFont(txPr, base, S) {
    var f = Object.assign({}, base);
    applyRPr(f, path(txPr, 'p', 'pPr', 'defRPr'), S);
    return f;
  }
  function px(font) { return font.sz / 100 * PT; }
  function textWidth(text, font) { return String(text).length * px(font) * 0.55; }
  function chartText(parent, x, y, text, font, anchor, S) {
    var e = svg('text', { x: r(x), y: r(y), 'font-size': r(px(font)), 'text-anchor': anchor || 'start', 'dominant-baseline': 'central', fill: font.color || 'rgba(89,89,89,1)' });
    e.style.fontFamily = fontStack(font.font, S);
    if (font.b) { e.setAttribute('font-weight', '700'); }
    if (font.i) { e.setAttribute('font-style', 'italic'); }
    e.textContent = text;
    parent.appendChild(e);
  }
  function formatNumber(v, code) {
    code = code || 'General';
    var pct = code.indexOf('%') >= 0, m = /\.(0+)/.exec(code), dec = m ? m[1].length : code === 'General' ? null : 0;
    if (pct) { v *= 100; }
    var s = dec === null ? String(Math.round(v * 100) / 100) : v.toFixed(dec);
    if (code.indexOf(',') >= 0) {
      var parts = s.split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      s = parts.join('.');
    }
    return s + (pct ? '%' : '');
  }
  function niceStep(range) {
    if (!(range > 0)) { return 1; }
    // Roughly what PowerPoint picks: 0-60 in tens, 0-2,500 in five hundreds
    var raw = range / 8, mag = Math.pow(10, Math.floor(Math.log(raw) / Math.LN10)), n = raw / mag;
    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * mag;
  }
  function accent(i, S) {
    var key = 'accent' + (i % 6 + 1), c = hexRgb(S.theme.colors[key] || DEFAULT_COLORS[key]);
    return css(i >= 6 ? modify(c, 'shade', 0.6) : c);
  }
  function solidOf(spPr, S) {
    var f = fillOf(spPr, S);
    if (f && !f.none && f.solid) { return f.solid; }
    var ln = kid(spPr, 'ln'), l = ln ? lineOf(ln, S) : null;
    return l && l.color ? l.color : null;
  }
  function labelsOf(el, base, S) {
    if (!el || valOf(el, 'delete', '0') === '1') { return null; }
    var l = { val: valOf(el, 'showVal', '0') === '1', pct: valOf(el, 'showPercent', '0') === '1', font: chartFont(kid(el, 'txPr'), base, S), code: attr(kid(el, 'numFmt'), 'formatCode') };
    return l.val || l.pct ? l : null;
  }

  // Bar, column, line, area, pie and doughnut charts; anything else shows a placeholder
  function renderChart(holder, b, root, S) {
    var chart = kid(root, 'chart'), plot = kid(chart, 'plotArea'), group = null, type = null;
    for (var c = plot ? plot.firstElementChild : null; c; c = c.nextElementSibling) {
      if (CHART_TYPES[c.localName]) { group = c; type = CHART_TYPES[c.localName]; break; }
    }
    if (!group) { return false; }
    var base = chartFont(kid(root, 'txPr'), { sz: 1000, color: 'rgba(89,89,89,1)' }, S);
    var groupLabels = labelsOf(kid(group, 'dLbls'), base, S);
    var series = kids(group, 'ser').map(function (ser, i) {
      return {
        ser: ser, name: (all(kid(ser, 'tx'), 'v')[0] || {}).textContent || 'Series ' + (i + 1),
        cats: cacheValues(kid(ser, 'cat')),
        vals: cacheValues(kid(ser, 'val')).map(function (v) { var n = parseFloat(v); return isFinite(n) ? n : null; }),
        code: (all(kid(ser, 'val'), 'formatCode')[0] || {}).textContent,
        color: solidOf(kid(ser, 'spPr'), S) || accent(num(kid(ser, 'idx'), 'val', i), S),
        labels: kid(ser, 'dLbls') ? labelsOf(kid(ser, 'dLbls'), base, S) : groupLabels,
        points: kids(ser, 'dPt')
      };
    });
    if (!series.length) { return false; }
    var pie = type === 'pie' || type === 'doughnut', W = b.w, H = b.h;
    var cats = (series.filter(function (x) { return x.cats.length; })[0] || series[0]).cats;
    var n = Math.max.apply(null, [cats.length].concat(series.map(function (x) { return x.vals.length; })));
    function pointColor(x, i) {
      for (var k = 0; k < x.points.length; k++) {
        if (num(kid(x.points[k], 'idx'), 'val', -1) === i) {
          var col = solidOf(kid(x.points[k], 'spPr'), S);
          if (col) { return col; }
        }
      }
      return pie && valOf(group, 'varyColors', '1') !== '0' ? accent(i, S) : x.color;
    }

    var s = svg('svg', { 'class': 'pptx-geom', width: r(W), height: r(H), overflow: 'visible', role: 'img' });
    var bg = fillOf(kid(root, 'spPr'), S);
    if (bg && !bg.none && bg.solid) { s.appendChild(svg('rect', { width: r(W), height: r(H), fill: bg.solid })); }
    holder.appendChild(s);
    var top = 8, bottom = H - 8, left = 8, right = W - 8;

    // Title: its own text, or the series name for a single-series chart
    var titleEl = kid(chart, 'title'), title = '';
    if (titleEl) {
      title = all(titleEl, 't').map(function (t) { return t.textContent; }).join('') || (series.length === 1 ? series[0].name : 'Chart Title');
    } else if (valOf(chart, 'autoTitleDeleted', '0') !== '1' && series.length === 1) {
      title = series[0].name;
    }
    if (title) {
      var rich = path(titleEl, 'tx', 'rich'), tf = Object.assign({}, base, { sz: 1400 });
      applyRPr(tf, path(rich, 'p', 'pPr', 'defRPr'), S);
      applyRPr(tf, kid(path(rich, 'p', 'r'), 'rPr'), S);
      chartText(s, W / 2, top + px(tf) * 0.7, title, tf, 'middle', S);
      top += px(tf) * 1.6;
    }

    // Legend
    var legend = kid(chart, 'legend'), pos = legend ? valOf(legend, 'legendPos', 'r') : null;
    var entries = pie ? cats.map(function (name, i) { return { name: name, color: pointColor(series[0], i) }; })
      : series.map(function (x) { return { name: x.name, color: x.color }; });
    if (pos && entries.length) {
      var lf = chartFont(kid(legend, 'txPr'), base, S), lh = px(lf), sw = lh * 0.7;
      if (pos === 't' || pos === 'b') {
        var total = entries.reduce(function (a, e) { return a + sw + 6 + textWidth(e.name, lf) + 14; }, 0), lx = Math.max(left, (W - total) / 2);
        var ly = pos === 't' ? top + lh * 0.7 : bottom - lh * 0.7;
        entries.forEach(function (e) {
          s.appendChild(svg('rect', { x: r(lx), y: r(ly - sw / 2), width: r(sw), height: r(sw), fill: e.color }));
          chartText(s, lx + sw + 6, ly, e.name, lf, 'start', S);
          lx += sw + 6 + textWidth(e.name, lf) + 14;
        });
        if (pos === 't') { top += lh * 1.6; } else { bottom -= lh * 1.6; }
      } else {
        var lw = Math.min(W * 0.35, Math.max.apply(null, entries.map(function (e) { return textWidth(e.name, lf); })) + sw + 14);
        var x0 = pos === 'l' ? left : right - lw, y0 = (top + bottom) / 2 - entries.length * lh * 1.4 / 2 + lh * 0.7;
        entries.forEach(function (e, i) {
          s.appendChild(svg('rect', { x: r(x0), y: r(y0 + i * lh * 1.4 - sw / 2), width: r(sw), height: r(sw), fill: e.color }));
          chartText(s, x0 + sw + 6, y0 + i * lh * 1.4, e.name, lf, 'start', S);
        });
        if (pos === 'l') { left += lw + 8; } else { right -= lw + 8; }
      }
    }

    if (pie) {
      var x = series[0], vals = x.vals.map(function (v) { return Math.max(v || 0, 0); });
      var sum = vals.reduce(function (a, v) { return a + v; }, 0);
      var cx = (left + right) / 2, cy = (top + bottom) / 2, rad = Math.max(0, Math.min(right - left, bottom - top) / 2 - 4);
      var hole = type === 'doughnut' ? clamp(num(kid(group, 'holeSize'), 'val', 50), 10, 90) / 100 * rad : 0;
      var ang = num(kid(group, 'firstSliceAng'), 'val', 0) * Math.PI / 180 - Math.PI / 2;
      if (!sum || !rad) { return true; }
      vals.forEach(function (v, i) {
        if (!v) { return; }
        var sweep = v / sum * 2 * Math.PI, a2 = ang + sweep, d;
        if (sweep >= 2 * Math.PI - 1e-6) {
          d = ellipse(cx, cy, rad, rad) + (hole ? ' ' + ellipse(cx, cy, hole, hole) : '');
        } else {
          var p1 = [cx + rad * Math.cos(ang), cy + rad * Math.sin(ang)], p2 = [cx + rad * Math.cos(a2), cy + rad * Math.sin(a2)];
          d = 'M' + (hole ? r(cx + hole * Math.cos(ang)) + ' ' + r(cy + hole * Math.sin(ang)) + ' L' : r(cx) + ' ' + r(cy) + ' L') + r(p1[0]) + ' ' + r(p1[1]) + arcTo(rad, rad, p2, sweep);
          d += hole ? ' L' + r(cx + hole * Math.cos(a2)) + ' ' + r(cy + hole * Math.sin(a2)) + arcTo(hole, hole, [cx + hole * Math.cos(ang), cy + hole * Math.sin(ang)], -sweep) + ' Z' : ' Z';
        }
        s.appendChild(svg('path', { d: d, fill: pointColor(x, i), stroke: '#fff', 'stroke-width': 1, 'fill-rule': 'evenodd' }));
        if (x.labels) {
          var mid = ang + sweep / 2, lr = hole ? (rad + hole) / 2 : rad * 0.65, txt = [];
          if (x.labels.val) { txt.push(formatNumber(x.vals[i], x.labels.code || x.code)); }
          if (x.labels.pct) { txt.push(Math.round(v / sum * 100) + '%'); }
          chartText(s, cx + lr * Math.cos(mid), cy + lr * Math.sin(mid), txt.join('; '), x.labels.font, 'middle', S);
        }
        ang = a2;
      });
      return true;
    }

    if (!n) { return true; }
    var horizontal = type === 'bar' && valOf(group, 'barDir', 'col') === 'bar';
    var grouping = valOf(group, 'grouping', type === 'bar' ? 'clustered' : 'standard');
    var stacked = grouping === 'stacked' || grouping === 'percentStacked', percent = grouping === 'percentStacked';
    var valAx = kid(plot, 'valAx'), catAx = kid(plot, 'catAx') || kid(plot, 'dateAx');
    var lo = 0, hi = 0, i, k;
    for (i = 0; i < n; i++) {
      var up = 0, down = 0;
      for (k = 0; k < series.length; k++) {
        var v0 = series[k].vals[i] || 0;
        if (stacked) { if (v0 > 0) { up += v0; } else { down += v0; } } else { hi = Math.max(hi, v0); lo = Math.min(lo, v0); }
      }
      if (stacked) { hi = Math.max(hi, up); lo = Math.min(lo, down); }
    }
    if (percent) { lo = lo < 0 ? -100 : 0; hi = 100; }
    var scaling = kid(valAx, 'scaling'), step = num(kid(valAx, 'majorUnit'), 'val', 0);
    var vmin = kid(scaling, 'min') ? num(kid(scaling, 'min'), 'val', lo) : lo, vmax = kid(scaling, 'max') ? num(kid(scaling, 'max'), 'val', hi) : hi;
    step = step > 0 ? step : niceStep(vmax - vmin);
    if (!kid(scaling, 'min')) { vmin = Math.floor(vmin / step) * step; }
    if (!kid(scaling, 'max')) { vmax = Math.ceil(vmax / step) * step; }
    if (vmax <= vmin) { vmax = vmin + step; }
    var ticks = [];
    for (var t = vmin; t <= vmax + step / 1e6 && ticks.length < 50; t += step) { ticks.push(t); }
    var valCode = attr(kid(valAx, 'numFmt'), 'formatCode') || 'General';
    var tickText = function (v) { return percent ? r(v) + '%' : formatNumber(v, valCode === 'General' ? series[0].code : valCode); };
    var valFont = chartFont(kid(valAx, 'txPr'), base, S), catFont = chartFont(kid(catAx, 'txPr'), base, S);
    var showVal = valAx && valOf(valAx, 'delete', '0') !== '1', showCat = catAx && valOf(catAx, 'delete', '0') !== '1';
    var reverse = valOf(kid(catAx, 'scaling'), 'orientation', 'minMax') === 'maxMin';
    var catWidth = Math.max.apply(null, [0].concat(cats.map(function (c) { return textWidth(c, catFont); })));
    if (horizontal) {
      if (showCat) { left += Math.min((right - left) * 0.4, catWidth + 8); }
      if (showVal) { bottom -= px(valFont) * 1.6; }
    } else {
      if (showVal) { left += Math.max.apply(null, ticks.map(function (v) { return textWidth(tickText(v), valFont); })) + 8; }
      if (showCat) { bottom -= px(catFont) * 1.6; }
    }
    var pw = right - left, ph = bottom - top;
    if (pw < 10 || ph < 10) { return true; }
    function V(v) { var q = (clamp(v, vmin, vmax) - vmin) / (vmax - vmin); return horizontal ? left + q * pw : bottom - q * ph; }
    function slot(idx) {
      var j = reverse ? n - 1 - idx : idx;
      return horizontal ? { a: bottom - (j + 1) * ph / n, size: ph / n } : { a: left + j * pw / n, size: pw / n };
    }
    function line(x1, y1, x2, y2, color, width) {
      s.appendChild(svg('line', { x1: r(x1), y1: r(y1), x2: r(x2), y2: r(y2), stroke: color, 'stroke-width': width || 1 }));
    }
    var grid = kid(valAx, 'majorGridlines');
    if (grid) {
      var gl = path(grid, 'spPr', 'ln'), gc = (gl && lineOf(gl, S).color) || 'rgba(217,217,217,1)';
      ticks.forEach(function (v) { if (horizontal) { line(V(v), top, V(v), bottom, gc); } else { line(left, V(v), right, V(v), gc); } });
    }
    if (showVal) {
      ticks.forEach(function (v) {
        if (horizontal) { chartText(s, V(v), bottom + px(valFont) * 0.8, tickText(v), valFont, 'middle', S); }
        else { chartText(s, left - 6, V(v), tickText(v), valFont, 'end', S); }
      });
    }
    if (showCat) {
      var every = Math.max(1, Math.ceil((horizontal ? px(catFont) * 1.2 : catWidth + 6) / (horizontal ? ph / n : pw / n)));
      if (!horizontal && every > 1 && catWidth > pw / n * 3) { every = Math.max(1, Math.ceil(px(catFont) * 1.2 / (pw / n))); }
      cats.forEach(function (c, idx) {
        if (idx % every) { return; }
        var sl = slot(idx);
        if (horizontal) { chartText(s, left - 6, sl.a + sl.size / 2, c, catFont, 'end', S); }
        else { chartText(s, sl.a + sl.size / 2, bottom + px(catFont) * 0.8, c, catFont, 'middle', S); }
      });
    }
    function label(xs, v, x1, y1, inside) {
      if (!xs.labels || !xs.labels.val || v === null) { return; }
      chartText(s, x1, y1, formatNumber(v, xs.labels.code || xs.code), xs.labels.font, inside || !horizontal ? 'middle' : 'start', S);
    }

    if (type === 'bar') {
      var gap = num(kid(group, 'gapWidth'), 'val', 150) / 100, count = stacked ? 1 : series.length;
      for (i = 0; i < n; i++) {
        var sl = slot(i), size = sl.size / (1 + gap), start = sl.a + (sl.size - size) / 2, bar = size / count, pos = 0, neg = 0;
        var whole = percent ? series.reduce(function (a, x) { return a + Math.abs(x.vals[i] || 0); }, 0) : 1;
        series.forEach(function (x, si) {
          var v = x.vals[i];
          if (v === null || v === undefined) { return; }
          var val = percent ? (whole ? v / whole * 100 : 0) : v, from = 0, to = val;
          if (stacked) { if (val >= 0) { from = pos; pos += val; to = pos; } else { from = neg; neg += val; to = neg; } }
          var p1 = V(from), p2 = V(to), off = stacked ? start : start + (horizontal ? count - 1 - si : si) * bar, rect;
          rect = horizontal ? { x: Math.min(p1, p2), y: off, width: Math.abs(p2 - p1), height: bar } : { x: off, y: Math.min(p1, p2), width: bar, height: Math.abs(p2 - p1) };
          s.appendChild(svg('rect', { x: r(rect.x), y: r(rect.y), width: r(rect.width), height: r(rect.height), fill: pointColor(x, i) }));
          if (stacked) { label(x, v, rect.x + rect.width / 2, rect.y + rect.height / 2, true); }
          else if (horizontal) { label(x, v, rect.x + rect.width + 4, rect.y + rect.height / 2); }
          else { label(x, v, rect.x + rect.width / 2, rect.y - px(x.labels ? x.labels.font : base) * 0.7); }
        });
      }
    } else {
      var acc = [];
      series.forEach(function (x) {
        var pts = [];
        for (var j = 0; j < n; j++) {
          var v = x.vals[j];
          if (v === null || v === undefined) { continue; }
          if (stacked) { acc[j] = (acc[j] || 0) + v; v = acc[j]; }
          var c2 = slot(j).a + slot(j).size / 2;
          pts.push(horizontal ? [V(v), c2, x.vals[j]] : [c2, V(v), x.vals[j]]);
        }
        if (!pts.length) { return; }
        var d = 'M' + pts.map(function (q) { return r(q[0]) + ' ' + r(q[1]); }).join(' L');
        if (type === 'area') {
          var zero = V(Math.max(vmin, 0));
          s.appendChild(svg('path', { d: d + ' L' + r(pts[pts.length - 1][0]) + ' ' + r(zero) + ' L' + r(pts[0][0]) + ' ' + r(zero) + ' Z', fill: x.color, 'fill-opacity': 0.85 }));
        } else {
          var ln = path(x.ser, 'spPr', 'ln'), w = ln && attr(ln, 'w') ? num(ln, 'w', 28575) / EMU : 3;
          s.appendChild(svg('path', { d: d, fill: 'none', stroke: x.color, 'stroke-width': r(w), 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }));
          if (valOf(path(x.ser, 'marker'), 'symbol', 'circle') !== 'none') {
            pts.forEach(function (q) { s.appendChild(svg('circle', { cx: r(q[0]), cy: r(q[1]), r: 3.5, fill: x.color })); });
          }
        }
        pts.forEach(function (q) { label(x, q[2], q[0], q[1] - px(x.labels ? x.labels.font : base) * 0.9, true); });
      });
    }
    if (catAx) {
      var al = path(catAx, 'spPr', 'ln'), axis = al ? lineOf(al, S) : null;
      if (!(axis && axis.none)) {
        var zero2 = V(clamp(0, vmin, vmax)), ac = (axis && axis.color) || 'rgba(191,191,191,1)';
        if (horizontal) { line(zero2, top, zero2, bottom, ac); } else { line(left, zero2, right, zero2, ac); }
      }
    }
    return true;
  }

  /* ---------- tables ---------- */

  function tablePart(e, S) {
    if (!e) { return null; }
    var out = {}, tx = kid(e, 'tcTxStyle'), tc = kid(e, 'tcStyle');
    if (tx) {
      if (attr(tx, 'b')) { out.bold = attr(tx, 'b') === 'on'; }
      var c = colorOf(tx, S) || colorOf(kid(tx, 'fontRef'), S);
      if (c) { out.color = css(c); }
    }
    if (tc) {
      var f = fillOf(kid(tc, 'fill'), S) || refFill(kid(tc, 'fillRef'), S);
      if (f) { out.fill = f.none ? 'transparent' : f.css; }
      var bdr = kid(tc, 'tcBdr');
      if (bdr) {
        out.borders = {};
        kids(bdr).forEach(function (side) {
          var ln = kid(side, 'ln'), l = ln ? lineOf(ln, S) : refLine(kid(side, 'lnRef'), S);
          if (l) { out.borders[side.localName] = l.none || !l.color ? { none: true } : { color: l.color, width: l.width || 1, dash: l.dash }; }
        });
      }
    }
    return out;
  }
  // PowerPoint's default table style (Medium Style 2, Accent 1), used when a built-in style is not stored in the file
  function builtinTableStyle(S) {
    var acc = hexRgb(S.theme.colors.accent1) || hexRgb(DEFAULT_COLORS.accent1);
    var tint = function (v) { return css(modify(Object.assign({}, acc), 'tint', v)); };
    var white = { color: 'rgba(255,255,255,1)', width: 1 }, head = { fill: css(acc), color: 'rgba(255,255,255,1)', bold: true };
    return {
      wholeTbl: { fill: tint(0.2), color: css(hexRgb(S.theme.colors.dk1) || hexRgb('000000')), borders: { left: white, right: white, top: white, bottom: white, insideH: white, insideV: white } },
      band1H: { fill: tint(0.4) }, band1V: { fill: tint(0.4) }, firstRow: head, lastRow: head, firstCol: head, lastCol: head
    };
  }
  function tableStyle(id, S) {
    var key = id + '|' + (S.master ? S.master.name : '');
    if (!(key in S.pres.tableCache)) {
      var el = S.pres.tableStyles[id], ts = el ? {} : builtinTableStyle(S);
      if (el) { TABLE_PARTS.forEach(function (n) { ts[n] = tablePart(kid(el, n), S); }); }
      S.pres.tableCache[key] = ts;
    }
    return S.pres.tableCache[key];
  }
  function cellLook(ts, f, ri, ci, nr, nc) {
    var parts = ['wholeTbl'], look = { borders: {} };
    if (f.bandCol) {
      var dc = ci - (f.firstCol ? 1 : 0);
      if (dc >= 0 && !(f.lastCol && ci === nc - 1)) { parts.push(dc % 2 ? 'band2V' : 'band1V'); }
    }
    if (f.bandRow) {
      var dr = ri - (f.firstRow ? 1 : 0);
      if (dr >= 0 && !(f.lastRow && ri === nr - 1)) { parts.push(dr % 2 ? 'band2H' : 'band1H'); }
    }
    if (f.firstCol && ci === 0) { parts.push('firstCol'); }
    if (f.lastCol && ci === nc - 1) { parts.push('lastCol'); }
    if (f.lastRow && ri === nr - 1) { parts.push('lastRow'); }
    if (f.firstRow && ri === 0) { parts.push('firstRow'); }
    parts.forEach(function (name) {
      var p = ts[name];
      if (!p) { return; }
      if (p.fill) { look.fill = p.fill; }
      if (p.color) { look.color = p.color; }
      if (p.bold !== undefined) { look.bold = p.bold; }
      var B = p.borders, row = /H$|Row$/.test(name), col = /V$|Col$/.test(name);
      if (!B) { return; }
      var edges = {
        top: row || ri === 0 ? B.top : B.insideH, bottom: row || ri === nr - 1 ? B.bottom : B.insideH,
        left: col || ci === 0 ? B.left : B.insideV, right: col || ci === nc - 1 ? B.right : B.insideV
      };
      for (var k in edges) { if (edges[k]) { look.borders[k] = edges[k]; } }
    });
    return look;
  }
  function renderTable(holder, tbl, S) {
    var tblPr = kid(tbl, 'tblPr'), flags = {};
    ['firstRow', 'lastRow', 'firstCol', 'lastCol', 'bandRow', 'bandCol'].forEach(function (k) { flags[k] = bool(attr(tblPr, k)); });
    var idEl = kid(tblPr, 'tableStyleId'), id = idEl ? idEl.textContent.trim() : S.pres.tableStyleDef;
    var ts = id ? tableStyle(id, S) : null, tableFill = fillOf(tblPr, S);
    var cols = kids(path(tbl, 'tblGrid'), 'gridCol').map(function (c) { return num(c, 'w', 0) / EMU; });
    var rows = kids(tbl, 'tr'), t = make('table', 'pptx-table'), cg = make('colgroup'), body = make('tbody');
    t.style.width = r(cols.reduce(function (a, b) { return a + b; }, 0)) + 'px';
    cols.forEach(function (w) { var c = make('col'); c.style.width = r(w) + 'px'; cg.appendChild(c); });
    rows.forEach(function (tr, ri) {
      var trEl = make('tr');
      trEl.style.height = r(num(tr, 'h', 0) / EMU) + 'px';
      kids(tr, 'tc').forEach(function (tc, ci) {
        if (bool(attr(tc, 'hMerge')) || bool(attr(tc, 'vMerge'))) { return; }
        var td = make('td'), tcPr = kid(tc, 'tcPr');
        var look = ts ? cellLook(ts, flags, ri, ci, rows.length, cols.length) : { borders: {} };
        if (num(tc, 'gridSpan', 1) > 1) { td.colSpan = num(tc, 'gridSpan', 1); }
        if (num(tc, 'rowSpan', 1) > 1) { td.rowSpan = num(tc, 'rowSpan', 1); }
        td.style.padding = [num(tcPr, 'marT', 45720), num(tcPr, 'marR', 91440), num(tcPr, 'marB', 45720), num(tcPr, 'marL', 91440)]
          .map(function (v) { return r(v / EMU) + 'px'; }).join(' ');
        td.style.verticalAlign = CELL_ANCHOR[attr(tcPr, 'anchor')] || 'top';
        var f = fillOf(tcPr, S), bg = f ? (f.none ? '' : f.css) : look.fill || (tableFill && !tableFill.none ? tableFill.css : '');
        if (bg && bg !== 'transparent') { td.style.background = bg; }
        [['lnL', 'left', 'borderLeft'], ['lnR', 'right', 'borderRight'], ['lnT', 'top', 'borderTop'], ['lnB', 'bottom', 'borderBottom']].forEach(function (s) {
          var own = kid(tcPr, s[0]), l = own ? lineOf(own, S) : null;
          td.style[s[2]] = borderCss(l && (l.none || l.color) ? l : look.borders[s[1]]);
        });
        var tx = kid(tc, 'txBody');
        if (tx) { renderParagraphs(td, tx, [S.pres.defaultText, kid(tx, 'lstStyle')], S, { color: look.color, bold: look.bold, scale: 1 }); }
        trEl.appendChild(td);
      });
      body.appendChild(trEl);
    });
    t.appendChild(cg);
    t.appendChild(body);
    holder.appendChild(t);
  }

  /* ---------- slides ---------- */

  async function renderBackground(slideEl, S) {
    var owner = null, bg = null;
    [S.slide, S.layout, S.master].some(function (p) {
      bg = p ? path(p.root, 'cSld', 'bg') : null;
      owner = p;
      return !!bg;
    });
    slideEl.style.background = '#fff';
    if (!bg) { return; }
    var f = kid(bg, 'bgPr') ? fillOf(kid(bg, 'bgPr'), S) : refFill(kid(bg, 'bgRef'), S);
    if (!f || f.none) { return; }
    if (f.blip) {
      var url = await S.pkg.image(owner, f.blip);
      if (url) {
        slideEl.style.backgroundImage = 'url("' + url + '")';
        slideEl.style.backgroundSize = '100% 100%';
      }
      return;
    }
    if (f.css) { slideEl.style.background = f.css; }
  }

  async function renderTree(part, slideEl, S, template) {
    var nodes = kids(path(part.root, 'cSld', 'spTree'));
    for (var i = 0; i < nodes.length; i++) {
      try { await renderNode(nodes[i], slideEl, S, part, IDENTITY, template); } catch (e) { /* skip what cannot be drawn */ }
    }
  }

  async function renderSlide(pkg, pres, name, index, slideEl) {
    var slide = await pkg.part(name);
    if (!slide) { throw new Error('Missing slide'); }
    var layout = await pkg.part(relOfType(slide, 'slideLayout'));
    var master = await pkg.part(relOfType(layout, 'slideMaster'));
    var themePart = await pkg.part(relOfType(master, 'theme'));
    if (themePart && !themePart.theme) { themePart.theme = readTheme(themePart.root); }
    var S = {
      pkg: pkg, pres: pres, slide: slide, layout: layout, master: master, number: index + 1, phClr: null, groupFill: null,
      theme: themePart ? themePart.theme : readTheme(null), map: colorMap(master, layout, slide),
      txStyles: {
        title: path(master && master.root, 'txStyles', 'titleStyle'),
        body: path(master && master.root, 'txStyles', 'bodyStyle'),
        other: path(master && master.root, 'txStyles', 'otherStyle')
      }
    };
    await renderBackground(slideEl, S);
    var showMaster = attr(slide.root, 'showMasterSp') !== '0';
    if (showMaster && master && (!layout || attr(layout.root, 'showMasterSp') !== '0')) { await renderTree(master, slideEl, S, true); }
    if (showMaster && layout) { await renderTree(layout, slideEl, S, true); }
    await renderTree(slide, slideEl, S, false);
    return attr(slide.root, 'show') === '0';
  }

  async function render(buffer, output) {
    var pkg = new Package(await window.JSZip.loadAsync(buffer));
    var rootRels = await pkg.rels(''), presName = 'ppt/presentation.xml';
    for (var id in rootRels) {
      if (rootRels[id].type === 'officeDocument') { presName = rootRels[id].target; }
    }
    var presPart = await pkg.part(presName);
    if (!presPart) { throw new Error('This is not a PowerPoint presentation.'); }
    var sz = kid(presPart.root, 'sldSz');
    var pres = {
      w: num(sz, 'cx', 9144000) / EMU, h: num(sz, 'cy', 6858000) / EMU,
      defaultText: kid(presPart.root, 'defaultTextStyle'), tableStyles: {}, tableCache: {}, tableStyleDef: null
    };
    var tsName = relOfType(presPart, 'tableStyles'), tsRoot = tsName ? await pkg.xml(tsName) : null;
    if (tsRoot) {
      pres.tableStyleDef = attr(tsRoot, 'def');
      kids(tsRoot, 'tblStyle').forEach(function (s) { pres.tableStyles[attr(s, 'styleId')] = s; });
    }
    var slides = kids(kid(presPart.root, 'sldIdLst'), 'sldId').map(function (s) {
      var rel = presPart.rels[rid(s, 'id')];
      return rel ? rel.target : null;
    }).filter(Boolean);

    var deck = make('div', 'pptx-deck');
    output.appendChild(deck);
    if (!slides.length) {
      var empty = make('div', 'pptx-label');
      empty.textContent = 'This presentation has no slides.';
      deck.appendChild(empty);
      return;
    }
    function fit() {
      var avail = document.documentElement.clientWidth - 32;
      if (avail <= 0) { return; }
      var k = Math.min(1, avail / pres.w);
      Array.prototype.forEach.call(deck.querySelectorAll('.pptx-frame'), function (f) {
        f.style.width = r(pres.w * k) + 'px';
        f.style.height = r(pres.h * k) + 'px';
        f.firstChild.style.transform = 'scale(' + k + ')';
      });
    }
    window.addEventListener('resize', fit);

    var count = Math.min(slides.length, MAX_SLIDES);
    for (var i = 0; i < count; i++) {
      var frame = make('div', 'pptx-frame'), slideEl = make('div', 'pptx-slide'), label = make('div', 'pptx-label');
      slideEl.style.width = r(pres.w) + 'px';
      slideEl.style.height = r(pres.h) + 'px';
      frame.appendChild(slideEl);
      deck.appendChild(frame);
      deck.appendChild(label);
      label.textContent = 'Slide ' + (i + 1) + ' of ' + slides.length;
      fit();
      try {
        if (await renderSlide(pkg, pres, slides[i], i, slideEl)) { label.textContent += ' (hidden in the slide show)'; }
      } catch (e) {
        slideEl.textContent = '';
        note(slideEl, 'This slide could not be shown.');
      }
    }
    if (slides.length > count) {
      var more = make('div', 'pptx-label');
      more.textContent = 'Showing the first ' + count + ' slides. Download the file to see the rest.';
      deck.appendChild(more);
    }
    fit();
  }

  window.FmsPptx = { render: render };
})();
