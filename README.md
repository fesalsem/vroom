<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# CapBay Auto — Test Drive Registration System

A Laravel 13 application built for **CapBay Auto Sdn. Bhd.** to manage test drive registrations, determine promotion eligibility, and calculate loan amounts for car purchases.

This was built as a technical assessment demonstrating service-oriented architecture, state machine patterns, and scalable database design.

---

## Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Frontend | Blade + TailwindCSS |
| Database | MySQL (production) / SQLite (local dev) |
| Testing | PestPHP |
| Auth | Laravel Breeze (Blade) |
| PHP | ^8.3 |

---

## Installation

```bash
# 1. Clone and install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — configure in .env
# For SQLite (default): just touch database/database.sqlite
# For MySQL:
#   DB_CONNECTION=mysql
#   DB_DATABASE=capbay_auto
#   DB_USERNAME=root
#   DB_PASSWORD=

# 4. Migrate and seed
php artisan migrate
php artisan db:seed

# 5. Build frontend assets
npm run build
```

## Default Login

| Field | Value |
|-------|-------|
| Email | `agent@capbayauto.com` |
| Password | `password` |

---

## Running Tests

```bash
# Run everything
php artisan test

# Run specific test files
php artisan test --filter=PromotionEligibilityTest
php artisan test --filter=StateTransitionTest
php artisan test --filter=StateMachineServiceTest
php artisan test --filter=CustomerRegistrationTest
```

There are **77 tests** with **163 assertions** covering unit tests (enum, services) and feature tests (controllers, auth, business logic).

---

## Architecture Overview

I went with a **service-oriented architecture** to keep the controllers thin and the business logic testable in isolation. The structure looks like this:

```
Request → Controller → Service Layer → Model → Database
```

### Services

| Service | Responsibility |
|---------|---------------|
| [`RegistrationService`](app/Services/RegistrationService.php) | Orchestrator — coordinates the other services for create, update, list operations |
| [`PromotionEligibilityService`](app/Services/PromotionEligibilityService.php) | Checks if a customer qualifies for the 15% promotion discount |
| [`LoanCalculationService`](app/Services/LoanCalculationService.php) | Calculates loan amounts and determines approval |
| [`RegistrationStateService`](app/Services/RegistrationStateService.php) | Manages the state machine and logs transitions |

### State Machine

```
Registered → Test Drive Scheduled → Test Drive Completed → Purchased
    ↓                    ↓                      ↓
    └────→ Cancelled ←──┴────────←──────────────┘
```

Both `Purchased` and `Cancelled` are **terminal states** — once reached, no further transitions are allowed. This is enforced by the [`RegistrationStatus`](app/Enums/RegistrationStatus.php) enum's `allowedTransitions()` method.

### Why services instead of putting logic in models?

I wanted to keep the models focused on data (relationships, scopes, casts) and move business rules into dedicated classes. This makes testing easier — I can test `PromotionEligibilityService::isEligible()` without needing to hit a controller route. It also means if the promotion rules change later, I only need to modify one file.

---

## Assumptions I Made

1. **Duplicates are allowed** — The same email can register multiple times. The system doesn't enforce unique emails because a family member might register separately, or someone might want to test drive a different model later.

2. **Car model is fixed to CapBay Vroom** — The public form only offers the CapBay Vroom (RM 200,000). The seeder creates other models (Sedan, SUV, Hatchback, EV) for testing, but the customer-facing form is scoped to one model.

3. **Cancelled registrations don't free promotion slots** — This was a deliberate design decision (explained below in the promotion section).

4. **No authentication for the public form** — Customers don't need to log in to register for a test drive. Only the agent dashboard requires authentication.

5. **Down payment starts at 0** — When a customer first registers, they haven't committed to a down payment yet. The agent updates this later during follow-up.

---

## Money Datatype Explanation

All monetary values are stored as **integer cents** using a `bigint` column.

| Real Amount | Stored As |
|-------------|-----------|
| RM 200,000.00 | `20_000_000` |
| RM 15,000.00 | `1_500_000` |
| RM 0.50 | `50` |

**Why not float/decimal?** Floating-point arithmetic causes precision issues (0.1 + 0.2 = 0.30000000000000004). With integers, `20_000_000 - 4_000_000` always equals exactly `16_000_000`. The [`<x-money>`](resources/views/components/money.blade.php) Blade component handles the display formatting (`RM 200,000.00`).

---

## Promotion Eligibility Reasoning

### The Rules

A customer qualifies for the 15% discount if **all four** conditions are met:

1. Car model is **CapBay Vroom**
2. They are among the **first 10** to register for this model
3. They have paid **≥10% down payment**
4. Their **loan is approved**

### Why cancelled registrations don't free up slots

This was the trickiest design decision. Here's my reasoning:

- **First-come, first-served** — The promotion says "first 10 customers." Slot assignment happens at registration time, not purchase time. Customer B registered 2nd, so they occupy slot 2 regardless of whether they eventually buy.

- **Predictability** — If cancellations freed slots, eligibility could change days later. An agent might tell a customer "sorry, you're 11th," then a week later someone cancels and suddenly they're 10th. That's confusing and hard to communicate.

- **Simplicity** — The queue position is just a count of earlier registrations. No recalculation needed when cancellations happen.

