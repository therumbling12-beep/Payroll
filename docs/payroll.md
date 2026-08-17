# Team 4 — Payroll & Benefits
## Transportation Network Vehicle Service (TNVS) Capstone Project
### Complete Module Feature Documentation — Version 2.0
### (Updated with Professor Notes: one.md, two.md, three.md + Scope Review)

---

## Important Note on Scope

Team 4 is part of the **HR Cluster** alongside Teams 1, 2, and 3. To avoid overlap, the following boundaries must be strictly followed:

| Team | Owns |
|---|---|
| **Team 1** | Applicant Management, Recruitment, New Hire Onboarding, Core HCM, Employee Self-Service (ESS), Employee Records Management |
| **Team 2** | Time & Attendance, Shift & Schedule, Timesheet, Leave Management, Workforce Analytics |
| **Team 3** | Performance Management, Competency Management, Learning & Training, Succession Planning, Social Recognition |
| **Team 4 (Us)** | Payroll Management, Compensation Planning, Claims & Reimbursement, HMO & Benefits Administration, HR Analytics Dashboard |

Team 4 **receives data** from Teams 1, 2, and 3 via integration — but does **not own** those features.

---

## What Was Added from Professor Notes

The following features were identified from the professor's notes (one.md, two.md, three.md) that were **missing or not detailed enough** in the previous version:

### Module 1 — Payroll Management (New/Updated)
| # | Added Feature | Source |
|---|---|---|
| 1 | Salary computation must be strictly based on Compensation Planning data | three.md |
| 2 | Manual correction / editing of wrong salary computation | three.md |
| 3 | Payslip must explicitly show: Gross Pay, Net Pay, Deductions, Incentives, Claims & Reimbursement | three.md |
| 4 | 13th Month Pay must go through Admin approval → Financial budget request → release workflow | three.md |
| 5 | Mode of payment: Cash or Bank (with bank details per employee) | three.md |
| 6 | Bank salary deposit process (e.g., BDO account, bank reference number per employee) | three.md |
| 7 | After payroll is approved by Admin, it proceeds to Financial — it does NOT return to Payroll | two.md |
| 8 | Attendance-based salary computation: Payroll requests attendance data from Team 2 for a specific period | two.md |
| 9 | Reimbursement amount from Claims module must appear as a separate line item in the payslip | one.md |
| 10 | Salary computation depends on the employee's position (not one-size-fits-all) | one.md |
| 11 | Batch computation — salary should not need to be computed one employee at a time | one.md |

### Module 2 — Compensation Planning (New/Updated)
| # | Added Feature | Source |
|---|---|---|
| 1 | Counter offer computation must consider the employee's credentials (automated + manual) | three.md |
| 2 | Counter offer numbers must fit within the Financial module's budget — integration with Finance required | three.md |
| 3 | Merit increase must also be checked against Financial budget | three.md |
| 4 | Compensation must integrate with Team 3 data (performance) for promotion computation | three.md |
| 5 | Compensation determines salary for employees from highest-paid to lowest-paid position (e.g., janitor) | one.md |
| 6 | System must determine how much a salary can grow based on years of service | one.md |
| 7 | Compensation Planning integration with Applicant Management (Team 1) for counter offer to candidates | one.md |
| 8 | Once Compensation Planning is finalized, Payroll automatically gets the updated salary data | two.md |
| 9 | Compensation data must go through Administrator approval before Payroll uses it | two.md |

### Module 3 — Claims and Reimbursement (New/Updated)
| # | Added Feature | Source |
|---|---|---|
| 1 | Driver incentives based on ride count (e.g., complete 20 rides = ₱500 incentive) | three.md |
| 2 | Incentive qualification rules must be configurable per driver | three.md |
| 3 | Subpage / navigation for Driver Incentives separate from general claims | three.md |
| 4 | Maternity leave incentive computation (provided by Team 2, computed in Team 4) | three.md |
| 5 | Maternity leave computation depends on employee position | three.md |
| 6 | Integration with Fleet & Transportation Management (Team 7) for driver expense reimbursement | one.md |
| 7 | Approved reimbursements must be sent to Payroll and included in the employee's pay | one.md |
| 8 | General employee incentives (e.g., good performance) processed through Claims & Reimbursement | two.md |
| 9 | Once incentive/claim is processed, it must be visible in the ESS portal (Team 1) — send data to ESS | two.md |

### Module 4 — HMO & Benefits Administration (New/Updated)
| # | Added Feature | Source |
|---|---|---|
| 1 | HMO process must follow the client's existing process — must ask client first | one.md |
| 2 | Driver-specific HMO — coverage for accidents and work-related injuries | three.md |
| 3 | If driver contributes % of salary to their own benefit, the deduction must be computed and sent to Payroll | three.md |
| 4 | HMO & Benefits applied through ESS portal (Team 1) — integration required | two.md |
| 5 | Team 4 must request budget from Financial for HMO & Benefits funding | two.md |
| 6 | Need to ask client: Does company pay driver's medical/accident bills? Is there an HMO specifically for drivers? | three.md |

### Module 5 — HR Analytics Dashboard (New/Updated)
| # | Added Feature | Source |
|---|---|---|
| 1 | Dashboard must show reports on overall employee performance (from Team 3 integration) | one.md |
| 2 | Dashboard must show total salaries paid by the company | one.md |
| 3 | Dashboard must show good and bad employee performance overview | one.md |
| 4 | Dashboard is primarily a **reporting dashboard** — not just analytics charts | one.md |

---

---

