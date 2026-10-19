const fs = require('fs');
const vm = require('vm');
const template = fs.readFileSync('app/Views/landing/index.php', 'utf8');
const css = fs.readFileSync('public/assets/landing-surabaya.css', 'utf8');
if (template.includes('#kontak') || template.includes('surabaya-login-bookmark')) throw new Error('Old contact or bookmark remains.');
if (!/<nav[\s\S]*?data-login-card-toggle[\s\S]*?<\/nav>/.test(template)) throw new Error('Login action must be in navigation.');
if (!/\.surabaya-landing-links a \{ display: none; \}/.test(css)) throw new Error('Mobile navigation must retain the login action.');

function classes(initial = []) {
    const state = new Set(initial);
    return { add: (...values) => values.forEach((value) => state.add(value)), remove: (...values) => values.forEach((value) => state.delete(value)), contains: (value) => state.has(value), toggle: (value, force) => { if (force === undefined ? !state.has(value) : force) state.add(value); else state.delete(value); } };
}
function element(initial = []) {
    return { classList: classes(initial), dataset: {}, attributes: {}, handlers: {}, setAttribute(key, value) { this.attributes[key] = value; }, getAttribute(key) { return this.attributes[key]; }, addEventListener(event, handler) { this.handlers[event] = handler; }, focus() {} };
}
const home = element(['active']);
const about = element();
const toggle = element(); toggle.dataset.openOnLoad = 'false';
const card = element(); card.scrollIntoView = () => {};
const flow = element();
const username = element();
const nodes = { '[data-login-card]': card, '[data-landing-flow]': flow, '[data-login-card-toggle]': toggle };
const document = { querySelector: (selector) => nodes[selector] || null, querySelectorAll: () => [home, about], getElementById: (id) => id === 'surabayaUsername' ? username : null, addEventListener() {} };
const window = { addEventListener() {}, clearTimeout() {}, setTimeout: (fn) => { fn(); return 1; }, matchMedia: () => ({ matches: false }) };
const script = template.match(/<script>\s*\(\(\) => \{[\s\S]*?<\/script>/)[0].replace(/^<script>/, '').replace(/<\/script>$/, '');
vm.runInNewContext(script, { document, window, requestAnimationFrame: (fn) => fn() });
toggle.handlers.click();
if (toggle.getAttribute('aria-expanded') !== 'true' || !toggle.classList.contains('active') || home.classList.contains('active') || !card.classList.contains('is-open') || !flow.classList.contains('is-active')) throw new Error('Login navigation did not open the existing card and flow.');
toggle.handlers.click();
if (toggle.getAttribute('aria-expanded') !== 'false' || toggle.classList.contains('active') || !home.classList.contains('active') || card.classList.contains('is-open')) throw new Error('Closing login did not restore navigation.');
toggle.handlers.click();
about.handlers.click();
if (!about.classList.contains('active') || toggle.classList.contains('active') || card.classList.contains('is-open')) throw new Error('Navigation selection did not close login and move active state.');
console.log('Landing navigation: passed');
