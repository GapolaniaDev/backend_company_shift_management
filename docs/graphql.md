# GraphQL API Documentation

This document describes the GraphQL API implementation for the Shift Management system using Laravel 10 and Lighthouse GraphQL.

## Authentication

The GraphQL API uses Laravel Passport for authentication with the `api` guard.

### Headers Required

All GraphQL requests must include the authentication token:

```
Authorization: Bearer YOUR_ACCESS_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Obtaining Access Tokens

Use the existing REST API authentication endpoints or Laravel Passport's OAuth2 flow to obtain access tokens.

> **Note**: If Passport hasn't been initialized yet, run `php artisan passport:install` to set up the OAuth2 keys.

## Company Scoping

**Important**: All data is automatically scoped by company. Users can only access resources that belong to their company.

- All list queries automatically filter results by the authenticated user's `company_id`
- All mutations automatically set `company_id` to the authenticated user's company
- All individual resource queries verify that the resource belongs to the user's company

This scoping is handled by:
- Custom `@whereAuthCompany` directive for list queries
- Business logic in mutations (ShiftMutator, EmployeeMutator, etc.)
- Authorization policies that check company ownership

## Pagination

- Default page size: 25 items per page
- Maximum page size: 100 items per page
- All paginated queries use Laravel's `PaginatorInfo` format:

```graphql
{
  paginatorInfo {
    count          # Items on current page
    currentPage    # Current page number
    firstItem      # Index of first item on page
    hasMorePages   # Boolean indicating if more pages exist
    lastItem       # Index of last item on page
    lastPage       # Total number of pages
    perPage        # Items per page
    total          # Total items across all pages
  }
  data [          # Array of actual items
    # ... your items here
  ]
}
```

## GraphQL Endpoint

**URL**: `/graphql`

## Core Types and Operations

### Shifts

**Key Features**:
- Date/time validation (end must be after start)
- Employee overlap checking (prevents double-booking)
- Weekly hour limit validation
- Company scoping

**Available Operations**:
```graphql
# Get single shift
query {
  shift(id: "123") {
    id
    date_start
    date_end
    total_hours
    shift_status
    employee {
      first_name
      last_name
    }
    shiftType {
      name
    }
  }
}

# Get paginated shifts with filtering
query {
  shifts(
    filter: {
      status: PUBLISHED
      dateRange: { from: "2025-01-01T00:00:00Z", to: "2025-01-31T23:59:59Z" }
      employeeId: 5
    }
    orderBy: [{ column: "date_start", order: ASC }]
  ) {
    paginatorInfo { /* ... */ }
    data {
      id
      date_start
      date_end
      employee { first_name last_name }
    }
  }
}

# Create shift
mutation {
  createShift(input: {
    shift_type_id: 1
    employee_id: 5
    date_start: "2025-01-15T09:00:00Z"
    date_end: "2025-01-15T17:00:00Z"
    total_hours: 8.0
    weekday_code: 1
  }) {
    id
    date_start
    date_end
  }
}

# Update shift
mutation {
  updateShift(id: "123", input: {
    date_start: "2025-01-15T08:00:00Z"
    comments: "Updated start time"
  }) {
    id
    date_start
    comments
  }
}

# Delete shift
mutation {
  deleteShift(id: "123")
}
```

**Supported Filters**:
- `status`: Filter by shift status (DRAFT, PUBLISHED, CANCELLED)
- `dateRange`: Filter by date range with `from` and `to` dates
- `employeeId`: Filter by specific employee
- `shiftTypeId`: Filter by specific shift type

### Employees

**Available Operations**:
```graphql
# Get single employee
query {
  employee(id: "123") {
    id
    first_name
    last_name
    email
    weekly_working_hours
    supervisor {
      first_name
      last_name
    }
  }
}

# Get paginated employees
query {
  employees(orderBy: [{ column: "last_name", order: ASC }]) {
    paginatorInfo { /* ... */ }
    data {
      id
      first_name
      last_name
      email
    }
  }
}
```

### Users

**Available Operations**:
```graphql
# Get current user info
query {
  me {
    id
    name
    email
    role
    employee {
      first_name
      last_name
    }
  }
}
```

## Business Validations

### Shift Validations

1. **Date Validation**: `date_end` must be after `date_start`
2. **Employee Overlap**: No overlapping shifts for the same employee
3. **Weekly Hours**: Total weekly hours cannot exceed employee's `weekly_working_hours` limit
4. **Company Scoping**: All shifts are automatically scoped to user's company

### Employee Validations

1. **Supervisor Validation**: Supervisor must exist and belong to same company
2. **Self-Supervision**: Employee cannot be their own supervisor
3. **User Association**: Associated user must exist

## Error Handling

The API returns standard GraphQL errors with additional information:

```json
{
  "errors": [
    {
      "message": "The shift end time must be after the start time.",
      "extensions": {
        "category": "validation"
      },
      "locations": [{"line": 2, "column": 3}],
      "path": ["createShift"]
    }
  ]
}
```

## Security Features

1. **Authentication Required**: All queries/mutations require valid Bearer token
2. **Company Isolation**: Users can only access their company's data
3. **Authorization Policies**: Each resource checks user permissions
4. **Input Validation**: All mutations validate input data
5. **No Subscriptions**: Real-time features disabled for security

## Performance Considerations

1. **N+1 Prevention**: Relations are eager-loaded automatically by Lighthouse
2. **Pagination**: Large datasets are paginated to prevent memory issues
3. **Query Complexity**: Basic limits configured to prevent resource abuse
4. **Caching**: Schema caching enabled in production

## Foreign Key Notes

> **Important**: Some tables have foreign key relationships that reference `users(id)` instead of a dedicated `companies` table. This appears to be a design where a "user" record may represent a company entity. The GraphQL layer handles this by consistently filtering on `company_id` and ensuring data isolation between different company contexts.

Future migrations may be needed to clarify these relationships with proper `companies` table references.

## Future Roadmap

### Phase 2 - Real-time Features
- Add Redis for caching and session management
- Implement GraphQL Subscriptions for real-time updates
- WebSocket support for live shift notifications

### Phase 3 - Advanced Features
- Advanced filtering and search capabilities
- Bulk operations for shift management
- Reporting and analytics queries
- File upload support for employee documents

## Development Notes

### Adding New Domains

1. Create GraphQL schema files in `graphql/{domain}/`
2. Create corresponding Eloquent model scopes
3. Create mutation classes in `app/GraphQL/Mutations/`
4. Create and register policies in `AuthServiceProvider`
5. Add domain imports to root `graphql/schema.graphql`

### Testing GraphQL

Use tools like GraphQL Playground, Altair, or Insomnia with the endpoint:
- Development: `http://localhost:8000/graphql`
- With Docker: `http://0.0.0.0:8000/graphql`

### Debugging

- Set `LIGHTHOUSE_DEBUG=3` in `.env` for detailed error messages
- Use `php artisan lighthouse:cache` to clear schema cache during development
- Check Laravel logs for detailed error information

## Example Client Usage

```javascript
// Using fetch API
const query = `
  query GetShifts($first: Int!, $page: Int!) {
    shifts(first: $first, page: $page) {
      paginatorInfo {
        currentPage
        hasMorePages
        total
      }
      data {
        id
        date_start
        date_end
        employee {
          first_name
          last_name
        }
      }
    }
  }
`;

fetch('/graphql', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    query,
    variables: { first: 25, page: 1 }
  })
})
.then(response => response.json())
.then(data => console.log(data));
```