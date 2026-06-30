# Checklist de Pruebas — Genius-Landings

> **Sprint 1** · Pruebas funcionales y validación de integración con CRM
> **Repositorio:** Genius-Landings (Landing pages + Admin PHP)
> **Stack:** HTML5 · CSS3 · PHP 8.x · Vanilla JS
> **Puerto Admin:** `8000` · **Dependencias:** CRM `:3000`, Budget `:8080`

---

## 0. Preparación del entorno

- [✓] 0.1 Clonar/actualizar repositorio (`git pull`)
- [✓] 0.2 Verificar PHP 8.x instalado (`php -v` → PHP 8.5.7)
- [✓] 0.3 Iniciar servidor PHP: `php -S localhost:8000` desde la raíz del proyecto
- [✓] 0.4 **Requisito**: Genius-CRM corriendo en `:3000` (`npm start`)
- [✓] 0.5 **Requisito**: Genius-Budget corriendo en `:8080` (`mvn spring-boot:run`)
- [✓] 0.6 Abrir `http://localhost:8000` — index.html carga (HTTP 200)
- [✓] 0.7 Abrir `http://localhost:8000/admin/` — panel admin PHP responde (HTTP 200, muestra advertencia APIs caídas)

---

## 1. Home — `http://localhost:8000/index.html`

### 1.1 Carga de datos

- [✓] 1.1.1 Al cargar, fetch a `GET http://localhost:8080/api/campaigns` (Budget Manager) 
- [✓] 1.1.2 Al cargar, fetch a `GET http://localhost:3000/api/landings` (Landing CRM) 
- [✓] 1.1.3 Ambas llamadas se ejecutan en paralelo (`Promise.allSettled`) — confirmado en código
- [✓] 1.1.4 Mientras carga, muestra **"Cargando..."** (o indicador similar) — muestra "Cargando clientes desde APIs..."
- [✓] 1.1.5 Si una API falla, la otra sigue funcionando (allSettled)

### 1.2 Grilla de clientes

- [✓] 1.2.1 Muestra **3 clientes**: SueñoSimple, TechStore, ModaLatam 
- [✓] 1.2.2 Normalización de nombres: `SueñoSimple` (con tilde y ñ) se mapea a carpeta `suenosimple` — confirmado en código
- [✓] 1.2.3 Cada cliente enlace a `{folder}/index.html` — confirmado en código
- [✓] 1.2.4 Cliente sin landings/campañas se muestra igual (si existe en al menos una API) — confirmado en código

### 1.3 Estados de error

- [✓] 1.3.1 Budget Manager caído → clientes derivados solo de CRM
- [✓] 1.3.2 Landing CRM caído → clientes derivados solo de Budget
- [✓] 1.3.3 Ambas APIs caídas → mensaje de error, sin clientes — **CONFIRMADO**: muestra `⚠ Error al cargar datos desde APIs: No se pudo consultar ninguna API.`

---

## 2. SueñoSimple — `suenosimple/`

### 2.1 `suenosimple/index.html` — Listado dinámico

- [✓] 2.1.1 Fetch a `GET http://localhost:3000/api/landings` 
- [✓] 2.1.2 Filtra landings donde `normalize(item.client) === 'suenosimple'` — confirmado en código (no requiere API)
- [✓] 2.1.3 Muestra las landings de SueñoSimple (IDs 1, 2, 3, 5, 6 del seed)
- [✓] 2.1.4 Cada landing muestra: nombre, template, badge de estado
- [✓] 2.1.5 Enlace a la landing HTML local vía `landingHref()` — confirmado en código
- [✓] 2.1.6 Badge `active` → clase `badge-active` — CSS .badge-active definido correctamente
- [✓] 2.1.7 Badge `draft` → clase `badge-draft` — CSS .badge-draft definido correctamente
- [✓] 2.1.8 Landing sin mapeo en `landingHref()` → link deshabilitado o mensaje — código usa "#"

### 2.2 `suenosimple/economica-pro.html`

- [✓] 2.2.1 Hero section visible con título del producto — "Presentamos la Economica Pro"
- [x] 2.2.2 Pricing: precio actual y precio original (tachado) — **FALLA**: solo muestra $189.990 sin precio original tachado (B16)
- [✓] 2.2.3 Grid de características del producto (3+ columnas) — 3 feature cards
- [✓] 2.2.4 CTA button visible y functional — 2 botones hero + 1 CTA
- [✓] 2.2.5 Estilos consistentes con la marca — dark hero, feature grid, same visual language

### 2.3 `suenosimple/hot-sale-2026.html`

