import { createContext } from 'react';
import type { GloryContext, GloryContentMap } from '../types/glory';

const defaultContext: GloryContext = {
    siteUrl: '',
    themeUrl: '',
    restUrl: '/wp-json',
    nonce: '',
    isAdmin: false,
    locale: 'es',
};

export interface GloryProviderValue {
    context: GloryContext;
    content: GloryContentMap;
}

export const GloryReactContext = createContext<GloryProviderValue | null>(null);

export function getGloryProviderValue(): GloryProviderValue {
    return {
        context: { ...defaultContext, ...window.GLORY_CONTEXT },
        content: window.__GLORY_CONTENT__ ?? {},
    };
}
