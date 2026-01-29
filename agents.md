<laravel-boost-guidelines>
=== foundation rules ===

# Project Guidelines

가이드라인은 영어지만 대화는 모두 한국어로 진행해.

These guidelines are aligned to the current repository. If a rule conflicts with existing code or installed dependencies, follow the code and update this file if needed.

## Foundational Context
Use the actual versions in composer.json / package.json. Current key dependencies:

- php - ^8.2
- laravel/framework - ^12.0
- laravel/tinker - ^2.10.1
- laravel/pint - ^1.24
- laravel/sail - ^1.41
- phpunit/phpunit - ^11.5.3
- vite - ^7
- tailwindcss - ^4

## Conventions
- Follow existing code conventions used in this application. When creating or editing a file, check sibling files for structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Reuse existing components or helpers before writing new ones.

## Tooling
- Do not assume special MCP/Boost tools are available.
- Prefer local code inspection and standard Artisan commands.
- Use `php artisan list` if you need to confirm command options.
- When sharing project URLs, ask the user for the running host/port if unknown.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to the existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.
- This project keeps middleware in `app/Http/Middleware/` and registers aliases in `bootstrap/app.php`.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- Only create documentation files if explicitly requested by the user.


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Avoid inline comments unless the logic is genuinely complex.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (migrations, controllers, models, etc.).
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input.
- If you need to check available Artisan options, use `php artisan list`.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM.
- Prevent N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create factories/seeders when useful. Ask the user if they want these.

### APIs & Eloquent Resources
- For APIs, default to Eloquent API Resources and API versioning unless existing API routes do not. Follow existing application conventions.

### Controllers & Validation
- Prefer Form Request classes for complex validation, but follow existing patterns in this codebase (currently uses inline `$request->validate()`).
- Include both validation rules and custom error messages when appropriate.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use `env()` directly outside of config files. Use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use factories. Check for custom states before manual setup.
- Faker: use methods such as `fake()->word()` or `fake()->randomDigit()`. Follow existing conventions.
- Use `php artisan make:test` for feature tests; pass `--unit` for unit tests.

### Vite Error
- If you receive "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest", run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- This project uses the Laravel 11+ structure.
- `bootstrap/app.php` registers middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php`.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available.

### Database
- When modifying a column, the migration must include all previously defined attributes to avoid dropping them.
- Laravel 11 allows limiting eagerly loaded records natively: `$query->latest()->limit(10);`.

### Models
- Casts may be defined in `casts()` or `$casts` depending on existing model conventions. Follow neighboring models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- If you change PHP files, run `vendor/bin/pint --dirty` before finalizing changes.
- Do not run `vendor/bin/pint --test`; run `vendor/bin/pint` to fix formatting issues.


=== testing rules ===

## PHPUnit
### Testing
- Use test-first workflow (TDD) when adding or changing behavior: write/update tests first, then implement production code.
- Always add or update Unit / Feature tests for any code change that affects behavior.
- Do not remove any tests or test files from the tests directory without approval.
- Tests should cover happy paths, failure paths, and edge cases.
- Tests live in `tests/Feature` and `tests/Unit`.

### Running Tests
- Run the minimal number of tests using an appropriate filter after implementing changes to verify them.
- Always run tests with escalated permissions (approve) when the environment is sandboxed to ensure MariaDB connectivity and avoid permission-related failures.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName`.
- When related tests are passing, ask the user if they want the full test suite run.

### Assertions
- When asserting status codes on a response, use helper methods like `assertForbidden` or `assertNotFound` instead of `assertStatus(403)`.
</laravel-boost-guidelines>