## Table of Contents
1. [Module 1 — Payroll Management](#module-1--payroll-management)
2. [Module 2 — Compensation Planning](#module-2--compensation-planning)
3. [Module 3 — Claims and Reimbursement](#module-3--claims-and-reimbursement)
4. [Module 4 — HMO & Benefits Administration](#module-4--hmo--benefits-administration)
5. [Module 5 — HR Analytics Dashboard](#module-5--hr-analytics-dashboard)
6. [Integration Map — What Team 4 Receives and Sends](#integration-map)
7. [Module Interconnection Flow](#module-interconnection-flow)
8. [Priority Guide Summary](#priority-guide-summary)

---

---

# MODULE 1 — PAYROLL MANAGEMENT

**Core Purpose:**
Accurately compute, process, record, and release employee salaries every payroll period. All salary computations must be strictly based on approved Compensation Planning data. The system must be automated, position-based, and batch-capable — while allowing manual corrections when needed. All payroll releases must pass through Administrator approval and Financial budget confirmation before payout.

---

## 1.1 Employee Salary Setup

### Features:
- Store and manage each employee's compensation details:
  - Basic pay (pulled from approved Compensation Planning record)
  - Employment type: Regular, Probationary, Contractual, Part-time
  - Job title, department, job level / position
- **Salary computation must depend on the employee's position** — different positions have different pay structures, computation methods, and applicable allowances
- Link salary record to the approved job grade from Compensation Planning module
- Track salary history: old salary → new salary with effectivity date and approver
- Flag employees with no salary record set up — payroll cannot proceed without it
- **Salary data is received from Compensation Planning** — Payroll does not independently set salaries

### Business Rule:
- No payroll can be processed for an employee without a complete and approved salary setup from Compensation Planning
- Any salary change must be approved in Compensation Planning first before Payroll reflects it
- Payroll must always match the latest approved Compensation Planning data exactly

---

## 1.2 Attendance-Based Salary Computation (Integration with Team 2)

### Features:
- Payroll does **not** store attendance data — it **requests** attendance from Team 2 (Workforce Management) for a specific payroll period
- Payroll sends a request specifying the cutoff range, e.g.:
  ```
  Request: Attendance data for July 1–15
  From: Payroll Management (Team 4)
  To: Workforce Management (Team 2)
  ```
- Team 2 provides:
  - Time-in and time-out records per employee
  - Total days present, absent, late, undertime
  - Approved overtime hours
  - Leave without pay (LWOP) days
  - Approved leave days (with pay)
- Payroll uses the received attendance data to auto-compute:
  - Days worked
  - Absences and tardiness deductions
  - Overtime pay entitlement
  - Night differential hours
- **Salary computation is automated and batch-processed** — the system computes all employees in a department or company-wide at once, not one by one

### Business Rule:
- Payroll cannot finalize computation without confirmed attendance data from Team 2
- If attendance data is not yet available for a period, payroll run is blocked and HR is notified

---

## 1.3 Payroll Period Management

### Features:
- Configure payroll cutoff schedules:
  - Semi-monthly: 1st–15th and 16th–end of month
  - Monthly: 1st–end of month
- Assign payroll period per employee group or department
- Payroll period status tracking:
  - Open → Processing → For Approval → Approved → Released → Closed
- Lock payroll period after processing to prevent unauthorized edits
- Reopen locked period only with HR Admin approval and full audit log entry
- Only one active payroll run per period is allowed at a time

### Business Rule:
- Cutoff dates must be configured before the start of each calendar year
- A period cannot be closed until all employee payslips have been generated and the payroll has been approved by the Administrator

---

## 1.4 Automated Salary Computation

### Features:
- **Batch computation** — compute all employees simultaneously, not one at a time
- Computation is automatically triggered once attendance data is received from Team 2
- Salary computation per employee based on their position:
  - **Regular employees** — monthly rate ÷ working days × days present
  - **Daily rate employees** — daily rate × days present
  - **Driver / TNVS staff** — may include per trip allowances and ride-based incentives
- Earnings included in computation:
  - Basic Salary (from Compensation Planning)
  - Overtime Pay:
    - Regular OT: basic hourly rate × 1.25
    - Rest day OT: basic hourly rate × 1.30
    - Holiday OT: basic hourly rate × 2.00
  - Holiday Pay:
    - Regular Holiday: basic daily rate × 2.00
    - Special Non-Working Holiday: basic daily rate × 1.30
  - Night Differential: +10% for hours worked between 10:00 PM – 6:00 AM
  - Fixed Allowances (Transportation, Meal, Communication — from Compensation Planning)
  - Variable Allowances (Per Trip, Fuel — from Compensation Planning, for TNVS drivers)
  - Incentives and reimbursements received from Claims & Reimbursement module (Module 3)
- **Gross Pay** = Basic Pay + Overtime + Holiday Pay + Night Differential + Allowances + Incentives + Reimbursements

### Business Rule:
- All earnings components must be sourced from approved data — Compensation Planning for salary/allowances, Team 2 for attendance, Module 3 for incentives/reimbursements
- No earnings may be manually added without an approval trail

---

## 1.5 Government Mandatory Deductions

### Features:
- Deductions are **not the same for every employee** — they depend on each employee's salary level
- **SSS Contribution:**
  - Employee share and employer share computed separately
  - Based on Monthly Salary Credit (MSC) table from SSS
  - EC (Employees' Compensation) contribution included
  - System clearly shows how much was deducted for SSS per employee
- **PhilHealth Contribution:**
  - 5% of basic monthly salary (2.5% employee, 2.5% employer)
  - Subject to minimum and maximum salary cap set by PhilHealth
  - System clearly shows the PhilHealth deduction amount per employee
- **Pag-IBIG (HDMF) Contribution:**
  - 2% of monthly compensation (employee share)
  - Employer counterpart automatically computed
  - System clearly shows how much was deducted for Pag-IBIG per employee
- **Withholding Tax:**
  - Based on BIR Tax Table (TRAIN Law / RA 10963)
  - Annualized tax computation
  - Tax status: Single (S), Married (ME/ME1/ME2/ME3/ME4)
- Auto-update contribution tables when government rates change
- Contribution table version history maintained for audit purposes
- All deductions are **transparent to the employee** — they can see the exact deduction amount per contribution in their payslip

### Business Rule:
- Government deductions are mandatory and cannot be waived or skipped
- System must always reflect the most current BIR, SSS, PhilHealth, and Pag-IBIG tables
- Deduction amounts must be individually identified — a lump sum deduction is not acceptable

---

## 1.6 Company Deductions

### Features:
- **Absence Deductions** — auto-computed from attendance data received from Team 2:
  - Formula: (Basic Monthly Salary ÷ Working Days in Period) × Days Absent
- **Tardiness Deductions** — computed per minute or hour late based on attendance data
- **Loan Deductions:**
  - SSS Salary Loan (monthly amortization)
  - Pag-IBIG Multi-Purpose Loan (monthly amortization)
  - Company Cash Advance / Salary Loan
  - Track outstanding balance and monthly amortization per employee
- **Other Deductions:**
  - Unreturned equipment or uniform
  - Overpayment recovery from previous payroll period
  - Driver benefit contribution (e.g., 3% of salary deducted for driver benefit fund — from HMO & Benefits module)
- All deductions tracked per employee with running balance and history log

### Business Rule:
- Total deductions cannot exceed 50% of gross pay per Labor Code of the Philippines
- Loan deductions must be authorized by the employee via a signed deduction authorization

---

## 1.7 Manual Salary Correction

### Features:
- HR Admin or Payroll Officer can manually edit a computed salary before final approval if an error is found
- Manual correction use cases:
  - Wrong salary rate was used
  - Incorrect OT hours included
  - Attendance data from Team 2 had an error that was corrected after computation
  - Wrong deduction amount applied
- Manual correction requires:
  - Reason for correction (mandatory text field)
  - Approver sign-off before correction is saved
  - Correction is logged in the audit trail with: who corrected, what was changed (old vs. new value), reason, timestamp
- Corrected payroll is re-computed automatically after the manual adjustment is saved
- Original computed value is retained in the audit trail — it is never permanently overwritten

### Business Rule:
- Manual corrections can only be done while the payroll period is in "Processing" or "For Approval" status
- Once payroll is approved by the Administrator, no further manual corrections are allowed — a reversal process must be initiated instead

---

## 1.8 Net Pay Computation

### Features:
- Formula:
  ```
  Net Pay = Gross Pay − Total Government Deductions − Total Company Deductions
  ```
- Real-time computation as earnings and deductions are entered or corrected
- Flag negative net pay (over-deduction alert) — system prevents releasing a negative payslip
- Per employee net pay summary screen before submitting for approval
- Batch net pay summary for all employees in a department or company-wide

---

## 1.9 Payroll Approval & Financial Release Workflow

### Features:
- After salary computation is finalized, payroll does **not** immediately release — it goes through a mandatory approval process:

```
Payroll Computed (Team 4)
        ↓
Submitted to Administrator for Review
  → Administrator reviews total payroll amount
  → Administrator checks if all amounts are correct
  → Administrator Approves or Returns with remarks
        ↓
Proceeds to Financial Management (Team 5)
  → Financial releases the budget/funds for payroll
  → Financial confirms fund availability
        ↓
Payroll Released to Employees
  → Via Cash or Bank Transfer
        ↓
Payroll Period is Closed
```

- **After payroll proceeds to Financial, it does NOT return to Payroll** — the release is handled by Financial
- If Administrator rejects: payroll is returned to Payroll team with remarks for correction
- Notification sent to each approver at every step
- Payroll status is updated in real time: Computed → For Admin Approval → For Financial Release → Released → Closed

### Business Rule:
- No salary can be released without Administrator approval
- No salary can be paid out without Financial confirming the budget release
- This workflow applies to every payroll period including 13th month pay

---

## 1.10 Mode of Payment — Cash or Bank

### Features:
- Each employee has a configured mode of payment:
  - **Cash** — payroll officer prepares cash envelope; system generates cash list
  - **Bank Transfer** — salary is sent to the employee's designated bank account
- **Bank Account Setup per Employee:**
  - Bank name (e.g., BDO, BPI, UnionBank, Metrobank, GCash)
  - Bank account number / reference number
  - Account name (must match employee's legal name)
  - Each employee has their own unique bank information stored in the system
- **Bank Transfer Process:**
  - System generates a bank disbursement file formatted for the company's partner bank
  - File is uploaded to the bank's online platform for batch salary crediting
  - Bank confirms successful crediting — confirmation is recorded in the system
  - Employees paid via bank receive a notification that salary has been credited
- Multiple bank accounts can be stored per employee (primary and secondary)
- Employee can update bank details — update requires HR approval before it takes effect

### Business Rule:
- Bank details must be verified before the first payroll is processed for a new employee
- If bank transfer fails (e.g., wrong account number), HR is immediately notified and a re-processing is triggered
- Cash payment requires a signed payroll acknowledgment receipt from the employee

---

## 1.11 13th Month Pay Computation & Approval

### Features:
- Formula (as specified by professor):
  ```
  13th Month Pay = Total Basic Salary Earned in a Calendar Year ÷ 12
  ```
- Example (from professor notes):
  ```
  Monthly Salary: ₱30,000 × 12 months ÷ 12 = ₱30,000 (13th Month Pay)
  ```
- Prorated 13th month for employees who did not work the full year:
  ```
  Example: Monthly Salary ₱30,000, worked 6 months
  13th Month Pay = ₱30,000 × 6 ÷ 12 = ₱15,000
  ```
- System automatically computes the 13th month pay based on salary and actual months worked — no manual computation needed
- Handle special cases:
  - New hires (less than 12 months in the year) — prorated
  - Resigned employees — prorated up to last working day
  - Employees on Leave Without Pay (LWOP) — LWOP days reduce the computation base

### 13th Month Pay Approval Workflow:
```
System Auto-Computes 13th Month Pay for All Employees
        ↓
HR Reviews and Submits to Administrator
  → Administrator reviews total 13th month payroll
  → Administrator Approves
        ↓
System Requests Budget from Financial Management (Team 5)
  → Financial confirms availability of funds
  → Financial approves the budget release
        ↓
13th Month Pay is Released to Employees
  → Via Cash or Bank (same mode of payment as regular payroll)
        ↓
13th Month Pay appears on a separate payslip or labeled separately
```

### Business Rule:
- 13th month pay must be released on or before December 24 (per Presidential Decree 851)
- All rank-and-file employees are entitled to 13th month pay regardless of their method of payment
- Managerial employees are excluded by law unless stated in company policy
- The 13th month pay release is blocked until Administrator approval and Financial budget release are both completed

---

## 1.12 Retroactive Pay Processing

### Features:
- Triggered when a salary increase from Compensation Planning was approved after the effectivity date (i.e., late approval)
- Formula:
  ```
  Retro Pay = (New Salary − Old Salary) × Number of Months/Days Delayed
  ```
- System auto-computes the retroactive amount as soon as the salary change is approved in Compensation Planning
- Retro pay is flagged and added to the next payroll run as a separate earnings line item
- Labeled clearly in the payslip as "Retroactive Pay — [period covered]"
- Audit trail: who approved the retro, what period it covers, and when it was processed

---

## 1.13 Separation & Final Pay Computation

### Features:
- Triggered upon: Resignation, Termination, End of Contract, Retirement (trigger comes from Team 1 — Offboarding)
- Auto-compute final pay components:

| Component | Computation |
|---|---|
| Last Salary | Basic Daily Rate × Days Worked in Last Period |
| Unused Leave Conversion | Daily Rate × Number of Unused Leave Days (data from Team 2) |
| 13th Month (Prorated) | Total Basic Earned in Year ÷ 12 (up to last working day) |
| Separation Pay (if applicable) | ½ month pay per year of service (involuntary termination) |
| Loan Deductions | Outstanding loan balance deducted from final pay |
| Other Deductions | Equipment, cash advances, uniform not returned |
| **Net Final Pay** | Total earnings minus total deductions |

- Final pay also goes through Administrator approval → Financial release workflow (same as regular payroll)
- Generate **Certificate of Final Pay** document (downloadable PDF)
- 30-day release deadline tracker from last working day (DOLE regulation)
- Alert HR when 30-day deadline is approaching

### Business Rule:
- Final pay must be released within 30 days from the employee's last day of work (DOLE regulation)
- Unused leave data for final pay is received from Team 2 (Leave Management)

---

## 1.14 Payslip Generation

### Features:
- Auto-generate individual payslip per employee per payroll period
- **Payslip must clearly show (as specified by professor):**
  - Employee name, ID, department, position
  - Payroll period covered
  - **Gross Pay** — total earnings before deductions
  - **Net Pay** — take-home pay after all deductions
  - **Deductions** — itemized breakdown:
    - SSS (employee share) — exact amount
    - PhilHealth (employee share) — exact amount
    - Pag-IBIG (employee share) — exact amount
    - Withholding Tax — exact amount
    - Loan deductions (if any)
    - Absence / tardiness deductions (if any)
    - Driver benefit contribution deduction (if applicable)
  - **Incentives** — itemized (performance incentive, attendance bonus, driver ride incentive, etc.)
  - **Claims & Reimbursement** — if the employee has an approved reimbursement this period, it appears as a separate line item on the payslip
  - **Retroactive Pay** — if applicable, shown as a separate line item
  - YTD (Year-to-Date) totals for all components
  - Mode of payment (Cash / Bank)
  - Bank details (last 4 digits of account number, for bank transfer employees)
- All deductions are **transparent** to the employee — no hidden or lumped deductions
- Downloadable as PDF
- Employee can view current and historical payslips

### Business Rule:
- Payslip is only made available to the employee after the payroll has been fully approved and released
- Payslip cannot be altered after release — corrections require a payroll adjustment in the next period

---

## 1.15 Payroll Summary Report

### Features:
- Company-wide payroll summary per period:
  - Total gross pay
  - Total government deductions (SSS + PhilHealth + Pag-IBIG + Tax)
  - Total company deductions (loans, absences)
  - Total incentives and reimbursements included
  - Total net pay released
  - Total employer contributions (SSS employer share, PhilHealth employer share, Pag-IBIG employer share)
- Filter by: department, employment type, payroll period, mode of payment
- Export to CSV, Excel, or PDF
- Payroll register: full list of all employees with computed pay components per period
- Government remittance reports:
  - SSS R3 Monthly Remittance List
  - PhilHealth RF-1 Remittance Form
  - HDMF Pag-IBIG Remittance List

---

## 1.16 Integration Points for Module 1

| Data Needed | Source | Details |
|---|---|---|
| Employee basic info, position, hire date | **Team 1** (Employee Records) | Required for payroll setup |
| Attendance, OT hours, absences, tardiness | **Team 2** (Workforce Management) | Required for computation |
| LWOP and approved leave days | **Team 2** (Leave Management) | Required for deductions and 13th month |
| Approved salary / job grade | **Module 2** (Compensation Planning) | Payroll must match Compensation exactly |
| Approved incentives and reimbursements | **Module 3** (Claims & Reimbursement) | Included as earnings in payslip |
| Driver benefit contribution deduction | **Module 4** (HMO & Benefits) | Deducted from driver salary |
| Budget confirmation / fund release | **Team 5** (Financial Management) | Required before payroll is released |
| Offboarding trigger for final pay | **Team 1** (Onboarding) | Triggers final pay computation |

---

---

# MODULE 2 — COMPENSATION PLANNING

**Core Purpose:**
Determine, plan, and manage how much each employee should be paid — from the applicant stage through active employment and retention. Compensation covers all positions in the company, from the highest-paid executive down to the lowest-paid role (e.g., janitor). All compensation decisions must fit within the budget approved by Financial Management. Compensation data is the single source of truth that Payroll Management depends on for all salary computations.

---

## 2.2 Salary Band Management

### Features:
- Define salary bands per job grade covering **all positions in the company** — from executive to support staff:

| Job Grade | Position Examples | Minimum | Midpoint | Maximum |
|---|---|---|---|---|
| Grade 1 | Janitor, Utility | ₱13,000 | ₱15,000 | ₱18,000 |
| Grade 2 | Admin Assistant | ₱15,000 | ₱18,000 | ₱22,000 |
| Grade 3 | Driver | ₱18,000 | ₱22,000 | ₱28,000 |
| Grade 4 | Dispatcher | ₱20,000 | ₱25,000 | ₱32,000 |
| Grade 5 | Fleet Supervisor | ₱28,000 | ₱35,000 | ₱45,000 |
| Grade 6 | Department Manager | ₱40,000 | ₱55,000 | ₱70,000 |
| Grade 7 | Senior Manager | ₱60,000 | ₱80,000 | ₱100,000 |

- Visual salary band indicator per employee:
  ```
  Min ─────[●]──────────── Max
         ₱22,000
         (Within Band ✅)
  ```
  - Green = Within Band
  - Red = Below Minimum
  - Orange = Above Maximum
- Bulk update salary bands (for annual market adjustment)
- Band change history log with effectivity dates

### Business Rule:
- No salary can be set below the band minimum without documented exceptional approval
- Salary above maximum requires VP/HR Head approval and documented justification
- Payroll Management reads salary data from this module — bands must always be accurate

---

## 2.3 Compensation Based on Employee Credentials

### Features:
- When determining salary for a new hire or promotion, the system considers:
  - **Educational attainment** (e.g., college graduate vs. vocational)
  - **Years of relevant work experience**
  - **Certifications or licenses held** (e.g., professional driver's license)
  - **Previous salary history** (if provided)
  - **Skills and competencies** (received from Team 3 — Competency Management)
- Salary placement algorithm:
  - High credentials + long experience → place closer to midpoint or above midpoint of salary band
  - Entry-level with no experience → place at or near band minimum
  - System suggests a salary placement within the band based on credentials
- HR can accept the system suggestion or manually adjust within the band
- All manual adjustments require documented justification and approver sign-off

### Business Rule:
- Credential-based salary placement must always stay within the defined salary band for the position
- Salary placement above midpoint requires additional approval

---

## 2.6 Compensation for Applicants — Salary Offer Package

### Features:
- **This module builds the salary offer package; the actual negotiation and communication with the applicant is handled by Team 1 (Applicant Management)**
- Team 4 provides the salary computation; Team 1 uses it when making a formal offer or counter offer to a candidate
- Compensation Planning is **integrated with Team 1 (Applicant Management)** for this purpose

### Salary Offer Package Builder:
- Receives applicant data from Team 1:
  - Position applied for
  - Expected salary (from application form)
  - Credentials: education, work experience, certifications
- Builds a complete offer package based on credentials and salary band:

| Component | Proposed Amount |
|---|---|
| Basic Salary | ₱28,000 |
| Transportation Allowance | ₱2,000 |
| Meal Allowance | ₱1,500 |
| HMO Coverage | Individual |
| Signing Bonus (if any) | ₱5,000 |
| **Total Package** | **₱36,500** |

- System computes the suggested salary placement within the band based on applicant's credentials
- HR can adjust the suggestion manually within the band
- Auto-check: does the proposed offer fit within the Financial budget? (Integration with Financial — Team 5)
- Flag if applicant's expected salary exceeds band maximum

### Counter Offer Computation for Applicants:
- If Team 1 needs to make a counter offer to a strong candidate, Compensation Planning provides the computation:
  - Both **automated computation** (system-suggested based on credentials) and **manual computation** (HR manually sets the amount) are supported
  - Automated computation: based on credentials scoring × salary band position
  - Manual computation: HR overrides the system suggestion with justification
- Counter offer must fit within the Financial budget — system checks against approved headcount budget from Financial (Team 5)
- System generates the salary package computation that Team 1 uses to present to the applicant

### Offer vs. Expectation Comparison:

| Component | Applicant Expected | HR Offer | Difference |
|---|---|---|---|
| Basic Salary | ₱35,000 | ₱28,000 | -₱7,000 🔴 |
| Allowances | ₱3,000 | ₱3,500 | +₱500 🟢 |
| Total | ₱38,000 | ₱31,500 | -₱6,500 🔴 |

---

## 2.7 Compensation Approval & Financial Integration

### Features:
- After compensation is finalized (for new hire, merit, promotion, or counter offer), it goes through approval:

```
HR Drafts Compensation Package
        ↓
Department Manager Reviews
        ↓
HR Head Approves
        ↓
Financial Management (Team 5) Checks Budget
  → Confirms that the salary fits within approved budget
  → If over budget: returns with remarks for revision
  → If within budget: approves
        ↓
Compensation is Finalized and Locked
        ↓
Payroll Management receives the approved salary data
        ↓
Payroll uses this data for the next payroll computation
```

- **All compensation changes (merit, counter offer, promotion) must fit within the budget approved by Financial Management (Team 5)**
- If the proposed salary exceeds the budget, the system flags it and blocks submission until revised
- Once compensation is approved, Payroll automatically receives the updated salary data — no manual transfer of data is needed

### Business Rule:
- Compensation data sent to Payroll must always reflect the latest approved record
- Payroll and Compensation Planning must always be perfectly in sync — there must be no discrepancy between what Compensation approved and what Payroll computes

---

## 2.8 Probationary to Regular Conversion

### Features:
- Track probationary period per employee (standard: 6 months from hire date — data from Team 1)
- Alert HR at:
  - 60 days before probationary end: Begin performance evaluation
  - 30 days before: Submit regularization recommendation
  - 7 days before: Final reminder
- Regularization Review Workflow:
  ```
  System Flags Probationary End (60 days before)
          ↓
  Manager Submits Performance Evaluation (from Team 3)
          ↓
  HR Reviews:
    → Attendance summary (from Team 2)
    → Performance rating (from Team 3)
    → Disciplinary records
          ↓
       ┌──────────────────────────┐
    REGULARIZE                EXTEND or TERMINATE
       ↓                           ↓
  Compensation Adjusted        Document reason
  Benefits Activated           Trigger separation
  Tenure Clock Starts          or extension
  ```
- Compensation adjustment on regularization:
  - Salary adjusted from probationary rate to regular rate (within salary band)
  - Effectivity date recorded
  - Retro pay computed if regularization is processed late
- Regularization triggers Module 4 (HMO & Benefits) to activate full benefits entitlement

---

## 2.9 Merit / Raise Planning

### Features:
- Set total merit budget per department or company-wide (budget must be confirmed by Financial — Team 5)
- HR views all employees eligible for merit increase in the current review cycle
- Input raise per employee:
  - Percentage-based (e.g., +5% of current salary)
  - Fixed amount (e.g., +₱2,000 per month)
- System validates: new salary must not exceed salary band maximum
- Real-time budget tracker:
  ```
  Total Merit Budget:     ₱500,000
  Allocated So Far:       ₱320,000  ████████░░░░  64%
  Remaining:              ₱180,000
  ```
  - Yellow warning at 80% consumed
  - Red alert at 100% (over-budget — submission blocked)
- Merit cycle summary report before final submission for approval
- **Merit increases also require Financial budget confirmation** before they take effect in Payroll

---

## 2.10 Compensation from Team 3 (Performance Data Integration)

### Features:
- Team 3 (Performance & Development) provides performance data to Team 4 for compensation-related decisions:
  - Performance rating per employee (e.g., Outstanding, Very Satisfactory, Satisfactory, Poor)
  - Promotion recommendations
  - Competency assessment results
- Team 4 uses this data for:
  - **Merit Raise Computation:** higher performance rating = higher raise percentage
  - **Bonus Allocation:** performance rating determines bonus multiplier
  - **Promotion Salary Adjustment:** when Team 3 recommends a promotion, Team 4 computes the new salary for the promoted position
  - **Tenure Step Approval:** poor performance rating can hold a step increment
- Computation when Team 3 provides promotion data:
  ```
  Employee promoted from Driver (Grade 3) to Dispatcher (Grade 4)
  Current Salary: ₱22,000 (Grade 3, Step 4)
  New Salary Band (Grade 4): Min ₱20,000 — Mid ₱25,000 — Max ₱32,000
  Credential-based placement: ₱24,000 (above min due to experience)
  Previous tenure credit: 3 years → placed at Step 3 of new grade
  Final Promoted Salary: ₱24,000
  ```
- All promotion salary adjustments follow the same approval workflow (HR → Financial → Payroll)

---

## 2.11 Bonus Allocation

### Features:
- Define bonus pool amount per department or company-wide (confirmed with Financial — Team 5)
- Distribute bonuses using configurable rules:
  - Performance rating multiplier (from Team 3):
    - Outstanding = 2x base bonus
    - Very Satisfactory = 1.5x
    - Satisfactory = 1x
    - Needs Improvement = 0.5x or no bonus
  - Tenure multiplier: longer service = higher bonus
  - Custom rules configured by HR
- Bonus types supported:
  - Performance Bonus
  - Attendance Bonus (zero absences)
  - Anniversary / Loyalty Bonus
  - Profit-sharing Bonus
- Budget consumed vs. remaining tracker
- Approved bonus amounts are sent to Payroll for inclusion in the next payroll cycle

---

## 2.12 Tenure Step Process

### 2.12.1 Tenure Step Table Configuration
- HR defines the step increment table per job grade:

| Step | Years of Service | Increment | Example (Base ₱20,000) |
|---|---|---|---|
| Step 1 | 0 – 1 year | Starting Rate | ₱20,000 |
| Step 2 | 1 – 2 years | +3% | ₱20,600 |
| Step 3 | 2 – 3 years | +3% | ₱21,218 |
| Step 4 | 3 – 5 years | +5% | ₱22,279 |
| Step 5 | 5 – 7 years | +5% | ₱23,393 |
| Step 6 | 7 – 10 years | +7% | ₱25,030 |
| Step 7 | 10+ years | +10% | ₱27,533 |

- The system determines how much an employee's salary can grow based on their number of years of service
- Each job grade has its own step table
- Maximum step sets the salary ceiling per grade

### 2.12.2 Tenure Auto-Tracking
- System auto-calculates years of service from regularization date (received from Team 1)
- Employee tenure card:
  ```
  Employee:          Juan dela Cruz
  Position:          Driver (Grade 3)
  Regularization:    March 15, 2022
  Years of Service:  3 years, 4 months
  Current Step:      Step 4 — ₱22,279
  Next Step:         Step 5 (eligible: March 15, 2027)
  Projected Salary:  ₱23,393 (+₱1,114)
  ```

### 2.12.3 Step Eligibility Notification
- 60 days before eligibility: Notify HR to begin review
- 30 days before: Remind HR and Department Manager
- On eligibility date: Trigger approval workflow

### 2.12.4 Step Approval Workflow
```
System Flags Eligibility
        ↓
HR Reviews:
  → Attendance record (from Team 2)
  → Performance rating (from Team 3)
  → Active disciplinary cases
        ↓
     ┌──────────────────────────┐
  APPROVE                    HOLD
     ↓                          ↓
Manager Confirms           Document hold reason
     ↓                     Set re-evaluation date
Financial Confirms Budget
     ↓
Step Applied to Salary
     ↓
Payroll Updated for Next Period
     ↓
Employee Notified
     ↓
Audit Log Updated
```

### 2.12.5 Step Hold Conditions

| Condition | Effect |
|---|---|
| Active disciplinary case | Step on hold until case resolved |
| Written warning | Step delayed by 6 months |
| Failed performance review (from Team 3) | Step delayed by 1 year |
| AWOL for 30+ days (from Team 2) | Step frozen |
| Promotion to new grade | Step resets to Step 1 of new grade (with possible partial credit) |

---

## 2.13 Counter Offer — Employee Retention

### Features:
- Triggered when:
  - Employee submits resignation (received from Team 1 — offboarding)
  - Manager flags a retention risk
  - Employee presents an external competing offer

### Counter Offer Computation:
- Both **automated** and **manual** computation are supported:
  - **Automated:** system computes a suggested counter offer based on:
    - Employee's credentials and performance rating (from Team 3)
    - Current position in salary band
    - Market positioning configured in company policy
  - **Manual:** HR can override the automated suggestion with a custom amount and documented justification
- Counter offer must fit within the budget confirmed by Financial Management (Team 5)
- System checks: does the proposed counter offer exceed the salary band maximum?
- Peer equity check: does the counter offer create pay equity issues with colleagues in the same role?

### Current vs. Proposed Package:

| Component | Current | Counter Offer |
|---|---|---|
| Basic Salary | ₱30,000 | ₱36,000 |
| Allowance | ₱2,000 | ₱3,000 |
| Bonus | ₱5,000 | ₱7,000 |
| HMO Coverage | Individual | With Dependents |
| **Total Package** | **₱37,000** | **₱46,000** |

### Counter Offer Approval Workflow:
```
Manager Submits Counter Offer
        ↓
HR Reviews (band compliance, equity check)
        ↓
Financial Confirms Budget (Team 5)
        ↓
HR Head / Executive Approves
        ↓
Counter Offer Presented to Employee (via Team 1)
        ↓
Employee Response: Accepted / Declined / Negotiating
        ↓
If Accepted → Salary updated in Payroll
If Declined → Offboarding triggered (Team 1)
```

---

## 2.16 Audit Trail & Compliance Log

### Features:
- Every compensation action is logged:
  - Who made the change
  - What was changed (old value → new value)
  - When it was changed (timestamp)
  - What approval authorized it and who approved
- Read-only — cannot be edited or deleted
- Filter by: employee, date range, change type, approver
- Exportable for labor inspection or compliance audit

---

## 2.17 Integration Points for Module 2

| Data Needed | Source | Details |
|---|---|---|
| Applicant info + expected salary | **Team 1** (Applicant Management) | For salary offer computation |
| Employee hire / regularization date | **Team 1** (Employee Records) | For tenure step and probationary tracking |
| Promotion recommendation | **Team 1** (Onboarding / Records) | For position change compensation adjustment |
| Attendance summary for step review | **Team 2** (Workforce Management) | For step increment approval |
| Performance rating | **Team 3** (Performance Management) | For merit, bonus, promotion, step hold |
| Competency data | **Team 3** (Competency Management) | For credential-based salary placement |
| Budget confirmation | **Team 5** (Financial Management) | For all compensation approvals |
| Sends approved salary to | **Module 1** (Payroll Management) | Payroll reads from Compensation |
| Sends benefits package to | **Module 4** (HMO & Benefits) | Triggers benefits enrollment |

---

---

# MODULE 3 — CLAIMS AND REIMBURSEMENT

**Core Purpose:**
Process, approve, and record all employee expense reimbursements and incentive payments — including driver ride-based incentives, performance incentives, maternity leave incentives, and work-related expense reimbursements. All approved amounts are fed into Payroll and reflected in the employee's payslip. This module is also integrated with Fleet & Transportation Management (Team 7) for driver-specific expense claims.

---

## 3.1 Claim Category Setup & Management

### Features:
- HR Admin defines all allowable claim and incentive categories:

| Category | Type | Description |
|---|---|---|
| Medical / Dental | Reimbursement | Out-of-pocket medical expenses |
| Transportation | Reimbursement | Work-related travel expenses |
| Meal / Representation | Reimbursement | Business meals |
| Training / Seminar | Reimbursement | Job-related learning |
| Communication / Internet | Reimbursement | Work-related communication |
| Driver Gas Expense | Reimbursement | Fuel costs incurred by drivers |
| Driver Work-Related Expense | Reimbursement | Other driver work expenses (e.g., tolls) |
| Driver Ride Incentive | Incentive | Reward for completing target number of rides |
| Performance Incentive | Incentive | General employee performance reward |
| Maternity Leave Incentive | Incentive | Maternity benefit computation |
| Attendance Bonus | Incentive | Zero-absence reward |

- Set maximum claimable amount per category per claim and per month
- Enable or disable categories per department or employee type
- Category change history log

---

## 3.2 General Employee Claims Submission

### Features:
- Employee fills out a claim form:
  - Date of expense
  - Claim category
  - Amount claimed
  - Description / business purpose
  - Department tag
- Upload supporting receipt or document:
  - Accepted formats: JPG, PNG, PDF
  - Maximum file size: 5MB per attachment
  - Multiple attachments allowed per claim
- Auto-validate against category maximum limits
- Duplicate submission detection (same date, amount, category)
- Claim reference number auto-generated upon submission
- Employee receives submission confirmation

---

## 3.3 Driver Ride-Based Incentive (TNVS-Specific)

### Features:
- This submodule specifically handles incentives earned by TNVS drivers based on the number of completed rides per period
- **Incentive Qualification Rules** (configurable by HR/Admin):
  - Define ride count thresholds and corresponding incentive amounts:

| Rides Completed | Incentive Amount |
|---|---|
| 10 rides | ₱250 |
| 20 rides | ₱500 |
| 30 rides | ₱1,000 |
| 50 rides | ₱2,000 |

  - Rules are configurable — the client defines the ride targets and incentive amounts
  - Multiple incentive tiers can be active simultaneously
- **Ride Count Data Source:**
  - Ride completion data is received from **TNVS Operations & Driver Management (Team 9)** or **Fleet & Transportation Management (Team 7)**
  - Team 4 does not generate ride data — it only receives and processes it
- **Incentive Determination:**
  - System automatically checks each driver's completed ride count for the period
  - System determines which tier the driver qualifies for based on configured rules
  - Qualified amount is displayed in the Driver Incentive subpage
- **Subpage / Navigation for Driver Incentives:**
  - Separate navigation section within Claims & Reimbursement dedicated to driver incentives
  - Shows per driver: rides completed, incentive tier qualified for, incentive amount, payment status
  - HR can review and approve driver incentives before sending to Payroll

### Driver Incentive Approval Workflow:
```
Ride data received from Team 7 / Team 9
        ↓
System Computes Qualified Incentive per Driver
        ↓
HR Reviews Driver Incentive List
        ↓
Manager Approves
        ↓
Finance Confirms Budget (Team 5)
        ↓
Incentive Amount sent to Payroll (Module 1)
        ↓
Appears on Driver's Payslip as "Ride Incentive"
```

---

## 3.4 Driver Expense Reimbursement (Integration with Team 7)

### Features:
- Drivers may incur work-related expenses during their trips:
  - Gas / fuel expenses
  - Toll fees
  - Parking fees
  - Vehicle maintenance (pre-approved only)
  - Other work-related travel expenses
- **Integration with Fleet & Transportation Management (Team 7):**
  - Fleet & Transportation (Team 7) tracks and logs driver expenses
  - Once a driver expense is logged and verified by Team 7, it is sent to Team 4 (Claims & Reimbursement) for reimbursement processing
  - Team 4 does not generate the expense records — it receives them from Team 7
- Driver submits receipts and supporting documents through the claims form
- Approved driver reimbursements are sent to Payroll (Module 1)
- Reimbursement amount appears as a separate line item on the driver's payslip

### Driver Expense Workflow:
```
Driver incurs expense during trip
        ↓
Team 7 (Fleet & Transportation) logs and verifies expense
        ↓
Expense record sent to Team 4 (Claims & Reimbursement)
        ↓
Driver submits receipts via Claims module
        ↓
Manager approves
        ↓
Finance approves (Team 5)
        ↓
Approved reimbursement sent to Payroll (Module 1)
        ↓
Amount included in next payslip as "Driver Reimbursement"
```

---

## 3.5 Performance Incentive (General Employees)

### Features:
- Employees with notable performance may receive an incentive processed through Claims & Reimbursement
- Example: Employee has exceptional output for the month → Manager nominates for performance incentive
- Incentive amount is configured by the client / HR Admin — the amount varies per company policy
- Process:
  - Manager submits incentive nomination with justification
  - HR reviews and validates against performance data (from Team 3)
  - Finance approves budget (Team 5)
  - Approved incentive sent to Payroll (Module 1)
  - Employee can see their incentive in the ESS portal (Team 1) after it is processed
- Incentive amount and type appear as a separate line item on the payslip

---

## 3.6 Maternity Leave Incentive / Benefit Computation

### Features:
- Maternity leave data is provided by **Team 2 (Leave Management)**
- Team 4 receives the maternity leave request and computes the corresponding benefit/incentive
- Computation depends on the employee's position and salary:
  - Daily rate employees: daily rate × number of maternity leave days
  - Monthly rate employees: prorated based on leave days
- SSS Maternity Benefit integration:
  - SSS provides a portion of the maternity benefit
  - Company may provide a top-up (company maternity pay minus SSS benefit)
  - System computes both SSS portion and company top-up separately
- Maternity incentive computation workflow:
  ```
  Team 2 sends approved maternity leave request to Team 4
          ↓
  Team 4 computes maternity benefit:
    → SSS maternity benefit (based on average daily salary credit)
    → Company maternity top-up (based on position and policy)
          ↓
  HR Reviews computation
          ↓
  Finance approves budget (Team 5)
          ↓
  Amount sent to Payroll (Module 1)
          ↓
  Appears on payslip as "Maternity Benefit"
  ```

---

## 3.7 Multi-Level Approval Workflow (All Claims)

### Approval Flow:
```
Employee / System Submits Claim or Incentive
        ↓
Immediate Manager Reviews
  → Approve / Reject / Request Revision with remarks
        ↓
HR Department Validates
  → Verify eligibility, completeness, and category limits
        ↓
Finance Department Approves (Team 5)
  → Confirms budget availability
        ↓
Claim Approved for Payment
        ↓
Amount sent to Payroll for inclusion in next payroll run
```

- Notification sent at each approval step (in-system and email)
- Deadline per approval step (e.g., must act within 3 business days)
- Auto-escalate if approver misses the deadline
- Each approver can: Approve / Approve Partial Amount / Reject / Request Revision

---

## 3.8 Claim Status Tracking

### Features:
- Employee can track their claim status in real time:
  - Draft — not yet submitted
  - Pending Manager Approval
  - Pending HR Review
  - Pending Finance Approval
  - Approved — awaiting next payroll inclusion
  - Paid / Included in Payroll
  - Rejected — with mandatory reason
  - Revision Required — employee must resubmit
- Timeline view showing each approval step with date and name of approver
- Employee receives notification at every status change

---

## 3.9 Payroll Feed & ESS Notification

### Features:
- All approved claims and incentives are automatically sent to Payroll (Module 1) for inclusion in the next payroll run
- Payroll includes the amount as a separate, labeled line item in the payslip (as specified by professor)
- Employee can see their approved incentive or reimbursement in the **ESS portal (Team 1)**:
  - Incentive type and amount are visible
  - Status: processed / pending payroll inclusion
- The incentive amount shown in ESS is the amount configured/approved — it matches what appears in the payslip

---

## 3.10 Claims Summary Report

### Features:
- Summary reports filterable by: employee, department, date range, claim category, status
- Available reports:
  - Total claims filed and approved per period
  - Total reimbursement amount paid out
  - Total incentives disbursed per type
  - Driver ride incentive summary per driver per period
  - Pending claims aging report (how long each claim has been waiting at each stage)
  - Rejected claims with rejection reasons
- Export to CSV, Excel, or PDF

---

## 3.11 Integration Points for Module 3

| Data Needed | Source | Details |
|---|---|---|
| Driver ride count data | **Team 7** (Fleet & Transportation) or **Team 9** (TNVS Operations) | For ride incentive qualification |
| Driver expense records | **Team 7** (Fleet & Transportation) | For driver reimbursement |
| Maternity leave approval | **Team 2** (Leave Management) | For maternity incentive computation |
| Performance data for incentive | **Team 3** (Performance Management) | For performance incentive validation |
| Employee info | **Team 1** (Employee Records) | For claim tagging and eligibility |
| Budget confirmation | **Team 5** (Financial Management) | For claim budget approval |
| Sends approved claims to | **Module 1** (Payroll Management) | Included in payslip |
| Sends incentive info to | **Team 1** (ESS Portal) | Employee visibility in self-service |

---

---

# MODULE 4 — HMO & BENEFITS ADMINISTRATION

**Core Purpose:**
Manage employee benefits enrollment, HMO coverage, dependent records, and government-mandated benefit contributions. The HMO process must follow the client's existing process — the client must be consulted before implementing this module. Driver-specific HMO (for accidents and work-related injuries) is a key focus. Benefits are applied through the ESS portal (Team 1). Budget requests for HMO & Benefits are coordinated with Financial Management (Team 5).

---

## 4.1 Client HMO Process Consultation

### Features:
- Before implementing the HMO module, the following questions must be asked to the client:
  - Does the company have an existing HMO process?
  - Which HMO provider does the company currently use (e.g., Maxicare, Medicard, PhilCare)?
  - What is the coverage plan structure (individual, with dependents)?
  - Does the company have a specific HMO process for drivers (accident coverage, work injury)?
  - Does the company pay the driver's medical or accident-related bills directly?
  - Do drivers contribute a percentage of their salary to their own benefit fund? If so, what percentage?
  - Does the company have an assistance process when a driver gets into an accident?
- The HMO module implementation and configuration will be based on the client's answers
- If the client has an existing process, the system follows that process exactly

### Business Rule:
- HMO configuration cannot be finalized until the client consultation is completed and documented
- All HMO setup decisions must be traceable to client-approved requirements

---

## 4.2 Benefits Type Setup & Configuration

### Features:
- HR Admin configures all available benefit types based on client requirements:

| Benefit | Type | Eligibility |
|---|---|---|
| HMO (General) | Health Insurance | Regular employees after regularization |
| HMO (Driver-Specific) | Accident & Work Injury Coverage | All TNVS drivers |
| Life Insurance | Insurance | Regular employees |
| Dental | Health | Regular employees (optional add-on) |
| Optical | Health | Regular employees (optional add-on) |
| SSS | Government Mandated | All employees from day 1 |
| PhilHealth | Government Mandated | All employees from day 1 |
| Pag-IBIG | Government Mandated | All employees from day 1 |
| 13th Month Pay | Statutory | All rank-and-file employees |
| Maternity Benefit | Statutory | Female employees (via SSS) |
| Paternity Leave | Statutory | Married male employees |

- Set eligibility rules per benefit:
  - Employment type required (Regular only, or all types)
  - Minimum tenure before eligibility (e.g., HMO activates upon regularization)
  - Dependent coverage options (employee only / employee + spouse / employee + family)
- Benefit package templates per job level or department
- All benefits setup must align with client's confirmed process

---

## 4.3 Employee Benefits Enrollment

### Features:
- Enrollment is triggered upon:
  - Regularization (primary trigger — compensation planning sends regularization notification)
  - Annual open enrollment period
  - Life event changes (marriage, new child, loss of dependent)
- Benefits enrollment checklist per employee
- Track enrollment date and effectivity date per benefit
- Employee benefits profile showing all active benefits and their details
- **Government-mandated benefits (SSS, PhilHealth, Pag-IBIG) are enrolled automatically from the first day of employment** — no waiting period
- Optional benefits (dental, optical) require employee election during enrollment

---

## 4.4 HMO Plan Management

### Features:
- Define HMO plans and coverage tiers based on client's HMO provider structure:

| Plan | Coverage | Annual Limit | Monthly Premium |
|---|---|---|---|
| Basic | Employee only | ₱100,000 | ₱500 |
| Plus | Employee + 1 dependent | ₱150,000 | ₱900 |
| Premium | Employee + 3 dependents | ₱200,000 | ₱1,400 |

- Assign HMO plan per employee based on job level and company policy
- Store HMO card details per employee:
  - Card number
  - HMO provider name
  - Coverage start and end date
  - Annual limit amount
- Track HMO plan upgrades and downgrades with history log
- HMO plan renewal management:
  - Alert HR 60 days before company-wide HMO plan renewal
  - Alert HR 30 days before individual coverage expiry

---

## 4.5 Driver-Specific HMO & Accident Coverage

### Features:
- TNVS drivers have a separate HMO concern — they face accident risks while working
- Driver-specific HMO/coverage addresses:
  - Work-related injuries sustained while driving
  - Accident-related hospitalization
  - Medical bills arising from work incidents
- Configuration based on client's actual driver accident process:
  - Does the company directly pay the driver's medical bills? → System tracks payments made
  - Is there a specific accident insurance policy for drivers? → Store policy details
  - Is there a driver assistance fund? → Track fund contributions and disbursements
- **Driver Benefit Contribution Deduction:**
  - If the driver contributes a percentage of their salary to their own benefit fund (e.g., 3% of salary):
    - The contribution percentage is configured in this module
    - The deduction amount is automatically sent to Payroll (Module 1) for each payroll period
    - Payroll deducts the amount from the driver's salary and it appears on the payslip as "Driver Benefit Contribution"
    - The deducted amount is tracked in the driver's benefit fund balance in this module
- Driver HMO / accident claim process:
  - Driver or manager files an accident/claim report
  - Supporting documents uploaded (medical certificate, receipts, police report if applicable)
  - HR processes the claim against the driver's coverage
  - Approved benefit/coverage disbursement recorded

---

## 4.6 Dependent Management

### Features:
- Add dependents linked to employee:
  - Spouse
  - Children (up to maximum allowed by HMO plan)
  - Parents (if covered by plan based on client's HMO contract)
- Dependent information stored:
  - Full name, relationship, date of birth
  - Valid ID or birth certificate uploaded for verification
- Track dependent eligibility:
  - Age limit (e.g., children covered up to age 21 if studying)
  - Marital status changes (e.g., dependent spouse who becomes employed elsewhere)
- Auto-alert when dependent is approaching the age limit for coverage
- Dependent addition and removal history log

---

## 4.7 Benefits Applied Through ESS (Integration with Team 1)

### Features:
- HMO and Benefits information is made available to employees through the **ESS (Employee Self Service) portal owned by Team 1**
- Team 4 sends the following data to Team 1 (ESS):
  - Employee's active benefits list
  - HMO card details and coverage information
  - Dependent enrollment status
  - Benefits utilization summary (amount used vs. remaining)
  - Open enrollment notifications
- Employees view and access their benefits information through Team 1's ESS portal — Team 4 does not build a separate self-service portal
- Benefits enrollment elections made by employees through ESS are sent back to Team 4 for processing

---

## 4.8 Benefits Utilization Tracking

### Features:
- Track used vs. remaining benefit balance per employee:
  - HMO: amount utilized vs. annual limit
  - Dental: consultations used vs. annual allowance
  - Optical: eyewear claims vs. annual limit
- Utilization log per employee:

| Date | Benefit Used | Amount | Remaining Balance |
|---|---|---|---|
| Jan 5 | HMO — ER Visit | ₱8,500 | ₱91,500 |
| Feb 14 | Dental — Cleaning | ₱1,200 | ₱3,800 |

- Alert employee (via ESS) when benefit balance falls below 20%
- HR can view utilization across all employees for annual budget planning

---

## 4.9 Government Mandatory Benefits Tracking

### Features:
- Track government-mandated contribution schedules and remittance status per employee:
  - SSS contribution history
  - PhilHealth contribution history
  - Pag-IBIG contribution history
- Contribution table version control (when government updates rates, old rates are retained for historical accuracy)
- Generate government remittance reports:
  - SSS R3 Monthly Remittance List
  - PhilHealth RF-1 Remittance Form
  - HDMF Pag-IBIG Remittance List
- Track government loan balances per employee:
  - SSS Salary Loan outstanding balance
  - Pag-IBIG Multi-Purpose Loan outstanding balance
  - Monthly amortization deductions are sent to Payroll (Module 1) for processing

---

## 4.10 Open Enrollment Period Management

### Features:
- HR configures the annual open enrollment window (e.g., November 1–30)
- During enrollment period, employees can (via ESS — Team 1):
  - Upgrade or downgrade their HMO plan tier
  - Add or remove dependents
  - Enroll in optional benefits (dental add-on, optical add-on)
- Changes take effect on the next plan year
- Enrollment form with digital confirmation / electronic signature
- Reminder notifications sent: 30 days before enrollment opens, at enrollment start, and 3 days before enrollment closes
- After enrollment closes, Team 4 processes all elections and updates benefit records

---

## 4.11 Benefits Budget Request (Integration with Financial — Team 5)

### Features:
- Team 4 must request budget from Financial Management (Team 5) for all benefit-related costs:
  - Annual HMO premium (total for all enrolled employees)
  - Life insurance premiums
  - Open enrollment benefit additions
  - Driver accident coverage / assistance fund
- Budget request process:
  ```
  Team 4 computes total annual benefits cost
          ↓
  Budget request submitted to Financial (Team 5)
          ↓
  Financial reviews and approves budget allocation
          ↓
  Approved budget is used as ceiling for all benefits spending
          ↓
  Team 4 monitors actual vs. approved budget throughout the year
  ```
- Benefits spending vs. approved budget tracked in real time

---

## 4.12 Benefits Cost Tracking

### Features:
- Track total benefits cost per employee (Total Cost of Employment):
  ```
  Basic Salary:                  ₱28,000
  Allowances:                    ₱3,500
  HMO Premium (employer share):  ₱900
  SSS (employer share):          ₱1,145
  PhilHealth (employer share):   ₱700
  Pag-IBIG (employer share):     ₱100
  ─────────────────────────────────────
  Total Cost of Employment:      ₱34,345
  ```
- Department-level benefits cost summary
- Year-over-year benefits cost comparison
- Benefits cost report shared with HR Analytics Dashboard (Module 5)

---

## 4.13 Integration Points for Module 4

| Data Needed | Source | Details |
|---|---|---|
| Employee regularization date | **Module 2** (Compensation Planning) | Triggers benefits enrollment |
| Employment type and position | **Team 1** (Employee Records) | For eligibility determination |
| Driver benefit contribution % | **Client configuration** | Configured per client's process |
| Driver contribution deduction | Sends to **Module 1** (Payroll) | Deducted from driver salary each period |
| Benefits data for employees | Sends to **Team 1** (ESS Portal) | Employee views benefits via ESS |
| Employee enrollment elections | Receives from **Team 1** (ESS) | During open enrollment |
| Budget approval | **Team 5** (Financial Management) | For all benefits spending |
| Government loan amortizations | Sends to **Module 1** (Payroll) | Deducted from salary each period |

---

---

# MODULE 5 — HR ANALYTICS DASHBOARD

**Core Purpose:**
Provide HR leadership and management with a real-time, unified **reporting dashboard** that consolidates data from all Team 4 modules — Payroll, Compensation, Claims & Reimbursement, and HMO & Benefits. The dashboard shows the overall state of employee compensation, payroll costs, benefits utilization, and incentive disbursements. It also reflects employee performance data received from Team 3 for HR decision-making. This module is primarily a **reporting tool** — not just charts, but actionable reports that HR and management can use and export.

---

## 5.1 Dashboard Overview & Navigation

### Features:
- Role-based dashboard views:
  - **HR Admin** — full access to all reports and metrics across all Team 4 modules
  - **Department Manager** — own department data only (payroll, compensation, claims)
  - **Finance** — payroll cost and benefits budget metrics only
  - **Executive / Management** — high-level KPIs and trend summaries
- Date range filter: Today / This Week / This Month / This Quarter / This Year / Custom Range
- Department filter: all departments or a specific department
- Export entire dashboard view as PDF or Excel report
- Print-ready report formatting
- Refresh data on demand or at configurable intervals

---

## 5.2 Payroll Reports & Analytics (from Module 1)

### Features:
- **Total Payroll Cost Report** per period:
  - Total gross pay released
  - Total government deductions collected (SSS, PhilHealth, Pag-IBIG, Tax)
  - Total net pay disbursed
  - Total employer contributions
  - Total incentives and reimbursements included in payroll
- **Month-over-Month Payroll Trend** (line chart):
  - Tracks total payroll spend from January to December
  - Highlights months with significant increases or decreases
- **Payroll Cost per Department** (bar chart):
  - Compare payroll spend across all departments
- **Payroll Components Breakdown** (pie chart):
  - Basic pay vs. overtime vs. allowances vs. government contributions vs. incentives
- **Mode of Payment Summary:**
  - Total employees paid by cash vs. bank transfer
  - Total amount released per mode
- **13th Month Pay Status Report:**
  - Total 13th month pay liability for the year
  - Monthly accrual vs. projected total
  - Status: Pending Approval / Approved / Released
- **Payroll Approval Status Tracker:**
  - Current period payroll status: Computed / For Admin Approval / For Financial Release / Released

---

## 5.3 Salary Reports (from Module 2)

### Features:
- **Total Salaries Paid Report:**
  - Total salary expenditure per period and year-to-date
  - Average salary per department and job level
  - Salary distribution across all positions (from highest-paid to lowest-paid)
- **Salary Band Compliance Report:**
  - Employees below band minimum (needs immediate attention 🔴)
  - Employees within band (on track 🟢)
  - Employees above band maximum (requires justification 🟠)
- **Merit Cycle Summary Report:**
  - Total merit budget allocated vs. consumed
  - Average raise percentage given this cycle
  - Department with highest and lowest average raise
- **Bonus Disbursement Report:**
  - Total bonus pool used vs. remaining
  - Bonus amount per performance rating tier
- **Tenure Step Increment Report** (upcoming 90 days):

| Employee | Position | Current Step | Next Step Date | Salary Impact |
|---|---|---|---|---|
| Juan dela Cruz | Driver | Step 4 | Mar 15 | +₱1,114 |
| Maria Santos | Dispatcher | Step 3 | Apr 2 | +₱900 |

- **Counter Offer Outcome Report:**
  - Total counter offers made this year
  - Accepted vs. Declined vs. Pending
  - Retention success rate
  - Average package increase in successful counter offers
- **Compensation Forecast Report:**
  - Projected payroll cost for next 6 months
  - Breakdown of upcoming cost drivers (steps, merit, new hires)

---

## 5.4 Employee Performance Overview (from Team 3 Integration)

### Features:
- The dashboard shows **overall employee performance data** received from Team 3 (Performance & Development)
- This gives HR a combined view of both compensation and performance in one place
- **Performance Distribution Report:**
  - Number and percentage of employees per performance rating:
    - Outstanding
    - Very Satisfactory
    - Satisfactory
    - Needs Improvement
    - Poor
  - Performance breakdown by department
- **Good Performance vs. Bad Performance Overview:**
  - Visual summary showing which departments have high vs. low performing employees
  - Highlight employees flagged for performance improvement
  - Highlight top performers being considered for merit or promotion
- **Performance vs. Compensation Correlation:**
  - Compare average salary vs. average performance rating per department
  - Identify departments where high performers are underpaid relative to band (retention risk)
  - Identify departments where low performers are overpaid (equity concern)
- **Note:** Team 4 does not own performance data — it receives it from Team 3 via integration for reporting purposes only

---

## 5.5 Claims & Reimbursement Reports (from Module 3)

### Features:
- **Total Claims Report** per period:
  - Total claims filed
  - Total claims approved, rejected, and pending
  - Total reimbursement amount paid out
- **Claims by Category Report:**

| Category | Claims Filed | Total Amount | % of Total |
|---|---|---|---|
| Medical | 45 | ₱85,000 | 38% |
| Transportation | 62 | ₱55,000 | 25% |
| Driver Gas Expense | 30 | ₱42,000 | 19% |
| Training | 12 | ₱28,000 | 13% |
| Others | 8 | ₱12,000 | 5% |

- **Driver Ride Incentive Report:**
  - Total ride incentives disbursed per period
  - Number of drivers who qualified per incentive tier
  - Total incentive amount per tier
- **Claims Aging Report:**
  - Claims pending beyond 7 days (needs attention — flagged per approver)
  - Average days to approval per department
- **Incentive Disbursement Summary:**
  - Total incentives paid per type (performance, ride, attendance, maternity)
  - Month-over-month incentive trend

---

## 5.6 HMO & Benefits Reports (from Module 4)

### Features:
- **HMO Enrollment Report:**
  - Total employees enrolled in HMO
  - Breakdown by HMO plan tier
  - Enrollment rate: enrolled vs. eligible
- **Benefits Utilization Report:**
  - Average HMO utilization per employee (amount used vs. annual limit)
  - Departments with highest utilization
  - Employees who have used 80%+ of their HMO limit (alert for potential overrun)
- **Benefits Cost vs. Budget Report:**
  ```
  Approved Benefits Budget:    ₱2,500,000
  Benefits Spent to Date:      ₱1,780,000  ████████░░  71%
  Remaining Budget:            ₱720,000
  Projected Year-End Spend:    ₱2,400,000 (within budget ✅)
  ```
- **Driver Benefit Fund Report:**
  - Total contributions collected from driver deductions
  - Total disbursements (accident claims, medical assistance)
  - Current fund balance
- **Government Contribution Compliance Report:**
  - SSS remittance status: ✅ Up to date / ⚠️ Overdue
  - PhilHealth remittance status
  - Pag-IBIG remittance status
  - Total employer contributions paid year-to-date
- **Upcoming Renewals Report:**
  - HMO plans expiring in the next 30, 60, 90 days

---

## 5.7 Pending Actions Center

### Features:
- Consolidated view of all pending approvals across all Team 4 modules:

| Action Required | Module | Count | Oldest Pending |
|---|---|---|---|
| Payroll for Admin approval | Payroll | 1 | 2 days ago |
| Merit raise approvals | Compensation | 8 | 5 days ago |
| Claim reimbursements pending Finance | Claims | 14 | 3 days ago |
| Driver incentive approvals | Claims | 6 | 1 day ago |
| HMO enrollments pending | Benefits | 3 | 7 days ago |
| Tenure step reviews | Compensation | 2 | 2 days ago |
| 13th month pay pending Admin approval | Payroll | 1 | 4 days ago |

- Click on any row to navigate directly to the pending action record
- Alert (red highlight) if any item is pending beyond the defined SLA threshold
- SLA thresholds are configurable per action type

---

## 5.8 Key HR KPIs Summary

### Features:
- High-level KPI cards displayed prominently at the top of the dashboard:

| KPI | Value | vs. Last Period |
|---|---|---|
| Total Active Employees | 128 | +5 ▲ |
| Monthly Payroll Cost | ₱3,245,000 | +2.3% ▲ |
| Total Salaries Paid (YTD) | ₱22,680,000 | — |
| Average Salary | ₱25,352 | +1.1% ▲ |
| HMO Enrollment Rate | 94% | Same — |
| Claims Approval Rate | 87% | +3% ▲ |
| Counter Offer Retention Rate | 72% | -5% ▼ |
| Driver Incentives Disbursed | ₱125,000 | +12% ▲ |
| Pending Approvals (all modules) | 35 | -4 ▼ |
| % Employees Within Salary Band | 89% | +2% ▲ |
| Performance: Outstanding / Very Sat | 42% | +5% ▲ |

- Trend arrows (▲ up / ▼ down) with color coding (green = good trend, red = concern)
- Click any KPI card to drill down to the detailed records behind the number

---

## 5.9 Scheduled & On-Demand Reports

### Features:
- HR can generate reports on demand or schedule them for automatic delivery:
  - Payroll Summary Report — every 15th and 30th of the month
  - 13th Month Pay Accrual Report — monthly
  - Compensation Review Report — per review cycle
  - Claims Summary Report — monthly
  - Benefits Utilization Report — quarterly
  - HR KPI Report — monthly and quarterly
  - Employee Performance Overview — quarterly (data from Team 3)
- Export formats: PDF, CSV, Excel
- Report delivery: download in-system or auto-send via email to configured recipients
- Report history log: all previously generated reports are stored and re-downloadable

---

## 5.10 Integration Points for Module 5

| Data Source | Module / Team | Data Received |
|---|---|---|
| Payroll computation data | **Module 1** (Payroll) | Payroll costs, payslip data, deductions |
| Compensation data | **Module 2** (Compensation Planning) | Salary bands, merit, steps, counter offers |
| Claims and incentive data | **Module 3** (Claims & Reimbursement) | Claim amounts, incentives, driver incentives |
| Benefits and HMO data | **Module 4** (HMO & Benefits) | Enrollment, utilization, cost |
| Employee performance data | **Team 3** (Performance Management) | Performance ratings for HR overview |
| Employee headcount data | **Team 1** (Employee Records) | Headcount for reports |

**Note:** Module 5 is a **read-only reporting consumer** — it does not create, modify, or own any data. All data displayed is sourced from the other modules and teams listed above.

---

---

# INTEGRATION MAP

## What Team 4 Receives from Other Teams

| Data | From | Used By |
|---|---|---|
| New employee info (hire date, position, department) | Team 1 — Employee Records | Module 1, Module 2 |
| Applicant info + expected salary | Team 1 — Applicant Management | Module 2 (offer computation) |
| Regularization / offboarding trigger | Team 1 — Onboarding | Module 2, Module 1 (final pay) |
| Attendance, OT, absences, tardiness | Team 2 — Time & Attendance | Module 1 (payroll computation) |
| LWOP and approved leave days | Team 2 — Leave Management | Module 1 (deductions, 13th month) |
| Maternity leave approval | Team 2 — Leave Management | Module 3 (maternity benefit) |
| Performance rating | Team 3 — Performance Management | Module 2 (merit, bonus, step, counter offer) |
| Competency / promotion recommendation | Team 3 — Competency Management | Module 2 (promotion salary) |
| Performance overview data | Team 3 — Performance Management | Module 5 (HR Analytics reporting) |
| Driver ride count data | Team 7 / Team 9 — Fleet / Operations | Module 3 (ride incentive) |
| Driver expense records | Team 7 — Fleet & Transportation | Module 3 (driver reimbursement) |
| Budget approval / fund release | Team 5 — Financial Management | Module 1, 2, 3, 4 |

## What Team 4 Sends to Other Teams

| Data | To | Purpose |
|---|---|---|
| Approved salary data | Module 1 (Payroll) | Used for all salary computations |
| Regularization notification | Module 4 (HMO & Benefits) | Triggers benefits enrollment |
| Approved incentives / reimbursements | Module 1 (Payroll) | Included in payroll run |
| Incentive / claim notification | Team 1 (ESS Portal) | Employee visibility in self-service |
| Benefits data | Team 1 (ESS Portal) | Employee views benefits in ESS |
| Driver contribution deduction | Module 1 (Payroll) | Deducted from driver salary |
| Government loan amortizations | Module 1 (Payroll) | Deducted from salary |
| Payroll budget request | Team 5 (Financial) | For fund release approval |
| Benefits budget request | Team 5 (Financial) | For benefits spending approval |
| Compensation forecast | Team 5 (Financial) | For future budget planning |

---

---

# MODULE INTERCONNECTION FLOW

```
┌──────────────────────────────────────────────────────────────────┐
│                  TEAM 1 — RECRUITMENT & ONBOARDING               │
│  Applicant Selected → Hire Date → Employee Record Created        │
│  Regularization Trigger → Offboarding Trigger                    │
│  ESS Portal (receives benefits and incentive data from Team 4)   │
└───────────────────────────┬──────────────────────────────────────┘
                            │ Employee info, applicant data,
                            │ regularization, offboarding triggers
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│               TEAM 2 — WORKFORCE MANAGEMENT                       │
│  Attendance, OT, Absences, LWOP, Leave Approvals                 │
└───────────────────────────┬──────────────────────────────────────┘
                            │ Attendance data, leave records
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│              TEAM 3 — PERFORMANCE & DEVELOPMENT                   │
│  Performance Ratings, Competency Data, Promotion Recommendations │
└───────────────────────────┬──────────────────────────────────────┘
                            │ Performance ratings, promotion data
                            ▼
┌──────────────────────────────────────────────────────────────────┐
│           MODULE 2 — COMPENSATION PLANNING (Team 4)              │
│  Salary Bands → Job Offer Computation → Merit Planning           │
│  Tenure Step → Counter Offer → Bonus → Probationary Review       │
│  All approved by Financial (Team 5) before taking effect         │
└────────────┬─────────────────────────────┬───────────────────────┘
             │ Approved salary              │ Benefits package
             ▼                             ▼
┌────────────────────┐          ┌──────────────────────────────────┐
│   MODULE 1         │          │   MODULE 4                        │
│   PAYROLL          │          │   HMO & BENEFITS                  │
│   MANAGEMENT       │◄─────────│   Administration                  │
│                    │ Driver   │                                   │
│ Attendance data ◄──┼──Team 2  │ Enrollment, HMO Plans            │
│ Claims data ◄──────┼──Mod 3   │ Driver Benefit Deductions →      │
│                    │          │ Sends to Payroll                  │
│ Payslip generated  │          │ Sends data to ESS (Team 1)        │
│ 13th Month Pay     │          │ Budget request → Financial        │
│ Final Pay          │          └──────────────────────────────────┘
│ Admin Approval     │
│ Financial Release  │◄─────────────────────────────────────────────
└────────────┬───────┘         Team 5 (Financial) approves release
             │
             ▼
┌──────────────────────────────────────────────────────────────────┐
│           MODULE 3 — CLAIMS & REIMBURSEMENT (Team 4)             │
│  Driver Ride Incentives (data from Team 7 / Team 9)              │
│  Driver Expense Reimbursement (data from Team 7)                 │
│  Performance Incentives, Maternity Benefit (data from Team 2)    │
│  General Claims and Reimbursements                               │
│  Approved amounts → sent to Module 1 (Payroll)                   │
│  Incentive info → sent to Team 1 (ESS)                           │
└────────────┬─────────────────────────────────────────────────────┘
             │ All data flows into reporting
             ▼
┌──────────────────────────────────────────────────────────────────┐
│           MODULE 5 — HR ANALYTICS DASHBOARD (Team 4)             │
│  Aggregates data from ALL Team 4 modules + Team 3 performance    │
│  Payroll Reports │ Salary Reports │ Claims Reports                │
│  Benefits Reports │ Performance Overview │ KPI Summary           │
│  Pending Actions │ Scheduled Report Generation                   │
└──────────────────────────────────────────────────────────────────┘
```

---

---

# PRIORITY GUIDE SUMMARY

## Module 1 — Payroll Management

| Priority | Feature |
|---|---|
| 🔴 Must Build | Attendance-based salary computation (Team 2 integration), automated batch computation, government deductions (SSS/PhilHealth/Pag-IBIG) with exact itemization, net pay computation, payslip with Gross/Net/Deductions/Incentives/Reimbursements clearly shown |
| 🔴 Must Build | Payroll approval workflow (Admin → Financial), mode of payment (Cash / Bank), bank account setup per employee |
| 🟡 Should Build | Manual salary correction with audit trail, 13th month pay with Admin → Financial workflow, retroactive pay processing, separation and final pay |
| 🟢 Nice to Have | Bank disbursement file export, payroll calendar, automated payslip email, payroll remittance reports |

## Module 2 — Compensation Planning

| Priority | Feature |
|---|---|
| 🔴 Must Build | Salary bands for all positions (highest to lowest), credential-based salary placement, compensation approval with Financial budget check, salary data feed to Payroll |
| 🔴 Must Build | Merit planning with budget tracker, tenure step table and auto-tracking, step approval workflow |
| 🟡 Should Build | Counter offer (automated + manual computation), probationary conversion, Team 3 performance integration for merit/bonus/step, bonus allocation |
| 🟢 Nice to Have | Pay equity analysis, compensation forecasting, audit trail |

## Module 3 — Claims and Reimbursement

| Priority | Feature |
|---|---|
| 🔴 Must Build | Driver ride incentive with configurable qualification rules and driver incentive subpage, approval workflow, feed to Payroll |
| 🔴 Must Build | Driver expense reimbursement (Team 7 integration), general claims submission, payslip line item for claims |
| 🟡 Should Build | Performance incentive, maternity leave benefit computation (Team 2 integration), ESS notification (Team 1 integration) |
| 🟢 Nice to Have | Claims summary reports, claims aging report, bulk claim processing |

## Module 4 — HMO & Benefits Administration

| Priority | Feature |
|---|---|
| 🔴 Must Build | Client HMO consultation and configuration, driver-specific HMO/accident coverage, driver benefit contribution deduction feed to Payroll |
| 🔴 Must Build | Government mandated benefits (SSS, PhilHealth, Pag-IBIG) enrollment and tracking, benefits budget request to Financial |
| 🟡 Should Build | HMO plan management, dependent management, benefits utilization tracking, ESS integration (Team 1) |
| 🟢 Nice to Have | Open enrollment period management, benefits cost tracking, renewal alerts |

## Module 5 — HR Analytics Dashboard

| Priority | Feature |
|---|---|
| 🔴 Must Build | Payroll cost report, total salaries paid report, pending approvals center, employee performance overview (from Team 3) |
| 🔴 Must Build | Claims and incentive disbursement reports, on-demand report export (PDF/Excel) |
| 🟡 Should Build | Salary band compliance report, driver incentive report, benefits utilization report, KPI summary cards |
| 🟢 Nice to Have | Scheduled automated reports, drill-down navigation, performance vs. compensation correlation chart |

---

*Document prepared for: Team 4 — Payroll & Benefits*
*System: Transportation Network Vehicle Service (TNVS) — Human Resource Management System (HRMS / HCM)*
*Capstone Project Documentation*
*Version 2.0 — Updated with Professor Notes (one.md, two.md, three.md) + Scope Review*