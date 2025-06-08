└── 📁glory-framework/         # Raíz del proyecto
    ├── 📁assets/                 # Recursos públicos (CSS, JS, imágenes)
    │   ├── 📁css/
    │   └── 📁js/
    ├── 📁config/                 # Archivos de configuración que retornan arreglos
    │   ├── app.php
    │   └── post-types.php
    ├── 📁src/                    # TODO el código fuente PHP (Lógica)
    │   ├── 📁Admin/                # Clases para el panel de administración de WP
    │   ├── 📁Ajax/                 # Clases que manejan endpoints de admin-ajax.php
    │   ├── 📁Components/           # Clases PHP (Cerebros) que controlan los componentes
    │   │   └── UserProfile.php
    │   ├── 📁Core/                 # Clases nucleares del framework
    │   │   ├── GloryLogger.php
    │   │   ├── PostTypeManager.php
    │   │   └── ScriptManager.php
    │   ├── 📁Services/             # Clases que proveen funcionalidades específicas
    │   │   └── ServidorChat.php
    │   └── 📁Utilities/            # Clases de ayuda con métodos estáticos (Helpers)
    └── 📁view/
        ├── 📁admin/
        ├── 📁components/
        ├── 📁partials/
        │   └── header.php
        └── 📁search/                   # <--- Carpeta para todo lo visual de la búsqueda
            ├── form.php                # <--- La "cara" del formulario de búsqueda
            ├── results-list.php        # <--- La "cara" que renderiza la lista de resultados
            └── result-item.php         # <--- (Opcional) La "cara" de un único item en la lista
    └── load.php                  # Punto de entrada principal del framework

Característica	                                    ¿Servicio?      ¿Componente?

¿Devuelve datos (arrays, objetos)?	                Sí	            No
¿Devuelve HTML renderizado?	                        No	            Sí
¿Contiene lógica de negocio compleja?	            Sí	            No (delega al servicio)
¿Sabe cómo mostrarse (usa una plantilla view)?	    No	            Sí
¿Es reutilizable por un endpoint AJAX?	            Sí	            No directamente
