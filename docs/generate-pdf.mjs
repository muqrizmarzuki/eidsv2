import { createRequire }  from 'module';
import { readFileSync, writeFileSync, unlinkSync } from 'fs';
import { fileURLToPath } from 'url';
import path from 'path';

const require = createRequire(import.meta.url);
const __dir   = path.dirname(fileURLToPath(import.meta.url));
const md       = readFileSync(path.join(__dir, 'USER_MANUAL.md'), 'utf8');

// ── 1. Convert Markdown to HTML via marked ─────────────────────────────────
const { marked } = await import('/var/www/node_modules/marked/lib/marked.esm.js');

// Fix image paths to absolute file:// so Chromium can load them
const mdFixed = md.replace(
  /!\[([^\]]*)\]\(screenshots\/([^)]+)\)/g,
  (_, alt, file) => `![${alt}](file:///var/www/docs/screenshots/${file})`
);

let body = marked.parse(mdFixed);

// Replace emoji tick/cross with styled HTML so headless Chromium renders them without emoji fonts
body = body
  .replace(/✅/g, '<span style="color:#1a7f37;font-weight:bold">✓</span>')
  .replace(/❌/g, '<span style="color:#cf222e;font-weight:bold">✗</span>');

// Convert GitHub-style [!NOTE] / [!IMPORTANT] blockquotes to styled divs
body = body.replace(
  /<blockquote>\s*<p>\[!(NOTE|IMPORTANT|WARNING|TIP)\]([\s\S]*?)<\/blockquote>/g,
  (_, type, content) => {
    const labels = { NOTE: '📝 Note', IMPORTANT: '⚠️ Important', WARNING: '⚠️ Warning', TIP: '💡 Tip' };
    const colors = { NOTE: '#0969da', IMPORTANT: '#d1242f', WARNING: '#bf8700', TIP: '#1a7f37' };
    const bgs    = { NOTE: '#ddf4ff', IMPORTANT: '#fff0ee', WARNING: '#fff8c5', TIP: '#dafbe1' };
    return `<div style="border-left:4px solid ${colors[type]};background:${bgs[type]};padding:8px 16px;margin:1em 0;border-radius:0 4px 4px 0">
      <strong style="color:${colors[type]}">${labels[type]}</strong>${content}</div>`;
  }
);

// ── 2. Build the full HTML page ────────────────────────────────────────────
const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>E-IDS v2 — User Manual</title>

<!-- KaTeX -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
  onload="renderMathInElement(document.body, {
    delimiters: [
      {left: '$$', right: '$$', display: true},
      {left: '$', right: '$', display: false}
    ]
  });"></script>

<!-- Mermaid -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>

<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11pt;
    line-height: 1.6;
    color: #1a1a1a;
    max-width: 900px;
    margin: 0 auto;
    padding: 30px 40px;
  }
  h1 { font-size: 22pt; border-bottom: 3px solid #1a6b3c; padding-bottom: 8px; color: #1a6b3c; }
  h2 { font-size: 15pt; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-top: 2em; color: #1a3a2a; }
  h3 { font-size: 12pt; color: #1a3a2a; margin-top: 1.5em; }
  h4 { font-size: 11pt; color: #333; }
  code { font-family: 'Consolas','Courier New',monospace; background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-size: 9.5pt; }
  pre { background: #f4f4f4; padding: 12px; border-radius: 5px; overflow-x: auto; font-size: 9pt; }
  pre code { background: none; padding: 0; }
  table { border-collapse: collapse; width: 100%; margin: 1em 0; font-size: 9pt; table-layout: fixed; }
  th { background: #1a6b3c; color: white; padding: 6px 8px; text-align: left; word-wrap: break-word; }
  th:first-child { width: 42%; }
  th:not(:first-child) { width: 19.3%; text-align: center; }
  td { padding: 5px 8px; border: 1px solid #ddd; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word; }
  td:not(:first-child) { text-align: center; }
  td code { font-size: 8pt; background: #eef; padding: 1px 3px; border-radius: 2px; white-space: normal; word-break: break-all; }
  tr:nth-child(even) td { background: #f9f9f9; }
  img { max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0; display: block; }
  em { color: #555; font-size: 9.5pt; }
  blockquote { border-left: 4px solid #1a6b3c; margin: 1em 0; padding: 8px 16px; background: #f0f7f4; color: #333; }
  blockquote p { margin: 0; }
  /* GitHub-style alerts */
  blockquote p:first-child { margin-bottom: 4px; }
  .mermaid { margin: 1em auto; text-align: center; }
  hr { border: none; border-top: 1px solid #ddd; margin: 2em 0; }
  ol, ul { padding-left: 1.5em; }
  li { margin: 4px 0; }
  @media print {
    body { padding: 0; max-width: 100%; }
    h2 { page-break-before: auto; }
    img, .mermaid { page-break-inside: avoid; }
    @page { margin: 20mm; size: A4; }
  }
</style>
</head>
<body>
${body}
<script>
  // Replace marked's pre>code.language-mermaid blocks with .mermaid divs BEFORE mermaid.init
  document.querySelectorAll('pre > code').forEach(el => {
    if ((el.className || '').includes('mermaid')) {
      const div = document.createElement('div');
      div.className = 'mermaid';
      div.textContent = el.textContent;
      el.parentElement.replaceWith(div);
    }
  });
  mermaid.initialize({ startOnLoad: true, theme: 'default', securityLevel: 'loose' });
  // Signal to Playwright when done
  mermaid.run().then(() => { window.__mermaidDone = true; });
</script>
</body>
</html>`;

const htmlPath = '/tmp/user-manual.html';
writeFileSync(htmlPath, html);
console.log('HTML written →', htmlPath);

// ── 3. Use Playwright to render and export PDF (waits for Mermaid to finish) ─
const { chromium } = require('/var/www/node_modules/playwright-core/index.js');

const browser = await chromium.launch({
  executablePath: '/usr/bin/chromium',
  headless: true,
  args: ['--no-sandbox', '--disable-gpu'],
});
const page = await browser.newPage();

await page.goto('file://' + htmlPath, { waitUntil: 'networkidle', timeout: 30000 });

// Wait for Mermaid diagrams to render
await page.waitForFunction(() => window.__mermaidDone === true, { timeout: 15000 }).catch(() => {
  console.warn('Mermaid wait timed out — proceeding anyway');
});

// Also wait for KaTeX (deferred scripts)
await page.waitForTimeout(1500);

const outPdf = path.join(__dir, 'E-IDS-v2-User-Manual.pdf');
await page.pdf({
  path: outPdf,
  format: 'A4',
  margin: { top: '20mm', right: '20mm', bottom: '20mm', left: '20mm' },
  printBackground: true,
});

await browser.close();
unlinkSync(htmlPath);
console.log('PDF written →', outPdf);
