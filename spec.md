# TaskFlow - Specification

A premium, multi-tenant Personal Task Management Application built with Laravel 11, Vue 3, Inertia.js, and Tailwind CSS.

## Goals

- Provide a secure, multi-tenant task management system where users can only access their own data
- Enable users to organize tasks with customizable categories and color coding
- Offer advanced filtering and search capabilities for efficient task management
- Implement a soft-delete system with trash and restore functionality
- Deliver a premium UI/UX with glassmorphism, animations, and responsive design

---

## User Roles

| Role | Description |
|------|-------------|
| Guest | Unauthenticated visitor (can only view landing page) |
| Authenticated User | Registered user who can manage their own tasks and categories |

---

## User Flows

### 1. Authentication Flow

```
┌─────────────┐
│  Landing    │
│   Page      │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐
│   Login     │────▶│  Dashboard  │
└──────┬──────┘     └─────────────┘
       │
       ▼
┌─────────────┐
│  Register   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Email      │
│ Verification│
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Dashboard  │
└─────────────┘
```

**Features:**
- User registration with name, email, password, password confirmation
- Login with email and password
- Password reset via email
- Email verification
- Remember me functionality

---

### 2. Dashboard Flow

```
┌─────────────────────────────────────────┐
│              Dashboard                  │
├─────────────────────────────────────────┤
│  Stats Cards:                           │
│  - Total Tasks                          │
│  - Pending Tasks                        │
│  - In Progress Tasks                    │
│  - Completed Tasks                      │
├─────────────────────────────────────────┤
│  Recent Tasks (last 5)                  │
│  - Quick view with status/priority      │
│  - Link to full task list               │
└─────────────────────────────────────────┘
```

**Features:**
- Display task statistics (total, pending, in progress, completed)
- Show recent tasks (last 5 created)
- Quick navigation to task list and categories

---

### 3. Task Management Flow

#### 3.1 Task List View

```
┌─────────────────────────────────────────────────────────┐
│  Tasks                                        [Trash]  │
│                                           [+ New Task] │
├─────────────────────────────────────────────────────────┤
│  Filters:                                               │
│  ┌─────────────┐ ┌─────────┐ ┌─────────┐ ┌───────────┐ │
│  │   Search    │ │ Status  │ │Priority │ │ Category  │ │
│  └─────────────┘ └─────────┘ └─────────┘ └───────────┘ │
│  [All Time] [Due Soon] [Overdue]    [Clear filters]     │
├─────────────────────────────────────────────────────────┤
│  Task Cards Grid:                                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │ Task 1   │  │ Task 2   │  │ Task 3   │              │
│  │ Status   │  │ Status   │  │ Status   │              │
│  │ Priority │  │ Priority │  │ Priority │              │
│  └──────────┘  └──────────┘  └──────────┘              │
├─────────────────────────────────────────────────────────┤
│                    [Pagination]                          │
└─────────────────────────────────────────────────────────┘
```

