# Pure Ride TNVS — Payroll & Benefits System
## Team 4 | Comprehensive System Design Document

**Client:** Pure Ride TNVS / PureRide Corp.
**Team:** Team 4 — Payroll & Benefits
**Document Type:** System Blueprint & Integration Guide
**Bank Partner:** Security Bank Corporation
**Regulatory Body:** LTFRB (Land Transportation Franchising and Regulatory Board)
**Last Updated:** August 2026
**Regulatory References:** SSS Circular 2024-006 | PhilHealth Advisory PA2025-0002 | HDMF Circular No. 460 | Wage Order NCR-27 | LTFRB MC 2026 | DOLE | BIR (TRAIN Law, RA 10963) | RA 11210 (Expanded Maternity Leave)

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Government Contribution Framework (2025–2026)](#2-government-contribution-framework-20252026)
3. [TNVS Driver Income & Trip Computation](#3-tnvs-driver-income--trip-computation)
4. [Minimum Wage Reference](#4-minimum-wage-reference)
5. [Module 1 — Payroll Management](#5-module-1--payroll-management)
6. [Module 2 — Compensation Planning](#6-module-2--compensation-planning)
7. [Module 3 — Claims & Reimbursement](#7-module-3--claims--reimbursement)
8. [Module 4 — HMO & Benefits Administration](#8-module-4--hmo--benefits-administration)
9. [Module 5 — HR Analytics Dashboard](#9-module-5--hr-analytics-dashboard)
10. [Cross-Module Integration Map](#10-cross-module-integration-map)
11. [Employee Self-Service Portal (ESS)](#11-employee-self-service-portal-ess)
12. [Role-Based Access Control (RBAC)](#12-role-based-access-control-rbac)
13. [Approval Workflow Matrix](#13-approval-workflow-matrix)
14. [Security Bank Integration](#14-security-bank-integration)
15. [Philippine Labor Law & Compliance Reference](#15-philippine-labor-law--compliance-reference)
16. [Database Schema Overview](#16-database-schema-overview)
17. [Payslip Structure](#17-payslip-structure)
18. [Open Items & Client Clarification Points](#18-open-items--client-clarification-points)
19. [Implementation Roadmap](#19-implementation-roadmap)
20. [Compliance & Penalties Reference](#20-compliance--penalties-reference)
21. [Appendices](#21-appendices)

---

## 1. System Overview

PureRide Corp. is an LTFRB-accredited Transport Network Company (TNC) operating under the TNVS framework in the Philippines. It manages a fleet of ride-hailing drivers and a corporate back-office workforce.

### 1.1 Employee Types

| Employee Type | Description | Payroll Nature |
|---|---|---|
| **TNVS Drivers (Regular)** | App-based ride-hailing operators on payroll | Variable trip-based pay + fixed base |
| **TNVS Drivers (Boundary)** | Boundary arrangement; boundary fee deducted first | Trip-based net of boundary |
| **Office / Corporate Staff** | Admin, Finance, HR, Operations, IT | Fixed monthly salary, standard benefits |
| **Dispatchers / Coordinators** | Rank-and-File | Daily rate + allowances |
| **Operations Supervisors** | Supervisory | Monthly salary |
| **Mechanics / Fleet Staff** | Fixed monthly + OT | OT tracked from Fleet module |
| **Department Managers** | Managerial | Monthly salary |

> **Critical Note:** TNVS drivers may be classified as employees or independent contractors depending on engagement type. As of August 2026, DOLE has extended Employees' Compensation (EC) Program coverage to TNVS drivers registered under SSS. This affects how benefits, deductions, and HMO are tracked.

### 1.2 System Architecture Data Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        PURE RIDE TNVS SYSTEM                            │
│                                                                          │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────────────────┐   │
│  │ COMPENSATION │───▶│  PERFORMANCE │    │   WORKFORCE / TIME &     │   │
│  │   PLANNING   │◀───│  MANAGEMENT  │    │   ATTENDANCE MODULE      │   │
│  │  (Module 2)  │    │   (Team 3)   │    │  (External Integration)  │   │
│  └──────┬───────┘    └──────────────┘    └────────────┬─────────────┘   │
│         │ Salary Data                                  │ Attendance      │
│         │                                             │ Records         │
│         ▼                                             ▼                 │
│  ┌────────────────────────────────────────────────────────────────┐     │
│  │                   PAYROLL MANAGEMENT (Module 1)                │     │
│  │   [Compute] → [Admin Approval] → [Finance Release]            │     │
│  └──────────────────────────┬─────────────────────────────────────┘     │
│                             │                                            │
│         ┌───────────────────┼───────────────────┐                       │
│         ▼                   ▼                   ▼                       │
│  ┌─────────────┐   ┌─────────────────┐  ┌──────────────┐              │
│  │  CLAIMS &   │   │  HMO & BENEFITS │  │ HR ANALYTICS │              │
│  │REIMBURSEMENT│   │   (Module 4)    │  │  DASHBOARD   │              │
│  │ (Module 3)  │   └─────────────────┘  │  (Module 5)  │              │
│  └─────────────┘                        └──────────────┘              │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │               EMPLOYEE SELF-SERVICE PORTAL (ESS)                 │   │
│  │  [Apply Benefits] [View Payslip] [Claims] [View Incentives]      │   │
│  └──────────────────────────────────────────────────────────────────┘   │
│                              │                                           │
│                              ▼                                           │
│                   ┌─────────────────────┐                               │
│                   │  Security Bank      │                               │
│                   │  Payroll Manager    │                               │
│                   │  (Bulk Crediting)   │                               │
│                   └─────────────────────┘                               │
└──────────────────────────────────────────────────────────────────────────┘
```

### 1.3 System Actors

| Actor | Role in System |
|---|---|
| **Employee / Driver** | Views payslip, applies benefits, submits claims via ESS |
| **Team 3 (Performance)** | Evaluates performance; triggers promotion and merit flags |
| **Payroll Officer** | Initiates payroll computation, reviews output |
| **Payroll Manager** | Reviews and approves payroll computations |
| **HR Admin / Team 4** | HMO & Benefits, Claims approval, HR Analytics |
| **HR Director / HR Manager** | Oversees all HR modules, views analytics dashboard |
| **Administrator / CEO** | Final approval authority for payroll, compensation, benefits |
| **Finance / Treasury** | Releases funds after administrator approval |
| **Budget Officer** | Approves budget for HMO, benefits, incentives |
| **System Administrator** | Configuration, RBAC management |

---

## 2. Government Contribution Framework (2025–2026)

### 2.1 SSS (Social Security System)

**Governing Law:** Republic Act 11199 (Social Security Act of 2018)
**Current Circular:** SSS Circular No. 2024-006
**Effective:** January 2025 (Final scheduled rate increase)

#### Rate Structure

| Party | Rate |
|---|---|
| Employer | 10% of Monthly Salary Credit (MSC) |
| Employee | 5% of Monthly Salary Credit (MSC) |
| **Total** | **15%** |

#### Monthly Salary Credit (MSC) Brackets (Selected)

| Monthly Compensation Range | MSC | Employee Share (5%) | Employer Share (10%) | EC (Employer Only) | Total |
|---|---|---|---|---|---|
| Below ₱5,250 | ₱5,000 | ₱250.00 | ₱500.00 | ₱10.00 | ₱760.00 |
| ₱9,750 – ₱10,249.99 | ₱10,000 | ₱500.00 | ₱1,000.00 | ₱10.00 | ₱1,510.00 |
| ₱14,750 – ₱15,249.99 | ₱15,000 | ₱750.00 | ₱1,500.00 | ₱30.00 | ₱2,280.00 |
| ₱17,750 – ₱18,249.99 | ₱18,000 | ₱900.00 | ₱1,800.00 | ₱30.00 | ₱2,730.00 |
| ₱19,750 – ₱20,249.99 | ₱20,000 | ₱1,000.00 | ₱2,000.00 | ₱30.00 | ₱3,030.00 |
| ₱24,750 – ₱25,249.99 | ₱25,000 | ₱1,250.00 | ₱2,500.00 | ₱30.00 | ₱3,780.00 |
| ₱29,750 – ₱30,249.99 | ₱30,000 | ₱1,500.00 | ₱3,000.00 | ₱30.00 | ₱4,530.00 |
| ₱34,750 and above | ₱35,000 (MAX) | ₱1,750.00 | ₱3,500.00 | ₱30.00 | ₱5,280.00 |

> **EC:** ₱10 if MSC < ₱15,000; ₱30 if MSC ≥ ₱15,000 — Employer only.
> **MPF Note:** For employees with MSC > ₱20,000, the amount exceeding ₱20,000 is subject to the Mandatory Provident Fund (MPF) Program, credited to the employee's MySSS Pension Booster account.

#### SSS Computation Formula

```
MSC = Bracket based on Monthly Basic Salary
Employee SSS Deduction = MSC × 5%
Employer SSS Contribution = MSC × 10%
EC = ₱10 (if MSC < ₱15,000) or ₱30 (if MSC ≥ ₱15,000) — Employer only

Example — Monthly Basic Salary ₱18,000:
  MSC Bracket: ₱17,750–₱18,249.99 → MSC = ₱18,000
  Employee SSS = ₱18,000 × 5% = ₱900.00
  Employer SSS = ₱18,000 × 10% = ₱1,800.00
  EC (Employer) = ₱30.00
  Total Employer Cost = ₱1,800 + ₱30 = ₱1,830.00
```

---

### 2.2 PhilHealth (Philippine Health Insurance Corporation)

**Governing Law:** Republic Act 11223 (Universal Health Care Act)
**Current Advisory:** PhilHealth Advisory PA2025-0002
**Effective:** January 2025

#### Rate Structure

| Parameter | Value |
|---|---|
| Contribution Rate | **5%** of Monthly Basic Salary |
| Employee Share | 2.5% |
| Employer Share | 2.5% |
| Minimum Monthly Salary Floor | ₱10,000 |
| Maximum Monthly Salary Ceiling | ₱100,000 |
| Minimum Total Monthly Contribution | ₱500 (₱250 each) |
| Maximum Total Monthly Contribution | ₱5,000 (₱2,500 each) |

#### PhilHealth Computation Formula

```
If Monthly Basic Salary ≤ ₱10,000:
  Total Premium = ₱500.00 (Employee: ₱250 | Employer: ₱250)

If ₱10,000 < Monthly Basic Salary < ₱100,000:
  Total Premium = Monthly Basic Salary × 5%
  Employee Share = Total Premium ÷ 2
  Employer Share = Total Premium ÷ 2

If Monthly Basic Salary ≥ ₱100,000:
  Total Premium = ₱5,000.00 (Employee: ₱2,500 | Employer: ₱2,500)

Example — Monthly Basic Salary ₱25,000:
  Total PhilHealth Premium = ₱25,000 × 5% = ₱1,250.00
  Employee Share = ₱625.00 | Employer Share = ₱625.00
```

---

### 2.3 Pag-IBIG / HDMF (Home Development Mutual Fund)

**Governing Law:** Republic Act 9679
**Current Circular:** HDMF Circular No. 460
**Effective:** February 2024 (still current as of 2025–2026)

#### Rate Structure

| Monthly Compensation | Employee Rate | Employer Rate |
|---|---|---|
| ₱1,500 and below | 1% | 2% |
| Over ₱1,500 | 2% | 2% |

- Maximum Monthly Fund Salary (MFS): **₱10,000**
- Maximum Employee Monthly Contribution: **₱200** (2% × ₱10,000)
- Maximum Employer Monthly Contribution: **₱200** (2% × ₱10,000)

#### Pag-IBIG Computation Formula

```
If Monthly Salary ≤ ₱1,500:
  Employee Share = Monthly Salary × 1%
  Employer Share = Monthly Salary × 2%

If ₱1,500 < Monthly Salary < ₱10,000:
  Employee Share = Monthly Salary × 2%
  Employer Share = Monthly Salary × 2%

If Monthly Salary ≥ ₱10,000:
  Employee Share = ₱200.00 (fixed cap)
  Employer Share = ₱200.00 (fixed cap)

Example — Monthly Basic Salary ₱18,000:
  Employee Pag-IBIG = ₱200.00 (capped) | Employer = ₱200.00
```

> Employees may opt for higher voluntary contributions. The system should have a field for "Additional Pag-IBIG Voluntary Savings."

---

### 2.4 BIR Withholding Tax (TRAIN Law, RA 10963)

**Reference:** Revenue Regulations No. 11-2018; BIR Revised Withholding Tax Table effective January 1, 2023

#### Annual Income Tax Table

| Annual Taxable Income | Tax Rate |
|---|---|
| ₱0 – ₱250,000 | 0% (Exempt) |
| ₱250,001 – ₱400,000 | 15% of excess over ₱250,000 |
| ₱400,001 – ₱800,000 | ₱22,500 + 20% of excess over ₱400,000 |
| ₱800,001 – ₱2,000,000 | ₱102,500 + 25% of excess over ₱800,000 |
| ₱2,000,001 – ₱8,000,000 | ₱402,500 + 30% of excess over ₱2,000,000 |
| Above ₱8,000,000 | ₱2,202,500 + 35% of excess over ₱8,000,000 |

#### Withholding Tax Computation Steps

```
Step 1: Determine Monthly Taxable Income
  Taxable Income = Gross Income – SSS – PhilHealth – Pag-IBIG
                 – Non-taxable benefits (up to ₱90,000/year for 13th month + bonuses)

Step 2: Annualize Taxable Income
  Annual Taxable Income = Monthly Taxable Income × 12

Step 3: Apply annual tax table → Compute Annual Tax

Step 4: Monthly Withholding Tax = Annual Tax ÷ 12

Example — Monthly Basic ₱35,000:
  SSS = ₱1,350 | PhilHealth = ₱875 | Pag-IBIG = ₱100
  Monthly Taxable Income = ₱35,000 – ₱1,350 – ₱875 – ₱100 = ₱32,675
  Annual Taxable Income = ₱32,675 × 12 = ₱392,100
  Tax = 15% × (₱392,100 – ₱250,000) = ₱21,315/year
  Monthly Withholding Tax = ₱21,315 ÷ 12 = ₱1,776.25
```

---

### 2.5 Complete Deduction Summary (Sample by Salary Level)

| Monthly Basic Salary | SSS (Employee) | PhilHealth (Employee) | Pag-IBIG (Employee) | Total Employee Deductions |
|---|---|---|---|---|
| ₱16,610 (NCR Min Wage) | ₱830.50 | ₱415.25 | ₱200.00 | **₱1,445.75** |
| ₱18,000 | ₱900.00 | ₱450.00 | ₱200.00 | **₱1,550.00** |
| ₱20,000 | ₱1,000.00 | ₱500.00 | ₱200.00 | **₱1,700.00** |
| ₱25,000 | ₱1,250.00 | ₱625.00 | ₱200.00 | **₱2,075.00** |
| ₱30,000 | ₱1,500.00 | ₱750.00 | ₱200.00 | **₱2,450.00** |
| ₱40,000 | ₱1,750.00 | ₱1,000.00 | ₱200.00 | **₱2,950.00** |
| ₱50,000 | ₱1,750.00 | ₱1,250.00 | ₱200.00 | **₱3,200.00** |
| ₱100,000+ | ₱1,750.00 | ₱2,500.00 | ₱200.00 | **₱4,450.00** |

> SSS is capped at MSC ₱35,000 regardless of higher salary. PhilHealth is capped at ₱100,000 salary basis. Pag-IBIG is capped at ₱200 per employee.

---

## 3. TNVS Driver Income & Trip Computation

### 3.1 LTFRB-Approved Fare Matrix (Effective March 19, 2026)

**Source:** LTFRB Memorandum Circular, March 17, 2026

#### Base Fare by Vehicle Type

| Vehicle Type | New Base Fare (March 2026) |
|---|---|
| Hatchback / Subcompact | ₱55 |
| Sedan | ₱65 |
| AUV (Asian Utility Vehicle) | ₱75 |
| Premium TNVS | ₱165 |

#### Pick-Up Fare (Per Kilometer from Passenger — max 5 km)

| Distance | Hatchback/Sedan/AUV | Premium |
|---|---|---|
| 1 km | ₱24 | ₱58 |
| 2 km | ₱48 | ₱116 |
| 3 km | ₱72 | ₱174 |
| 4 km | ₱96 | ₱232 |
| 5 km (MAX) | ₱120 | ₱290 |

> LTFRB prohibits pickup fares beyond 5 km.

#### Per-Kilometer and Per-Minute Charges

| Charge Type | Sedan/AUV | Hatchback | Premium |
|---|---|---|---|
| Per succeeding km | ~₱14.00/km | ~₱14.00/km | ~₱18.00/km |
| Per minute (travel time) | ~₱2.50/min | ~₱2.50/min | ~₱3.00/min |

- **Surge Pricing:** LTFRB caps surge at 2× the base fare.

### 3.2 Sample Trip Fare Computation (Sedan)

**Scenario:** Driver picks up passenger 2 km away, travels 8 km, trip takes 25 minutes.

```
Base Fare:              ₱65.00
Pick-Up Fare (2 km):    ₱48.00
Distance Charge (8 km): ₱14 × 8 = ₱112.00
Time Charge (25 min):   ₱2.50 × 25 = ₱62.50
─────────────────────────────
Gross Trip Fare:        ₱287.50
TNC Commission (20%):  -₱57.50
─────────────────────────────
Driver's Net per Trip:  ₱230.00
```

### 3.3 Driver Daily Gross Income Computation

```
Daily Income = Sum of All Trip Net Earnings in a Day
             + Incentives/Bonuses (TNC platform or company)
             - Fuel Deductions (if company shoulders fuel and deducts)
             - Boundary Fee (if boundary driver arrangement applies)
```

**Example — Full Shift (8 hours, 10 trips):**

| Trip | Gross Fare | TNC Commission (20%) | Net to Driver |
|---|---|---|---|
| Trip 1 | ₱180 | ₱36 | ₱144 |
| Trip 2 | ₱290 | ₱58 | ₱232 |
| Trip 3 | ₱210 | ₱42 | ₱168 |
| Trip 4 | ₱350 | ₱70 | ₱280 |
| Trip 5 | ₱175 | ₱35 | ₱140 |
| Trip 6 | ₱240 | ₱48 | ₱192 |
| Trip 7 | ₱300 | ₱60 | ₱240 |
| Trip 8 | ₱265 | ₱53 | ₱212 |
| Trip 9 | ₱195 | ₱39 | ₱156 |
| Trip 10 | ₱320 | ₱64 | ₱256 |
| **Total** | **₱2,525** | **₱505** | **₱2,020** |

Incentives: ₱300 (TNC daily quest bonus)
**Daily Net Income: ₱2,020 + ₱300 = ₱2,320**

### 3.4 Monthly Driver Income for Statutory Deductions

```
Monthly Gross Income = Average Daily Net Income × Working Days in Month

Example:
  Average Daily Net: ₱2,200 | Working Days: 22
  Monthly Gross Income = ₱2,200 × 22 = ₱48,400

Then apply:
  SSS MSC Bracket: ₱48,400 → MSC = ₱35,000 (MAX) → Employee SSS = ₱1,750
  Employee PhilHealth = ₱48,400 × 2.5% = ₱1,210
  Employee Pag-IBIG = ₱200 (capped)
  Total Deductions = ₱3,160
  Net Take-Home = ₱48,400 – ₱3,160 = ₱45,240
```

> **System Flag:** For drivers under the boundary model, the system must track the daily boundary amount, deduct it from gross trip income, and compute net before applying government deductions.

---

## 4. Minimum Wage Reference

### NCR — 2026

**Governing Order:** Wage Order No. NCR-27 | **Approved:** June 23, 2026

| Sector | 1st Tranche (July 25, 2026) | 2nd Tranche (January 20, 2027) |
|---|---|---|
| Non-Agriculture (private sector) | **₱755/day** | ₱780/day |
| Agriculture / Retail-Service (≤15 workers) | ₱718/day | ₱743/day |

> **Status Note (August 2026):** NCR-27 is subject to a Temporary Restraining Order (TRO). The system must have a **Wage Order Override** setting that HR Admin can activate if the TRO is lifted.

**Monthly Equivalent (NCR, Non-Agriculture):**

```
DOLE standard: Daily Rate × 313 working days ÷ 12 months
  ₱755 × 313 ÷ 12 = ₱19,680.42/month

Using 26-day factor (common payroll practice):
  ₱755 × 26 = ₱19,630/month
```

| Region | Non-Agriculture Daily Rate (2025–2026) |
|---|---|
| NCR (Metro Manila) | ₱755 (₱780 by Jan 2027) |
| CALABARZON | ₱600 |
| Central Luzon (Region III) | ~₱540–₱560 |
| Northern Mindanao (Region X) | ₱500 |
| Caraga (Region XIII) | ₱475 |
| Eastern Visayas (Region VIII) | ₱470 |

> **System Rule:** The payroll system must store the applicable wage order per employee's work location and flag any salary that falls below the current minimum wage.

---

## 5. Module 1 — Payroll Management

### 5.1 Module Overview

The Payroll Management module is the **core computational engine** of the system. It handles all salary computations, government deductions, net pay disbursement, payslip generation, statutory deduction remittance, and 13th month pay processing for all PureRide employees and drivers. It is the **single source of truth for take-home pay** and must always reflect approved rates from Compensation Planning.

### 5.2 Employee Categories & Payroll Types

| Employee Category | Pay Structure | Pay Frequency |
|---|---|---|
| CEO / C-Suite | Fixed Monthly | Monthly |
| Senior Managers / Directors | Fixed Monthly | Monthly |
| Operations / Finance / HR / Admin Staff | Fixed Monthly | Semi-Monthly |
| Dispatchers | Fixed Monthly + Allowances | Semi-Monthly |
| Mechanics / Fleet Staff | Fixed Monthly + OT | Semi-Monthly |
| TNVS Drivers (Regular) | Trip-Based + Fixed Base | Semi-Monthly |
| TNVS Drivers (Boundary) | Trip-Based net of Boundary | Semi-Monthly |
| Janitors / Utility | Daily Rate / Fixed Monthly | Semi-Monthly |

### 5.3 Payroll Period Configuration

| Setting | Options |
|---|---|
| Pay Frequency | Semi-monthly (1–15, 16–EOM), Monthly, Weekly |
| Default Period | Semi-monthly |
| Pay Day | 5th and 20th of each month (configurable) |
| Driver Cut-off | Per-period (e.g., July 1–15) |

### 5.4 Salary Computation Engine

#### 5.4.1 Automated Computation (Default Mode)

The system automatically pulls approved base salary from Compensation Planning and computes:

**Gross Pay Components:**

| Component | Source | Notes |
|---|---|---|
| Basic Salary | Compensation Planning | Locked; cannot be edited without amendment workflow |
| Overtime Pay | DTR / Timekeeping | Regular: 1.25×; Holiday: 2.60×; Rest Day: 1.30× |
| Night Differential | DTR | +10% of basic hourly rate (10 PM–6 AM) |
| Holiday Pay | DOLE Holiday Calendar | Regular: 2×; Special Non-Working: 1.30× |
| Allowances | Compensation Planning | Transport, meal, communication, TNVS operational |
| Driver Incentives | Claims & Reimbursement Module | Ride milestones, bonuses |
| Claims & Reimbursements | Claims Module | Pre-approved reimbursements |

**Statutory Deductions (2026 Rates):**

| Deduction | Rate | Employee Share | Employer Share | Cap |
|---|---|---|---|---|
| SSS | 15% of MSC | 5% | 10% + EC | MSC max: ₱35,000 |
| PhilHealth | 5% | 2.5% | 2.5% | Salary ceiling: ₱100,000 |
| Pag-IBIG (HDMF) | 4% combined | 2% | 2% | Max employee: ₱200/month |
| Withholding Tax | BIR TRAIN Law | 100% employee | — | Based on taxable income |

**Net Pay Formula:**
```
Net Pay = Gross Pay
        – SSS Employee Share
        – PhilHealth Employee Share
        – Pag-IBIG Employee Share
        – Withholding Tax (BIR)
        – Other Authorized Deductions (loans, salary advances, HMO premium, etc.)
        + Approved Incentives
        + Approved Claims & Reimbursements
```

**Taxable Income Formula:**
```
Taxable Income = Gross Pay
               – SSS Employee Share
               – PhilHealth Employee Share
               – Pag-IBIG Employee Share
               – Non-taxable Benefits (up to ₱90,000/year for 13th month + bonuses)
```

#### 5.4.2 Manual Computation (Override Mode)

**When Manual Override is Triggered:**
- Incorrect salary was previously released (underpayment or overpayment)
- Employee was hired or resigned mid-period
- Retroactive salary adjustment from approved counter offer or merit increase
- Data entry correction needed
- System computation error requiring human verification

**Manual Computation Workflow:**
```
Step 1: Payroll Officer identifies the error
        └─► Flags the specific employee record
            └─► Selects "Request Manual Override"
                └─► Enters reason, affected period, and new values

Step 2: System displays SIDE-BY-SIDE comparison
        ├─► Original automated computation
        └─► Proposed manual values (differences highlighted)

Step 3: Payroll Officer submits for Payroll Manager review
        └─► If approved → HR Admin and Finance are notified

Step 4: Correction Disbursement
        ├─► Underpayment → credited in next payroll or immediate bank transfer
        └─► Overpayment → salary deduction scheduled across agreed periods
```

**Override Rules:**
- Only Payroll Officers and above may initiate
- Written justification required (stored in audit log)
- Corrected salary must match the approved rate from Compensation Planning
- All overrides are flagged in the payroll register and visible in the audit trail

### 5.5 Payroll Computation Formulas

**Reference:** Labor Code of the Philippines (Articles 86, 87, 93–94); PD 851; RA 10963

#### A. Basic Pay

```
For Daily-Rate Employees (Drivers, Rank-and-File):
    Basic Pay = Daily Rate × Days Actually Worked
    Hourly Rate = Daily Rate ÷ 8

For Monthly-Rate Employees (Admin, Managers):
    Daily Rate = Monthly Rate ÷ 22 (DOLE Labor Advisory No. 6, Series of 2010)
    Basic Pay (Semi-monthly) = Monthly Rate ÷ 2
```

#### B. Overtime Pay (Article 87, Labor Code)

```
Regular Day OT (beyond 8 hours):     Hourly Rate × 1.25
Rest Day OT (first 8 hours):         Hourly Rate × 1.30
Rest Day OT (beyond 8 hours):        Hourly Rate × 1.69
Regular Holiday OT (beyond 8 hours): Hourly Rate × 2.60
Special Non-Working Holiday OT:      Hourly Rate × 1.69

Example — Driver ₱700/day, 3 hours Regular OT:
    Hourly Rate = ₱700 ÷ 8 = ₱87.50
    OT Pay = ₱87.50 × 1.25 × 3 = ₱328.13
```

#### C. Night Differential (Article 86)

```
Night Differential = 10% additional for work between 10:00 PM – 6:00 AM
Night Diff Pay = Hourly Rate × 0.10 × Night Hours Worked

Example — ₱87.50/hour, 4 night hours:
    Night Diff = ₱87.50 × 0.10 × 4 = ₱35.00
```

#### D. Holiday Pay (Articles 93–94)

```
Regular Holiday (e.g., Christmas, Labor Day):
    If worked:     Daily Rate × 2.00
    If not worked: Daily Rate × 1.00 (still paid)

Special Non-Working Holiday (e.g., Ninoy Aquino Day):
    If worked:     Daily Rate × 1.30
    If not worked: No Pay (No Work, No Pay Rule)

Example — Driver ₱700/day, worked Regular Holiday:
    Holiday Pay = ₱700 × 2.00 = ₱1,400.00
```

#### E. Late and Undertime Deductions

```
Deduction Per Minute = Daily Rate ÷ (8 × 60) = Daily Rate ÷ 480
Absence Deduction = Daily Rate × Number of Absent Days

Example — ₱700/day, arrived 25 minutes late:
    Deduction = ₱700 ÷ 480 × 25 = ₱36.46
```

#### F. 13th Month Pay (PD 851)

```
13th Month Pay = Total Basic Salary Earned (Jan 1 – Dec 31) ÷ 12

Includes: Basic daily/monthly rate ONLY
Excludes: OT pay, allowances, holiday premium, night differential,
          profit sharing, cash equivalent of leave credits

Scenario 1 — Full Year at ₱30,000/month:
    13th Month = (₱30,000 × 12) ÷ 12 = ₱30,000.00

Scenario 2 — Started July (6 months):
    13th Month = (₱30,000 × 6) ÷ 12 = ₱15,000.00

Scenario 3 — Mid-Year Salary Change:
    Basic ₱25,000 (Jan–Jun) + ₱30,000 (Jul–Dec)
    13th Month = ((₱25,000 × 6) + (₱30,000 × 6)) ÷ 12 = ₱27,500.00

Scenario 4 — Daily Rate Driver (₱700/day, 22 days/month, 12 months):
    13th Month = ₱700 × 22 × 12 ÷ 12 = ₱15,400.00

Tax Exemption: Up to ₱90,000 combined with other bonuses (TRAIN Law)
Release Deadline: On or before December 24 (DOLE requirement)
```

### 5.6 Complete Payroll Workflow

```
Step 1: REQUEST TIME & ATTENDANCE DATA
    Payroll Officer selects period (e.g., July 1–15)
    System pulls from Workforce / Time & Attendance module:
        ✔ Days worked / Hours rendered / Overtime hours
        ✔ Late/Undertime deductions / Absences
        ✔ Night differential hours / Holiday worked flags
        ✔ Trip count (for drivers)

Step 2: FETCH COMPENSATION DATA
    System pulls from Compensation Planning module:
        ✔ Daily rate / Monthly rate / Pay grade
        ✔ Allowances / Effective date of last salary change

Step 3: COMPUTE PAYROLL
    System runs all computation formulas
    Draft payroll register generated per employee

Step 4: PAYROLL OFFICER REVIEW
    Reviews computed payroll register
    Flags anomalies (negative net pay, missing attendance)
    Makes adjustments with audit log

Step 5: SUBMIT FOR ADMINISTRATOR APPROVAL
    System routes to Administrator
    Administrator reviews: Gross Pay, Total Deductions, Net Pay, Total Cost
    Administrator → APPROVE or REJECT with remarks

Step 6: REJECTED PAYROLL
    Returns to Payroll Officer with remarks
    Payroll Officer corrects and resubmits

Step 7: APPROVED → ROUTE TO FINANCE
    Finance prepares fund transfer / check release
    Finance marks payroll as RELEASED
    Payslips auto-generated and pushed to ESS portal
    Employee receives notification of payslip availability

  ⚠ NOTE: Payroll does NOT return to Payroll Management after Finance release.
           Finance handles final disbursement independently.
```

#### Payroll State Machine

```
[DRAFT]
    ↓ Payroll Officer submits for review
[FOR REVIEW — PAYROLL OFFICER]
    ↓ Payroll Officer confirms computation
[SUBMITTED FOR ADMIN APPROVAL]
    ↓ ←── Admin rejects with remarks ──┐
[ADMIN REVIEW]──────────────────────────▶ [RETURNED TO PAYROLL OFFICER]
    ↓ Admin approves                              ↓ Corrections made
[ADMIN APPROVED]                            [RESUBMITTED]
    ↓ Forwarded to Finance
[FOR FINANCIAL RELEASE]
    ↓ Finance disburses funds
[RELEASED / PAID]
    ↓
[PAYSLIPS GENERATED → PUSHED TO ESS]
    ↓
[ARCHIVED]
```

### 5.7 13th Month Pay Module

**Approval & Release Workflow:**

```
STAGE 1 — PAYROLL PREPARATION
└─► Payroll Officer runs automated 13th month pay computation
    ├─► System uses actual basic salary records for Jan–Nov
    ├─► Pro-rates for employees hired/resigned during the year
    └─► Generates 13th Month Pay Report (draft)

STAGE 2 — ADMIN APPROVAL
└─► Payroll Manager submits to HR Admin / CEO
    ├─► Admin reviews: headcount, individual amounts, total payout
    └─► APPROVES → system locks computation | REJECTS → returns with comments

STAGE 3 — FINANCE BUDGET REQUEST
└─► System auto-generates Budget Request to Finance / CFO
    ├─► Includes: Total payout amount, target release date
    └─► Finance APPROVES budget | REJECTS → provides alternate schedule

STAGE 4 — PAYROLL RELEASE
└─► Payroll Officer generates final payslips with 13th month line item
    ├─► For Bank employees: uploads disbursement file to Security Bank
    └─► For Cash employees: generates cash voucher

STAGE 5 — ACKNOWLEDGMENT
└─► Employee receives payslip notification and digitally acknowledges receipt
```

**Status Tracker:**

| Status | Meaning |
|---|---|
| `DRAFT` | Payroll Officer computing |
| `PENDING_ADMIN` | Submitted; awaiting Admin/CEO approval |
| `ADMIN_APPROVED` | Admin signed off; budget request sent to Finance |
| `PENDING_FINANCE` | Finance evaluating budget availability |
| `FINANCE_APPROVED` | Budget confirmed; ready for release |
| `RELEASED` | Salary credited; payslips generated |
| `REJECTED` | Returned to previous stage with comments |

### 5.8 Payment Mode Management

**Two modes per employee profile:**

**Cash Payment:**
- Payroll generates a Cash Voucher listing all cash-paid employees
- Employee signs payroll register upon receipt
- System records confirmation with cashier and employee signatures

**Security Bank Transfer:**
- Bulk file upload via SBC Business Online Banking (Maker-Authorizer workflow)
- Employee must be enrolled as a beneficiary before first credit
- See Section 14 for full Security Bank integration details

### 5.9 Integration with Fleet & Transportation (Driver Trips)

```
Fleet & Transportation Module sends to Payroll:
  driver_id, trip_date, trip_id
  gross_fare_collected, tnc_commission_deducted
  platform_incentives, toll_reimbursement (non-income)
  fuel_deduction (if applicable)
  boundary_amount (if boundary driver)
  net_driver_income_for_trip

Driver Payroll Computation Flow:
1. Pull All Trip Records for Pay Period
2. Sum: Total Gross Fares for Period
3. Deduct: TNC Commission (usually 20–21%)
4. Add: Platform Incentives / Bonuses
5. Deduct: Boundary Fees (if applicable)
6. Deduct: Fuel Costs (if company-shoulder arrangement)
7. Result = Monthly Gross Driver Income
8. Apply SSS / PhilHealth / Pag-IBIG based on monthly gross
9. Add: Approved Reimbursements from Claims Module
10. Net Pay = Gross Income – Government Deductions – Other Deductions
```

### 5.10 Deduction Register

| Deduction Code | Description | Type |
|---|---|---|
| SSS-EE | SSS Employee Contribution | Mandatory Government |
| PH-EE | PhilHealth Employee Share | Mandatory Government |
| HDMF-EE | Pag-IBIG Employee Share | Mandatory Government |
| BIR-WHT | BIR Withholding Tax | Mandatory Government |
| LOAN-SSS | SSS Salary Loan | Employee-authorized |
| LOAN-HDMF | Pag-IBIG Multi-Purpose Loan | Employee-authorized |
| ADV | Cash Advance from Company | Employee-authorized |
| HMO-EE | HMO Premium Employee Share | Benefits |
| ABSENT | Unauthorized Absence | Attendance-driven |
| TARDY | Tardiness Deduction | Attendance-driven |
| BOUNDARY | Daily Boundary Fee | Driver-specific |
| FUEL-DED | Fuel Cost Recovery | Driver-specific |

### 5.11 Payroll Features Checklist

- [ ] Semi-monthly and monthly payroll processing
- [ ] Automatic SSS, PhilHealth, Pag-IBIG computation with latest tables
- [ ] BIR withholding tax computation (TRAIN Law)
- [ ] Overtime calculator (Regular, Rest Day, Holiday)
- [ ] Night differential tracker
- [ ] Holiday pay calendar integration (linked to Philippine Presidential Proclamations)
- [ ] Late/undertime deduction calculator
- [ ] 13th month pay running ledger (auto-computed, year-end summary)
- [ ] Driver trip incentive integration
- [ ] Loan amortization deduction management
- [ ] Manual override workflow with dual-approval and audit trail
- [ ] Payroll register generation (PDF / Excel export)
- [ ] Payslip generation (printable PDF + ESS push)
- [ ] Multi-level approval workflow
- [ ] Government remittance reports (SSS R3, PhilHealth RF-1, Pag-IBIG MCRF)
- [ ] BIR alphalist generation (1604-C)
- [ ] Payroll correction / off-cycle payroll support
- [ ] Security Bank disbursement file generation
- [ ] Minimum wage compliance guard (auto-flag)

---

## 6. Module 2 — Compensation Planning

### 6.1 Module Overview

The Compensation Planning module establishes the salary structure and grade system for all positions in Pure Ride — from entry-level to C-suite — and powers automated salary recommendations for new hires and promotions. It integrates with Team 3 (Performance Management) for promotion-based pay changes, with Applicant Management for counter-offer computation, and feeds approved salary data to the Payroll Management module.

### 6.2 Pay Grade / Salary Band Structure

| Pay Grade | Job Level | Sample Positions | Salary Band (Monthly) |
|---|---|---|---|
| PG-1 | Entry Level | Utility Worker, Janitor, Messenger | ₱16,000 – ₱20,000 |
| PG-2 | Junior | Data Encoder, Customer Service Rep, Entry-Level Driver | ₱18,000 – ₱24,000 |
| PG-3 | Intermediate | Dispatcher, Billing Clerk, Driver (Regular) | ₱20,000 – ₱30,000 |
| PG-4 | Senior | Senior Dispatcher, Accounting Staff, Senior Driver | ₱28,000 – ₱40,000 |
| PG-5 | Supervisor | Fleet Supervisor, Payroll Supervisor, Sr. Coordinator | ₱38,000 – ₱55,000 |
| PG-6 | Manager | Operations Manager, Finance Manager, HR Manager | ₱50,000 – ₱80,000 |
| PG-7 | Senior Manager | Regional Manager, Fleet Director | ₱75,000 – ₱120,000 |
| PG-8 | Executive | VP Operations, COO, CFO | ₱120,000 – ₱200,000+ |
| PG-9 | C-Suite | CEO, President | ₱200,000+ |

> **Minimum Wage Guard:** PG-1 minimum must never fall below the DOLE-mandated minimum wage (currently ₱755/day NCR = approximately ₱19,630/month). System auto-flags any employee below applicable regional minimum wage.

### 6.3 Salary Determination Factors

When onboarding a new employee, the Compensation Planning module computes the offer salary using:

```
Salary Score = (Education Weight × 0.25)
             + (Years Experience Weight × 0.35)
             + (Relevant Skills Weight × 0.20)
             + (Market Benchmark Weight × 0.10)
             + (Internal Equity Weight × 0.10)
```

**Education Level Scoring:**

| Level | Score |
|---|---|
| High School Graduate | 1 |
| Vocational / TESDA NC II | 2 |
| College Graduate | 3 |
| Bachelor's with Honors | 4 |
| Master's Degree | 5 |
| Doctoral Degree | 6 |

**Years of Experience Scoring:**

| Years | Score |
|---|---|
| 0–1 year | 1 |
| 1–3 years | 2 |
| 3–5 years | 3 |
| 5–8 years | 4 |
| 8–12 years | 5 |
| 12+ years | 6 |

**Band Placement by Score Result:**

| Score Result | Band Placement |
|---|---|
| Low (1–2) | Minimum of Band |
| Mid-Low (2–3) | 25th Percentile |
| Mid (3–4) | 50th Percentile (Midpoint) |
| Mid-High (4–5) | 75th Percentile |
| High (5–6) | Maximum of Band |

### 6.4 Tenure-Based Salary Progression

| Tenure Milestone | Action | Increment |
|---|---|---|
| End of Probation (6 months) | Regularization | +5% to +10% |
| Year 1 Performance Review | Merit Increase | 3% to 8% based on rating |
| Year 2 | Merit Increase | 3% to 8% |
| Year 3 | Performance + Longevity | 5% to 10% |
| Year 5 | Service Award + Increase | 8% to 15% |
| Year 10 | Long Service Increase | 10% to 20% |
| Promotion (any year) | Grade Promotion | New PG minimum or 15% increase, whichever is higher |

### 6.5 Compensation Planning Workflow

```
Step 1: SALARY GRADE SETUP (Initial / Annual Review)
    HR Manager → defines / updates pay grade matrix
    System → stores effective date of each grade revision

Step 2: EMPLOYEE SALARY ASSIGNMENT
    HR creates new employee record
    Compensation Planning assigns Pay Grade and Starting Salary
    System validates:
        ✔ Salary ≥ Regional Minimum Wage
        ✔ Salary within Pay Grade band
        ✔ Effective date is set

Step 3: PROMOTION-TRIGGERED SALARY ADJUSTMENT
    Performance Module (Team 3) → sends promotion flag:
        - Employee ID, Current/New Pay Grade, Performance Score, Effective Date
    Compensation Planning reviews and sets new salary within new grade band
    System → updates record with New PG, New Salary, Effective Date, Approved By

Step 4: MERIT INCREASE (Non-Promotion Adjustment)
    HR Manager initiates salary adjustment form
    Compensation Planning Officer approves
    System → updates record and flags Payroll Management

Step 5: SALARY DECREASE / CORRECTION
    HR Manager initiates with justification
    Requires dual approval (HR + Admin/CEO)
    System logs all decreases with mandatory remarks

Step 6: DATA FEED TO PAYROLL
    Approved compensation records → auto-pushed to Payroll Management
    Historical salaries retained for 13th month ledger and audit
```

### 6.6 Counter Offer Module

#### Mode A — Automated Computation

```
Inputs used:
  - Position Level (from Salary Grade structure)
  - Years of Experience (from applicant/employee profile)
  - Educational Attainment
  - Current Market Rate (configurable market rate table)
  - Internal Equity (checks existing employees in same role/band)
  - Budget Band (Finance-approved salary budget per department)

Recommended Salary Range = [Min, Mid, Midpoint, Max] based on Salary Grade

Conversion Examples:
  Bachelor's Degree + 3–5 years exp → 80th–85th percentile of grade
  Master's Degree + 5+ years exp    → 90th–95th percentile of grade
  Internal transfer + performance ≥ 4/5 → midpoint + 10%

Counter Offer Limit Logic:
  Max Counteroffer = MIN(Pay Grade Maximum, Competing Offer × 1.10)
  If above Pay Grade Maximum → Escalate to HR Manager for exception approval
  If accepted salary creates wage distortion → Flag for internal equity review
```

#### Mode B — Manual Computation

| Component | Entry Type |
|---|---|
| Basic Salary | Manual input |
| Allowances (transport, meal, comms) | Manual input per line item |
| Signing Bonus (if any) | Manual input |
| Probationary vs. Regular Rate | System flags if probationary rate differs |
| Total Cost to Company (CTC) | Auto-computed from inputs + statutory employer shares |

#### Counter Offer → Financial Budget Integration

```
Counter Offer Submitted
    └─► System creates Budget Impact Record in Financial Module:
        ├─► Monthly CTC (salary + employer SSS + PhilHealth + Pag-IBIG)
        ├─► Annual CTC | 13th month pay projection
        └─► Status: PENDING_FINANCE_VALIDATION

Finance reviews:
    ├─► WITHIN BUDGET → Finance marks BUDGET_APPROVED → proceed to finalize offer
    └─► OVER BUDGET → Finance rejects with revised ceiling → HR revises
```

#### Counter Offer Approval Workflow

```
1. HR / Recruiter drafts counter offer (automated or manual)
2. Department Head reviews and endorses
3. Finance validates budget impact → APPROVED or REJECTED
4. HR Director / CEO approves final offer letter
5. Offer is presented to candidate
6. Candidate accepts → Compensation Planning record created → Payroll updated
   Candidate declines → Record archived; Payroll unchanged
```

### 6.7 Merit Increase Module

#### Merit Matrix (configurable by HR Admin)

| Performance Rating | Merit Increase % |
|---|---|
| 5.0 — Outstanding | 8%–12% |
| 4.0–4.9 — Exceeds Expectations | 5%–8% |
| 3.0–3.9 — Meets Expectations | 2%–5% |
| 2.0–2.9 — Needs Improvement | 0%–2% |
| Below 2.0 — Unsatisfactory | 0% (may trigger PIP) |

**Computation Formula:**

```
Merit Increase Amount = Current Basic Salary × Merit Increase %
New Basic Salary = Current Basic Salary + Merit Increase Amount
New Monthly CTC = New Basic Salary + Allowances
                + Employer SSS Share + Employer PhilHealth Share
                + Employer Pag-IBIG Share
Annual Impact = New Monthly CTC × 12 + Projected 13th Month Pay
```

**Merit → Financial Budget Integration:**

```
Merit Computation Batch (per department)
    └─► System sends Budget Impact Request to Financial Module
        ├─► Per-employee additional monthly cost
        ├─► Department-level total additional annual cost
        └─► Status: PENDING_FINANCE_VALIDATION

Finance reviews → APPROVED or sends revised budget ceiling
    └─► If APPROVED → HR finalizes merit increases
                   → Effective date set → Payroll uses new rate
```

### 6.8 Retroactive Pay Computation

```
Retroactive Pay = (New Daily Rate – Old Daily Rate) × Days Worked Before Cut-off

Example:
  Promotion effective July 15; Payroll cut-off July 1–15
  Days from effective date in cut-off: 1 day
  Old Daily Rate: ₱818 | New Daily Rate: ₱1,000
  Retroactive = (₱1,000 – ₱818) × 1 = ₱182.00
  → Included in next payroll cycle
```

### 6.9 Integration with Applicant Management

When Applicant Management identifies a strong candidate, Compensation Planning:
1. Retrieves the Pay Grade of the open position
2. Computes the Recommended Offer Salary based on credentials
3. Checks Internal Equity — ensures offer doesn't exceed or severely undercut existing employees
4. Generates Counter-Offer Range if candidate has competing offers
5. Returns: `{ recommended_offer, min_offer, max_offer, internal_equity_flag }`

### 6.10 Compensation Planning Features Checklist

- [ ] Pay grade / salary band configuration (PG-1 to PG-9)
- [ ] Minimum wage validation (by region / DOLE order)
- [ ] Salary history per employee (full audit trail)
- [ ] Promotion-triggered adjustment workflow (linked to Performance module)
- [ ] Counter offer automation (automated + manual modes)
- [ ] Merit increase computation with configurable matrix
- [ ] Merit → Finance budget integration
- [ ] Counter offer → Finance budget integration
- [ ] Retroactive pay calculator
- [ ] Effective-date management
- [ ] Allowance management (transport, meal, TNVS operational)
- [ ] Total compensation summary (salary + benefits + allowances)
- [ ] Compensation change approval workflow with dual approver support
- [ ] Real-time sync feed to Payroll on approval
- [ ] Compensation summary reports and cost-of-workforce reports

---

## 7. Module 3 — Claims & Reimbursement

### 7.1 Module Overview

The Claims & Reimbursement module manages all non-salary monetary transactions payable to employees and drivers — including trip-based incentives, performance incentives, fuel reimbursements, toll fees, vehicle maintenance claims, medical reimbursements, and maternity benefits. All approved claims appear as **line items in the payslip** and are included in the net pay computation.

### 7.2 Module Navigation

```
Claims & Reimbursement
├── 7.2.1  Employee Reimbursements (receipted expenses)
├── 7.2.2  Driver Incentives
│   ├── Ride Milestone Incentives
│   └── Performance Bonus Dashboard
├── 7.2.3  Maternity Leave Benefits
├── 7.2.4  Other Claims (medical, travel, tools)
└── 7.2.5  Claims Status Tracker
```

### 7.3 Types of Claims

| Claim Type | Who Can Claim | Approver |
|---|---|---|
| **Trip / Ride Milestone Incentive** | Drivers | Operations Supervisor |
| **Performance Incentive** | All employees | HR Manager |
| **Fuel Reimbursement** | Drivers | Operations Supervisor |
| **Toll Fee Reimbursement** | Drivers | Operations Supervisor |
| **Vehicle Maintenance Reimbursement** | Drivers | Fleet Manager |
| **Medical Reimbursement** | All employees | HR Manager |
| **Travel / Transportation Allowance** | Admin / Field staff | Department Head |
| **Communication Allowance** | Supervisors and above | HR Manager |
| **Training / Seminar Reimbursement** | All employees | HR Manager |
| **Business Travel (Meals, Lodging)** | All employees | Department Head |

### 7.4 Driver Incentives — Ride Milestone System

**Purpose:** TNVS drivers earn additional incentives based on completed rides within a defined period (weekly, monthly, or campaign-based). The system tracks completed rides per driver, computes the applicable incentive tier, and routes payout to payroll.

#### Incentive Tier Configuration (Sample — Fully Configurable)

| Tier | Minimum Completed Rides | Incentive Amount |
|---|---|---|
| Tier 1 | 20 rides | ₱500 |
| Tier 2 | 40 rides | ₱1,000 |
| Tier 3 | 60 rides | ₱1,500 |
| Tier 4 | 80 rides | ₱2,500 |
| Tier 5 | 100+ rides | ₱4,000 |

> **Business Rule:** Tiers are non-cumulative by default (driver earns incentive for highest tier reached). This is configurable — cumulative mode can be enabled.
> **Example (Cumulative OFF):** Driver completes 45 rides → qualifies for Tier 2 = ₱1,000.
> **Example (Cumulative ON):** Driver completes 45 rides → ₱500 + ₱1,000 = ₱1,500.

Additional incentive tiers:
```
Monthly Consistency Bonus:
  If driver hits Tier 2+ for [N] consecutive days → additional ₱[Amount]

Perfect Attendance + Incentive:
  No absences for the month + Tier 2+ → additional ₱[Amount]
```

#### Driver Incentive Qualification Dashboard

| Column | Data |
|---|---|
| Driver ID | Unique identifier |
| Driver Name | Full name |
| Rides This Period | Real-time count (from app / operations API) |
| Qualified Tier | Auto-detected based on rides count |
| Incentive Amount | Auto-computed |
| Status | `QUALIFIED` / `PENDING` / `DISBURSED` |
| Payroll Cycle | Which payroll period this will be included in |

**Qualified Driver Incentive Flow:**
```
Operations system / app sends ride completion data to HRIS
    └─► System aggregates per driver per period
        └─► System flags drivers crossing a tier threshold
            └─► Payroll Officer reviews qualified list
                └─► Bulk approve → amounts queued for next payroll
                    └─► Reflected in driver payslip as "Ride Incentive"
```

**Incentive Rules:**
- Only **completed trips** count (cancelled/rejected rides excluded)
- Rides must be completed within the defined incentive period
- Driver must be **in good standing** (no active LTFRB or PureRide suspension)
- Non-taxable up to the ₱90,000 threshold combined with 13th month pay (TRAIN Law)

### 7.5 Fuel Reimbursement Validation Logic

```
Expected Fuel Usage = Trip Distance (km) ÷ Vehicle Fuel Efficiency (km/L)
Expected Fuel Cost = Expected Fuel Usage × Current Pump Price per Liter

If Claimed Amount ≤ Expected Fuel Cost × 1.15 (15% tolerance):
  Auto-Approve
Else:
  Flag for Fleet Manager review
```

**Reimbursable Expense Types:**

| Expense Type | Auto-Validated? | Notes |
|---|---|---|
| Fuel | Yes (via odometer) | Cross-checked against km traveled in trip logs |
| Toll | Yes (via trip route data) | Auto-matched to actual toll charges |
| Parking | Manual review | Must have receipt + trip reference |
| Vehicle Maintenance | Manual + Fleet Manager | Major repairs require COO approval |
| Meals (Out-of-Town) | Manual review | With travel memo reference |
| Lodging (Out-of-Town) | Manual review | With travel authority |

### 7.6 Maternity Leave Benefits Module

**Legal Basis:** RA 11210 — Expanded Maternity Leave Law

#### Eligibility
- Female employees / drivers registered under SSS
- At least **3 monthly SSS contributions** in the 12-month period preceding the semester of delivery
- Applicable to live birth, miscarriage, and emergency termination of pregnancy

#### Leave Duration

| Case | Paid Leave Days | Solo Parent Additional |
|---|---|---|
| Live birth (normal / CS) | 105 days | +15 days |
| Miscarriage / ETP | 60 days | — |
| Transfer to partner / spouse | Up to 7 days may be transferred |  |

#### SSS Maternity Benefit Computation

PureRide **advances** the SSS benefit to the employee, then claims reimbursement from SSS.

```
Step 1: Identify Semester of Contingency
        (6-month period covering the quarter of delivery)
        Example: Delivery August 2026 → Semester: July–December 2026

Step 2: Identify Qualifying Period
        (12 months immediately before the semester)
        Example: July 2025–June 2026

Step 3: From the qualifying period, identify 6 HIGHEST Monthly Salary Credits (MSC)
        Max MSC for maternity: ₱20,000 (as of 2026)

Step 4: Compute Average Daily Salary Credit (ADSC)
        ADSC = Sum of 6 Highest MSCs ÷ 180

Step 5: Compute Maternity Benefit
        Normal/CS Delivery:   ADSC × 105 (Max ₱70,000)
        Solo Parent:          ADSC × 120 (Max ₱80,000)
        Miscarriage/ETP:      ADSC × 60  (Max ₱40,000)
```

#### Position-Based Salary Differential

```
Salary Differential = Full Pay for Leave Period – SSS Maternity Benefit
  Full Pay for Leave = (Basic Salary ÷ 22 working days) × Leave Days + Allowances

  If Full Pay > SSS Benefit → PureRide pays the difference
  If Full Pay ≤ SSS Benefit → No additional payment from PureRide
```

#### Maternity Benefit Workflow (Team 2 → Payroll)

```
Employee notifies HR (at least 60 days before leave, per RA 11210)
    └─► Team 2 (HR Benefits) opens Maternity Benefit Record
        └─► System auto-computes: SSS benefit, salary differential, leave schedule

Team 2 submits for HR Head approval
    └─► HR Head approves → Payroll notified
        └─► Payroll advances SSS benefit in applicable payroll cycle
            └─► Salary differential (if any) added as separate payroll line item
                └─► PureRide files SSS reimbursement claim post-leave
```

### 7.7 Claims Submission & Approval Workflow

```
Step 1: EMPLOYEE SUBMITS CLAIM VIA ESS
    Employee selects "File a Claim"
    Fills in: Claim Type, Amount, Date incurred, Description, Attachments (receipts)
    Submits → claim status: SUBMITTED

Step 2: IMMEDIATE SUPERVISOR REVIEW
    Supervisor receives notification
    → APPROVE (routes to Finance)
    → REJECT with remarks (returns to employee)
    → ESCALATE (for large amounts, routes to HR Manager)

Step 3: HR MANAGER REVIEW (performance incentives / escalated claims)
    HR Manager approves or rejects with documented reason

Step 4: APPROVED CLAIM → PAYMENT PROCESSING
    a) Included in next Payroll cycle: amount added to payroll register
    b) Off-cycle reimbursement: separate release via Finance

Step 5: EMPLOYEE VIEWS STATUS IN ESS
    Claim status: Submitted / Under Review / Approved / Rejected / Paid
    Approved amount, expected payment date, rejection reason (if rejected)

Step 6: PAYMENT RELEASED
    Finance marks claim as PAID
    Employee receives notification
    Claim appears in ESS payslip or as separate reimbursement record
```

### 7.8 Payslip Reflection

```
REIMBURSEMENTS (Non-Taxable):
  Fuel (13 Jun – 28 Jun): ₱1,240.00
  Toll Charges:            ₱380.00
  Total Reimbursements:   ₱1,620.00
```

> **Tax Rule:** Reimbursements for actual business expenses are non-taxable per BIR rules, provided they are documented with official receipts and supported by a legitimate business purpose.

### 7.9 Taxability of Claims (BIR Reference — TRAIN Law)

```
NON-TAXABLE (De Minimis Benefits — up to stated limits):
    ✔ Medical cash allowance (dependents): up to ₱1,500/semester
    ✔ Rice subsidy: up to ₱2,000/month or 1 sack of 50kg rice
    ✔ Clothing/uniform allowance: up to ₱6,000/year
    ✔ Laundry allowance: up to ₱300/month
    ✔ Employee achievement awards: up to ₱10,000/year
    ✔ Actual medical benefits: up to ₱10,000/year

TAXABLE (subject to withholding tax if exceeding above):
    ✗ Excess amounts beyond de minimis limits
    ✗ Performance cash incentives (above exempt threshold)
    ✗ Profit-sharing cash payments
```

### 7.10 Claims & Reimbursement Features Checklist

- [ ] Claim submission via ESS with document upload (photos, PDF receipts)
- [ ] Ride milestone incentive auto-computation (from TNVS dispatch/app data)
- [ ] Performance incentive computation (from Performance Module)
- [ ] Configurable incentive tier system (cumulative / non-cumulative toggle)
- [ ] Fuel reimbursement auto-validation (odometer + pump price cross-check)
- [ ] Maternity benefit computation (SSS formula + salary differential)
- [ ] Multi-level approval workflow per claim type
- [ ] Claim status tracking (real-time updates in ESS)
- [ ] Budget validation before claim approval
- [ ] Claims register per period (by type, by employee)
- [ ] Rejection management with required remarks
- [ ] Off-cycle payment support
- [ ] Receipt / document archive per claim
- [ ] Duplicate claim detection (same amount, same date, same type)
- [ ] Claim limits per type per period (configurable)
- [ ] Government-compliant taxability tagging (non-taxable vs. taxable)

---

## 8. Module 4 — HMO & Benefits Administration

### 8.1 Module Overview

The HMO & Benefits Administration module manages all non-salary benefits — mandatory government benefits, company-provided HMO, TNVS-specific accident insurance, and statutory benefits such as 13th Month Pay, leave entitlements, and service incentive leave. It tracks eligibility, handles enrollment workflows, monitors compliance deadlines, and reflects benefit premiums in payslips.

> **Regulatory Context (August 2026):** DOLE and the Employees' Compensation Commission (ECC) have formally extended the EC Program to TNVS drivers registered under SSS. PureRide must ensure all drivers are SSS-registered and contributions are properly remitted.

### 8.2 Mandatory Benefits (Required by Law)

| Benefit | Legal Basis | Computation |
|---|---|---|
| 13th Month Pay | PD 851 | 1/12 of total basic salary earned during calendar year |
| Service Incentive Leave (SIL) | Labor Code Art. 95 | 5 days paid leave per year |
| SSS Benefits | RA 11199 | Sickness, maternity, disability, retirement, death |
| PhilHealth Benefits | RA 11223 | Hospitalization, outpatient, Konsulta package |
| Pag-IBIG Benefits | RA 9679 | Housing loans, MPL, calamity loans |
| Separation Pay | Labor Code | Depends on reason for separation |
| Retirement Pay | RA 7641 / RA 8558 | At least 22.5 days per year of service |
| EC Program (Drivers) | ECC / DOLE Aug 2026 | Work-related injury/death benefits via SSS |

### 8.3 Benefits Catalog — Pure Ride TNVS

| Benefit | Eligibility | Coverage | Provider |
|---|---|---|---|
| **HMO (Health Insurance)** | All regularized employees | Annual MBL per grade | Accredited HMO provider |
| **Group Life Insurance** | All regularized employees | Per salary grade multiple | Insurance provider |
| **SSS (Mandatory)** | All employees / drivers | Per SSS schedule | Social Security System |
| **PhilHealth (Mandatory)** | All employees / drivers | Per PhilHealth rules | PhilHealth |
| **Pag-IBIG (Mandatory)** | All employees / drivers | Per HDMF schedule | Pag-IBIG Fund |
| **Annual Physical Exam (APE)** | All employees | Per grade / per year | Accredited clinic |
| **Bereavement Assistance** | All employees | Fixed amount (configurable) | Company |
| **TNVS Accident Coverage** | Drivers only | Per LTFRB requirement | Insurance provider |
| **Driver Incentive Programs** | Drivers | Trip-based (see Module 3) | Company |
| **EC Program** | SSS-registered drivers | Work-related injury / death | SSS / ECC |

### 8.4 HMO Configuration Setup

The following fields must be filled by the client during system setup:

```
HMO Configuration:
  [ ] Company has HMO provider?
  If YES:
    HMO Provider Name: ___________
    HMO Plan Type: [ ] Room & Board | [ ] Outpatient | [ ] Comprehensive
    Annual Limit per Employee: ₱__________
    Who shoulders the premium?
      [ ] 100% Company
      [ ] Shared (Company __% | Employee __%)
    HMO Coverage Start: After [ ] 3 months | [ ] 6 months | [ ] 1 year
    Dependent Coverage: [ ] Yes | [ ] No
    Max Dependents Covered: ____
    Premium per Employee: ₱______ / month
    Premium per Dependent: ₱______ / month
```

### 8.5 HMO Maximum Benefit Limits (MBL) Per Grade — Sample

> To be configured by Pure Ride management per chosen HMO provider.

| Pay Grade | MBL Per Year | Room and Board | Dependent Coverage |
|---|---|---|---|
| PG-1–2 (Drivers / Entry) | ₱100,000 | Semi-Private | Not included |
| PG-3–4 | ₱150,000 | Semi-Private | 1 dependent (additional premium) |
| PG-5 | ₱200,000 | Private | 2 dependents |
| PG-6 | ₱300,000 | Private | 3 dependents |
| PG-7 and above | ₱500,000 | Suite | 4 dependents |

### 8.6 HMO Enrollment Workflow

```
Step 1: EMPLOYEE APPLIES VIA ESS
    Employee selects "Apply for HMO Enrollment"
    Fills in: Personal info, dependents to cover, preferred HMO plan
    Uploads: Birth Certificate (dependents), Marriage Certificate (spouse), ID photo
    Submits → status: APPLICATION SUBMITTED

Step 2: HR TEAM 4 REVIEW
    HR Team 4 validates eligibility (regularization date, grade, documents)

Step 3: BUDGET REQUEST TO BUDGET OFFICER
    HR Team 4 sends budget request:
        - Employee Name/ID, HMO Plan, Annual Premium Cost, Effective Date
    Budget Officer reviews → APPROVED: budget allocated | REJECTED: HR notifies employee

Step 4: HMO PROVIDER ENROLLMENT
    HR Team 4 submits enrollment to HMO provider
    HMO card issued / e-card generated

Step 5: EMPLOYEE RECEIVES HMO DETAILS IN ESS
    ESS shows: Provider Name, Member ID, Coverage Type, MBL,
    Accredited Hospitals, Digital HMO Card, Effectivity/Expiry Date

Step 6: ANNUAL RENEWAL
    System sends renewal reminder 30 days before expiry
    HR Team 4 processes renewal; budget request resubmitted
    Employee confirms dependents (can add/remove)
```

### 8.7 TNVS Driver Insurance & Accident Coverage

#### LTFRB Baseline Requirements

- Minimum **₱4 million in passenger accident protection per vehicle**
- Standard Compulsory Third Party Liability (CTPL) insurance

#### Proposed Driver HMO/Insurance Module Design

**Driver Insurance Profile (per driver):**

| Field | Description |
|---|---|
| Insurance Provider | HMO provider name (e.g., Intellicare, Maxicare, PhilCare) |
| Policy Type | Personal Accident / Group HMO |
| Coverage Amount | e.g., ₱500,000 per incident |
| Premium Per Month | Total monthly premium |
| Driver Contribution | % of salary deducted (e.g., 3%) |
| Employer Contribution | Remaining premium absorbed by PureRide |
| Policy Start / Expiry Date | Auto-alert 30 days before expiry |
| Beneficiary | Listed in driver profile |

**Premium Deduction Flow:**

```
Monthly Premium = (Driver Basic Salary × 3%)  ← Employee Share
                + Employer Complement (if any)

Driver's 3% Deduction:
  ├─► Auto-computed from approved basic salary (from Compensation Planning)
  ├─► Appears in payslip as "HMO/Insurance Premium Deduction"
  └─► Passes to Benefits module → remitted to insurance provider
```

#### Accident Claims Workflow

```
STAGE 1: INCIDENT REPORTING
└─► Driver or next-of-kin reports to PureRide HR/Operations
    └─► HR opens Accident Incident Record
        ├─► Date, time, location, nature of injury
        ├─► Police blotter reference number
        └─► Hospital/clinic where driver was treated

STAGE 2: INSURANCE COORDINATION
└─► Benefits Officer contacts insurance provider
    └─► Submits Incident Report → Insurance issues Case Reference Number

STAGE 3: HOSPITALIZATION SUPPORT
└─► If hospitalized:
    ├─► PureRide may shoulder initial hospital bills
    └─► Finance records as Advance Payment Against Insurance Claim

STAGE 4: REIMBURSEMENT / SETTLEMENT
└─► Insurance provider processes claim
    ├─► Claim paid to PureRide (reimbursement) or directly to hospital
    └─► Benefits module records settlement and closes incident

STAGE 5: DRIVER RECOVERY & RETURN TO WORK
└─► Medical clearance uploaded to driver profile
    └─► Driver's payroll status updated (sick leave, paid/unpaid)
        └─► SSS EC Program claim filed in parallel if applicable
```

### 8.8 SSS Employees' Compensation (EC) Program

DOLE confirmed (August 3, 2026) TNVS drivers registered under SSS are eligible for EC Program:

| Benefit | Coverage |
|---|---|
| Medical Benefits | Hospital, doctor, medicine for work-related injuries |
| Income Benefit | Cash allowance while unable to work (disability) |
| Death Benefit | Pension for dependents if driver dies from work-related cause |
| Funeral Benefit | ₱30,000 funeral assistance |
| Rehabilitation | Physical therapy, prosthetics, etc. |

**System Requirements:**
- Track each driver's SSS registration and contribution status
- Auto-flag if a driver is not SSS-registered (blocks incentive disbursement until resolved)
- Allow HR to file and track EC Program claims per driver

### 8.9 Government-Mandated Benefits Compliance Dashboard

| Benefit | Rate | Remittance Deadline |
|---|---|---|
| SSS | 15% (10% employer, 5% employee) | 15th of following month |
| PhilHealth | 5% (2.5% each) | 10th of following month |
| Pag-IBIG | 4% (2% each, max ₱200 employee) | 10th of following month |
| BIR Withholding Tax | Per TRAIN Law | 10th of following month (eFPS) |
| 13th Month Pay | PD 851 | On or before December 24 |

```
BENEFITS COMPLIANCE DASHBOARD — [Month, Year]
┌──────────────┬────────────┬──────────────┬─────────────┐
│  Agency      │ Due Date   │ Total Amount │ Status      │
├──────────────┼────────────┼──────────────┼─────────────┤
│  SSS         │ Sept 15    │ ₱XXX,XXX.XX  │ ⏳ PENDING  │
│  PhilHealth  │ Sept 10    │ ₱XXX,XXX.XX  │ ✅ REMITTED │
│  Pag-IBIG    │ Sept 10    │ ₱XX,XXX.XX   │ ✅ REMITTED │
│  BIR (WHT)   │ Oct 10     │ ₱XXX,XXX.XX  │ ✅ FILED    │
└──────────────┴────────────┴──────────────┴─────────────┘
```

> **System Feature:** Auto-generate government remittance reports (SSS R3, PhilHealth RF-1, HDMF MCRF, BIR 1601-C) ready for upload, with auto-reminder notifications 7 days before each deadline.

### 8.10 Other Company Benefits (Configurable)

| Benefit | Configurable Options |
|---|---|
| Rice Allowance | ₱1,000–₱2,000/month (non-taxable up to ₱2,000/month) |
| Transportation Allowance | Amount + taxability toggle |
| Clothing/Uniform Allowance | Annual amount |
| Medical Allowance | Annual ceiling |
| Perfect Attendance Bonus | Amount per month with zero absences |
| Loyalty Bonus | By tenure year milestone |
| Performance Bonus | % of annual salary based on performance rating |

### 8.11 HMO & Benefits Features Checklist

- [ ] ESS-based benefit application (HMO, Group Life, APE)
- [ ] Document upload for enrollment requirements
- [ ] Budget request workflow (approval before enrollment)
- [ ] HMO provider integration (API or manual upload)
- [ ] Dependent management (add/remove with document tracking)
- [ ] Digital HMO card in ESS
- [ ] Accredited hospital/clinic directory
- [ ] HMO utilization tracking per employee
- [ ] Annual renewal management with auto-alerts (30 days before expiry)
- [ ] Benefit entitlement by pay grade (MBL table)
- [ ] TNVS Driver accident insurance tracking
- [ ] Accident claims workflow (incident → insurance → settlement)
- [ ] SSS EC Program filing tracker per driver
- [ ] Government-mandated benefits compliance tracker
- [ ] Mandatory benefit compliance remittance calendar with alerts
- [ ] Auto-generate SSS R3, PhilHealth RF-1, HDMF MCRF, BIR 1601-C
- [ ] Life events management (marriage, new dependent, separation)
- [ ] Benefits enrollment calendar with deadlines
- [ ] Benefits cost reporting (annual HMO premium by department)
- [ ] Budget utilization report (Benefits vs. Budget allocation)

---

## 9. Module 5 — HR Analytics Dashboard

### 9.1 Module Overview

The HR Analytics Dashboard is the **intelligence layer** of the system. It aggregates data from all four operational modules (Payroll, Compensation Planning, Claims & Reimbursement, HMO & Benefits) plus Performance Management and Workforce, transforming raw data into **actionable insights** for HR Managers, Finance, and Senior Management. It is a **read-only** reporting layer — it presents insights but does not perform any transactions.

### 9.2 Dashboard Layout Overview

```
╔══════════════════════════════════════════════════════════════╗
║  PURE RIDE TNVS — HR ANALYTICS DASHBOARD                    ║
║  Logged in as: [HR Manager Name]    Date: [Today's Date]     ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  [Headcount] [Payroll Cost] [Attendance Rate] [Turnover]    ║
║  Top KPI Cards                                               ║
╠══════════════════════════════════════════════════════════════╣
║                          ║                                   ║
║  Payroll Trend Chart     ║  Driver Performance               ║
║  (Monthly/Semi-monthly)  ║  Heatmap                          ║
║                          ║                                   ║
╠══════════════════════════╬═══════════════════════════════════╣
║  Headcount by Dept.      ║  Claims Summary                   ║
║  (Donut Chart)           ║  (Bar Chart by type)              ║
║                          ║                                   ║
╠══════════════════════════╩═══════════════════════════════════╣
║  PENDING APPROVALS (Quick Action Panel)                      ║
║  [X Payrolls] [X Claims] [X HMO Applications] [X Benefits]  ║
╚══════════════════════════════════════════════════════════════╝
```

### 9.3 Key Performance Indicators (KPIs)

#### A. Workforce KPIs

| KPI | Formula | Frequency |
|---|---|---|
| Total Headcount | Count of active employees | Real-time |
| Headcount by Type | Drivers / Admin / Operations / Management | Real-time |
| New Hires | Employees hired in current month | Monthly |
| Separations (Resigned/Terminated) | Employees separated in current month | Monthly |
| Employee Turnover Rate | (Separations ÷ Average Headcount) × 100 | Monthly |
| Average Tenure | Sum of all tenures ÷ Headcount | Monthly |
| Positions Vacant | Open headcount vs. approved headcount | Real-time |

**Turnover Rate Computation:**
```
Turnover Rate = (Number of Separations in Period ÷ Average Headcount) × 100
Average Headcount = (Beginning Headcount + Ending Headcount) ÷ 2

Example — January: 85 employees start, 88 end, 3 resigned:
    Average Headcount = (85 + 88) ÷ 2 = 86.5
    Turnover Rate = (3 ÷ 86.5) × 100 = 3.47%

Industry benchmark for TNVS: target below 5%/month
```

#### B. Payroll KPIs

| KPI | Formula | Frequency |
|---|---|---|
| Total Payroll Cost | Sum of all net pays released in period | Per payroll |
| Average Salary by Grade | Sum of salaries in grade ÷ count | Monthly |
| Payroll-to-Revenue Ratio | Total payroll cost ÷ Total revenue | Monthly |
| OT Cost | Total overtime pay released | Per payroll |
| Government Contributions Cost | SSS + PhilHealth + Pag-IBIG (employer share) | Monthly |
| 13th Month Liability | Running total of 13th month accrual | Monthly |
| Payroll Accuracy Rate | (Corrections ÷ Total payroll entries) × 100 | Per payroll |

#### C. Attendance KPIs

| KPI | Formula | Frequency |
|---|---|---|
| Attendance Rate | (Days Worked ÷ Expected Days) × 100 | Monthly |
| Absenteeism Rate | (Absent Days ÷ Expected Days) × 100 | Monthly |
| Late Frequency | Count of late occurrences per employee | Monthly |
| Undertime Deduction Total | Sum of undertime deductions | Per payroll |
| AWOL Incidents | Count of Absent Without Leave tags | Monthly |

#### D. Driver Performance KPIs (TNVS-Specific)

| KPI | Formula | Frequency |
|---|---|---|
| Average Daily Trip Count | Total trips ÷ Active driver-days | Daily/Monthly |
| Average Gross Fare per Driver | Total gross fares ÷ Active drivers | Daily/Monthly |
| Trip Incentive Cost | Total trip incentive released | Per payroll |
| Top Performing Drivers | Ranked by trip count or rating | Monthly |
| Driver Utilization Rate | (Drivers with trips ÷ Total active drivers) × 100 | Daily |
| Fleet Productivity | Total trips ÷ Total vehicles | Monthly |
| Incident Rate | Accidents or complaints per 1,000 trips | Monthly |

#### E. Claims KPIs

| KPI | Formula | Frequency |
|---|---|---|
| Total Claims Submitted | Count by period | Per period |
| Total Claims Approved | Count and amount | Per period |
| Claims Approval Rate | (Approved ÷ Submitted) × 100 | Per period |
| Average Claim Processing Time | Avg. days from submission to approval | Monthly |
| Claims by Type | Breakdown by claim category | Monthly |
| Rejected Claims Rate | (Rejected ÷ Submitted) × 100 | Monthly |
| Fuel Cost as % of Driver Income | Total fuel reimbursements ÷ Total driver gross income | Monthly |

#### F. Benefits & HMO KPIs

| KPI | Formula | Frequency |
|---|---|---|
| HMO Enrollment Rate | (Enrolled ÷ Eligible) × 100 | Monthly |
| HMO Utilization Rate | Claims filed ÷ Enrolled members | Monthly |
| HMO Cost per Employee | Total HMO premium ÷ Enrolled employees | Annual |
| Benefits Budget Utilization | Actual spend ÷ Budget allocation | Monthly |
| Pending HMO Applications | Count in queue | Real-time |
| Leave Utilization | SIL, VL, SL consumed vs. accrued | Monthly |
| 13th Month Accrual Tracker | Running balance per employee | Monthly |

### 9.4 Standard Reports

| Report Name | Data Source | Frequency | Format |
|---|---|---|---|
| Payroll Summary Report | Payroll Module | Per payroll period | PDF / Excel |
| Government Contribution Report | Payroll Module | Monthly | PDF |
| Government Remittance Files (SSS R3, PhilHealth RF-1, HDMF MCRF) | Payroll Module | Monthly | Government format |
| BIR Monthly Alphalist (1604-C) | Payroll Module | Monthly | BIR format |
| 13th Month Pay Computation | Payroll Module | November | PDF / Excel |
| Driver Productivity & Income Report | Fleet + Payroll | Monthly | PDF / Excel |
| Headcount Report | HR Master Data | Monthly | PDF / Excel |
| Turnover Analysis | HR Master Data | Monthly | PDF / Chart |
| Compensation Structure Report | Compensation Module | Quarterly | Excel |
| Cost of Workforce Report | All Modules | Monthly | PDF |
| Payroll Cost vs. Budget | Finance + Payroll | Monthly | Dashboard |
| Claims Register | Claims Module | Per period | Excel |
| HMO Enrollment List | Benefits Module | As needed | PDF / Excel |
| Benefits Utilization Report | Benefits Module | Monthly | PDF / Excel |
| Minimum Wage Compliance Report | Payroll Module | Per payroll | PDF |

### 9.5 Dashboard Views by User Role

| Role | Dashboard Access |
|---|---|
| CEO / President | Full company-wide view, all modules |
| CFO / Finance Manager | Payroll costs, government remittances, budget vs. actual |
| HR Manager | All HR modules, headcount, compensation, benefits |
| Operations Manager | Driver productivity, fleet performance, claims |
| Payroll Officer | Payroll processing view only, deductions register |
| Fleet Manager | Driver income, trip stats, reimbursements |
| Employee (Self-Service) | Own payslip, leave balance, government contribution history |
| Department Head | Own department headcount, cost, claims |

### 9.6 HR Analytics Dashboard Features Checklist

- [ ] Role-specific dashboards (HR Manager, Finance, Admin, CEO)
- [ ] Real-time headcount and payroll KPI cards
- [ ] Payroll trend line chart (monthly/semi-monthly comparison)
- [ ] Department-level cost breakdown
- [ ] Driver performance heatmap (trips vs. target per driver per day)
- [ ] Turnover trend analysis with industry benchmarks
- [ ] Attendance heatmap (who was absent, what dates)
- [ ] Claims pipeline visualization (submitted → approved → paid)
- [ ] 13th month liability tracker (running balance, projected year-end)
- [ ] Government contribution remittance calendar with alerts
- [ ] Budget utilization meters (per department, per benefit type)
- [ ] Minimum wage compliance flags dashboard
- [ ] Alerts and notifications center:
    - Payroll pending approval
    - HMO renewal due in 30 days
    - Employees on AWOL
    - Budget utilization > 80%
    - Minimum wage violation flags
    - Government remittance deadlines approaching
- [ ] Custom report builder (date range, department, employee type)
- [ ] Export all reports to PDF and Excel
- [ ] Scheduled report email delivery (auto-send monthly reports)
- [ ] Drill-down capability (click a KPI → see individual records)
- [ ] Mobile-responsive dashboard

---

## 10. Cross-Module Integration Map

### 10.1 Data Flow Matrix

```
FROM ──────────────────────────────────────────── TO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

COMPENSATION PLANNING ────────────────────────────────────────────
  ├── Approved Salary Rate ──────────────────────► PAYROLL (read-only)
  ├── Counter Offer CTC ─────────────────────────► FINANCIAL MODULE (budget)
  └── Merit Increase Amount ─────────────────────► FINANCIAL MODULE (budget)

CLAIMS & REIMBURSEMENT ───────────────────────────────────────────
  ├── Approved Driver Incentives ────────────────► PAYROLL (add to pay)
  ├── Approved Employee Reimbursements ──────────► PAYROLL (add to pay)
  └── Maternity Benefit Amount ──────────────────► PAYROLL (add to pay)

HMO & BENEFITS ───────────────────────────────────────────────────
  ├── HMO Premium Deduction ─────────────────────► PAYROLL (deduction)
  └── SSS EC Program Status ─────────────────────► PAYROLL (compliance)

PAYROLL MANAGEMENT ───────────────────────────────────────────────
  ├── Final Net Pay ─────────────────────────────► FINANCIAL MODULE (expense)
  ├── 13th Month Pay (approved) ─────────────────► FINANCIAL MODULE (budget)
  ├── Disbursement File ─────────────────────────► SECURITY BANK
  └── Payslip ────────────────────────────────────► ESS PORTAL

PERFORMANCE MODULE (Team 3) ──────────────────────────────────────
  └── Promotion flag, performance score ──────────► COMPENSATION PLANNING

WORKFORCE / TIME & ATTENDANCE ────────────────────────────────────
  └── Days worked, OT hours, absences, late ──────► PAYROLL MANAGEMENT

FINANCIAL MODULE ─────────────────────────────────────────────────
  ├── Budget Approval ───────────────────────────► COMPENSATION PLANNING
  ├── Budget Approval ───────────────────────────► PAYROLL (13th month)
  └── Funds Confirmation ────────────────────────► SECURITY BANK

HR ANALYTICS DASHBOARD ───────────────────────────────────────────
  └── Read-only aggregated view from ALL modules (no write-back)
```

### 10.2 API Integration Points

| Integration | Type | Frequency | Notes |
|---|---|---|---|
| Time & Attendance → Payroll | Automated pull | Each payroll cut-off | REST API or file-based |
| TNVS Dispatch/App → Claims | Automated push | Daily / per period | Trip count, ratings |
| Fleet Management → Payroll | Automated push | Per payroll cut-off | Trip logs, fuel data |
| HMO Provider → Benefits | Manual / API | Monthly | Enrollment confirmation |
| SSS → Payroll | Reference table | On circular release | Contribution table updates |
| PhilHealth → Payroll | Reference table | On circular release | Rate adjustments |
| Pag-IBIG → Payroll | Reference table | On circular release | Contribution update |
| BIR → Payroll | Reference table | On regulation update | Tax table updates |
| Security Bank → Finance | Batch file | Per payroll release | Bank-specific format |
| ESS Portal → All Modules | Real-time read | On login | Push notifications |

---

## 11. Employee Self-Service Portal (ESS)

### 11.1 ESS Features Per Module

| Section | Features |
|---|---|
| **My Profile** | View/edit personal info, emergency contacts, government IDs |
| **My Payslip** | View/download payslips per period, YTD summary |
| **My Attendance** | View attendance records, leave balance, late/absence log |
| **My Benefits** | Apply for HMO, view HMO card, dependent list, coverage |
| **My Claims** | Submit claims, upload receipts, track claim status |
| **My Incentives** | View trip incentives (drivers), performance incentives |
| **My Compensation** | View current salary, salary history, pay grade |
| **13th Month** | View running 13th month accrual, projected amount |
| **Notifications** | Payslip ready, claim approved/rejected, HMO renewal |
| **Documents** | Download COE, payslip history, benefit documents |

### 11.2 ESS Access Matrix

| Feature | Driver | Admin Staff | Supervisor | Manager | HR | Payroll | Admin |
|---|---|---|---|---|---|---|---|
| View Payslip | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| View Attendance | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Apply Benefits | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Submit Claims | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| Approve Claims | — | — | ✔ | ✔ | ✔ | — | ✔ |
| View Payroll Register | — | — | — | — | ✔ | ✔ | ✔ |
| Approve Payroll | — | — | — | — | — | — | ✔ |
| View All Employee Data | — | — | — | ✔ (dept) | ✔ | ✔ | ✔ |
| Analytics Dashboard | — | — | — | ✔ (dept) | ✔ | partial | ✔ |

---

## 12. Role-Based Access Control (RBAC)

| Role | Payroll | Compensation | Claims | HMO & Benefits | Financial |
|---|---|---|---|---|---|
| **Payroll Officer** | Full access | Read-only | Read + Queue | Read-only | Read-only |
| **Payroll Manager** | Full + Approve | Read-only | Approve | Read-only | Read-only |
| **HR Admin (Team 4)** | View only | Full access | Approve | Full access | View budget only |
| **HR Director** | View + Override | Full + Approve | Full + Approve | Full + Approve | View only |
| **Finance Officer** | View net pay | Budget validation | Approve claims | View premiums | Full access |
| **CFO** | View summary | Approve budget | Approve budget | Approve budget | Full access |
| **Department Head** | View own team | View own team | Approve own team | View own team | View own budget |
| **Employee (Self)** | View own payslip | — | Submit claims | View own coverage | — |
| **Driver** | View own payslip | — | View incentives | View own coverage | — |
| **CEO / Admin** | Full + Final approval | Final approval | Final approval | Final approval | Full access |
| **System Administrator** | Configuration | Configuration | Configuration | Configuration | Configuration |

---

## 13. Approval Workflow Matrix

| Transaction | Initiator | Reviewer 1 | Reviewer 2 | Final Approver | Finance Required? |
|---|---|---|---|---|---|
| Regular Payroll | Payroll Officer | Payroll Manager | HR Admin | CEO (optional) | No |
| Manual Salary Override | Payroll Officer | Payroll Manager | HR Director | HR Director | Yes (if override > threshold) |
| 13th Month Pay | Payroll Officer | Payroll Manager | Admin/CEO | CEO | Yes — budget release |
| Counter Offer | HR / Recruiter | Dept Head | HR Director | CEO | Yes — before finalizing |
| Merit Increase | Payroll/HR | Dept Head | HR Director | CEO | Yes — budget validation |
| Driver Incentive Payout | Operations | Payroll Officer | Payroll Manager | HR Admin | No |
| Employee Claim | Employee | Dept Head | HR Admin | Finance | Yes |
| Maternity Benefit | Team 2 (HR Benefits) | HR Head | Payroll | — | No (SSS-funded) |
| HMO Enrollment | HR Admin | Budget Officer | — | HR Director | Yes |
| Accident Insurance Claim | HR / Benefits Officer | HR Director | Finance | CFO | Yes |

---

## 14. Security Bank Integration

### 14.1 Security Bank Payroll Facility Details

Security Bank Corporation uses a **Payroll Manager** platform for corporate salary crediting with a **Maker-Authorizer Workflow** (two-level authorization required before funds are released).

| Feature | Description |
|---|---|
| Disbursement Method | Bulk file upload (CSV/XML) via SBC Business Online Banking |
| Workflow | Maker-Authorizer (two-level authorization) |
| Enrollment | Each employee must be enrolled as a beneficiary before first credit |
| Account Type | Security Bank Easy Savings Payroll Account |
| Crediting Speed | Same-day crediting if submitted before bank cutoff |
| Confirmation | SMS alert to employee upon credit; confirmation to employer |
| File Format | SBC-prescribed format (to be confirmed with Security Bank's corporate team) |

### 14.2 Employee Bank Onboarding Process

```
Step 1: HR creates employee profile in HRIS
        └─► Mode of payment: set to "Security Bank"
Step 2: HR collects Security Bank account details from employee
        ├─► Account Name (must match legal name)
        ├─► Account Number (10 digits)
        └─► Branch
Step 3: HR submits enrollment to SBC Payroll Manager (via bank portal)
        └─► SBC validates account
            ├─► VALID → Enrollment confirmed; status: ENROLLED
            └─► INVALID → HR notifies employee to open SBC payroll account
Step 4: Employee opens SBC Easy Savings Payroll Account at nearest branch
        ├─► Requirements: 1 valid ID, company employment certificate
        └─► Account enrolled; linked to PureRide's payroll facility
```

### 14.3 Payroll Disbursement File Specification

**File Format (SBC standard — confirm with bank):**

```csv
SEQ_NO, EMPLOYEE_ID, ACCOUNT_NAME, ACCOUNT_NUMBER, AMOUNT, REFERENCE_NUMBER, REMARKS
1, EMP001, JUAN DELA CRUZ, 1234567890, 25000.00, PR-20260815-001, AUG 2026 SALARY
2, EMP002, MARIA SANTOS, 0987654321, 18500.00, PR-20260815-001, AUG 2026 SALARY
```

**Reference Number Format:** `PR-[YYYYMMDD]-[BATCH_SEQ]`
Example: `PR-20260815-001`

### 14.4 Reconciliation Process

```
After SBC credits salaries:
1. Download SBC crediting confirmation report (from bank portal)
2. HRIS auto-matches against payroll register by:
   ├─► Account number
   ├─► Amount
   └─► Reference number
3. Matched records → Status: CREDITED ✅
4. Unmatched records → Status: FAILED ❌ → HR investigates
5. Payroll register is closed; Finance records the expense
```

---

## 15. Philippine Labor Law & Compliance Reference

### 15.1 Key Laws and Regulations

| Law / Regulation | Coverage |
|---|---|
| **Labor Code of the Philippines (PD 442)** | Employment standards, wages, OT, holidays |
| **RA 10963 (TRAIN Law)** | Income tax reform; BIR withholding tax |
| **RA 11199 (SSS Act of 2018)** | SSS contribution rates and schedules |
| **RA 11223 (Universal Health Care Act)** | PhilHealth contribution rates |
| **RA 9679 (HDMF Law)** | Pag-IBIG contribution rules |
| **PD 851** | 13th Month Pay Law |
| **RA 11210** | Expanded Maternity Leave (105 days) |
| **RA 9504** | Minimum wage exemption for personal employees |
| **DOLE DO No. 174** | Contracting and subcontracting rules |
| **LTFRB MC 2015-015 and amendments** | TNVS franchise and operations |
| **LTFRB MC 2019-036** | TNVS operational compliance |
| **SSS Circular 2024-006** | Updated SSS contribution table (15%, effective 2025) |
| **PhilHealth Advisory PA2025-0002** | PhilHealth premium at 5% (2026) |
| **HDMF Circular No. 460** | Pag-IBIG contribution rules |
| **BIR Revenue Regulations No. 11-2018** | TRAIN Law implementing rules |
| **DOLE RTWPB Regional Orders** | Minimum wage per region |
| **Wage Order NCR-27** | NCR minimum wage ₱755/day (July 2026) |

### 15.2 Mandatory Remittance Deadlines

| Contribution | Remittance Deadline |
|---|---|
| **SSS** | 15th of following month (based on last digit of Employer ID) |
| **PhilHealth** | 10th–15th of the following month |
| **Pag-IBIG** | 10th–15th of the following month |
| **BIR Withholding Tax** | 10th of following month (eFPS) or 15th (non-eFPS) |
| **13th Month Pay** | On or before December 24 |

---

## 16. Database Schema Overview

```sql
-- employees
employee_id, full_name, position_id, pay_grade_id, department_id,
employment_type, start_date, regular_date, region_code,
base_salary, daily_rate, employment_model (regular/boundary/owner-driver),
mode_of_payment, bank_account_number, bank_account_name,
sss_number, philhealth_number, pagibig_number, tin_number

-- positions
position_id, title, pay_grade_id, min_salary, max_salary, department_id

-- pay_grades
grade_id, grade_name, min_salary, max_salary

-- attendance_records
record_id, employee_id, date, time_in, time_out, hours_worked,
ot_hours, is_absent, leave_type, is_paid_leave

-- trip_records (from Fleet module)
trip_id, driver_id, vehicle_id, trip_date, gross_fare,
tnc_commission, platform_incentives, pickup_km,
toll_charges, boundary_deduction, net_driver_income

-- payroll_runs
run_id, pay_period_start, pay_period_end, status, approved_by, approved_at

-- payroll_line_items
line_id, run_id, employee_id, gross_pay, sss_employee, philhealth_employee,
pagibig_employee, withholding_tax, hmo_premium_ee, other_deductions,
reimbursements, incentives, net_pay,
sss_employer, philhealth_employer, pagibig_employer, ec_contribution

-- government_contribution_tables
table_type (SSS/PH/HDMF), effective_date, bracket_from, bracket_to,
employee_rate, employer_rate, employee_amount, employer_amount

-- claims
claim_id, employee_id, trip_id, claim_type, amount, receipt_ref,
status, submitted_date, approved_date, approved_by, payroll_run_id

-- driver_incentives
incentive_id, driver_id, period_start, period_end, rides_completed,
qualified_tier, incentive_amount, status, payroll_run_id

-- benefits_config
benefit_id, name, type, amount, taxable, applicable_grade_ids, effective_date

-- hmo_config
provider_name, plan_type, annual_limit, employee_premium,
employer_premium, coverage_start_months, max_dependents

-- hmo_enrollments
enrollment_id, employee_id, plan_id, start_date, expiry_date,
status, dependents_count, member_id, hmo_card_url

-- compensation_history
record_id, employee_id, old_salary, new_salary, pay_grade_id,
change_reason, effective_date, approved_by, change_type (merit/promotion/correction)

-- payslips
payslip_id, employee_id, run_id, generated_at, pdf_url, sent_at, acknowledged_at

-- audit_log
log_id, transaction_id, module, user_id, user_role, timestamp,
original_values, new_values, justification, ip_address, approval_chain
```

---

## 17. Payslip Structure

### Standard Payslip (PureRide Corp.)

```
╔══════════════════════════════════════════════════════╗
║             PURERIDE CORP. — PAYSLIP                 ║
║   Pay Period: [Start Date] to [End Date]             ║
║   Release Date: [Date]                               ║
╠══════════════════════════════════════════════════════╣
║ EMPLOYEE DETAILS                                     ║
║   Name:              [Full Name]                     ║
║   Employee ID:       [ID]                            ║
║   Position:          [Job Title]                     ║
║   Department:        [Dept]                          ║
║   Employment Type:   [Regular / Contractual / Driver]║
║   SSS No.:           00-0000000-0                    ║
║   PhilHealth No.:    00-000000000-0                  ║
║   Pag-IBIG No.:      0000-0000-0000                  ║
║   Mode of Payment:   [Cash / Security Bank]          ║
╠══════════════════════════════════════════════════════╣
║ EARNINGS                                             ║
║   Basic Salary                        ₱ [Amount]    ║
║   Overtime Pay                        ₱ [Amount]    ║
║   Holiday Pay                         ₱ [Amount]    ║
║   Night Differential                  ₱ [Amount]    ║
║   Transportation Allowance            ₱ [Amount]    ║
║   Meal Allowance                      ₱ [Amount]    ║
║   Communication Allowance             ₱ [Amount]    ║
║   Driver Ride Incentives              ₱ [Amount]    ║
║   Claims & Reimbursements (Non-Taxable) ₱ [Amount]  ║
║     └ Fuel:                  ₱ [Amt]                ║
║     └ Toll Charges:          ₱ [Amt]                ║
║   ─────────────────────────────────────              ║
║   GROSS PAY                           ₱ [Total]     ║
╠══════════════════════════════════════════════════════╣
║ GOVERNMENT DEDUCTIONS                                ║
║   SSS Contribution (Employee Share)   ₱ [Amount]    ║
║     └ MSC: ₱[X,XXX] → Rate: 5%                      ║
║   PhilHealth Premium (Employee Share) ₱ [Amount]    ║
║     └ Basic × 2.5%                                   ║
║   Pag-IBIG Contribution               ₱ [Amount]    ║
║     └ Capped at ₱10,000 MFS × 2%                    ║
║   Withholding Tax (BIR TRAIN Law)     ₱ [Amount]    ║
╠══════════════════════════════════════════════════════╣
║ OTHER DEDUCTIONS                                     ║
║   HMO Premium (Employee Share)        ₱ [Amount]    ║
║   SSS Loan Amortization               ₱ [Amount]    ║
║   Pag-IBIG Loan                       ₱ [Amount]    ║
║   Cash Advance                        ₱ [Amount]    ║
║   Other Deductions                    ₱ [Amount]    ║
║   ─────────────────────────────────────              ║
║   TOTAL DEDUCTIONS                    ₱ [Total]     ║
╠══════════════════════════════════════════════════════╣
║ NET PAY                               ₱ [Net]       ║
╠══════════════════════════════════════════════════════╣
║ PAYMENT DETAILS                                      ║
║   Mode:              Cash / Security Bank            ║
║   Bank:              Security Bank Corporation       ║
║   Account Number:    ****-****-1234 (masked)         ║
║   Reference No.:     PR-[YYYYMMDD]-[BATCH]           ║
╠══════════════════════════════════════════════════════╣
║ EMPLOYER CONTRIBUTIONS (for your reference):         ║
║   SSS (Employer):          ₱ [Amount]               ║
║   PhilHealth (Employer):   ₱ [Amount]               ║
║   Pag-IBIG (Employer):     ₱ [Amount]               ║
║   EC Contribution:         ₱ [Amount]               ║
╚══════════════════════════════════════════════════════╝
```

> **Transparency Principle:** Employer contribution amounts are shown on the payslip so every employee understands the full cost-of-employment the company shoulders on their behalf.

### Payslip Validation Gate

Before a payslip is finalized and sent, the system performs an automated check:

| Validation | Check |
|---|---|
| Gross Pay Match | Must match compensation planning approved gross |
| Deduction Accuracy | SSS/PhilHealth/Pag-IBIG must use 2026 contribution tables |
| Net Pay Balance | Net = Gross − Total Deductions (must be ≥ ₱0) |
| Incentives Verification | All incentives must have a corresponding approved claim record |
| Reimbursements Verification | All reimbursements must have an approved claim with receipts |

---

## 18. Open Items & Client Clarification Points

| # | Module | Item | Why It Matters |
|---|---|---|---|
| 1 | HMO & Benefits | Does PureRide have an existing HMO provider for drivers? If yes, provider name and coverage details. | Determines if we build an HMO claims module or a simple premium-tracking module |
| 2 | HMO & Benefits | Is the 3% driver salary deduction for HMO/insurance confirmed? Is this a fixed 3% or tiered? | Drives the deduction computation logic |
| 3 | HMO & Benefits | When a driver is in an accident, does PureRide pay hospital bills first then claim from insurance, or does insurance pay directly? | Determines advance-and-reimburse vs. direct-pay workflow |
| 4 | HMO & Benefits | Is driver insurance limited to on-trip accidents or does it cover off-trip incidents too? | Affects eligibility determination in the accident claims module |
| 5 | Claims | Does PureRide have a counter offer / financial assistance process for drivers in accidents (separate from insurance)? | May require an additional approval workflow |
| 6 | Payroll | Are TNVS drivers classified as **employees** (full payroll deductions) or **independent contractors** (SSS voluntary/self-paid)? | Fundamental difference in statutory deduction handling |
| 7 | Compensation | What is PureRide's salary grade structure and bands? (Min/Mid/Max per level) | Required to configure the automated counter offer computation |
| 8 | Compensation | What are PureRide's merit increase percentages per performance rating? | Required to configure the merit matrix |
| 9 | Driver Incentives | Is the ride incentive structure fixed or does it vary by campaign/month? | Determines if incentive tiers are hardcoded or admin-configurable |
| 10 | Bank | Has PureRide signed a Payroll Facility Agreement with Security Bank? What is the SBC Payroll Facility code? | Required for the bank disbursement file header and employer tagging |
| 11 | 13th Month | Does PureRide release 13th month pay to drivers as well? If yes, is it computed the same way as office staff? | Driver 13th month computation may differ if they are contractors |
| 12 | Payroll Cycle | Is payroll released semi-monthly (15th and last day) or monthly? | Affects payslip generation schedule and bank file upload frequency |

---

## 19. Implementation Roadmap

### Phase 1 — Core Foundation (Months 1–2) 🔴 Critical

```
✔ Employee Master Data setup (including bank account details)
✔ Pay Grade and Salary Band configuration (PG-1 to PG-9)
✔ Government contribution tables loading (SSS, PhilHealth, Pag-IBIG, BIR)
✔ Time & Attendance integration
✔ Basic Payroll computation engine (auto-computation)
✔ Payslip generation
✔ Admin approval workflow
✔ Finance release workflow
✔ Security Bank disbursement file generation
✔ Basic ESS (payslip view)
```

### Phase 2 — Extended Modules (Months 3–4) 🔴 Critical

```
✔ Manual computation / override module with audit trail
✔ 13th Month Pay module with full approval workflow
✔ Compensation Planning full module
✔ Counter offer automation (automated + manual modes)
✔ Counter offer → Finance budget integration
✔ Performance integration (promotion and merit flags from Team 3)
✔ Claims & Reimbursement module
✔ Driver ride milestone incentive tracking
✔ ESS claims submission
✔ Maternity benefit computation module
```

### Phase 3 — Benefits & Analytics (Month 5) 🟠 High

```
✔ HMO & Benefits module
✔ Budget request workflow (for benefits)
✔ TNVS driver accident insurance tracking
✔ SSS EC Program filing tracker
✔ Merit increase computation module
✔ Merit → Finance budget integration
✔ HR Analytics Dashboard
✔ KPI cards (Workforce, Payroll, Attendance, Driver, Claims, Benefits)
```

### Phase 4 — Compliance & Automation (Month 6) 🟡 Medium

```
✔ Government remittance file generation (SSS R3, PhilHealth RF-1, HDMF MCRF)
✔ BIR alphalist generation (1604-C)
✔ Security Bank reconciliation module
✔ TNVS-specific driver analytics
✔ Full audit trail and logging
✔ Automated report scheduling (monthly email delivery)
✔ System-wide notifications
✔ Mobile ESS access (responsive design)
```

---

## 20. Compliance & Penalties Reference

### SSS Non-Compliance

- Late remittance: **3% per month** interest on amount due
- Non-remittance: Criminal liability under RA 11199; imprisonment minimum 6 years + fine ₱5,000 minimum

### PhilHealth Non-Compliance

- Late payment: **2% per month** penalty + administrative surcharges
- Non-filing: ₱5,000 to ₱10,000 per violation per month

### Pag-IBIG Non-Compliance

- Late remittance: **1/10 of 1% per day** of unremitted amount
- Criminal liability for employers who do not remit contributions collected from employees

### DOLE Minimum Wage Violations

- Double indemnity (employee receives double the difference)
- Administrative fine: ₱50,000 per violation
- Criminal prosecution under the Labor Code

---

## 21. Appendices

### Appendix A: TNVS Operating Cost Reference (Per Driver, Monthly)

| Cost Item | Estimated Amount |
|---|---|
| Fuel (for ~2,000 km/month at ~12 km/L, ₱63/L) | ₱10,500 |
| Vehicle depreciation (amortized) | ₱3,000–₱8,000 |
| Insurance (annual ÷ 12) | ₱1,500–₱3,000 |
| Maintenance / oil change | ₱1,500–₱2,500 |
| Car wash / cleaning | ₱500–₱1,000 |
| Connectivity (mobile data) | ₱300–₱500 |
| LTO / LTFRB fees (annual ÷ 12) | ₱500–₱800 |
| **Total Estimated Monthly Operating Cost** | **₱17,800–₱26,300** |

### Appendix B: Quick Reference — Government Deduction Summary

| Monthly Basic Salary | SSS (Employee) | PhilHealth (Employee) | Pag-IBIG (Employee) | Total Deduction |
|---|---|---|---|---|
| ₱16,610 (NCR Min Wage) | ₱830.50 | ₱415.25 | ₱200.00 | **₱1,445.75** |
| ₱18,000 | ₱900.00 | ₱450.00 | ₱200.00 | **₱1,550.00** |
| ₱20,000 | ₱1,000.00 | ₱500.00 | ₱200.00 | **₱1,700.00** |
| ₱25,000 | ₱1,250.00 | ₱625.00 | ₱200.00 | **₱2,075.00** |
| ₱30,000 | ₱1,500.00 | ₱750.00 | ₱200.00 | **₱2,450.00** |
| ₱35,000 | ₱1,750.00 | ₱875.00 | ₱200.00 | **₱2,825.00** |
| ₱40,000 | ₱1,750.00 | ₱1,000.00 | ₱200.00 | **₱2,950.00** |
| ₱50,000 | ₱1,750.00 | ₱1,250.00 | ₱200.00 | **₱3,200.00** |
| ₱80,000 | ₱1,750.00 | ₱2,000.00 | ₱200.00 | **₱3,950.00** |
| ₱100,000+ | ₱1,750.00 | ₱2,500.00 | ₱200.00 | **₱4,450.00** |

### Appendix C: Payroll Approval SLAs

| Stage | Responsible Party | Target SLA |
|---|---|---|
| Payroll Computation | Payroll Officer | Within 2 days of cut-off |
| Payroll Review & Submission | Payroll Officer | 48 hours |
| Admin Approval | HR Admin / Administrator | 24 hours |
| Finance Validation | Finance Officer | 24 hours |
| CEO/Final Approval | CEO / COO | 24 hours |
| Disbursement to Security Bank | Finance + Payroll | Same day as final approval |
| Payslip Generation | System (automated) | Immediate upon disbursement |

---

*End of Document*

**Document Control:**
Version: 2.0 (Combined Master)
Status: For Team Review
Prepared for: Pure Ride TNVS — Team 4 (Payroll & Benefits)
Regulatory Sources: SSS.gov.ph | PhilHealth.gov.ph | HDMF.gov.ph | DOLE.gov.ph | LTFRB | BIR.gov.ph | PNA | Security Bank Corporation

*All computations are based on official Philippine labor laws and government regulations effective 2026. All configurable values (incentive tiers, merit percentages, salary bands) are illustrative and must be confirmed with PureRide Corp. before system implementation.*
