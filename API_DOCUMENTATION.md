# 🔌 WorkLog API Documentation

REST API reference for WorkLog. All endpoints require authentication.

---

## Authentication

All endpoints require a valid user session (Inertia.js automatically handles this).

```
Headers: Accept: application/json
Status codes: 200 (OK), 201 (Created), 404 (Not Found), 422 (Validation), 500 (Error)
```

---

## Tasks API

### List Tasks
```
GET /tasks?page=1&search=query&status=pending&priority=urgent&project_id=1
```
**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Complete report",
      "description": "Finish Q2 report",
      "priority": "high",
      "status": "in_progress",
      "due_date": "2026-06-10",
      "is_overdue": false,
      "project": { "id": 1, "name": "Reporting", "color": "#3B82F6" },
      "tags": [{ "id": 1, "name": "urgent", "color": "#EF4444" }]
    }
  ],
  "links": { "first": "...", "last": "...", "next": "..." },
  "meta": { "current_page": 1, "total": 50 }
}
```

### Get Task
```
GET /tasks/{id}
```
**Response:** Single task object

### Create Task
```
POST /tasks
Content-Type: application/json

{
  "title": "New task",
  "description": "Optional details",
  "priority": "medium",
  "due_date": "2026-06-15",
  "project_id": 1,
  "tags": [1, 2, 3]
}
```
**Response:** Created task (201)

### Update Task
```
PUT /tasks/{id}
Content-Type: application/json

{
  "title": "Updated title",
  "status": "done",
  ...
}
```
**Response:** Updated task

### Delete Task
```
DELETE /tasks/{id}
```
**Response:** 204 No Content

### Update Task Status
```
PATCH /tasks/{id}/status
Content-Type: application/json

{
  "status": "in_progress"
}
```
**Status values:** `pending`, `in_progress`, `done`

### Kanban View
```
GET /tasks/kanban?project_id=1&priority=high&tag=urgent
```
**Response:**
```json
{
  "columns": {
    "pending": [...],
    "in_progress": [...],
    "done": [...]
  },
  "projects": [...],
  "tags": [...]
}
```

### Reorder Tasks
```
POST /tasks/reorder
Content-Type: application/json

{
  "tasks": [
    { "id": 1, "sort_order": 0 },
    { "id": 2, "sort_order": 1 }
  ]
}
```

---

## Entries API

### List Entries
```
GET /entries?page=1&search=query&type=general&project_id=1&from=2026-06-01&to=2026-06-30
```
**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Team meeting",
      "type": "reunion",
      "entry_date": "2026-06-05",
      "entry_time": "14:30",
      "project": { "id": 1, "name": "Team", "color": "#10B981" },
      "tags": [{ "id": 1, "name": "discussion" }],
      "attachments_count": 2
    }
  ],
  "meta": { "current_page": 1, "total": 120 }
}
```

### Create Entry
```
POST /entries
Content-Type: application/json

{
  "title": "Meeting notes",
  "type": "reunion",
  "content": "Discussed Q3 goals",
  "entry_date": "2026-06-05",
  "entry_time": "14:30",
  "project_id": 1,
  "tags": [1, 2],
  "is_pinned": false
}
```
**Type values:** `general`, `reunion`, `deploy`, `code_review`, `investigacion`, `planificacion`

### Update Entry
```
PUT /entries/{id}
Content-Type: application/json

{
  "title": "Updated notes",
  "content": "New content"
}
```

### Delete Entry
```
DELETE /entries/{id}
```

---

## Focus Sessions API

### List Sessions
```
GET /focus
```
**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "task_id": 1,
      "task_title": "Complete report",
      "started_at": "2026-06-05 10:00:00",
      "ended_at": "2026-06-05 10:45:00",
      "duration_minutes": 45,
      "status": "completed"
    }
  ]
}
```

### Start Focus Session
```
POST /focus/start
Content-Type: application/json

