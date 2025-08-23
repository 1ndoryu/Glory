// Masonry row-major para Glory (agnóstico)
// Exponer API en español y camelCase: Glory.inicializarMasonryRowMajor y Glory.recalcularMasonryRowMajor
(function(){
    window.Glory = window.Glory || {};

    function detectarColumnasDesdeCSS(el){
        try{
            const gridCols = getComputedStyle(el).getPropertyValue('grid-template-columns');
            if(gridCols && gridCols.trim() !== 'none'){
                return gridCols.split(' ').length || 2;
            }
        }catch(e){/* ignore */}
        return 2;
    }

    function inicializarMasonryRowMajor(containerSelector, columnas){
        const container = document.querySelector(containerSelector);
        if(!container) return;

        container.classList.add('masonry-js');
        columnas = columnas || detectarColumnasDesdeCSS(container) || 2;

        function esVisible(el){
            try{
                const cs = getComputedStyle(el);
                return cs.display !== 'none' && cs.visibility !== 'hidden' && el.offsetParent !== null;
            }catch(e){
                return !el.classList.contains('oculto');
            }
        }

        function obtenerItemsVisibles(){
            return Array.from(container.querySelectorAll('.seccionComponente')).filter(esVisible);
        }

        function layout(){
            const items = obtenerItemsVisibles();
            const contWidth = container.clientWidth;
            const cols = columnas;
            const gap = parseFloat(getComputedStyle(container).getPropertyValue('gap')) || 16;
            const colWidth = (contWidth - gap * (cols - 1)) / cols;

            const colHeights = new Array(cols).fill(0);

            for(let i=0;i<items.length;i++){
                const c = i % cols;
                const el = items[i];
                el.style.position = 'absolute';
                el.style.width = colWidth + 'px';
                const x = c * (colWidth + gap);
                const y = colHeights[c];
                el.style.transform = `translate(${x}px, ${y}px)`;
                const h = el.getBoundingClientRect().height;
                colHeights[c] += h + gap;
            }

            const maxH = Math.max.apply(null, colHeights);
            container.style.height = (isFinite(maxH) ? maxH : 0) + 'px';
        }


        Glory.recalcularMasonryRowMajor = function(){ layout(); };

        const observer = new MutationObserver(function(){
            clearTimeout(container._masonryMutate);
            container._masonryMutate = setTimeout(layout, 80);
        });
        observer.observe(container, {childList: true, subtree: true, attributes: true, attributeFilter: ['class']});

        window.addEventListener('load', layout);
        window.addEventListener('resize', function(){
            clearTimeout(container._masonryResize);
            container._masonryResize = setTimeout(layout, 120);
        });

        container.querySelectorAll('img').forEach(img => img.addEventListener('load', layout));

        document.addEventListener('glory:seccionesActualizadas', function(){ layout(); });

        layout();

        return { layout: layout, desconectar: function(){ observer.disconnect(); } };
    }

    document.addEventListener('DOMContentLoaded', function(){
        const c = document.querySelector('#glory-component-examples');
        if(!c) return;
        const cols = detectarColumnasDesdeCSS(c) || 2;
        Glory._masonryInstance = inicializarMasonryRowMajor('#glory-component-examples', cols);
    });

    Glory.inicializarMasonryRowMajor = inicializarMasonryRowMajor;
})();


