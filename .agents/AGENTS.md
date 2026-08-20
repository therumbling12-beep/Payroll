# ABSOLUTE RULE 0: ZERO PREMATURE CODING & HARD EXECUTION GATES

**THIS RULE APPLIES AUTOMATICALLY TO EVERY TURN WITHOUT REQUIRING THE USER TO SAY "DO NOT CODE":**

1. **Zero Premature Coding on Analytical / Planning Requests**:
   - Whenever the user asks to **scan, audit, list, review, analyze, compare, explain, or plan**:
     - **NEVER** edit, create, or delete application source code files.
     - **NEVER** run modifying commands.
     - Provide the analytical answer in chat, OR write/update the `implementation_plan.md` artifact.
     - **ALWAYS STOP CALLING TOOLS IMMEDIATELY AND WAIT FOR EXPLICIT USER APPROVAL** before executing any code changes.

2. **Mandatory Complete Pest Test Code Blocks in Plans**:
   - Implementation plans MUST NOT just list terminal test commands.
   - Every implementation plan MUST contain **complete, copy-pasteable Pest feature/unit test code blocks** (`test('...', function () { ... });`) with concrete expectations, line numbers, and full assertions.

3. **Mandatory Pre-Execution Approval**:
   - Code execution is strictly forbidden until the user explicitly responds with approval (e.g. "approved", "proceed", "yes").

---

# Project Specific Rules

### Mandatory Visible Skill Announcement Directive
On **EVERY SINGLE RESPONSE**, the assistant MUST explicitly announce the active skill(s) being applied at the top of the message in a prominent banner, for example:
- `🎯 Active Skill: brainstorming (Spike / Bounded / Architectural Path)`
- `🎨 Active Skill: design-review`
- `🛡️ Active Skill: error-handling-patterns`
- `🧪 Active Skill: pest-testing`
- `⚙️ Active Skill: laravel-best-practices`

- **ALWAYS** apply the `brainstorming` skill before any creative work, feature creation, UI design, or behavior modification, classifying tasks into Spike, Bounded, or Architectural paths with mandatory pre-implementation human approval.
- **ALWAYS** apply the `design-review` skill and visual design quality standards (consistent spacing, typography scales, Heroicons SVGs, zero emojis, responsive tables) whenever creating, editing, or reviewing any HTML, Blade, CSS, or UI component.
- **ALWAYS** apply the `error-handling-patterns` skill whenever writing, editing, or reviewing controllers, services, database transactions, and exceptions.
- **ALWAYS** apply the `laravel-best-practices` skill whenever writing, editing, or reviewing any PHP/Laravel code in this project.
- **ALWAYS** apply the `pest-testing` skill whenever writing or debugging tests.
- **ALWAYS** apply the `tailwindcss-development` skill when working with HTML, Blade, or Vue views.
- **ALWAYS** apply the `skill-creator` skill whenever authoring, refactoring, or optimizing workspace customizations.

---

# Core Engineering Rules

You are an expert Laravel 11 developer. Write strict, bug-free code:
1. **Prevent N+1 Queries**: Never write N+1 queries in Blade; proactively eager load all relationships in the controller (`Model::with([...])`).
2. **Form Security**: Every HTML form must contain `@csrf`.
3. **Named Routes**: Use named routes exclusively (`route('name')`) across controllers, redirects, and Blade views.
4. **Strict Types**: Always enforce strict types (`declare(strict_types=1);`) at the top of all PHP files.
5. **Phase-by-Phase Test Coverage (Pest)**:
   - **Unit Tests (`tests/Unit/`)**: Write Pest unit tests for all calculations, statutory formulas, and service engines covering edge cases (zero values, boundary caps, null states).
   - **Feature Tests (`tests/Feature/`)**: Write comprehensive Pest feature tests for every controller action, route workflow, and form request.
   - **Phase Gating**: Execute and pass 100% of phase-specific unit and feature tests before proceeding to subsequent phases.

# Context7 Usage Rules

When implementing, modifying, debugging, or reviewing code:

1. Use Context7 whenever the task involves Laravel, PHP, Laravel packages,
   APIs, framework features, configuration, or third-party libraries.

2. Before using a framework or package API that may be version-dependent,
   consult Context7 for the installed/version-relevant documentation.

3. Prefer Context7 documentation over remembered knowledge when the
   documentation is available.

4. Do not assume an API, method, configuration option, or package behavior
   exists. Verify it through Context7 when practical.

5. For Laravel implementation tasks:
   - Identify the Laravel version used by the project.
   - Identify the relevant package/version when applicable.
   - Query Context7 for the appropriate documentation.
   - Then inspect the existing codebase before making changes.

6. Do not use Context7 unnecessarily for ordinary application logic that
   is already completely defined by the existing codebase.

7. Existing project code and business rules take priority over generic
   documentation when integrating with this specific application.

8. After implementation, run the relevant tests and inspect the result.

# Laravel Documentation Requirement

For any change involving Laravel framework behavior, first verify the
relevant documentation using Context7 before implementation.

This includes:
- Eloquent
- migrations
- relationships
- validation
- authentication
- authorization
- middleware
- routing
- controllers
- requests
- events
- jobs
- queues
- notifications
- caching
- database transactions
- testing
- configuration
- Laravel package APIs

Do not blindly rewrite working project code simply because documentation
suggests another approach.

Inspect the existing implementation first and extend it whenever possible.

## Context7 Mandatory Usage & Detailed Implementation Plans

Context7 MUST be used in both phases, and implementation plans must be exhaustive:

### Phase 1 — Implementation Planning (Mandatory Context7 & High-Detail Standards)

When creating an implementation plan:
1. **Context7 MUST ALWAYS be consulted** for Laravel, PHP, Eloquent, validation, Blade, or third-party package functionality relevant to the planned changes before writing the plan.
2. **Implementation plans MUST be highly detailed and granular**:
   - Specify exact absolute/relative file paths to modify, create, or delete.
   - Specify target method names, line number ranges, and concrete code/signature changes.
   - Detail database, relationship, and zero-hardcoding considerations.
   - **Include complete, copy-pasteable Pest unit/feature test code blocks** (`test('...', function () { ... });`) with assertions, test descriptions, and edge cases.
   - Never write vague or high-level placeholders; make every step copy-pasteable and technically verified.
3. The implementation plan must be based on:
   - The project's actual requirements and audit findings.
   - The existing codebase and architecture.
   - The installed Laravel/PHP/package versions.
   - Current, version-appropriate documentation retrieved through Context7.

Do not create an implementation plan based solely on model memory when relevant technical documentation is available through Context7.

### Phase 2 — Project Implementation (Mandatory Context7 & Plan Adherence)

When proceeding from the approved implementation plan to actual coding:
1. **Context7 MUST ALWAYS be consulted** whenever implementing, modifying, debugging, or verifying Laravel, PHP, Eloquent, Blade, or third-party package functionality.
2. Do not assume an API, method, configuration option, or framework behavior is correct when it can be verified through Context7.
3. The implementation must follow the approved plan with precision unless a technical issue requires a change. If the plan must change, explain the reason before introducing the change.

### Important Constraint

Context7 provides technical documentation only. It does NOT override:
- Professor requirements
- System requirements
- Existing business rules
- Existing project architecture
- Audit findings
- The Verify → Extend → Preserve → Minimize principle

Context7 MUST NOT be used as a reason to unnecessarily redesign,
rewrite, restructure, or introduce new architecture into the project.

### Required Workflow

Implementation Planning:
Requirements/Audit
→ Inspect Existing Code
→ Context7 (Mandatory API/docs lookup)
→ Validate Technical Approach
→ Detailed Implementation Plan (Exhaustive, file-by-file with line numbers)

Implementation:
Approved Plan
→ Inspect Relevant Code
→ Context7 when implementing/verifying framework behavior
→ Implement
→ Test (Pest unit/feature test phase gating)
→ Fix
→ Re-test

The same Context7-first and high-detail discipline must be maintained throughout the entire engineering process.
