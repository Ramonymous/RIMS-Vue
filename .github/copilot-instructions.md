# RIMS - Resource Inventory Management System

Inventory tracking system for parts with transaction-based receivings (GR) and outgoings (GI) workflows, maintaining immutable stock movement history.

## Architecture

### Domain Model
- **Parts** tracked via **Receivings** (inbound) and **Outgoings** (outbound) transactions
- Each transaction has multiple items (`ReceivingItems`/`OutgoingItems`)
- All stock changes generate immutable `PartMovements` audit trail
- Workflow: draft → completed → cancelled (stock applies only on completed)
- Status reversion automatically reverses stock in atomic DB transactions

### Service Layer (CRITICAL)
NEVER inline business logic in controllers. Use:
- `OutgoingService` - Outgoing transactions + stock validation
- `ReceivingService` - Incoming stock + movement tracking  
- `AuthorizationService` - Centralized permission checks (JSON column)

### Exceptions
Domain errors auto-render JSON: `BusinessException` (422), `InsufficientStockException`, `UnauthorizedException`

## Database (PostgreSQL)

- UUIDs for all IDs
- Users: JSON `permissions` column (string array)
- Status: CHECK constraints (`draft|completed|cancelled`)
- Composite unique indexes: `receiving_id,part_id` and `outgoing_id,part_id`
- Polymorphic tracking: `part_movements.reference_type/reference_id`

### Models
- Use `casts()` method, NOT `$casts` property
- `Parts` query scopes: `->active()`, `->inStock()`, `->lowStock(10)`, `->search($term)`
- Soft deletes: `SoftDeletes` trait

### DTOs
Constructor property promotion in `app/DataTransferObjects/`. Include `fromRequest()` and `toArray()` methods.

## Frontend (Vue 3 + Inertia v2)

- **Wayfinder**: Import from `@/routes/` (named) or `@/actions/` (controllers). Use `.form()` for Inertia forms
- **Structure**: Pages in `resources/js/pages/`, components in `components/`. Uses shadcn-vue
- **Forms**: `useForm()` + Wayfinder + FormRequest validation on backend
- **Paths**: `@/*` → `resources/js/*`

## Development

### Commands
- `composer run dev` - Both backend + frontend
- `php artisan serve` - Backend only
- `bun run dev` - Frontend only (HMR)
- `bun run build` - Production build

### Testing (Pest)
- `php artisan test --compact --filter=testName`
- Use factories (check states before manual setup)

### Formatting
- PHP: `vendor/bin/pint --dirty` (MUST before commit)
- JS: `bun run format && bun run lint`

### Excel
Maatwebsite Excel - see `app/Exports/PartsTemplateExport.php` and `app/Imports/PartsImport.php`

## Conventions

- **Form Requests**: Always use. Check siblings for array/string rule format. Include `messages()`
- **Authorization**: `AuthorizationService` methods (JSON array matching)
- **Stock**: NEVER modify `parts.stock` directly - always via services
- **Document Numbers**: `RCV-{ddmmyy}-001` or `OUT-{ddmmyy}-001` (frontend generates)
- **Components**: Check existing before creating new

<laravel-boost-guidelines>

# Stack
- PHP 8.4.16, Laravel 12, Inertia v2, Fortify v1, Wayfinder v0, Pest v4, PHPUnit v12, Bun, Vite, PostgreSQL, Redis, Tailwind CSS, shadcn-vue

## Skills (Activate when working in domain)
- `wayfinder-development` - Backend routes in frontend (@/actions, @/routes)
- `pest-testing` - Writing/debugging tests

## Conventions
- Follow existing code patterns (check siblings)
- Descriptive names: `isRegisteredForDiscounts` not `discount()`
- Reuse existing components
- No verification scripts when tests exist
- Preserve directory structure
- Be concise in explanations

## Boost Tools (MCP Server)
- `list-artisan-commands` - Check Artisan params
- `get-absolute-url` - Generate project URLs
- `tinker` - Execute PHP/query Eloquent
- `database-query` - Read-only DB queries
- `browser-logs` - Frontend errors (recent only)
- `search-docs` (CRITICAL) - Version-specific docs. Use BEFORE code changes. Multiple broad queries: `['rate limiting', 'routing']`. No package names in queries.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.
## PHP
- Curly braces for all control structures
- Constructor property promotion: `public function __construct(public GitHub $github) {}`
- Explicit return types + param type hints
- Enum keys: TitleCase (`FavoritePerson`, `Monthly`)
- PHPDoc blocks (not inline comments). Add array shape types when useful they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing
Every change needs a test. Run: `php artisan test --compact --filter=testName`
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
## Inertia v2
- Components: `resources/js/Pages`. Use `Inertia::render()` not Blade
- v2 features: deferred props (add skeleton), infinite scroll, lazy load, polling, prefetch
- ALWAYS use `search-docs` for version-specific examples

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.
## Laravel Patterns
- Use `php artisan make:*` (pass `--no-interaction`)
- **Database**: Eloquent relationships (return types) > raw queries. Prevent N+1 with eager loading
- **Models**: Create with factories + seeders
- **Validation**: FormRequest classes (check siblings for array/string rules). Include `messages()`
- **Auth**: Gates, policies, Sanctum
- **URLs**: Named routes + `route()` function
- **Queues**: `ShouldQueue` interface
- **Config**: `config('key')` NOT `env('KEY')`
- **Testing**: Use factories (check states first). Most tests = feature tests
- **Vite error**: Run `bun run dev` or `composer run dev`## Laravel 12 Structure
- No `app/Http/Kernel.php` - middleware in `bootstrap/app.php` via `withMiddleware()`
- No `app/Console/Kernel.php` - console in `bootstrap/app.php` or `routes/console.php`
- `bootstrap/providers.php` - service providers
- Commands in `app/Console/Commands/` auto-register
- **Migrations**: Modifying column? Include ALL previous attributes or they're dropped
- **Models**: `casts()` method > `$casts` property (check siblings)
- **Eager loading**: Native limit: `$query->latest()->limit(10)`## Wayfinder
TypeScript route functions from `@/actions/` (controllers) or `@/routes/` (named).
- Activate `wayfinder-development` skill when working with routes
- Invokable: `import StorePost from '@/actions/.../StorePostController'`
- Params: `show({ slug: "my-post" })`
- Query merge: `show(1, { mergeQuery: { page: 2, sort: null } })`
- Inertia: `.form()` with `<Form>` or `form.submit(store())`

## Pint
MUST run `vendor/bin/pint --dirty` before finalizing (not `--test`)

## Pest
- Create: `php artisan make:test --pest {name}`
- Run: `php artisan test --compact --filter=testName`
- Activate `pest-testing` skill for all test work
- DON'T delete tests without approval