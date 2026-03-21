/// <reference types="vite/client" />

/* [2003A-15] Declaraciones para APIs globales de Capacitor (mobile) y flags internos */
interface Window {
    Capacitor?: {
        isNativePlatform?: () => boolean;
        getPlatform?: () => string;
    };
    __KAMPLES_MOBILE__?: boolean;
}
