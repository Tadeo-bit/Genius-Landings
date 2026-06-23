# Estado del repositorio — Genius-Landings

> Reporte de estado generado el 2026-06-21. Para la documentación funcional y de uso, ver [README.md](README.md).

## Resumen

Repositorio que centraliza las landing pages estáticas (HTML/CSS) de cada cliente, más un panel de administración en PHP conectado a las APIs internas del ecosistema Genius.

| Campo | Valor |
|---|---|
| Repositorio | `Tadeo-bit/Genius-Landings` |
| Stack | HTML + CSS puro · Panel admin en PHP |
| Persistencia | Archivos HTML estáticos (admin sin persistencia aún) |
| Servidor | Estático para landings · PHP para `admin/` |

## Estado de Git

| Campo | Valor |
|---|---|
| Rama actual | `main` |
| Rama por defecto | `main` |
| Cambios sin commitear | Ninguno (working tree limpio) |
| Sincronización | Al día con `origin/main` |
| Último commit | `3628344 — Add files via upload` |

## Estado funcional

**Implementado y operativo:**

- Panel de navegación estático (`index.html`) con listado de clientes y estilos compartidos (`css/styles.css`).
- 5 landing pages publicadas en 3 clientes:
  - **SueñoSimple** (colchones): `economica-pro.html`.
  - **TechStore** (electrónica): `black-friday.html`, `summer-tech.html`.
  - **ModaLatam** (moda): `nueva-coleccion.html`, `cyber-monday.html`.
- Panel admin PHP (`admin/`): dashboard (`index.php`), helper de APIs (`api.php`), gestión de clientes (`clientes.php`) y registro de landings (`landings.php`).

**Pendientes:**

- `GL-F07` — persistencia en la gestión de clientes (`admin/clientes.php`).
- `GL-F08` — persistencia en el registro/listado de landings (`admin/landings.php`).
- Las landings nuevas se agregan manualmente editando los archivos HTML del cliente.

## Dependencias con otros repos

> El panel `admin/` requiere un servidor PHP. Si alguna API no está corriendo, muestra una advertencia pero sigue cargando.

- **Genius-Budget** (Budget Manager) en `localhost:8080` — campañas por cliente.
- **Genius-CRM-main** (Landing CRM) en `localhost:3000` — landings y leads.

## Cómo ejecutar

```bash
# Landings estáticas: abrir index.html directamente en el navegador.

# Panel admin (requiere PHP):
php -S localhost:8000 -t admin
# Abrir: http://localhost:8000
```

## Diagnostico de errores actuales (2026-06-23)

### Problemas detectados

- El error `npm ENOENT package.json` en este repo es esperado: **Genius-Landings no usa Node/npm**.
- El admin intenta filtrar campañas por cliente con `?client=...`, pero Budget Manager no lo soportaba hasta la correccion BM-F02 (dependencia externa).
- El admin intenta filtrar landings por cliente con `?client=...`, pero Landing CRM aun no implementa ese filtro.
- En `admin/landings.php`, el link de preview apunta a `/landings/{id}/preview`; la ruta correcta del CRM es `/api/landings/{id}/preview`.
- La clase CSS dinamica del estado (`badge-{status}`) no coincide con las clases definidas (`badge-activa`, `badge-borrador`, `badge-inactiva`), por lo que puede no verse el estilo esperado.

### Impacto

- Puede mostrarse tabla vacia o datos inconsistentes al gestionar landings por cliente.
- El enlace de preview puede fallar con 404.
- Los badges de estado pueden renderizarse sin color correcto.

### Recomendacion de remediacion

- En Landings: corregir URL de preview y mapear estados del CRM (`active/draft/inactive`) a clases CSS validas.
- En Landing CRM: implementar `GET /api/landings?client={nombre}` para que el filtro del admin funcione como esta disenado.
- Mantener la ejecucion del repo con servidor PHP (`php -S localhost:8000 -t admin`) y no usar `npm install`.
