# ♿ A11y Implementation Progress

## ✅ COMPLETADO

### Global Styles (app.css)
- ✅ Focus-visible outline para keyboard navigation
- ✅ Dark mode focus colors
- ✅ Smooth scroll behavior
- ✅ Transiciones suaves en todos los elementos

### AppLayout.vue
- ✅ Mobile responsive (sidebar collapse, backdrop, hamburger menu)
- ✅ ARIA labels en botones críticos:
  - "Captura rápida (nueva entrada o tarea)"
  - "Abrir perfil"
  - "Cerrar sesión"
  - "Abrir menú"
  - "Buscar"
  - "Toggle Dark Mode"
- ✅ aria-hidden en SVG icons decorativos
- ✅ Focus-visible classes en botones

### Dashboard.vue
- ✅ Responsive grids (1 → 2 → 4 columnas)
- ✅ Mobile-first design

### Kanban.vue
- ✅ Scroll horizontal en mobile
- ✅ Responsive column widths

---

## ⏳ POR IMPLEMENTAR (High Priority)

### 1. Inputs & Forms - ARIA Labels
**Archivos:** 
- Entries/Form.vue
- Tasks/Form.vue
- Components en modals

**Cambios:**
```vue
<!-- Antes -->
<input type="text" placeholder="Buscar...">

<!-- Después -->
<label for="search" class="sr-only">Buscar tareas</label>
<input id="search" type="text" aria-label="Buscar tareas" placeholder="Buscar...">
```

### 2. Link Descriptive Text
**Archivos:** Todos los componentes con Links

**Cambios:**
```vue
<!-- ❌ Malo -->
<Link href="/tasks">Ver</Link>

<!-- ✅ Bueno -->
<Link href="/tasks" aria-label="Ver todas las tareas">Ver todas →</Link>
```

### 3. Color Contrast Review
**Revisar:**
- ✅ Text gray-700 en bg-white (WCAG AA)
- ⏳ Text gray-600 → cambiar a gray-700 en algunos lugares
- ✅ Dark mode contrasts

**Priority:** Buscar `text-gray-500` en content text (no es accessible)

### 4. Heading Hierarchy (H1→H2→H3)
**Revisar cada página:**
- Dashboard.vue: H1 "Buen trabajo", H2 "Tareas pendientes"
- Tasks/Index.vue: H1 "Tareas"
- Entries/Index.vue: H1 "Entradas"

### 5. Skip Link
**Agregar al AppLayout:**
```vue
<a href="#main-content" class="sr-only focus:not-sr-only">
    Saltar al contenido principal
</a>

<main id="main-content">
    <slot />
</main>
```

### 6. Icon-Only Buttons ARIA
**Patrón:**
```vue
<!-- Para iconos con funcionalidad -->
<button aria-label="Eliminar tarea">
    <svg aria-hidden="true">...</svg>
</button>
```

### 7. Modals & Dialogs
**Pattern para modals:**
```vue
<div role="dialog" aria-modal="true" aria-labelledby="dialog-title">
    <h2 id="dialog-title">Título del modal</h2>
    <!-- Focus trap -->
</div>
```

### 8. Live Regions (aria-live)
**Para notificaciones:**
```vue
<div role="status" aria-live="polite" aria-atomic="true">
    {{ notification }}
</div>
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Dashboard.vue
- [ ] Input búsqueda: agregar label+aria-label
- [ ] Botones: todas con aria-label
- [ ] Links "Ver todas →": aria-label descriptivo

### Tasks/Index.vue
- [ ] Inputs filtro: labels
- [ ] Toggle list/kanban: aria-label
- [ ] Botón "Nueva tarea": aria-label

### Tasks/Kanban.vue
- [ ] Inputs filtro: labels
- [ ] Botón "Nueva tarea": aria-label
- [ ] Botón "Eliminar": aria-label
- [ ] Drag-drop: aria-describedby

### Entries/Index.vue & Form.vue
- [ ] Input filtro: labels
- [ ] Input content: aria-describedby para ayuda
- [ ] Botón "Nueva entrada": aria-label

### Modals & Quick Actions
- [ ] Quick capture modal: role="dialog", aria-modal
- [ ] Form: aria-required en required fields
- [ ] Error messages: aria-describedby

### Color Contrast
- [ ] Buscar `text-gray-500` en texto principal → cambiar a `text-gray-700`
- [ ] Buscar `text-gray-600` en texto grande → cambiar a `text-gray-700`
- [ ] Verify 4.5:1 contrast en todos los colores

---

## 🎯 QUICK IMPLEMENTATION GUIDE

### Para cada componente:

**1. Inputs:**
```vue
<label for="task-title">Título</label>
<input id="task-title" type="text" aria-label="Título de la tarea" aria-required="true">
```

**2. Botones:**
```vue
<button aria-label="Descripción clara de la acción">
    <svg aria-hidden="true">...</svg>
</button>
```

**3. Links:**
```vue
<Link href="/path" aria-label="Descripción clara del destino">
    Texto del link →
</Link>
```

**4. Color Fix:**
```css
/* Buscar y reemplazar en componentes -->
.text-gray-500 en content → .text-gray-700
```

---

## ⏱️ TIEMPO ESTIMADO

- Input + ARIA labels: 2-3h
- Links descriptive: 1-2h
- Color contrast review & fix: 1h
- Modals & aria-live: 1-2h
- Testing & review: 1h

**Total: 6-9h para WCAG 2.1 AA full compliance**

---

## 🧪 TESTING REMINDERS

1. **Keyboard Navigation:**
   - Tab through every page
   - All buttons/links reachable
   - Focus visible obvious

2. **Screen Reader (NVDA/VoiceOver):**
   - Test with mouse OFF
   - Verify aria-labels readable
   - Check heading structure

3. **Color Contrast:**
   - Use axe DevTools Chrome extension
   - Check all text > 14px has 4.5:1
   - Use: https://contrast-ratio.com/

4. **Zoom:**
   - Test at 200% zoom
   - No text cutoff
   - All interactive elements accessible

---

## 🚀 NEXT STEPS

Apply the checklist above in this order:

1. **High Impact** (1-2h each):
   - Inputs with labels
   - Botones con aria-label
   - Color contrast fixes

2. **Medium Impact** (1h):
   - Links descriptive
   - Skip link

3. **Nice to Have** (2-3h):
   - Modals role/aria
   - aria-live regions
   - Focus management

Estimated: **6-8h total to WCAG AA**
