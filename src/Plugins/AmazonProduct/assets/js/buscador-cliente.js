/**
 * Motor de Busqueda del Lado del Cliente
 *
 * Proporciona busqueda instantanea sin latencia de red.
 * Carga un indice ligero de productos y ejecuta busqueda fuzzy en JavaScript.
 */
const BuscadorCliente = (function () {
    'use strict';

    let indice = null;
    let timestampIndice = 0;
    let cargandoIndice = false;
    let promesaCarga = null;

    const STORAGE_KEY = 'amazonSearchIndex';
    const STORAGE_TIMESTAMP_KEY = 'amazonSearchIndexTime';

    /**
     * Carga el indice de productos.
     * Intenta cargar desde sessionStorage primero, luego desde el servidor.
     */
    async function cargarIndice() {
        if (indice !== null) {
            return indice;
        }

        if (cargandoIndice && promesaCarga) {
            return promesaCarga;
        }

        cargandoIndice = true;

        promesaCarga = new Promise(async (resolve, reject) => {
            try {
                const indiceCacheado = obtenerIndiceDeCache();

                if (indiceCacheado) {
                    const esValido = await verificarValidezIndice(indiceCacheado.timestamp);

                    if (esValido) {
                        indice = indiceCacheado.products;
                        timestampIndice = indiceCacheado.timestamp;
                        cargandoIndice = false;
                        resolve(indice);
                        return;
                    }
                }

                const indiceNuevo = await descargarIndice();
                indice = indiceNuevo.products;
                timestampIndice = indiceNuevo.timestamp;
                guardarIndiceEnCache(indiceNuevo);
                cargandoIndice = false;
                resolve(indice);
            } catch (error) {
                cargandoIndice = false;
                reject(error);
            }
        });

        return promesaCarga;
    }

    /**
     * Obtiene el indice desde sessionStorage.
     */
    function obtenerIndiceDeCache() {
        try {
            const datos = sessionStorage.getItem(STORAGE_KEY);
            const timestamp = sessionStorage.getItem(STORAGE_TIMESTAMP_KEY);

            if (datos && timestamp) {
                return {
                    products: JSON.parse(datos),
                    timestamp: parseInt(timestamp, 10)
                };
            }
        } catch (e) {
            console.warn('Error al leer cache de busqueda:', e);
        }
        return null;
    }

    /**
     * Guarda el indice en sessionStorage.
     */
    function guardarIndiceEnCache(indiceData) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(indiceData.products));
            sessionStorage.setItem(STORAGE_TIMESTAMP_KEY, indiceData.timestamp.toString());
        } catch (e) {
            console.warn('Error al guardar cache de busqueda:', e);
        }
    }

    /**
     * Verifica si el timestamp del indice cacheado es valido.
     */
    async function verificarValidezIndice(timestampLocal) {
        if (typeof amazonProductAjax === 'undefined') {
            return false;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'amazon_search_index_timestamp');
            formData.append('nonce', amazonProductAjax.nonce);

            const response = await fetch(amazonProductAjax.ajax_url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success && data.data.timestamp) {
                return timestampLocal >= data.data.timestamp;
            }
        } catch (e) {
            /* Si hay error, consideramos valido el cache local */
            return true;
        }

        return false;
    }

    /**
     * Descarga el indice desde el servidor.
     */
    async function descargarIndice() {
        if (typeof amazonProductAjax === 'undefined') {
            throw new Error('amazonProductAjax no disponible');
        }

        const formData = new FormData();
        formData.append('action', 'amazon_search_index');
        formData.append('nonce', amazonProductAjax.nonce);

        const response = await fetch(amazonProductAjax.ajax_url, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && data.data.products) {
            return data.data;
        }

        throw new Error('Respuesta invalida del servidor');
    }

    /**
     * Busca productos en el indice local.
     */
    function buscar(termino, limite = 5) {
        if (!indice || !Array.isArray(indice)) {
            return {productos: [], total: 0};
        }

        const terminoNormalizado = normalizarTexto(termino);

        if (terminoNormalizado.length < 2) {
            return {productos: [], total: 0};
        }

        const palabrasBusqueda = extraerPalabras(terminoNormalizado);
        const coincidencias = [];

        for (const producto of indice) {
            const score = calcularScore(producto, terminoNormalizado, palabrasBusqueda);

            if (score > 0) {
                coincidencias.push({producto, score});
            }
        }

        coincidencias.sort((a, b) => b.score - a.score);

        const topCoincidencias = coincidencias.slice(0, limite);

        return {
            productos: topCoincidencias.map(c => ({
                title: c.producto.o,
                price: c.producto.p,
                image: c.producto.i,
                url: c.producto.u
            })),
            total: coincidencias.length
        };
    }

    /**
     * Calcula el score de coincidencia para un producto.
     */
    function calcularScore(producto, termino, palabrasBusqueda) {
        let score = 0;
        const tituloNormalizado = producto.t;
        const palabrasProducto = extraerPalabras(tituloNormalizado);

        /* Coincidencia exacta del termino completo */
        if (tituloNormalizado.includes(termino)) {
            score += 100;
        }

        /* Coincidencia por prefijo */
        if (tituloNormalizado.startsWith(termino)) {
            score += 50;
        }

        /* Busqueda fuzzy palabra por palabra */
        for (const palabraBusqueda of palabrasBusqueda) {
            if (palabraBusqueda.length < 3) {
                /* Para palabras cortas, solo coincidencia exacta o prefijo */
                for (const palabraProducto of palabrasProducto) {
                    if (palabraProducto === palabraBusqueda) {
                        score += 30;
                    } else if (palabraProducto.startsWith(palabraBusqueda)) {
                        score += 20;
                    }
                }
                continue;
            }

            for (const palabraProducto of palabrasProducto) {
                /* Coincidencia exacta de palabra */
                if (palabraProducto === palabraBusqueda) {
                    score += 30;
                    continue;
                }

                /* Coincidencia por prefijo */
                if (palabraProducto.startsWith(palabraBusqueda)) {
                    score += 25;
                    continue;
                }

                /* Busqueda fuzzy con Levenshtein */
                if (palabraProducto.length >= 3) {
                    const distancia = levenshtein(palabraBusqueda, palabraProducto);
                    const maxLen = Math.max(palabraBusqueda.length, palabraProducto.length);
                    const distanciaPermitida = Math.min(2, Math.floor(maxLen / 3));

                    if (distancia <= distanciaPermitida) {
                        score += (2 - distancia + 1) * 10;
                    }
                }
            }
        }

        return score;
    }

    /**
     * Calcula la distancia de Levenshtein entre dos strings.
     */
    function levenshtein(a, b) {
        if (a.length === 0) return b.length;
        if (b.length === 0) return a.length;

        const matrix = [];

        for (let i = 0; i <= b.length; i++) {
            matrix[i] = [i];
        }

        for (let j = 0; j <= a.length; j++) {
            matrix[0][j] = j;
        }

        for (let i = 1; i <= b.length; i++) {
            for (let j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(matrix[i - 1][j - 1] + 1, matrix[i][j - 1] + 1, matrix[i - 1][j] + 1);
                }
            }
        }

        return matrix[b.length][a.length];
    }

    /**
     * Normaliza texto removiendo acentos y convirtiendo a minusculas.
     */
    function normalizarTexto(texto) {
        return texto
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    /**
     * Extrae palabras de un texto.
     */
    function extraerPalabras(texto) {
        return texto.split(/[\s\-_.,;:!?()/\[\]]+/).filter(w => w.length >= 2);
    }

    /**
     * Verifica si el indice esta cargado.
     */
    function indiceCargado() {
        return indice !== null;
    }

    /**
     * Verifica si el indice esta cargando.
     */
    function indiceCargando() {
        return cargandoIndice;
    }

    /**
     * Limpia el indice de memoria y cache.
     */
    function limpiarIndice() {
        indice = null;
        timestampIndice = 0;
        try {
            sessionStorage.removeItem(STORAGE_KEY);
            sessionStorage.removeItem(STORAGE_TIMESTAMP_KEY);
        } catch (e) {
            /* Ignorar errores de sessionStorage */
        }
    }

    return {
        cargarIndice,
        buscar,
        indiceCargado,
        indiceCargando,
        limpiarIndice
    };
})();

/* Exportar para uso global */
window.BuscadorCliente = BuscadorCliente;
