import process from 'node:process';

const endpoint = process.argv[2] ?? 'http://127.0.0.1:9222';
const appUrl = process.argv[3] ?? 'http://127.0.0.1:8765';
const pages = await fetch(`${endpoint}/json/list`).then((response) => response.json());
const page = pages.find((candidate) => candidate.type === 'page');

if (! page) throw new Error('Chrome DevTools did not expose a page target.');

const socket = new WebSocket(page.webSocketDebuggerUrl);
const pending = new Map();
const browserErrors = [];
let commandId = 0;

socket.addEventListener('message', ({ data }) => {
    const message = JSON.parse(data);

    if (message.id && pending.has(message.id)) {
        const { resolve, reject } = pending.get(message.id);
        pending.delete(message.id);
        message.error ? reject(new Error(message.error.message)) : resolve(message.result);
    }

    if (message.method === 'Runtime.exceptionThrown') {
        browserErrors.push(message.params.exceptionDetails.text);
    }

    if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error') {
        browserErrors.push(`${message.params.entry.text} (${message.params.entry.url ?? 'unknown URL'})`);
    }

    if (message.method === 'Runtime.consoleAPICalled' && message.params.type === 'error') {
        browserErrors.push(message.params.args.map((argument) => argument.value ?? argument.description).join(' '));
    }
});

await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
});

function command(method, params = {}) {
    const id = ++commandId;
    socket.send(JSON.stringify({ id, method, params }));

    return new Promise((resolve, reject) => pending.set(id, { resolve, reject }));
}

async function evaluate(expression) {
    const result = await command('Runtime.evaluate', {
        expression,
        awaitPromise: true,
        returnByValue: true,
    });

    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);

    return result.result.value;
}

async function navigate(path) {
    await command('Page.navigate', { url: `${appUrl}${path}` });

    for (let attempt = 0; attempt < 100; attempt++) {
        const ready = await evaluate("document.readyState === 'complete' && document.querySelectorAll('[data-web-vue-component=accordion]').length === 2");

        if (ready) return;
        await new Promise((resolve) => setTimeout(resolve, 50));
    }

    throw new Error(`Vue did not mount after navigation to ${path}.`);
}

function assert(value, message) {
    if (! value) throw new Error(message);
}

await command('Runtime.enable');
await command('Log.enable');
await command('Page.enable');
await navigate('/_test/browser/set-locale-en');

let state = await evaluate(`(() => ({
    lang: document.documentElement.lang,
    dir: document.documentElement.dir,
    mounted: document.querySelector('#app')?.dataset.webVueMounted,
    instances: document.querySelectorAll('[data-web-vue-component="accordion"]').length,
    serverContent: document.querySelector('[data-showcase-server-content]')?.textContent,
    firstA: document.querySelector('#showcase-one-a-trigger')?.getAttribute('aria-expanded'),
    firstB: document.querySelector('#showcase-one-b-trigger')?.getAttribute('aria-expanded'),
}))()`);

assert(state.lang === 'en' && state.dir === 'ltr', 'English showcase is not LTR.');
assert(state.mounted === 'true' && state.instances === 2, 'Vue app/components did not mount exactly once/twice.');
assert(state.serverContent.includes('rendered by Laravel'), 'Vue mounting removed server content.');
assert(state.firstA === 'true' && state.firstB === 'false', 'Initial server disclosure state changed.');

await evaluate("document.querySelector('#showcase-one-b-trigger').click()");
state = await evaluate(`(() => ({
    firstA: document.querySelector('#showcase-one-a-trigger').getAttribute('aria-expanded'),
    firstB: document.querySelector('#showcase-one-b-trigger').getAttribute('aria-expanded'),
    secondA: document.querySelector('#showcase-two-a-trigger').getAttribute('aria-expanded'),
}))()`);
assert(state.firstA === 'false' && state.firstB === 'true', 'Single-open state did not switch.');
assert(state.secondA === 'false', 'First instance leaked state into the second instance.');

await evaluate("document.querySelector('#showcase-two-a-trigger').click(); document.querySelector('#showcase-two-b-trigger').click()");
state = await evaluate(`(() => ({
    secondA: document.querySelector('#showcase-two-a-trigger').getAttribute('aria-expanded'),
    secondB: document.querySelector('#showcase-two-b-trigger').getAttribute('aria-expanded'),
}))()`);
assert(state.secondA === 'true' && state.secondB === 'true', 'Multiple-open state was not preserved.');

await evaluate("document.querySelector('#showcase-one-a-trigger').focus()");
await command('Input.dispatchKeyEvent', { type: 'rawKeyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
assert(await evaluate("document.querySelector('#showcase-one-a-trigger').getAttribute('aria-expanded') === 'true'"), 'Enter did not activate the native trigger.');

await command('Input.dispatchKeyEvent', { type: 'rawKeyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
assert(await evaluate("document.querySelector('#showcase-one-a-trigger').getAttribute('aria-expanded') === 'false'"), 'Escape did not collapse the focused disclosure.');

await command('Input.dispatchKeyEvent', { type: 'rawKeyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
assert(await evaluate("document.activeElement?.id === 'showcase-one-b-trigger'"), 'Tab did not reach the next native trigger.');

await command('Input.dispatchKeyEvent', { type: 'rawKeyDown', key: ' ', code: 'Space', windowsVirtualKeyCode: 32 });
await command('Input.dispatchKeyEvent', { type: 'keyUp', key: ' ', code: 'Space', windowsVirtualKeyCode: 32 });
assert(await evaluate("document.querySelector('#showcase-one-b-trigger').getAttribute('aria-expanded') === 'true'"), 'Space did not activate the native trigger.');

await navigate('/_test/browser/set-locale-ar');
state = await evaluate("({ lang: document.documentElement.lang, dir: document.documentElement.dir, instances: document.querySelectorAll('[data-web-vue-component=accordion]').length })");
assert(state.lang === 'ar' && state.dir === 'rtl', 'Arabic showcase is not RTL.');
assert(state.instances === 2, 'Vue accordions did not mount in RTL.');
assert(browserErrors.length === 0, `Browser console/runtime errors: ${browserErrors.join(' | ')}`);

socket.close();
process.stdout.write(`${JSON.stringify({ status: 'PASS', ltr: true, rtl: true, instances: 2, keyboard: ['Tab', 'Enter', 'Space', 'Escape'], consoleErrors: 0 })}\n`);