**Features:**
- Paginated task list (configurable per page)
- Multi-parameter filtering:
  - Text search (debounced 300ms)
  - Status filter (Pending, In Progress, Completed)
  - Priority filter (Low, Medium, High, Urgent)
  - Category filter (user's categories)
  - Due date filter (All Time, Due Soon, Overdue)
- Clear all filters button
- Responsive grid layout (1-3 columns)

#### 3.2 Create Task

```
┌─────────────────────────────────────────┐
│  Create Task                            │
├─────────────────────────────────────────┤
│  Title *                                │
│  ┌─────────────────────────────────┐    │
│  │                                 │    │
│  └─────────────────────────────────┘    │
│                                         │
│  Description                            │
│  ┌─────────────────────────────────┐    │
│  │                                 │    │
│  │                                 │    │
│  └─────────────────────────────────┘    │
│                                         │
│  Status *        Priority *             │
│  ┌─────────┐    ┌─────────┐            │
│  │ Pending │    │ Medium  │            │
│  └─────────┘    └─────────┘            │
│                                         │
│  Category        Due Date               │
│  ┌─────────┐    ┌─────────┐            │
│  │ None    │    │         │            │
│  └─────────┘    └─────────┘            │
│                                         │
│  [Cancel]              [Create Task]    │
└─────────────────────────────────────────┘
```

**Fields:**
- Title (required, max 255 characters)
- Description (optional, text area)
- Status (required, enum: pending, in_progress, completed)
- Priority (required, enum: low, medium, high, urgent)
- Category (optional, user's categories only)
- Due Date (optional, date picker)

#### 3.3 Edit Task

Same as Create Task, but pre-populated with existing task data.

#### 3.4 Delete Task (Soft Delete)

- Click delete button on task card or edit page
- Confirmation dialog
- Task moved to Trash (soft deleted)
- Success message displayed

---

### 4. Trash & Restore Flow

```
┌─────────────────────────────────────────┐
│  Trash                                  │
├─────────────────────────────────────────┤
│  Soft-deleted tasks list                │
│  ┌──────────────────────────────────┐   │
│  │ Task Title        [Restore] [🗑] │   │
│  │ Deleted: 2 days ago              │   │
│  └──────────────────────────────────┘   │
│  ┌──────────────────────────────────┐   │
│  │ Task Title        [Restore] [🗑] │   │
│  │ Deleted: 5 days ago              │   │
│  └──────────────────────────────────┘   │
├─────────────────────────────────────────┤
│  [← Back to Tasks]                      │
└─────────────────────────────────────────┘
```

**Features:**
- List all soft-deleted tasks
- Restore task (removes deleted_at timestamp)
- Permanently delete task (force delete)
- Back to task list navigation

---

### 5. Category Management Flow

#### 5.1 Category List

```
┌─────────────────────────────────────────┐
│  Categories                    [+ New]  │
├─────────────────────────────────────────┤
│  ┌──────────────────────────────────┐   │
│  │ 🟢 Work                    [Edit]│   │
│  │ 🔴 Personal                [Edit]│   │
│  │ 🔵 Shopping                [Edit]│   │
│  │ 🟡 Health                  [Edit]│   │
│  └──────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

#### 5.2 Create/Edit Category

**Fields:**
- Name (required, max 255 characters)
- Color (required, valid hex color code with color picker)

---

### 6. Profile Management Flow

```
┌─────────────────────────────────────────┐
│  Profile Settings                       │
├─────────────────────────────────────────┤
│  Profile Information                   │
│  ┌─────────────────────────────────┐    │
│  │ Name:  John Doe                │    │
│  │ Email: john@example.com        │    │
│  └─────────────────────────────────┘    │
│  [Update Profile]                       │
├─────────────────────────────────────────┤
│  Update Password                        │
│  ┌─────────────────────────────────┐    │
│  │ Current Password:              │    │
│  │ New Password:                  │    │
│  │ Confirm Password:              │    │
│  └─────────────────────────────────┘    │
│  [Update Password]                      │
├─────────────────────────────────────────┤
│  Delete Account                         │
│  ⚠️ Permanently delete your account     │
│  [Delete Account]                       │
└─────────────────────────────────────────┘
```

---

## Security Requirements

### Multi-Tenant Isolation

- Users can ONLY access their own tasks and categories
- All database queries are scoped to `$user->id`
- Laravel Policies enforce authorization on all CRUD operations
- BOLA/IDOR vulnerabilities prevented through explicit authorization checks

### Authorization Rules

| Action | Task | Category |
|--------|------|----------|
| View Any | Owner only | Owner only |
| View | Owner only | Owner only |
| Create | Authenticated | Authenticated |
| Update | Owner only | Owner only |
| Delete | Owner only | Owner only |
| Restore | Owner only | N/A |
| Force Delete | Owner only | N/A |

### Validation Rules

- All form inputs validated server-side
- Category assignment validated (must belong to user)
- Hex color validation for category colors
- Required fields enforced

---

## UI/UX Requirements

### Design System

- **Glassmorphism**: backdrop-blur effects on cards and modals
- **Rounded corners**: 2xl (16px) for cards, xl (12px) for inputs
- **Shadows**: Subtle shadows with hover effects
- **Animations**: Smooth transitions on all interactive elements

### Color Scheme

- **Primary**: Indigo (indigo-600)
- **Status Colors**:
  - Pending: Gray
  - In Progress: Blue
  - Completed: Green
- **Priority Colors**:
  - Low: Gray
  - Medium: Yellow
  - High: Orange
  - Urgent: Red

### Responsive Design

- Mobile-first approach
- Breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px)
- Grid layouts adapt from 1 to 3 columns

---

## Non-Functional Requirements

### Performance

- Debounced search (300ms) to reduce API calls
- Paginated results (default: 12 per page)
- Eager loading for relationships

### Testing

- Feature tests for all CRUD operations
- Security tests for multi-tenant isolation
- Minimum 80% code coverage target

### Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- ES2020+ JavaScript features

---

## Future Enhancements (Out of Scope)

- [ ] Task activity log / audit trail
- [ ] Bulk operations (mass delete, status change)
- [ ] Drag-and-drop task reordering
- [ ] Email notifications for due dates
- [ ] Task labels/tags system
- [ ] Export to CSV/PDF
- [ ] REST API for mobile apps
- [ ] Dark mode toggle
- [ ] Task attachments
- [ ] Recurring tasks
