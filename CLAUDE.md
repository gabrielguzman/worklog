# WorkLog - Sistema de Gestión de Tiempo y Tareas

## 📋 Descripción General
**WorkLog** es una aplicación web para registrar y gestionar el tiempo dedicado a proyectos, tareas y actividades. Combina un registro de tiempo (worklog/timesheet) con gestión de tareas kanban, sesiones de enfoque y análisis de productividad impulsado por IA.

**Stack:**
- Backend: Laravel 11 (PHP)
- Frontend: Vue 3 + Inertia.js
- Styling: Tailwind CSS + Tailwind Forms
- Build: Vite
- Database: Probablemente MySQL/SQLite

**Ubicación:** `c:\wamp64\www\projects\worklog\`

---

## 🗂️ Estructura del Proyecto

### Modelos Principales (`app/Models/`)
- **User** — Sistema de autenticación y registro
- **Entry** — Registros de tiempo/worklog (diarios, con tipo, contenido, etiquetas, archivos)
- **Task** — Tareas con soporte para:
  - Subtareas (parent-child relationship)
  - Prioridad y estados (pending, in_progress, done, etc.)
  - Fechas de vencimiento
  - Recurrencia (daily, weekly, monthly)
  - Etiquetas y archivos adjuntos
- **Project** — Proyectos que agrupan tareas y entries
- **Tag** — Etiquetas reutilizables (relación many-to-many con tasks y entries)
- **Template** — Plantillas para crear tareas/entries rápidamente
- **FocusSession** — Sesiones de enfoque/pomodoro vinculadas a tareas
- **Attachment** — Archivos adjuntos (morphMany, puede adjuntarse a tasks o entries)

### Controladores Principales (`app/Http/Controllers/`)
- **DashboardController** — Dashboard principal
- **TaskController** — CRUD de tareas, vista kanban, reordenamiento, subtareas, cambio de estado
- **EntryController** — CRUD de registros de tiempo
- **ProjectController** — Gestión de proyectos
- **TagController** — Gestión de etiquetas
- **FocusController** — Gestión de sesiones de enfoque (start, complete, cancel)
- **TemplateController** — Plantillas predefinidas
- **FileController** — Carga y eliminación de archivos
- **AiController** — Integración con IA:
  - `dailySummary()` — Resumen automático del día
  - `extractTasks()` — Extracción de tareas desde texto
- **SearchController** — Búsqueda global
- **ProfileController** — Gestión del perfil de usuario

### Vistas Principales (`resources/js/Pages/`)
- **Dashboard** — Vista principal después de login
- **Entries/** — Listado, creación y edición de registros de tiempo
- **Tasks/** — Listado, kanban, vista individual, formulario
- **Projects/** — Gestión de proyectos
- **Tags/** — Gestión de etiquetas
- **Templates/** — Plantillas
- **Focus/** — Vista de sesiones de enfoque
- **Search/** — Búsqueda global

### Rutas Clave (`routes/web.php`)
```
GET  /dashboard                          — Dashboard
GET  /tasks                              — Listado de tareas
GET  /tasks/kanban                       — Vista kanban
PATCH /tasks/{task}/status               — Actualizar estado
PATCH /tasks/{task}/toggle               — Marcar/desmarcar
POST /tasks/reorder                      — Reordenar tareas
POST /tasks/{task}/subtasks              — Agregar subtarea
GET  /entries                            — Registros de tiempo
GET  /focus                              — Sesiones de enfoque
POST /focus/start                        — Iniciar sesión de enfoque
GET  /api/ai/summary                     — Resumen diario con IA
POST /api/ai/extract-tasks              — Extraer tareas con IA
```

---

## 🎯 Características Completamente Implementadas

✅ Autenticación y registro de usuarios (Breeze)  
✅ CRUD completo de tareas y subtareas  
✅ Vista Kanban de tareas con drag-drop  
✅ Registro de tiempo/worklog (Entries)  
✅ Proyectos y etiquetas (relaciones many-to-many)  
✅ Sesiones de enfoque/pomodoro  
✅ Recurrencia de tareas (daily, weekly, monthly)  
✅ Archivos adjuntos (morphable)  
✅ Plantillas reutilizables para entries y tasks  
✅ Dashboard completo con:
  - Métricas del día (entradas, tareas, archivos, completadas)
  - Top tareas pendientes priorizadas
  - Resumen IA (OpenAI)
  - Actividad semanal (gráfico de barras)
  - Entradas recientes
  - Archivos recientes
  - Actividad (entries + tasks completadas)
✅ Integración con IA:
  - Resúmenes automáticos del día  
  - Extracción de tareas desde texto libre
✅ Búsqueda con filtros (título, contenido, proyecto, tags, fechas, estado)  
✅ Perfiles de usuario  
✅ Seeder con datos de prueba (10 entradas, 10 tareas, 4 proyectos, 10 tags)  

---

## 🛠️ Setup Local

```bash
# Instalar dependencias
composer install
npm install

# Configurar base de datos
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan seed:run  # opcional: cargar datos de ejemplo

# Correr en desarrollo
php artisan serve          # en una terminal
npm run dev               # en otra terminal

# Build para producción
npm run build
```

---

## 🔄 Flujo Típico de Usuario

1. **Crear Proyecto** — Agrupar tareas por proyecto
2. **Crear Tareas** — Definir con prioridad, fecha de vencimiento, descripción
3. **Agregar Subtareas** — Desglosar tareas grandes
4. **Sesión de Enfoque** — Iniciar pomodoro/focus session vinculada a una tarea
5. **Registrar Entrada** — Documentar el trabajo realizado (worklog)
6. **Ver Resumen** — IA genera resumen automático del progreso
7. **Revisión** — Dashboard muestra estado general, kanban, métricas

---

## 📌 Notas Técnicas Importantes

- **Autenticación:** Middleware `auth` y `verified` requerido en rutas protegidas
- **Relaciones:** Task tiene muchas relaciones (proyecto, entrada, subtareas, etiquetas, sesiones de enfoque, archivos)
- **Recurrencia:** Soporta daily, weekly, monthly con intervalo configurable y fecha de fin
- **AI Integration:** Requiere clave API configurada (probablemente en .env como OPENAI_API_KEY)
- **Búsqueda:** Implementada con índice full-text probable en database
- **Reordenamiento Kanban:** Endpoint `tasks/reorder` acepta POST con array de IDs
- **Morphable Attachments:** Archivos pueden adjuntarse a multiple tipos de modelos

---

## ⚡ Convenciones del Código

- Controladores action-based (algunos con métodos personalizados como `kanban()`)
- Modelos con métodos helper (`markDone()`, `subtaskProgress()`, `spawnNextRecurrence()`)
- Vue components con Inertia (single-file .vue)
- Tailwind CSS para estilos
- Axios para llamadas AJAX
- Vite como bundler