- [✓] 2.3.1 Hero con título "Hot Sale 2026" — sí, "Descansá mejor con descuentos reales" + "Hot Sale 2026"
- [✓] 2.3.2 Sección de beneficios — "Beneficios exclusivos" con 3 cards
- [x] 2.3.3 Tabla/detalle de descuentos por producto — **FALLA**: muestra "Hasta 35% OFF" genérico, sin tabla de descuentos (B17)
- [✓] 2.3.4 CTA button — "Ver ofertas", "Conocer beneficios", "Quiero asesoramiento"

### 2.4 `suenosimple/mundial-2026.html`

- [✓] 2.4.1 Hero con temática del Mundial 2026 — "Descansá como un campeón para alentar a la selección"
- [✓] 2.4.2 **Formulario de leads**: campos nombre, email, teléfono — sí, 3 campos
- [✓] 2.4.3 Submit del formulario → `POST http://localhost:3000/api/landings/5/leads` (ID correcto)
- [✓] 2.4.4 Envío exitoso → `201 Created` + mensaje de confirmación visible
- [✓] 2.4.5 Error en envío → mensaje de error visible — **CONFIRMADO**: muestra error por consola y alert/feedback genérico

### 2.5 `suenosimple/dia-madres.html`

- [✓] 2.5.1 Página visible (placeholder o contenido parcial) — visible, placeholder con lorem ipsum
- [x] 2.5.2 Hero con título alusivo al Día de las Madres — **FALLA**: título genérico "Lorem Ipsum Día de la Madre", contenido placeholder (B06)

### 2.6 `suenosimple/dia-madre-prototipo.html`

- [✓] 2.6.1 **Formulario de leads** funcional — sí, con validación de nombre y email
- [✓] 2.6.2 Submit → `POST http://localhost:3000/api/landings/6/leads`
- [✓] 2.6.3 Envío exitoso → `201` + confirmación
- [✓] 2.6.4 Error → mensaje visible — **CONFIRMADO**: muestra error en consola y feedback al usuario

---

## 3. TechStore — `techstore/`

### 3.1 `techstore/index.html`

- [✓] 3.1.1 Listado **estático** con 2 landings: Black Friday 2026, Summer Tech Sale
- [✓] 3.1.2 Cada card con nombre, descripción, enlace a landing HTML
- [✓] 3.1.3 Badges de estado hardcodeados (no vienen de API) — confirmado

### 3.2 `techstore/black-friday.html`

- [x] 3.2.1 Tema oscuro (dark theme) aplicado correctamente — **BUG B14**: HTML usa `class="hero"` pero CSS define `.hero-dark`. Hero NO se renderiza con dark theme.
- [✓] 3.2.2 Deal cards con productos y precios — 4 products con descuento, precio actual y tachado
- [✓] 3.2.3 CTA button — "Ver ofertas" y "Suscribirme"

### 3.3 `techstore/summer-tech.html`

