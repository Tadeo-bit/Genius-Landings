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
| Rama actual | `dev` |
| Rama por defecto | `main` |
| Cambios sin commitear | Ninguno (working tree limpio) |
| Sincronización | Al día con `origin/dev` |
| Último commit | `8eb1ffa — Merge pull request #4 from Tadeo-bit/feature/landing-mundial` |

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

## Seguimiento de cambios pusheados

### 2026-06-24 — Actualizacion de registro

- La documentacion de diagnostico del admin quedo publicada en rama remota:
  - Rama: `docs/landings-admin-diagnosis`
  - Commit: `70adcfb` — `docs(landings): documenta diagnóstico de errores del gestor admin`

### 2026-06-24 — Correcciones de integracion Admin + APIs (rama de trabajo)

- Se sincronizo la rama local con `origin/feature/landing-mundial` usando rebase con autostash.
- Se corrigio un enlace roto en el panel de SueñoSimple:
  - `suenosimple/index.html` ahora apunta a `economica-pro.html`.
- Se elimino logica duplicada de submit/validacion en `suenosimple/mundial-2026.html` para evitar doble manejo del formulario y permitir el flujo real hacia CRM.
- Se robustecio la comparacion de clientes en el admin con normalizacion de texto (acentos, `ñ`, simbolos residuales):
  - `admin/api.php`
  - `admin/index.php`
- Se implemento filtro local defensivo en `admin/landings.php` para que la tabla muestre solo landings del cliente seleccionado, incluso si la API devuelve un listado amplio.
- Se resolvio el problema de navegacion del boton **Ver panel** cuando se ejecuta PHP con docroot en `admin/`:
  - nuevo archivo `admin/panel.php`
  - `admin/index.php` ahora usa `panel.php?carpeta=...`.

#### Verificaciones realizadas

- `php -l admin/api.php` ✅
- `php -l admin/index.php` ✅
- `php -l admin/landings.php` ✅
- `php -l admin/panel.php` ✅
- Validacion HTTP en admin:
  - `landings.php?cliente=SueñoSimple` muestra solo landings de SueñoSimple.
  - `panel.php?carpeta=suenosimple` responde 200 y carga el panel correcto.

---

### 2026-06-25 — Correcciones finales del admin PHP (commit `bfe4fc9`)

**Cambios realizados**

- `admin/landings.php`: botón Preview corregido a `/api/landings/{id}/preview`; navegación de retorno cambiada a `← Inicio → index.php`.
- `admin/clientes.php`: navegación de retorno cambiada a `← Inicio → index.php`.
- `admin/panel.php`: reescritura completa. Acepta `?carpeta=` y `?file=`. Reescribe rutas de CSS vía `assets.php`. Inyecta `MutationObserver` para parchear links `.html` generados asincrónamente.
- `admin/assets.php`: nuevo archivo. Sirve archivos estáticos fuera del docroot (`css/styles.css`) con validación de path traversal.

**Verificaciones**

- `php -l` sobre todos los archivos PHP: sin errores.
- Preview de landing abre URL correcta en el CRM.
- Panel de cliente carga con CSS correcto y links de landing funcionales.

---

### 2026-06-25/26 — `suenosimple/mundial-2026.html` — Integración CRM + WhatsApp (PR #4)

**Cambios mergeados desde `feature/landing-mundial` (commit `22f515d`, PR #4)**

- Integración asíncrona con la API del CRM para registro de leads.
- Mapeo de campos del formulario al endpoint `POST /api/landings/{id}/leads`.
- Lógica de redirección a WhatsApp tras registro exitoso.
- 110 inserciones / 65 eliminaciones en `suenosimple/mundial-2026.html`.

**Estado Git**

- Rama `dev` local sincronizada con `origin/dev` (fast-forward a commit `8eb1ffa`).
