# 📱 Guía de Mobile Responsiveness

## Quick Checklist de Mejoras

### 1. **Sidebar Colapsable Automático**
En AppLayout.vue, agregar:
```javascript
// En setup script
const isMobile = ref(window.innerWidth < 768)

onMounted(() => {
    const handleResize = () => {
        isMobile.value = window.innerWidth < 768
        if (isMobile.value) sidebarOpen.value = false
    }
    window.addEventListener('resize', handleResize)
    onUnmounted(() => window.removeEventListener('resize', handleResize))
})
```

### 2. **Mobile Backdrop para Sidebar**
Agregar overlay oscuro al cerrar sidebar en mobile:
```vue
<!-- Agregar después del sidebar -->
<div v-if="sidebarOpen && isMobile"
    @click="sidebarOpen = false"
    class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"></div>
```

### 3. **Botón Toggle Sidebar en Header**
Agregar hamburger menu visible solo en mobile:
```vue
<!-- En el header derecho, agregar: -->
<button @click="sidebarOpen = !sidebarOpen"
    class="p-2 text-gray-500 md:hidden"
    title="Menú">
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button>
```

### 4. **Componentes Responsivos por Página**

#### Dashboard.vue
```
- [ ] Grid: 1 columna en mobile, 2 en tablet, 4 en desktop
  grid-cols-1 md:grid-cols-2 lg:grid-cols-4
  
- [ ] Gráficos: Ocultar en mobile (height ajustable)
  hidden md:block o h-48 md:h-64
  
- [ ] Tareas pendientes: Scroll horizontal en mobile
  overflow-x-auto md:overflow-x-visible
```

#### Tasks/Kanban.vue
```
- [ ] Scroll horizontal en mobile para ver 3 columnas
  overflow-x-auto

- [ ] Cards más pequeñas en mobile
  p-2 md:p-3

- [ ] Botones más grandes (touch-friendly)
  px-3 py-2 md:px-4 md:py-2.5
```

#### Planning/Week.vue
```
- [ ] En mobile: mostrar solo 2 días al mismo tiempo
  grid-cols-1 md:grid-cols-2 lg:grid-cols-4

- [ ] Drag-drop mejorado para touch
  (usar librería vue-touch o similar)
```

#### Entries/Form.vue
```
- [ ] Editor: altura ajustable
  h-48 md:h-64 lg:h-96

- [ ] Botones: Full width en mobile
  w-full md:w-auto
  
- [ ] Toolbar: Vertical en mobile
  flex-col md:flex-row
```

### 5. **Bottom Sheets en Mobile** (en lugar de Modals)

Crear componente BottomSheet.vue:
```vue
<script setup>
defineProps({
    open: Boolean,
})
emit(['update:open'])
</script>

<template>
    <transition name="bottom-sheet">
        <div v-if="open" class="fixed inset-0 z-50">
            <!-- Backdrop -->
            <div @click="$emit('update:open', false)"
                class="absolute inset-0 bg-black bg-opacity-30 md:bg-opacity-50"></div>
            
            <!-- Sheet -->
            <div class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-900 rounded-t-2xl md:rounded-xl shadow-2xl max-h-[90vh] overflow-y-auto md:max-w-2xl md:left-1/2 md:-translate-x-1/2 md:bottom-auto md:top-1/2 md:-translate-y-1/2">
                <!-- Drag handle (visible solo en mobile) -->
                <div class="flex justify-center py-3 md:hidden">
                    <div class="h-1 w-12 rounded-full bg-gray-300"></div>
                </div>
                
                <slot />
            </div>
        </div>
    </transition>
</template>

<style scoped>
.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
    transition: all 0.3s ease;
}

.bottom-sheet-enter-from {
    transform: translateY(100%);
}

.bottom-sheet-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
```

Usar en Entries/Form.vue:
```vue
<BottomSheet :open="isFormOpen" @update:open="isFormOpen = $event">
    <!-- Form content -->
</BottomSheet>
```

### 6. **Buttons Touch-Friendly**

Tamaño mínimo para tocar: 44x44px

Crear clase:
```css
@layer components {
    .btn-touch {
        @apply min-h-[44px] min-w-[44px];
    }
}
```

### 7. **Typography Responsive**

Ajustar tamaños de letra:
```html
<!-- Títulos -->
<h1 class="text-xl md:text-2xl lg:text-3xl">
<h2 class="text-lg md:text-xl">
<p class="text-sm md:text-base">
```

### 8. **Spacing Responsive**

```html
<!-- Padding -->
<div class="p-3 md:p-4 lg:p-6">

<!-- Gaps -->
<div class="gap-2 md:gap-3 lg:gap-4">
```

### 9. **Mejorar Focus States**

Para mejor accesibilidad en touch:
```css
@layer components {
    .focus-ring {
        @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900;
    }
}
```

### 10. **Mobile Menu Actions**

Quick actions en mobile (bottom toolbar):
```vue
<!-- Agregar al bottom en mobile -->
<div class="fixed bottom-0 left-0 right-0 border-t bg-white dark:bg-gray-900 md:hidden flex gap-2 p-2">
    <button @click="createTask" class="flex-1">Nueva tarea</button>
    <button @click="createEntry" class="flex-1">Nueva entrada</button>
</div>

<!-- Adjust main content -->
<div class="pb-16 md:pb-0">
    <!-- Content -->
</div>
```

---

## Testing Checklist

- [ ] iPhone 12/13 (390px)
- [ ] iPhone 14 Pro Max (430px)
- [ ] iPad (768px)
- [ ] iPad Pro (1024px)
- [ ] Desktop (1280px+)

Use DevTools > Device Toggle:
- iPhone SE
- iPhone 12 Pro
- iPad
- Desktop 1920px

---

## Performance Tips Móvil

1. **Lazy Loading**: Cargar imágenes/gráficos bajo demanda
2. **Reduce animations** en mobile
3. **Smaller bundle**: Eliminar CSS no usado
4. **Preload critical assets**
5. **Service Worker** para offline

---

## Prioridad

1. **High**: Sidebar toggle, grid responsive (2h)
2. **Medium**: Bottom sheets, buttons touch-friendly (3h)
3. **Low**: Typography responsive, spacing (1h)

**Tiempo total**: 6h

Aplica estos cambios y prueba en DevTools!
