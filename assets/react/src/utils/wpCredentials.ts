/*
 * Singletons de credenciales WordPress (nonce y restUrl).
 * Extraído de useWordPressApi para cumplir SRP y límite de líneas.
 *
 * Solo se leen una vez de window y se cachean como singleton.
 */

let cachedNonce: string | null = null;
let cachedRestUrl: string | null = null;

export function getNonce(): string {
    if (cachedNonce !== null) return cachedNonce;

    const context = window.GLORY_CONTEXT;
    if (context?.nonce) {
        cachedNonce = context.nonce;
        return cachedNonce;
    }

    /* Fallback: WP Core pone el nonce en wpApiSettings */
    const wpSettings = (window as unknown as Record<string, unknown>).wpApiSettings as
        | { nonce?: string }
        | undefined;
    cachedNonce = wpSettings?.nonce ?? '';
    return cachedNonce;
}

export function getRestUrl(): string {
    if (cachedRestUrl !== null) return cachedRestUrl;

    const context = window.GLORY_CONTEXT;
    if (context?.restUrl) {
        cachedRestUrl = context.restUrl;
        return cachedRestUrl;
    }

    /* Fallback: WP Core */
    const wpSettings = (window as unknown as Record<string, unknown>).wpApiSettings as
        | { root?: string }
        | undefined;
    cachedRestUrl = wpSettings?.root ?? '/wp-json';
    return cachedRestUrl;
}

/* Resetea los singletons de nonce/restUrl. Util si el nonce se renueva sin recargar pagina. */
export function resetApiCredentials(): void {
    cachedNonce = null;
    cachedRestUrl = null;
}