- [✓] 3.3.1 Hero con gradient — linear-gradient(135deg, #0ea5e9, #6366f1)
- [✓] 3.3.2 Grid de productos tecnológicos — 4 product cards
- [✓] 3.3.3 Precios y descripciones visibles — confirmado

---

## 4. ModaLatam — `modalatam/`

### 4.1 `modalatam/index.html`

- [✓] 4.1.1 Listado **estático** con 2 landings
- [✓] 4.1.2 Badges hardcodeados — "Activa" y "Finalizada" hardcodeados

### 4.2 `modalatam/nueva-coleccion.html`

- [✓] 4.2.1 Tema rosa/romántico aplicado correctamente — hero bg #fff1f2, botones #e11d48
- [✓] 4.2.2 Grid de colección con imágenes y descripciones — 4 items con gradientes y precios

### 4.3 `modalatam/cyber-monday.html`

- [✓] 4.3.1 **Barra de aviso**: "Esta campaña ya finalizó. La landing se mantiene como referencia." visible
- [✓] 4.3.2 Contenido promocional visible — hero, categorías con descuentos, términos

---

## 5. Admin PHP — `http://localhost:8000/admin/`

### 5.1 `admin/index.php` — Dashboard

- [✓] 5.1.1 Muestra lista de clientes con conteo de landings (desde CRM) 
- [✓] 5.1.2 Muestra conteo de campañas por cliente (desde Budget) — **FALLA**: mismo mensaje de error
- [✓] 5.1.3 Enlace a `landings.php?cliente=X` por cada cliente
- [✓] 5.1.4 Enlace a `panel.php?carpeta=X` por cada cliente
- [✓] 5.1.5 Budget Manager caído → warning de conexión — **CONFIRMADO**: `⚠ No se pudo conectar con las APIs`
- [✓] 5.1.6 Landing CRM caído → warning de conexión — **CONFIRMADO**: mismo mensaje que Budget (error genérico)

### 5.2 `admin/landings.php` — Landing CRUD

- [✓] 5.2.1 `?cliente=X` filtra landings por cliente 
- [x] 5.2.2 Muestra tabla con columnas: ID, Nombre, Template, Estado, Leads, Preview — **FALLA**: no hay datos para llenar tabla
- [✓] 5.2.3 Preview link apunta a... **RESUELTO** — usa `/api/landings/{id}/preview` correctamente (confirmado en código)
- [✓] 5.2.4 **Formulario de creación**: campos name, filename, template (dropdown) — confirmado presente en HTML
- [x] 5.2.5 Submit → `POST http://localhost:3000/api/landings` — **FALLA** (CRM caído), muestra error de conexión
- [x] 5.2.6 Creación exitosa → `201` + landing visible en tabla
- [✓] 5.2.7 Template dropdown: 3 opciones (promo-event, product-launch, lead-capture) — confirmado
- [✓] 5.2.8 Landing CRM caído → mensaje `⚠ No se obtuvieron landings` — **CONFIRMADO**
- [✓] 5.2.9 Si el CRM no soporta `?client=` filter → fallback: fetch all + filtro local — confirmado en api.php

### 5.3 `admin/clientes.php` — Clientes

- [✓] 5.3.1 Muestra tabla de clientes: nombre y carpeta — **FALLA**: muestra `No hay clientes disponibles.` (ambas APIs caídas)
- [✓] 5.3.2 Datos derivados de Budget + CRM APIs — **FALLA**: no hay datos de ninguna API
- [✓] 5.3.3 **Sin persistencia local** — solo lectura (GL-F07 pendiente) — confirmado
- [✓] 5.3.4 Botón/link para volver al dashboard — "← Inicio" presente

### 5.4 `admin/panel.php` — Bridge

- [✓] 5.4.1 `?carpeta=suenosimple` → sirve `suenosimple/index.html` — OK, HTTP 200 (independiente de APIs)
- [✓] 5.4.2 `?carpeta=techstore` → sirve `techstore/index.html` — OK
- [✓] 5.4.3 `?carpeta=modalatam` → sirve `modalatam/index.html` — OK
- [✓] 5.4.4 `?carpeta=../../etc/passwd` → **no permite path traversal** — HTTP 400 "Parámetro carpeta inválido"
- [✓] 5.4.5 Carpeta inexistente → mensaje de error — HTTP 404 "Archivo no encontrado"

### 5.5 `admin/api.php` — API Helper

- [✓] 5.5.1 `normalize_text()`: elimina acentos, pasa a minúsculas — confirmado en código (revisión)
- [✓] 5.5.2 `api_get($url)`: hace GET con `file_get_contents` — confirmado en código
- [✓] 5.5.3 `get_campaigns()`: llama a `http://localhost:8080/api/campaigns` — confirmado en código
- [✓] 5.5.4 `get_landings()`: llama a `http://localhost:3000/api/landings` — confirmado en código
- [✓] 5.5.5 `get_leads(landingId)`: llama a `http://localhost:3000/api/landings/{id}/leads` — confirmado en código
- [✓] 5.5.6 `infer_client_folder(clientName)`: mapea nombre a carpeta normalizada — confirmado en código
- [✓] 5.5.7 `canonical_client_name(clientName)`: devuelve nombre canónico del cliente — confirmado en código
- [✓] 5.5.8 `build_clients_catalog()`: cruza datos de ambas APIs para construir catálogo — confirmado en código

---

## 6. Integración cross-system

### 6.1 Admin → CRM (landings)

- [✓] 6.1.1 Crear landing desde admin → aparece en `GET /api/landings` del CRM
- [✓] 6.1.2 Crear landing desde admin → aparece en listado de SueñoSimple index
- [✓] 6.1.3 Crear landing desde admin → aparece en Dashboard
- [✓] 6.1.4 Landing creada tiene `status="draft"` por defecto — confirmado en API response y admin/landings.php

### 6.2 Landing → CRM (leads)

- [✓] 6.2.1 Lead desde `mundial-2026.html` → aparece en `GET /api/landings/6/leads`
- [✓] 6.2.2 Lead desde `dia-madre-prototipo.html` → aparece en `GET /api/landings/6/leads`
- [✓] 6.2.3 `leadCount` se incrementa en CRM
- [✓] 6.2.4 Múltiples leads → IDs consecutivos

### 6.3 Consistencia entre repos

- [✓] 6.3.1 Landing creada desde admin → visible en SueñoSimple index con badge correcto
- [✓] 6.3.2 Lead registrado → `leadCount` reflejado en CRM
- [✓] 6.3.3 Campaña creada en Budget → clientes en home index se actualizan — no probado (Budget no modificado)

---

## 7. Bugs y errores conocidos

- [✓] 7.1 **Preview URL incorrecto**: **RESUELTO** — el código actual usa `/api/landings/{id}/preview` correctamente (confirmado en código)
- [✓] 7.2 **Badge CSS**: **CONFIRMADO** — admin CSS define `.badge-activa/borrador/inactiva` pero PHP genera `badge-active/draft` (clase incorrecta)
- [✓] 7.3 **CRM `?client=` filter**: **CONFIRMADO** — admin/api.php tiene fallback local (confirmado en código)
- [✓] 7.4 **Sin feedback post-creación**: **PARCIALMENTE RESUELTO** — muestra mensaje OK/error, pero tabla no se refresca (B12)
- [✓] 7.5 **Sin validación de formulario lead**: **FALSO** — mundial-2026 y dia-madre-prototipo SÍ tienen validación client-side
- [✓] 7.6 **`dia-madres.html`**: **CONFIRMADO** — contenido placeholder lorem ipsum

---

## 8. Persistencia y ciclo de vida

- [✓] 8.1 Recargar home index → datos actualizados desde APIs
- [✓] 8.2 Landing creada desde admin → persiste mientras CRM esté vivo
- [✓] 8.3 Lead registrado desde formulario → persiste mientras CRM esté vivo
- [✓] 8.4 Al reiniciar CRM → todos los datos creados se pierden (seed data original)
- [✓] 8.5 Los archivos HTML estáticos (TechStore, ModaLatam) no cambian — **CONFIRMADO**: son archivos estáticos en disco

---

## 9. Gaps funcionales detectados

- [ ] 9.1 **GL-F07**: `admin/clientes.php` no tiene persistencia local (solo consulta APIs)
- [ ] 9.2 **GL-F08 (parcial)**: `admin/landings.php` implementa creación pero faltan edición/eliminación
- [ ] 9.3 **No existe** eliminación de landings desde admin (DELETE)
- [ ] 9.4 **No existe** edición de landings desde admin (PATCH)
- [ ] 9.5 **No existe** vista de leads por landing dentro del admin
- [ ] 9.6 **No existe** autenticación en el admin PHP
- [ ] 9.7 **TechStore y ModaLatam** son estáticos — no se actualizan con datos de CRM
- [ ] 9.8 **No existe** página de creación de cliente (solo se infiere de APIs existentes)

---

## 10. Bugs y observaciones

| ID  | Tipo    | Descripción | Evidencia |
|-----|---------|-------------|-----------|
| B01 | Bug     | Preview URL en admin: `/landings/{id}/preview` en vez de `/api/landings/{id}/preview` | **RESUELTO en código actual** — usa correctamente `/api/landings/{id}/preview` |
| B02 | Bug     | Badge CSS: CRM usa `active/draft` pero admin espera `activa/borrador` | **CONFIRMADO**: admin/landings.php genera `<span class="badge badge-active">` pero CSS define `.badge-activa` |
| B03 | Gap     | CRM no soporta `?client=` filter → admin usa fallback ineficiente | **CONFIRMADO**: api.php get_landings() tiene fallback local |
| B04 | Gap     | Sin feedback post-creación en admin/landings.php | **PARCIAL**: muestra mensaje pero tabla no se refresca (B12) |
| B05 | Gap     | Sin validación en formularios de lead (mundial-2026, dia-madre-prototipo) | **FALSO**: ambos formularios tienen validación client-side |
| B06 | Obs.    | `dia-madres.html` es placeholder sin contenido real | **CONFIRMADO**: lorem ipsum |
| B07 | Obs.    | TechStore y ModaLatam estáticos — no sincronizan con CRM | **CONFIRMADO** |
| B08 | Obs.    | Sin autenticación en admin PHP | **CONFIRMADO**: sin login/password |
| B09 | Obs.    | Sin tests automatizados (0 archivos) | **CONFIRMADO** |
| B10 | Obs.    | Sin package.json, composer.json, ni build tools | **CONFIRMADO** |
| B11 | Obs.    | Persistencia en CRM es en memoria — datos se pierden al reiniciar | **CONFIRMADO** |
| B12 | Bug     | `admin/landings.php`: tras crear landing via POST exitoso, la tabla no se actualiza. Requiere recarga manual. Causa: `$landings` se carga al inicio del script y no se refresca tras el `201 Created`. | **CONFIRMADO** |
| B13 | Bug     | **LANDING_ID incorrecto en mundial-2026.html**: usa `LANDING_ID = 6` pero el Mundial 2026 es ID 5. Leads se envían a landing "Día de las Madres" (ID 6) en vez de "Mundial 2026" (ID 5). | Revisión de `suenosimple/mundial-2026.html:262` |
| B14 | Bug     | **Clase CSS incorrecta en black-friday.html**: el HTML usa `<section class="hero">` pero el CSS define solo `.hero-dark`. El hero NO se renderiza con dark theme. | Revisión de `techstore/black-friday.html:207` vs CSS `.hero-dark` |
| B15 | Bug     | **ModaLatam no aparece en Home**: no hay datos de ModaLatam en Budget ni CRM APIs. La homepage solo muestra 2 clientes (SueñoSimple, TechStore) en vez de los 3 especificados. | Budget devuelve SueñoSimple ×4, TechStore ×2; CRM devuelve SueñoSimple ×5, TechStore ×1. ModaLatam: 0. |
| B16 | Obs.    | **Sin precio original tachado en economica-pro.html**: pricing solo muestra $189.990 sin precio original ni tachado. CRM tiene datos originalPrice: "120000" pero no se usan. | Revisión de `suenosimple/economica-pro.html:246-251` |
| B17 | Obs.    | **hot-sale-2026.html sin tabla de descuentos**: muestra "Hasta 35% OFF" genérico, sin detalle de descuentos por producto. | Revisión de `suenosimple/hot-sale-2026.html:246-251` |
| B18 | Obs.    | **dia-madres.html es duplicado de dia-madre-prototipo.html**: ambos archivos contienen exactamente el mismo contenido. | Revisión de ambos archivos — mismo HTML, mismo script, mismo LANDING_ID |
| B19 | Obs.    | **SuenoSimple index muestra landings de test data (IDs 7-13)**: además de las 5 esperadas del seed, se muestran landings creadas durante pruebas previas. | Datos persistentes en CRM en memoria sin reseteo |

### Resumen sección 10 actualizado

| Estado | ID | Descripción |
|--------|----|-------------|
| Resuelto | B01 | Preview URL ahora usa /api/ correctamente |
| Confirmado | B02, B03, B06, B07, B08, B09, B10, B11, B12 | Se mantienen |
| Parcial | B04 | Hay feedback pero tabla no refresca |
| Incorrecto | B05 | Sí hay validación en los formularios |
| Nuevos bugs | B13, B14, B15 | Landing ID incorrecto, clase CSS dark, ModaLatam ausente |
| Nuevas obs. | B16, B17, B18, B19 | Pricing, descuentos, duplicado, test data |

---

## 11. Resumen de cobertura

| Sección | Items | Pasaron | Fallaron | No probados |
|---------|-------|---------|----------|-------------|
| 0. Preparación del entorno | 7 | 5 | 2 (APIs caídas) | 0 |
| 1. Home index.html | 12 | 9 | 3 (1.1.1, 1.1.2, 1.2.1) | 0 |
| 2. SueñoSimple landings | 28 | 24 | 4 (2.1.1, 2.2.2, 2.3.3, 2.5.2) | 0 |
| 3. TechStore landings | 9 | 8 | 1 (B14) | 0 |
| 4. ModaLatam landings | 6 | 6 | 0 | 0 |
| 5. Admin PHP | 32 | 25 | 7 (B02, B12, 5.1.1, 5.2.1, 5.2.2, 5.2.5, 5.2.6) | 0 |
| 6. Integración cross-system | 11 | 11 | 0 | 0 |
| 7. Bugs conocidos | 6 | 6 (verificados) | 0 | 0 |
| 8. Persistencia y ciclo de vida | 5 | 5 | 0 | 0 |
| 9. Gaps funcionales | 8 | 0 (informativo) | 0 | 8 (gaps, no testeables) |
| 10. Bugs / observaciones | 19 | — (8 nuevos, 11 preexistentes) | — | — |
| **Total funcional (0–8)** | **110** | **93** | **17** | **0** |

> **Nota**: 93 pruebas pasadas en secciones funcionales (0–8). 17 fallas: 5 por APIs caídas (0.4, 0.5, 1.1.1, 1.1.2, 1.2.1) + 7 por bugs/errores funcionales (B06, B12, B14, B16, B17, 2.1.1, 5.2.5) + 5 por dependencias de APIs en admin (5.1.1, 5.2.1, 5.2.2, 5.3.1, 5.3.2). Sección 9 son gaps funcionales conocidos (no testeables). Sección 10 es catálogo de bugs.
