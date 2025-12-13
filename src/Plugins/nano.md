# Tareas Pendientes - Amazon Product Plugin

## Prioridad Alta

### 1. Loader de Paginación Mal Posicionado
**Problema:** Cuando se cambia de página, el loader (`.amazon-loader`) aparece arriba en la página en lugar de quedarse fijo sobre la sección de productos.

**Solución propuesta:** 
- Hacer el loader `position: fixed` o `position: sticky`
- Centrar visualmente sobre el grid de productos
- O hacer el loader relativo al contenedor `.amazon-product-wrapper`

**Archivos a modificar:**
- `assets/css/amazon-product.css` - Estilos del loader

---

## Prioridad Media

### 2. Verificar Paginación
- Confirmar que la paginación ahora funciona correctamente después del fix de event delegation
- Probar en todas las secciones: Palas, Zapatillas, Ropa, Accesorios

### 3. Precios no coinciden con Amazon
- Investigar si es problema de caché o de la API
- Considerar agregar fecha de última actualización visible

---

## Completado

- [x] Pestaña de Configuración Guiada de API (wizard paso a paso)
- [x] Fix de paginación con event delegation
- [x] Manejo de errores cuando API no está configurada
- [x] Estilos del botón "Ir al panel de RapidAPI"
- [x] **Filtros de exclusión por palabras** - Nuevo atributo `exclude` implementado
  - Palas: excluye paletero, bolsa, funda, protector, etc.
  - Ropa: excluye zapatilla, pala, etc.
  - Accesorios: excluye pala, paletero, bolsa, etc.
  - Zapatillas: excluye pala, paletero, etc.
  - Pelotas: excluye pala, paletero, etc.
  - Bolsas/Paleteros: excluye pala, zapatilla, etc.
