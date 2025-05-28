<?php
# Glory/Admin/logsPage.php
# Estilos movidos a Glory\assets\css\logsPage.css

/**
 * Página de administración para visualizar los logs del Glory Framework.
 * Esta página permite filtrar, buscar y ver los logs generados por el framework.
 *
 * @package Glory\Admin
 */

function logPage()
{
    // NO MÁS ASEGURAR JQUERY AQUÍ

    ob_start();

    $unique_classes = [];
    $unique_methods = [];

    if (class_exists('Glory\Class\GloryLogger')) { // Chequea si la clase existe
        if (function_exists('get_unique_glory_log_classes_and_methods')) {
            list($unique_classes, $unique_methods) = get_unique_glory_log_classes_and_methods();
        }
    } else {
        echo "<p style='color:red;'>Error: GloryLogger class not found. Cannot populate filters.</p>";
    }

?>

    <div class="glory-logger-viewer">

        <div class="glory-filters">
            <div class="filter-group">
                <label for="filter-classes">Clase(s):</label>
                <select id="filter-classes" multiple="multiple" style="width:100%; min-height: 80px;">
                    <?php foreach ($unique_classes as $class_name) : ?>
                        <option value="<?php echo esc_attr($class_name); ?>"><?php echo esc_html($class_name); ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
            <div class="filter-group">
                <label for="filter-methods">Función/Método(s):</label>
                <select id="filter-methods" multiple="multiple" style="width:100%; min-height: 80px;">
                    <?php foreach ($unique_methods as $method_name) : ?>
                        <option value="<?php echo esc_attr($method_name); ?>"><?php echo esc_html($method_name); ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
            <div class="filter-group">
                <label for="filter-level">Nivel:</label>
                <select id="filter-level">
                    <option value="all">Todos</option>
                    <option value="info">INFO</option>
                    <option value="error">ERROR</option>
                </select>
            </div>
            <div class="filter-group" style="grid-column: span 1; display: flex; gap: 10px; align-items: flex-end;">
                <!-- Espacio vacío para alinear botones o añadir otro filtro -->
            </div>

        </div>
        <div class="botones-filter">
            <button id="apply-filters">Aplicar Filtros</button>
            <button id="glory-clear-filters">Limpiar Filtros</button>
        </div>

        <div id="glory-logs-container">
            <div class="glory-loader"></div>
            <!-- Los logs se insertarán aquí -->
        </div>
        <button id="glory-load-more" style="display:none;">Cargar Más</button>
    </div>

    <?php // EL SCRIPT INLINE HA SIDO ELIMINADO DE AQUÍ 
    ?>
    <style>
        .firefox * {
            scrollbar-width: thin;
            scrollbar-color: #373737 transparent;
        }

        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }

        img {
            border-style: none;
            vertical-align: top;
            max-width: 100%;
            height: auto;
        }

        body,
        body p,
        textarea {
            font-size: 13px;
            /* line-height: 12px; */
            display: block;
            margin-block-start: 0em;
            margin-block-end: 0em;
            margin-inline-start: 0px;
            margin-inline-end: 0px;
            unicode-bidi: isolate;
            color: #d4d4d4;
            line-height: 1.6;
            font-family: "Source Sans 3", Arial, Helvetica, sans-serif;
            letter-spacing: 0px;
            font-weight: 400px !important;
            text-decoration: none;
            text-wrap: pretty;
        }


        body,
        body p,
        textarea {
            font-family: "Source Sans 3", Arial, Helvetica, sans-serif !important;
            letter-spacing: 0px;
            font-style: normal;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            text-decoration: none;
            text-wrap: pretty;
        }

        .firefox *::-webkit-scrollbar {
            width: 4px;
            background: transparent;
        }

        .firefox *::-webkit-scrollbar-thumb {
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .firefox .menu-container {
            filter: url(#blur-effect) !important;
            background-color: rgba(9, 9, 9, 0.85);
        }

        .firefox .panelperfilsupsec {
            background: #070707;
            background-color: rgb(7, 7, 7);
            filter: url(#blur-effect) !important;
            background-color: rgba(9, 9, 9, 0.85);
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
            transition: width 0.3s ease, height 0.3s ease;
        }

        ::-webkit-scrollbar-track {
            background: none;
        }

        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 10px;
        }

        .scroll-visible::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .scroll-visible::-webkit-scrollbar-thumb {
            background: unset;
        }

        .scroll-visible::-webkit-scrollbar-thumb:hover {
            background: #888;
        }


        /*V4*/
        :root {
            --fondo: #070707;
            /*Post*/
            --borde: 1px #161616 solid;
            --radius: 5px;
            --bordeBoton: 1px #1f1f1f8c solid;
            --padding: 20px;
            --ancho: 600px;
        }

        .appAndroid p {
            font-size: 14px;
        }


        .appAndroid button {
            font-size: 14px !important;
        }


        .appAndroid a {
            font-size: 14px;
        }

        .appAndroid .JHVSFW a {
            font-size: 15px !important;
        }

        .appAndroid .ZVJVZA {
            padding-bottom: 0px;
            line-height: 13px;
            padding-top: 5px;
        }

        .appAndroid .HQLXWD a {
            font-size: 12px;
        }

        @media (max-width: 640px) {
            ::-webkit-scrollbar {
                width: 0px;
                height: 0px;
                transition: width 0.3s ease, height 0.3s ease;
            }

            .scroll-visible::-webkit-scrollbar {
                width: 0px;
                height: 0px;
            }
        }

        .appAndroid .infoIA-btn,
        .appAndroid span.postTag,
        .appAndroid .badge,
        .appAndroid .postTagToggle {
            font-size: 13px !important;

        }

        ul {
            list-style: none;
        }

        /*V4*/
        .bloque {
            display: flex;
            background: var(--fondo);
            border-radius: var(--radius);
            padding: var(--padding);
            border: var(--borde);
            width: var(--ancho);
            max-width: 100%;
            flex-direction: column;
            gap: 10px;
        }

        .borde {
            border: var(--borde);
        }

        /*V4*/
        textarea {
            color: #fff;
            border: none;
            padding: 0px;
            margin-bottom: 10px;
            font-size: 12px;
            background: none;
            resize: none;
            width: 100%;
        }

        .seleccionado {
            filter: brightness(2);
            opacity: 1 !important;
            border: 1px solid #676767;
        }

        /*V4*/
        button {
            all: unset;
            display: flex;
            margin: 0px;
            color: #fff;
            font-size: 11px;
            border-radius: 4px;
            cursor: pointer;
            padding: 5px 15px;
            align-items: center;
            height: fit-content;
            gap: 5px;

        }

        /*V4*/
        .botonprincipal {
            background: white !important;
            color: black !important;
            border: 1px #00000000 solid !important;
        }

        /*V4*/
        .previewAreaArchivos {
            border: var(--borde);
            border-radius: var(--radius);
            display: flex;
            align-content: center;
            text-align: center;
            padding: var(--padding);
            min-height: 60px;
            cursor: pointer;
            width: 100%;
        }

        /*V4*/
        .noborde {
            border: unset;
        }

        /*V4*/
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /*V4*/
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /*V4*/
        form {
            display: flex;
            flex-direction: column;
        }

        .heart-animation {
            animation: fade 2s ease forwards;
            color: red;
            font-size: 30px;
        }

        /*V4*/
        .waveform-container {
            cursor: pointer;
        }

        /*V4*/
        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: var(--fondo);
            border: var(--borde);
            padding: 20px;
            border-radius: var(--radius);
            width: 400px;
            max-width: 90%;
            display: flex;
            z-index: 1006;
            font-size: 11px;
            flex-direction: column;
            flex-wrap: nowrap;
            gap: 10px;
        }

        /*V4*/
        .custom-checkbox {
            display: flex;
            cursor: pointer;
            padding: 8px;
            user-select: none;
            color: #6b6b6b;
            border: var(--borde);
            border-radius: 5px;
            width: 100%;
            justify-content: center;
        }
    </style>

<?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}
?>