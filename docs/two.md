# Compensation Planning

- Compensation Planning is where the company determines how much each employee should be paid.
- It will determine if an employee's salary should increase or decrease.
- For employee promotion, the system will get the employee's performance information from Team 3.

## Payroll Management Integration

- Once Compensation Planning is finalized, Payroll Management will get the employee's salary information.
- Example: Employee #1 is currently receiving the minimum daily salary.
- Payroll will get the compensation data and use it for the salary computation.
- The computation should use the employee's approved compensation data.

## Attendance Integration

- Payroll will request the employee's attendance from Workforce Management.
- Example: Payroll can request attendance for July 1–15.
- Workforce Management will provide the requested time-in and attendance data.
- Payroll will then use the attendance data to compute the employee's salary.

## Approval and Financial Integration

- After the salary has been computed, it will first go to the Administrator for approval.
- The Administrator needs to review and approve the amount that will be paid to the employee.
- Once approved by the Administrator, it will proceed to Financial for the release of the money.
- After it goes to Financial for release, it will not return to Payroll.

# HMO & Benefits

- HMO and Benefits will be applied through the ESS (Employee Self Service) portal.
- Employees will have records/accounts where their HMO and benefits information can be managed.
- Team 4 will request the required budget because HMO and Benefits involve company funds.

# Claims & Reimbursements

- Incentives will also be handled through Claims & Reimbursements.
- This is where extra payments for employees can be recorded.
- Example: Employee #1 has good performance and needs to receive an incentive.
- The incentive will be processed through Claims & Reimbursements.

## Employee Self Service Integration

- Once the claim/incentive has been processed, it will be sent to the Self Service Portal.
- The employee should be able to see that they have received an incentive.
- The employee should be able to see the amount of the incentive.
- The incentive amount will depend on the amount configured by the client.

# 13th Month Pay

- Payroll will also compute the 13th Month Pay.
- Example:
  - Monthly salary: ₱30,000
  - 12 months of service
  - Computation: ₱30,000 × 12 ÷ 12
  - 13th Month Pay: ₱30,000
- If the employee worked for only 6 months, the computation should be based on the actual months worked.
- Example:
  - Monthly salary: ₱30,000
  - Months worked: 6
  - Computation: ₱30,000 × 6 ÷ 12
  - The result will be the employee's 13th Month Pay.
- The system needs to automatically compute the 13th Month Pay based on the employee's salary and months worked.
