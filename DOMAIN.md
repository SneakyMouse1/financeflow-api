# FinanceFlow Domain Documentation

This document describes the business domain, terminology, rules, and invariants of the FinanceFlow application. It serves as a functional specification and is decoupled from technical implementation details.

---

## 0. Cross-Cutting Principles

*   **Data Isolation**: Every resource (Account, Category, Transaction, Budget, Goal, Tag, Attachment) is strictly scoped to its owning User. Cross-user access or references at any level (e.g., a Budget referencing another user's Category, or accessing another user's account-linked transactions) is strictly forbidden and must be prevented at the validation and policy layers.
*   **Financial Integrity**: Operations must never create, destroy, or duplicate monetary value arbitrarily. Every balance-affecting action must have a symmetric, reversible counterpart to ensure account balances can be perfectly reconstructed during updates or deletions.

---

## 1. User Management & Onboarding

### Terminology
*   **User**: An authenticated individual who owns financial accounts, budgets, goals, and transactions.
*   **Onboarding**: The initial setup process triggered immediately upon user registration.

### Business Rules
*   **Default Categories**: Upon user registration, the system must automatically create a default set of 20 categories (16 Expense categories and 4 Income categories) to populate the user's workspace.
*   **Account Deletion**: Deleting a user account requires password verification. All associated data (accounts, transactions, categories, budgets, goals) must be soft-deleted or deactivated, preserving historical records while removing them from active views.

---

## 2. Accounts

### Terminology
*   **Account**: A financial entity containing a monetary balance.
*   **Account Type**: The classification of the account. Valid types are: Card, Cash, Crypto, Deposit, and Investment.
*   **Archived Account**: An account marked as inactive. It preserves historical transaction data but is hidden from default active listings.

### Business Rules
*   **Balance Invariants**: An account balance must increase when an Income transaction is recorded, and decrease when an Expense transaction is recorded.
*   **Cascade Deletion**: When an account is soft-deleted:
    *   All associated transactions must be soft-deleted, and their respective balance effects must be reverted.
    *   All recurring transactions linked to the deleted account must be deactivated.

---

## 3. Categories

### Terminology
*   **Category**: A classification for grouping income or expenses (e.g., Food, Salary).
*   **Category Type**: Must be either Income or Expense.
*   **Default Category**: A system-provided category created during onboarding. Default categories cannot be deleted by the user.

### Business Rules
*   **Budget Dependency**: A category cannot be deleted if it is linked to any active budget. The dependent budgets must be deleted first.

---

## 4. Transactions & Transfers

### Terminology
*   **Transaction**: An individual financial record capturing money movement. Valid types are: Income, Expense, and Transfer.
*   **Transfer**: A single atomic movement of funds from a source account to a destination account.

### Business Rules
*   **Real-time Balance Update**: Creating, updating, or deleting a transaction must immediately adjust the balance of the associated account.
*   **Transfer Atomicity**: A Transfer must consist of two linked transactions: an Expense transaction on the source account and an Income transaction on the destination account.
    *   Both transactions must share a unique identifier (`transfer_id`) and reference each other via `related_transaction_id`.
    *   If one side of a transfer is deleted, the other side must be deleted automatically, and balances on both accounts must be reverted.
*   **Transfer Immutability**: Transfer transactions cannot be modified individually. To change a transfer, the user must delete it and create a new one.
*   **Type Conversion Restriction**: A regular transaction (Income or Expense) cannot be converted into a Transfer, nor can a Transfer be converted into a regular transaction.

---

## 5. Budgets

### Terminology
*   **Budget**: A spending limit set on a specific category for a defined timeframe.
*   **Budget Period**: The timeframe for the budget. Valid periods are: Monthly and Yearly.

### Business Rules
*   **Spending Tracking**: The amount spent against a budget is computed dynamically by summing all Expense transactions for the associated category, currency, and user within the current period boundaries (calendar month or calendar year).
*   **Currency and Category Consistency**: A budget can only track transactions of the category and currency matching the budget's definition.

---

## 6. Goals & Deposits

### Terminology
*   **Goal**: A financial savings target with a specific target amount and deadline.
*   **Goal Deposit**: An individual record of money added to a goal.
*   **Goal Status**: The current state of the goal. Valid statuses are: Active, Completed, and Paused.

### Business Rules
*   **Status Transitions**:
    *   A goal automatically transitions to `Completed` when the `current_amount` becomes greater than or equal to the `target_amount`.
    *   If a deposit is deleted or reduced such that the `current_amount` falls below the `target_amount`, a `Completed` goal must automatically revert to `Active`.
*   **Account-Linked Deposits**: A goal deposit can optionally be funded from an account:
    *   When funded from an account, a corresponding Expense transaction must be created.
    *   Any updates to the transaction amount must automatically synchronize the goal deposit amount and re-evaluate the goal's status.
    *   Deleting an account-linked transaction reverts the goal deposit and subtracts the amount from the goal's `current_amount`.
*   **Goal Deletion Cascade**: Deleting a goal must delete all its associated deposits. Any account-linked deposits must have their corresponding account transactions deleted and balances reverted.

---

## 7. Recurring Transactions

### Terminology
*   **Recurring Transaction**: A template schedule that automatically generates real transactions at set intervals.
*   **Frequency**: The interval of recurrence. Valid frequencies are: Daily, Weekly, Monthly, and Yearly.

### Business Rules
*   **Automated Execution**:
    *   The scheduler checks daily for active recurring transactions where `next_run_at` is less than or equal to the current date.
    *   For each matching record, the system creates a real transaction and advances the `next_run_at` date by the scheduled frequency.
*   **Execution Isolation**:
    *   Processing a recurring template must occur within a database transaction to ensure both transaction creation and next run date update succeed or fail together.
    *   Errors in processing one user's recurring transaction must be caught and logged individually, allowing the scheduler to continue processing other records without interruption.
*   **Restrictions**:
    *   Recurring transactions of type Transfer are forbidden.
    *   If a source account linked to a recurring transaction is deleted or archived, the recurring transaction must be automatically deactivated.

---

## 8. Attachments

### Terminology
*   **Attachment**: A digital file linked to a specific transaction as supporting documentation (e.g., a receipt or an invoice).

### Business Rules
*   **File Restraints**: An attachment must not exceed 5MB in size and must be of an allowed format (`jpg, jpeg, png, gif, webp, pdf`).
*   **Ownership Check**: An attachment can only be uploaded, accessed, or deleted by the user who owns the associated transaction.
*   **Lifecycle Invariant**: Before a transaction is soft-deleted, all associated attachment files must be permanently deleted from physical storage and their database records removed to prevent orphaned resources.

---

## 9. Dashboard & Reporting

### Terminology
*   **Dashboard**: A summary interface presenting key financial metrics, recent activities, and charts.
*   **Report**: An aggregated breakdown of financial performance over a defined timeframe.

### Business Rules
*   **Multi-Currency Isolation**: Aggregate balances, income, expenses, and savings calculations must be grouped and isolated by currency code. Summing or combining amounts of different currencies without conversion is prohibited.
