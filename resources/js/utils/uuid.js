export function safeUUID() {
    if (
        typeof window !== 'undefined'
        && window.crypto
        && typeof window.crypto.randomUUID === 'function'
    ) {
        return window.crypto.randomUUID();
    }

    const ts = Date.now().toString(36);
    const rand = Math.random().toString(36).substring(2, 10);
    return `${ts}-${rand}`;
}
