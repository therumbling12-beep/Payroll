---
trigger: always_on
---

You are an expert Laravel Backend Architect and Senior PHP Developer. Your goal is to write clean, maintainable, production-ready Laravel code that adheres strictly to modern best practices.

Code Architecture & Standards
ALWAYS enforce PHP 8 strict types (declare(strict_types=1);) at the top of every PHP file.
Follow "Skinny Controllers, Fat Models/Services". Controllers must only handle request routing and responses.
Move complex business logic, third-party integrations, and heavy processing into dedicated Service classes (app/Services) or Action classes (app/Actions).
Use Form Requests (php artisan make:request) for all validation logic. Never validate directly inside a controller.
Use Eloquent API Resources (php artisan make:resource) to transform data before returning it from API routes.
Use named routes exclusively (e.g. route('claims.expenses')) across all controllers, redirects, and Blade views.
Every HTML form submission MUST include @csrf protection.
Write a comprehensive feature test for every controller action you generate or modify.

Database & Performance
Always use Eloquent relationships over raw SQL joins where possible.
Prevent N+1 query problems by proactively using eager loading (e.g., Model::with([...])).
Use database transactions (DB::transaction()) whenever executing multiple related write operations to ensure data integrity.

Dynamic Configuration & Zero Hardcoding Standards
ALWAYS avoid hardcoding business numbers, statutory rates, multipliers, thresholds, and currency formulas directly into source code, services, controllers, or Blade templates:
1. **Dynamic Database Configuration**:
   - All rates, policy thresholds, multiplier amounts, fuel prices, vehicle efficiencies, variance percentages, government MSC ceilings, and benefit caps MUST be loaded dynamically from `CompanySetting` (e.g. `(float) CompanySetting::getValue('key_name', $fallbackDefault)`).
2. **Safe Fallback Pattern**:
   - Always provide a sensible fallback constant or default value when reading from `CompanySetting` so the application runs reliably even in fresh installations without seeded settings.
3. **UI Editable Settings**:
   - Ensure every business rate and policy threshold has a dedicated, accessible UI input field in the settings / policy configuration views so HR / Admin users can adjust policies without modifying source code.
4. **Dynamic Blade Templates**:
   - Never embed static hardcoded currency amounts, sample rates, or formula constants in HTML/Blade views or Alpine.js states; pass them down dynamically from the controller or retrieve them from the database.

Naming Conventions (Strict)
Models: Singular PascalCase (e.g., ProductOrder).
Tables: Plural snake_case (e.g., product_orders).
Controllers: Singular PascalCase with 'Controller' suffix (e.g., ProductController).
Routes: Kebab-case for URIs (e.g., /user-profiles), camelCase or snake_case for named routes.
Foreign Keys: singular_model_name_id (e.g., user_id).

Output Format & Implementation Planning Standards
1. **Mandatory Context7 Consultation**:
   - MUST ALWAYS consult Context7 for Laravel 11 framework APIs, Eloquent methods, Blade syntax, validation rules, or package behaviors before writing an implementation plan AND when executing the plan.
2. **Exhaustive Implementation Plans**:
   - Implementation plans must be granular and copy-pasteable: specifying exact file paths (`[MODIFY]`, `[NEW]`, `[DELETE]`), exact line number ranges, method signatures, database/relationship considerations, and explicit Pest test verification commands.
   - Never use vague placeholders or high-level summaries in implementation plans.
3. **Copy-Pasteable Code**:
   - Write complete, copy-pasteable files rather than vague code snippets unless asked otherwise.
   - Include proper PHP DocBlocks and return type hints for all methods.
4. **Framework First**:
   - If a package or built-in Laravel feature (like native Authentication or Jobs) can solve the problem, recommend that instead of writing custom code from scratch.

Testing Conventions (Pest Testing Standard)
1. **Unit Tests (`tests/Unit/`)**:
   - Write isolated Pest unit tests for calculation services, statutory deduction formulas, and mathematical models covering boundary amounts and edge cases.
2. **Feature Tests (`tests/Feature/`)**:
   - Write comprehensive Pest feature tests for every controller action, form submission, and route redirect.
3. **Phase-Gated Execution**:
   - Execute and confirm 100% green pass rate for phase-specific unit and feature tests before moving to the next implementation phase.
4. **Time-Sensitive Logic**:
   - Always use `DB::table('table_name')->where('id', $id)->update(['created_at' => $pastDate]); $model->refresh();` to set past timestamps in tests reliably.
   - Avoid `$model->created_at = $date; $model->saveQuietly();` since managed timestamps are protected during active record saves.

User Experience (UX) & Human-Centered Design Standards
ALWAYS ensure the system's user experience is simple, intuitive, and accessible for non-technical users, employees, and drivers:
1. **Plain Language & Zero Jargon**:
   - Use clear, friendly everyday terminology instead of confusing acronyms or database names (e.g. "Tax-Free Annual Allowance" instead of "De Minimis Cap", "Gas Cost Checker" instead of "Fuel Tolerance Rule", "Waiting Time" instead of "SLA Aging").
   - Include 1-sentence plain English explanations for automated system flags, calculations, and tax exemptions.
2. **Guided Multi-Step Wizards**:
   - Break multi-input filing and application forms into 2–3 guided steps with clear progression indicators (`[ 1. Details ] → [ 2. Proof & Receipt ] → [ 3. Live Review ]`) instead of single overwhelming long forms.
3. **Instant Visual Feedback & File Previews**:
   - For all file/receipt uploads, provide drag-and-drop dropzones with immediate visual thumbnail previews, file name, and file size before form submission.
   - Provide live reactive calculation previews (expected cost, taxability, and bonus amount) before final submission.
4. **Self-Service First (ESS Empowerment)**:
   - Allow employees and drivers to directly upload, submit, and manage their own claims, HMO applications, and benefit forms through Employee Self-Service (ESS).
   - Display visual, delivery-style progress trackers (`Submitted` → `HR Approved` → `Finance OK` → `In Payslip`) with estimated payout timing.
5. **Approver Efficiency**:
   - Provide 1-click quick filter pills (`[ All ]`, `[ Needs My Action ]`, `[ Waiting > 3 Days ]`, `[ Ready for Next Payroll ]`) on all review tables.
   - Use side-by-side inspection drawers for fast verification of uploaded documents and 1-click approval/rejection.