{
  "task_id": 1
}
```
**Response:** Active session object (201)

### Complete Session
```
PATCH /focus/{id}/complete
```
**Response:** Completed session

### Cancel Session
```
PATCH /focus/{id}/cancel
```
**Response:** Cancelled session

---

## Reports API

### Daily Report
```
GET /reports/daily?date=2026-06-05
```
**Response:**
```json
{
  "date": "2026-06-05",
  "entries_count": 5,
  "tasks_completed": 3,
  "tasks_pending": 8,
  "focus_time_minutes": 180,
  "entries": [...],
  "completed_tasks": [...],
  "project_stats": [...]
}
```

### Weekly Report
```
GET /reports/weekly?week_start=2026-06-01
```
**Response:**
```json
{
  "week_start": "2026-06-01",
  "week_end": "2026-06-07",
  "total_entries": 35,
  "total_completed": 20,
  "total_focus_minutes": 1200,
  "daily_stats": [...],
  "project_ranking": [...],
  "entry_types": [...]
}
```

---

## Calendar API

### Get Calendar Events
```
GET /calendar
```
**Response:**
```json
{
  "events": [
    {
      "id": 1,
      "title": "Complete report",
      "start": "2026-06-10",
      "end": "2026-06-10",
      "priority": "high",
      "status": "pending",
      "project": "Reporting"
    }
  ],
  "projects": [...]
}
```

### Update Task Due Date
```
PATCH /calendar/task-due-date/{id}
Content-Type: application/json

{
  "due_date": "2026-06-15"
}
```

---

## Planning API

### Get Week Plan
```
GET /planning/week
```
**Response:**
```json
{
  "monday": [...tasks...],
  "tuesday": [...tasks...],
  "wednesday": [...tasks...],
  "thursday": [...tasks...],
  "friday": [...tasks...],
  "saturday": [...tasks...],
  "sunday": [...tasks...],
  "unassigned": [...tasks...]
}
```

### Update Task Due Date
```
PATCH /planning/task-due-date
Content-Type: application/json

{
  "task_id": 1,
  "due_date": "2026-06-12"
}
```

---

## Projects API

### List Projects
```
GET /projects
```
**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Reporting",
      "color": "#3B82F6",
      "is_active": true,
      "tasks_count": 15
    }
  ]
}
```

### Create Project
```
POST /projects
Content-Type: application/json

{
  "name": "New Project",
  "color": "#10B981"
}
```

### Update Project
```
PUT /projects/{id}

{
  "name": "Updated Name",
  "color": "#EF4444",
  "is_active": true
}
```

### Delete Project
```
DELETE /projects/{id}
```

---

## Tags API

### List Tags
```
GET /tags
```

### Create Tag
```
POST /tags
Content-Type: application/json

{
  "name": "urgent",
  "color": "#EF4444"
}
```

### Update Tag
```
PUT /tags/{id}

{
  "name": "critical",
  "color": "#DC2626"
}
```

### Delete Tag
```
DELETE /tags/{id}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "due_date": ["The due date must be a valid date."]
  }
}
```

### Not Found (404)
```json
{
  "message": "Not found"
}
```

### Unauthorized (403)
```json
{
  "message": "Unauthorized"
}
```

### Server Error (500)
```json
{
  "message": "Server error"
}
```

---

## Query Parameters

### Pagination
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20-30)

### Filtering
- `search`: Search by title/content
- `status`: Filter by status
- `priority`: Filter by priority
- `project_id`: Filter by project
- `tag`: Filter by tag name
- `type`: Filter by type (entries)
- `from`: Start date (YYYY-MM-DD)
- `to`: End date (YYYY-MM-DD)

### Sorting
- `sort`: Sort field
- `direction`: asc or desc

---

## Rate Limiting

No rate limiting implemented currently. Subject to change in production.

---

## API Versioning

Current version: v1 (default)

All endpoints use the base path `/`

Future versions may use `/api/v2/`, etc.

---

## WebSocket / Real-time (Future)

Real-time updates not currently supported. Refresh to see latest data.

---

**Last Updated:** 2026-06-05

For usage examples, see test files in `tests/` directory.
