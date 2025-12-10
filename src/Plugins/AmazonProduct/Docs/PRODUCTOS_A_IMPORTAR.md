# Lista de Productos a Importar para MaterialDePadel

## Guia Rapida de Importacion Manual

### Paso a paso
1. Ve a **Amazon.es** (o tu region)
2. Busca el producto (ej: "Bullpadel Vertex 03 2024")
3. Abre la pagina del producto
4. Presiona `Ctrl+U` para ver el codigo fuente
5. Selecciona todo (`Ctrl+A`) y copia (`Ctrl+C`)
6. En WordPress ve a **Amazon Products > Settings > Manual Import**
7. Pega el HTML en el area de texto
8. Click en "Procesar HTML"
9. Verifica los datos extraidos (titulo, precio, imagen)
10. **MUY IMPORTANTE**: Asigna la categoria correcta antes de guardar

### Importacion por lotes (mas rapido)
1. Guarda cada pagina de producto como archivo `.html` (Ctrl+S en el navegador)
2. Arrastra multiples archivos `.html` a la zona de importacion
3. Revisa la tabla de productos detectados
4. Asigna categorias en lote
5. Click en "Importar Seleccionados"

---

## Categorias a crear en WordPress

Antes de importar, crea estas categorias en **Amazon Products > Categorias**:

| Slug               | Nombre              | Descripcion                     |
| ------------------ | ------------------- | ------------------------------- |
| `palas`            | Palas de Padel      | Palas de todas las marcas       |
| `zapatillas`       | Zapatillas de Padel | Calzado para padel              |
| `ropa`             | Ropa de Padel       | Camisetas, shorts, faldas, etc. |
| `pelotas`          | Pelotas de Padel    | Botes y packs de pelotas        |
| `accesorios`       | Accesorios de Padel | Overgrips, protectores, etc.    |
| `bolsas-paleteros` | Bolsas y Paleteros  | Mochilas y paleteros            |

---

## Productos Recomendados por Categoria

### PALAS DE PADEL (categoria: `palas`)
Busca 2-3 productos por marca para cubrir todas las paginas de marcas.

#### Adidas
- Adidas Metalbone 2024
- Adidas Adipower CTRL 3.2
- Adidas Adipower Light 3.2

#### Bullpadel
- Bullpadel Vertex 04
- Bullpadel Hack 04
- Bullpadel Flow Light

#### NOX
- Nox AT10 Genius 18K
- Nox ML10 Pro Cup
- Nox Nerbo WPT

#### Babolat
- Babolat Technical Viper 2024
- Babolat Counter Viper
- Babolat Air Viper

#### Head
- Head Extreme One
- Head Zephyr Pro
- Head Delta Pro

#### Siux
- Siux Diablo Grafeno
- Siux Fenix
- Siux Optimus Pro

#### Black Crown
- Black Crown Piton Air
- Black Crown Genius
- Black Crown Naked

#### Star Vie
- Star Vie Metheora
- Star Vie Aquila Carbon
- Star Vie Brava Carbon

#### Vibor-A
- Vibor-A Yarara World Champion
- Vibor-A Lethal Hybrid
- Vibor-A King Cobra

#### Wilson
- Wilson Bela Pro V2
- Wilson Ultra Pro
- Wilson Carbon Force Pro

---

### ZAPATILLAS (categoria: `zapatillas`)
Busca 8-10 productos variados.

- Asics Gel-Padel Pro 5
- Asics Gel-Resolution 9 Clay
- Joma T.Slam 2404
- Head Sprint Pro 3.5
- Wilson Rush Pro 4.0
- Mizuno Wave Exceed Tour 5
- Bullpadel Vertex Grip 23
- Adidas Barricade 13
- Babolat Jet Mach 3

---

### ROPA (categoria: `ropa`)
Busca 6-8 productos. Mezcla hombre y mujer.

#### Hombre
- Camiseta Bullpadel Igara
- Polo Adidas Club Padel
- Short Head Club Basic
- Pantalon corto Joma Master

#### Mujer
- Vestido Bullpadel Naira
- Falda Head Club Basic
- Camiseta Babolat Play
- Short Adidas Club

---

### PELOTAS (categoria: `pelotas`)
Busca 4-6 productos.

- Head Padel Pro (bote 3)
- Head Padel Pro S (bote 3)
- Bullpadel Premium Pro (bote 3)
- Wilson Padel X3 (bote 3)
- Nox Pro Titanium (bote 3)
- Head Padel Pro (pack 24 - caja)

---

### ACCESORIOS (categoria: `accesorios`)
Busca 6-8 productos.

- Wilson Pro Overgrip (pack 3 o 12)
- Bullpadel Overgrip Comfort (pack)
- Protector de pala Head
- Protector de pala Bullpadel
- Munequera Adidas Tennis
- Munequera Head Wristband
- Calcetines Head Performance
- Pascal Box Presurizador

---

### BOLSAS Y PALETEROS (categoria: `bolsas-paleteros`)
Busca 4-6 productos.

- Paletero Bullpadel BPP-23014
- Mochila Adidas Multigame
- Paletero Nox Pro Series
- Bolsa Head Tour Team Padel
- Mochila Babolat Essential
- Paletero Wilson Team Padel

---

## Tips para una buena importacion

1. **Productos con oferta**: Busca productos que tengan descuento visible en Amazon para que aparezcan en la pagina de ofertas

2. **Titulos consistentes**: Al importar, el titulo viene de Amazon. Si quieres, puedes editarlo despues para optimizar SEO

3. **Categorias**: Asigna SIEMPRE la categoria correcta:
   - Productos de marca Adidas -> categoria `palas` (no crear categoria separada)
   - Las paginas de marcas filtran por `search="nombre_marca"` en el titulo

4. **Imagenes**: El plugin descarga las imagenes localmente automaticamente

5. **Verificar datos**: Revisa que el precio, rating y reviews se hayan extraido correctamente

---

## Cantidad minima sugerida

Para que el sitio se vea completo, sugiero importar al menos:

| Categoria        | Minimo | Ideal            |
| ---------------- | ------ | ---------------- |
| Palas            | 15     | 30 (3 por marca) |
| Zapatillas       | 6      | 10               |
| Ropa             | 6      | 10               |
| Pelotas          | 4      | 6                |
| Accesorios       | 6      | 10               |
| Bolsas/Paleteros | 4      | 6                |
| **TOTAL**        | **41** | **72**           |

---

*Documento creado: Diciembre 2024*
