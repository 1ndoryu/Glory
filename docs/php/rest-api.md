# REST API

Glory extiende la REST API de WordPress con endpoints propios bajo `/glory/v1/`.

## Endpoints

### Images

| Metodo | Endpoint | Descripcion | Tipo respuesta |
|--------|----------|-------------|----------------|
| GET | `/glory/v1/images` | Lista todas las imagenes | `ImageListResponse` |
| GET | `/glory/v1/images/url?alias=...` | URL de imagen por alias | `ImageUrlResponse` |
| GET | `/glory/v1/images/random` | Imagen aleatoria | `RandomImageResponse` |
| GET | `/glory/v1/images/aliases` | Todos los alias | `ImageAliasesResponse` |

### Page Blocks

| Metodo | Endpoint | Descripcion | Tipo respuesta |
|--------|----------|-------------|----------------|
| GET | `/glory/v1/page-blocks/{id}` | Bloques de una pagina | `PageBlocksResponse` |
| POST | `/glory/v1/page-blocks/{id}` | Guardar bloques | `PageBlocksResponse` |

### Newsletter

| Metodo | Endpoint | Descripcion | Tipo respuesta |
|--------|----------|-------------|----------------|
| POST | `/glory/v1/newsletter` | Suscribir email | `NewsletterSubscribeResponse` |

### MCP (Model Context Protocol)

| Metodo | Endpoint | Descripcion | Tipo respuesta |
|--------|----------|-------------|----------------|
| GET | `/glory/v1/mcp/token` | Obtener token | `MCPTokenResponse` |
| POST | `/glory/v1/mcp/token` | Crear token | `MCPTokenResponse` |
| DELETE | `/glory/v1/mcp/token` | Revocar token | — |
| GET | `/glory/v1/mcp/config` | Configuracion MCP | `MCPConfigResponse` |

## Autenticacion

Los endpoints usan el nonce de WordPress para autenticacion:

```
X-WP-Nonce: {nonce}
```

El hook `useWordPressApi` incluye el nonce automaticamente.

## Controllers

| Controller | Endpoints |
|------------|-----------|
| `ImagesController` | `/glory/v1/images/*` |
| `PageBlocksController` | `/glory/v1/page-blocks/*` |
| `NewsletterController` | `/glory/v1/newsletter` |
| `MCPController` | `/glory/v1/mcp/*` |

## Consumir desde React

```tsx
import { useWordPressApi } from '@/hooks';
import type { ImageListResponse } from '@/types';

const { data } = useWordPressApi<ImageListResponse>('/glory/v1/images');
```

## Respuesta estandar

Todas las respuestas siguen el patron:

```typescript
interface GloryApiResponse<T = unknown> {
    success: boolean;
    data?: T;
    message?: string;
    error?: string;
    code?: string;
}
```
