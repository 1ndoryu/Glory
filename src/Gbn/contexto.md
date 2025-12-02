# GBN - Contexto del Proyecto

## Guía de Documentación
Para mantener la claridad y el orden, la documentación del proyecto se divide en tres archivos clave:

1.  **`plan.md`**: **Plan de Acción**. Contiene el roadmap activo, tareas pendientes, estrategias futuras y el historial de cambios. Es el documento de trabajo diario.
2.  **`reglas.md`**: **Constitución del Proyecto**. Contiene las reglas inmutables, principios arquitectónicos, definiciones canónicas y la "verdad única" sobre cómo deben funcionar los sistemas (ej: Theme Settings).
3.  **`contexto.md`**: **Estado y Visión**. (Este archivo). Describe qué es el proyecto, su estado actual de funcionalidades y restricciones vitales.

---

## Información Vital y Restricciones
> [!NOTE]
> Reglas globales del usuario que aplican a todo el desarrollo.

-   **Idioma y Estilo**: Código en **Español** y **camelCase**. Comentarios útiles y modernos.
-   **Integridad**: **NUNCA omitir código** al editar. No usar `...resto del codigo`.
-   **Mentalidad**: Entender antes de actuar. Evitar sesgos de asunción.
-   **Calidad**: Código moderno, minimalista, SOLID y fácil de mantener.

---

## Funcionalidades Actuales (Estado del Arte)

### 1. Sistema de Componentes Inteligente
GBN no usa componentes propietarios opacos.
- **Detección Automática**: `roles.js` escanea el HTML y asigna roles (`principal`, `secundario`) basados en atributos estándar (`gloryDiv`).
- **Esquemas Dinámicos**: Los paneles de edición se generan en tiempo real (`panel-render.js`) leyendo el esquema definido en `ContainerRegistry.php`. Si añades un campo en PHP, aparece automáticamente en JS.

### 2. Motor de Estilos en Tiempo Real
- **Variables CSS**: El sistema prefiere variables CSS sobre estilos inline duros. Cambiar el "Color Primario" en el panel actualiza `--gbn-primary`, repintando todo el sitio instantáneamente (0ms delay).
- **Herencia Visual**: El panel indica visualmente si un valor es heredado.
    - *Azul*: Valor propio del bloque/página.
    - *Amarillo*: Valor heredado (del Tema o Breakpoint superior).

### 3. Sistema Responsive "True-View"
- **Simulación Real**: No usa iframes lentos. Simula viewports limitando el ancho del contenedor `body` (`responsive.js`), lo que garantiza que el CSS se comporte exactamente igual que en un dispositivo real.
- **Persistencia por Breakpoint**: Los valores se guardan asociados a su breakpoint (`_responsive: { mobile: { ... } }`).

### 4. Gestión de Contenido Híbrida
- **`gloryContentRender`**: Componente especial que puede renderizar contenido dinámico (PHP) dentro del editor visual.
- **AJAX Partial Views**: `ContentHandler` sirve fragmentos HTML renderizados por el servidor, permitiendo editar loops y queries complejos visualmente.
