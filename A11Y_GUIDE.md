# ♿ Guía de Accesibilidad (a11y)

## WCAG 2.1 AA Compliance Checklist

### 1. **ARIA Labels y Roles**

#### Botones
```vue
<!-- ❌ Malo -->
<button>🔍</button>

<!-- ✅ Bueno -->
<button aria-label="Buscar" title="Buscar (Cmd+K)">🔍</button>
```

#### Links
```vue
<!-- ❌ Malo -->
<a href="/tasks">Click aquí</a>

<!-- ✅ Bueno -->
<a href="/tasks" aria-label="Ver todas las tareas">Ver todas las tareas</a>
```

#### Inputs
```vue
<!-- ❌ Malo -->
<input type="text" placeholder="Buscar...">

<!-- ✅ Bueno -->
<label for="search">Buscar</label>
<input id="search" type="text" aria-label="Buscar tareas" placeholder="Ej: urgent">
```

#### Iconos
```vue
<!-- ❌ Malo -->
<svg class="icon">...</svg>

<!-- ✅ Bueno -->
<svg class="icon" aria-hidden="true" role="presentation">...</svg>
<!-- Si el icono comunica info -->
<svg aria-label="Tarea completada">...</svg>
```

### 2. **Keyboard Navigation**

#### Todos los clickables deben ser accesibles por teclado
```vue
<!-- ❌ Malo -->
<div @click="doSomething">Clickeable</div>

<!-- ✅ Bueno -->
<button @click="doSomething">Acción</button>
<!-- O si es div -->
<div role="button" tabindex="0" @click="doSomething" @keydown.enter="doSomething">
    Acción
</div>
```

#### Focus Management
```javascript
// Auto-focus en modals
<script setup>
    const inputRef = ref(null)
    onMounted(() => inputRef.value?.focus())
</script>

<template>
    <input ref="inputRef" />
</template>
```

#### Tab Order
```vue
<!-- Restaurar orden lógico -->
<button tabindex="1">Primero</button>
<button tabindex="2">Segundo</button>
<!-- Mejor: dejar que fluya naturalmente -->
<button>Primero</button>
<button>Segundo</button>
```

### 3. **Focus Visible**

Agregar en app.css:
```css
/* Focus visible para keyboard navigation */
button:focus-visible,
a:focus-visible,
input:focus-visible {
    @apply outline-2 outline-offset-2 outline-blue-500;
}

/* Dark mode */
.dark button:focus-visible,
.dark a:focus-visible,
.dark input:focus-visible {
    @apply outline-blue-400;
}
```

### 4. **Color Contrast**

WCAG AA requiere mínimo 4.5:1 para texto

Verificar con: https://contrast-ratio.com/

```css
/* ❌ Malo - bajo contraste -->
.text-gray-600 { @apply text-gray-500; } /* 3.5:1 */

/* ✅ Bueno -->
.text-gray-600 { @apply text-gray-700; } /* 5.5:1 */
```

Usar colores oscuros en texto:
- Texto normal: `text-gray-900` (dark: `text-gray-50`)
- Texto secundario: `text-gray-700` (dark: `text-gray-300`)
- Evitar: `text-gray-500` para texto principal

### 5. **Heading Hierarchy**

```vue
<!-- ❌ Malo -->
<h1>Dashboard</h1>
<h3>Tareas</h3>

<!-- ✅ Bueno -->
<h1>Dashboard</h1>
<h2>Tareas</h2>
<h3>Tareas completadas</h3>
```

### 6. **Form Accessibility**

```vue
<div class="form-group">
    <!-- Asociar label con input -->
    <label for="taskTitle">Título de tarea</label>
    <input id="taskTitle" type="text" required aria-required="true">
    
    <!-- Error messages -->
    <span id="error-message" class="error" role="alert">
        Este campo es requerido
    </span>
    <input aria-describedby="error-message" />
</div>
```

### 7. **Images & Icons**

```vue
<!-- Imagen decorativa -->
<img src="..." alt="" aria-hidden="true">

<!-- Imagen informativa -->
<img src="..." alt="Gráfico de productividad últimos 30 días">

<!-- Icono decorativo -->
<svg aria-hidden="true">...</svg>

<!-- Icono informativo -->
<svg aria-label="Error">...</svg>
```

### 8. **Skip Links**

Agregar al principio del AppLayout:
```vue
<a href="#main-content" class="sr-only focus:not-sr-only">
    Saltar al contenido principal
</a>

<!-- En main content -->
<main id="main-content">
    <!-- Contenido -->
</main>
```

Agregar en CSS:
```css
.sr-only {
    @apply absolute w-1 h-1 p-0 m-0 overflow-hidden clip-rect(0, 0, 0, 0) whitespace-nowrap border-0;
}

.sr-only:focus-visible {
    @apply not-sr-only;
}
```

### 9. **Dynamic Content**

Para cambios en ARIA:
```vue
<div role="status" aria-live="polite" aria-atomic="true">
    {{ notification }}
</div>

<!-- Para alerts urgentes -->
<div role="alert" aria-live="assertive">
    Error crítico!
</div>
```

### 10. **Modal Accessibility**

```vue
<div role="dialog" aria-modal="true" aria-labelledby="dialog-title">
    <h2 id="dialog-title">Crear tarea</h2>
    <!-- Focus trap: último elemento taba al primero -->
</div>
```

### 11. **Reducir Motion**

Respetar `prefers-reduced-motion`:
```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

### 12. **Language**

```vue
<!-- Especificar idioma -->
<html lang="es">

<!-- Cambios de idioma -->
<span lang="en">English</span>
```

---

## Checklist de Implementación

### Priority 1 (Must Have)
- [ ] Botones tienen aria-label
- [ ] Links descriptivos (no "click aquí")
- [ ] Contraste de color 4.5:1 para texto
- [ ] Heading hierarchy correcta
- [ ] Focus visible en todo keyboard-accessible
- [ ] Inputs con labels asociados

### Priority 2 (Should Have)
- [ ] Aria-live en notificaciones
- [ ] Skip links
- [ ] Reducir motion respetado
- [ ] Semantic HTML (button vs div)
- [ ] Alt text en imágenes

### Priority 3 (Nice to Have)
- [ ] Focus management en modals
- [ ] Aria-describedby en errores
- [ ] Color no es único indicador
- [ ] Zoom mínimo 200%
- [ ] Modo oscuro modo de respeto

---

## Herramientas de Testing

1. **axe DevTools** (Chrome extension)
2. **WAVE** (webim.org/wave)
3. **Lighthouse** (Chrome DevTools)
4. **Screen Reader**: NVDA (Windows), VoiceOver (Mac)

```bash
# Instalar axe-core para automated testing
npm install --save-dev @axe-core/playwright
```

---

## Recursos

- WCAG 2.1: https://www.w3.org/WAI/WCAG21/quickref/
- WebAIM: https://webaim.org/
- MDN: https://developer.mozilla.org/en-US/docs/Web/Accessibility

---

## Tiempo Estimado

- Audit actual: 2h
- Implementación: 6-8h
- Testing: 2h

**Total**: ~10 horas para WCAG 2.1 AA compliance

Empieza con Priority 1, son los cambios que más impacto tienen!
