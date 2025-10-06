class submenus {
    static instancia;

    menuActivo = null;
    disparadorActivo = null;
    temporizadorPulsacionLarga = null;
    fuePulsacionLarga = false;

    /**
     * Inicia el gestor de menús contextuales y garantiza que solo haya una instancia (Singleton).
     */
    static iniciar() {
        if (!submenus.instancia) {
            submenus.instancia = new submenus();
        }
        return submenus.instancia;
    }

    constructor() {
        if (submenus.instancia) {
            throw new Error('Esta clase es un Singleton. Usa submenus.iniciar()');
        }
        document.body.addEventListener('click', this._gestionarEventoPrincipal.bind(this), true);
        document.body.addEventListener('contextmenu', this._gestionarEventoPrincipal.bind(this), true);
        document.body.addEventListener('pointerdown', this._iniciarPulsacion.bind(this));
        document.body.addEventListener('pointerup', this._finalizarPulsacion.bind(this));
        document.body.addEventListener('pointermove', this._cancelarPulsacion.bind(this));
        document.addEventListener('keydown', this._gestionarTecla.bind(this));

        // Si se abre un modal o se hace clic en el fondo, cerramos el submenú activo
        document.addEventListener('gloryModal:open', this.cerrar.bind(this));
        document.addEventListener('gloryFondo:click', this.cerrar.bind(this));
    }

    abrir(disparador, opciones = {}) {
        if (!disparador) return;

        // CAMBIO AQUÍ: Usamos getAttribute para evitar problemas con mayúsculas en el nombre del atributo.
        const menuId = disparador.getAttribute('data-submenu');
        const menu = document.getElementById(menuId);

        if (!menu) {
            console.error(`Menu contextual no encontrado para el ID: ${menuId}`);
            return;
        }

        if (this.menuActivo && this.menuActivo !== menu) {
            this.cerrar();
        }

        this.menuActivo = menu;
        this.disparadorActivo = disparador;

        // Asegurar estilos mínimos por defecto si el menú no tiene clases conocidas
        if (!this.menuActivo.classList.contains('glory-submenu') && !this.menuActivo.classList.contains('submenus')) {
            this.menuActivo.classList.add('glory-submenu');
            this.menuActivo.dataset.gloryAutoStyled = 'true';
        }
        // Asegurar posicionamiento para coord. absolutas
        this.menuActivo.style.position = this.menuActivo.style.position || 'fixed';

        document.body.appendChild(this.menuActivo); // Asegura que está en el nivel superior
        this._posicionarMenu(opciones);
        this.menuActivo.classList.add('activo');

        // Reutilizamos el gestor de fondo global si existe
        if (typeof window.mostrarFondo === 'function') {
            window.mostrarFondo();
        }
    }

    cerrar() {
        if (!this.menuActivo) return;

        this.menuActivo.classList.remove('activo');
        // La clase noScroll ahora la gestiona el fondo global
        this.menuActivo = null;
        this.disparadorActivo = null;

        if (typeof window.ocultarFondo === 'function') {
            window.ocultarFondo();
        }
    }

    _gestionarEventoPrincipal(evento) {
        const disparador = evento.target.closest('[data-submenu]');

        if (this.fuePulsacionLarga) {
            evento.preventDefault();
            evento.stopPropagation();
            this.fuePulsacionLarga = false;
            return;
        }

        if (disparador) {
            evento.preventDefault();

            const tipoEventoRequerido = disparador.dataset.evento || 'click';
            if (evento.type !== tipoEventoRequerido) return;

            if (this.disparadorActivo === disparador) {
                this.cerrar();
            } else {
                this.abrir(disparador);
            }
        } else if (this.menuActivo && this.menuActivo.contains(evento.target)) {
            // Clic dentro del submenú: ejecutar acción y cerrar automáticamente
            try { setTimeout(() => this.cerrar(), 0); } catch(_) {}
        } else if (this.menuActivo && !this.menuActivo.contains(evento.target)) {
            // Clic fuera del submenú: cerramos, pero dejamos que el evento continúe para no interferir con otros componentes (modales)
            this.cerrar();
        }
        // Si no se cumple ninguna condición, no hacemos nada y dejamos que el evento continúe.
    }

    _iniciarPulsacion(evento) {
        const disparador = evento.target.closest('[data-submenu]');
        if (!disparador || evento.pointerType !== 'touch') return;

        const tipoEventoRequerido = disparador.dataset.evento || 'click';
        if (tipoEventoRequerido !== 'longpress') return;

        this.temporizadorPulsacionLarga = setTimeout(() => {
            this.fuePulsacionLarga = true;
            this.abrir(disparador);
        }, 500);
    }

    _finalizarPulsacion(evento) {
        clearTimeout(this.temporizadorPulsacionLarga);
    }

    _cancelarPulsacion(evento) {
        clearTimeout(this.temporizadorPulsacionLarga);
    }

    _gestionarTecla(evento) {
        if (evento.key === 'Escape' && this.menuActivo) {
            this.cerrar();
        }
    }

    _posicionarMenu(opciones = {}) {
        if (!this.menuActivo || !this.disparadorActivo) return;

        const menu = this.menuActivo;
        const disparador = this.disparadorActivo;
        const pos = disparador.dataset.posicion || 'abajo';
        const cursor = opciones && opciones.cursor ? opciones.cursor : null;
        const rect = disparador.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        // Resetear estilos para recalcular dimensiones reales
        menu.style.visibility = 'hidden';
        menu.style.display = 'flex';
        const menuWidth = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;
        menu.style.display = '';
        menu.style.visibility = '';

        let top, left;

        // Si nos pasan coordenadas exactas del cursor (click derecho), usar esas
        if (cursor && Number.isFinite(cursor.x) && Number.isFinite(cursor.y)) {
            top = cursor.y;
            left = cursor.x;
        } else {

        // En móvil, generalmente se prefiere una vista centrada tipo modal
        if (vw <= 640 || pos === 'centro') {
            top = (vh - menuHeight) / 2;
            left = (vw - menuWidth) / 2;
        } else {
            switch (pos) {
                case 'arriba':
                    top = rect.top - menuHeight;
                    left = rect.left + rect.width / 2 - menuWidth / 2;
                    break;
                case 'izquierda':
                    top = rect.top + rect.height / 2 - menuHeight / 2;
                    left = rect.left - menuWidth;
                    break;
                case 'derecha':
                    top = rect.top + rect.height / 2 - menuHeight / 2;
                    left = rect.right;
                    break;
                case 'abajo':
                default:
                    top = rect.bottom;
                    left = rect.left + rect.width / 2 - menuWidth / 2;
                    break;
            }
        }
        }

        // Ajuste para que no se salga de la pantalla
        menu.style.top = `${Math.max(0, Math.min(top, vh - menuHeight))}px`;
        menu.style.left = `${Math.max(0, Math.min(left, vw - menuWidth))}px`;
    }
}

submenus.iniciar();
// Exponer un cierre global para que otros componentes (modales) puedan cerrar submenús antes de abrirse
window.gloryCerrarSubmenus = function() { try { submenus.iniciar().cerrar(); } catch(_) {} };