**What I considered instead:** Freeing slots on cancellation. This would mean Customer C (11th) becomes eligible if Customer B (2nd) cancels. I decided against it because it makes the system less predictable.

### Example

| Customer | Slot | Status | Down Payment | Eligible? |
|----------|------|--------|-------------|-----------|
| A | 1st | Registered | 20% | ✅ Yes |
| B | 2nd | Cancelled | 0% | ❌ No |
| C | 11th | Registered | 10% | ❌ Slot 11 |

---

## Scalability Considerations

The seeder creates **50,015 registrations** to test performance. Here's what I did to keep things fast:

- **Database indexes** on `status`, `customer_email`, `created_at`, and a composite index on `(car_model, status, created_at)` for the promotion queue query
- **Batch inserts** in the seeder (500 records at a time) to avoid memory exhaustion
- **Pagination** on the agent listing page (default 15 per page, configurable up to 100)
- **Eager loading** of the `statusLogs` relationship on the detail page to prevent N+1 queries
- **Scope-based querying** on the model so filtering logic is reusable and readable

For even larger datasets (500k+), I'd consider:
- Cursor-based pagination instead of offset-based
- A dedicated search index (Algolia/Meilisearch) for the search bar
- Queueing the promotion eligibility check as a background job

---

## AI Usage Disclosure

I used an AI coding assistant (Roo) to help generate parts of this codebase. Specifically:

- **Initial scaffolding** — The AI helped generate the initial file structure based on my architecture plan
- **Boilerplate** — Migration schemas, factory definitions, Blade component templates
- **Test cases** — The AI suggested test scenarios that I reviewed and adjusted

**Everything was reviewed and tested.** I made sure I understood every line before committing it. The architecture decisions (service layer, state machine design, cancellation policy) were mine — the AI helped with implementation.

### One Example Where AI-Generated Code Was Wrong

The AI initially generated the `PromotionService::getQueuePosition()` method with an incorrectly scoped `orWhere` clause:

```php
// ❌ AI-generated — this counts ALL registrations, not just CapBay Vroom
return Registration::where('car_model', 'CapBay Vroom')
    ->where('created_at', '<', $registration->created_at)
    ->orWhere('created_at', $registration->created_at)
    ->where('id', '<=', $registration->id)
    ->count();
```

The `orWhere` broke the grouping, so it counted non-CapBay Vroom registrations too. I fixed it by properly nesting the conditions:

```php
// ✅ Fixed — properly scoped to CapBay Vroom only
return Registration::where('car_model', 'CapBay Vroom')
    ->where(function ($query) use ($registration) {
        $query->where('created_at', '<', $registration->created_at)
            ->orWhere(function ($q) use ($registration) {
                $q->where('created_at', $registration->created_at)
                    ->where('id', '<=', $registration->id);
            });
    })
    ->count();
```

This was caught by the tests — the queue position test failed because it was returning the wrong number. Good reminder that AI-generated code needs the same scrutiny as any other code.

---

## Project Structure

```
app/
├── Enums/
│   └── RegistrationStatus.php        # 5-state enum with transition rules
├── Exceptions/
│   └── InvalidTransitionException.php # Custom exception for invalid state changes
├── Http/
│   ├── Controllers/
│   │   ├── CustomerRegistrationController.php  # Public form (create, store)
│   │   ├── RegistrationController.php           # Agent CRUD + actions
│   │   └── DashboardController.php              # Agent dashboard stats
│   └── Requests/
│       ├── StoreRegistrationRequest.php         # Public form validation
│       ├── UpdateRegistrationRequest.php        # Down payment + notes
│       ├── UpdateRegistrationStateRequest.php   # Status transition
│       └── ListRegistrationRequest.php          # Filter/sort validation
├── Models/
│   ├── Registration.php              # Main model with scopes + helpers
│   └── RegistrationStatusLog.php     # Audit trail model
└── Services/
    ├── RegistrationService.php        # Orchestrator
    ├── PromotionEligibilityService.php # Promotion logic
    ├── LoanCalculationService.php     # Loan math
    └── RegistrationStateService.php   # State machine

database/
├── factories/
│   └── RegistrationFactory.php       # Factory with 20 state methods
├── migrations/
│   ├── ...create_registrations_table.php
│   └── ...create_registration_status_logs_table.php
└── seeders/
    ├── AdminUserSeeder.php           # agent@capbayauto.com
    └── RegistrationSeeder.php        # 50,015 registrations

resources/views/
├── test-drive/
│   ├── create.blade.php              # Public registration form
│   └── thank-you.blade.php           # Success page
├── agent/registrations/
│   ├── index.blade.php               # Paginated list with filters
│   └── show.blade.php                # Detail view with actions
├── components/
│   ├── status-badge.blade.php        # Color-coded status pills
│   ├── money.blade.php               # RM formatting from cents
│   └── promotion-badge.blade.php     # Eligible/Not Eligible/Not Checked
└── dashboard.blade.php               # Stats dashboard

tests/
├── Pest.php                          # Test configuration
├── Unit/
│   ├── Enums/RegistrationStatusTest.php
│   └── Services/
│       ├── PromotionServiceTest.php
│       ├── LoanServiceTest.php
│       └── StateMachineServiceTest.php
└── Feature/
    ├── CustomerRegistrationTest.php
    ├── AgentRegistrationManagementTest.php
    ├── PromotionEligibilityTest.php
    └── StateTransitionTest.php
```
