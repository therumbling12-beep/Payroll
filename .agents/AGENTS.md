# Project Specific Rules

- **ALWAYS** apply the `laravel-best-practices` skill whenever writing, editing, or reviewing any PHP/Laravel code in this project.
- **ALWAYS** apply the `pest-testing` skill whenever writing or debugging tests.
- **ALWAYS** apply the `tailwindcss-development` skill when working with HTML, Blade, or Vue views.

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

## Context7 Mandatory Usage

Context7 MUST be used in both phases:

### Phase 1 — Implementation Planning

When creating an implementation plan, Context7 MUST be consulted for
Laravel, PHP, and third-party package functionality that is relevant to
the planned changes.

The implementation plan must be based on:
1. The project's actual requirements and audit findings.
2. The existing codebase and architecture.
3. The installed Laravel/PHP/package versions.
4. Current, version-appropriate documentation retrieved through Context7.

Do not create an implementation plan based solely on model memory when
relevant technical documentation is available through Context7.

### Phase 2 — Project Implementation

When proceeding from the approved implementation plan to actual coding,
Context7 MUST remain available and MUST be consulted whenever implementing,
modifying, debugging, or verifying Laravel, PHP, or third-party package
functionality.

Do not assume an API, method, configuration option, or framework behavior
is correct when it can be verified through Context7.

The implementation must follow the approved plan unless a technical issue
requires a change. If the plan must change, explain the reason before
introducing the change.

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
→ Context7
→ Validate Technical Approach
→ Implementation Plan

Implementation:
Approved Plan
→ Inspect Relevant Code
→ Context7 when technically relevant
→ Implement
→ Test
→ Fix
→ Re-test

The same Context7-first discipline must be maintained throughout the
entire implementation process, not only during planning.
