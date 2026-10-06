# InvenTrack — Page Navigation Guide

A walkthrough of every screen in the web app, grouped the same way the sidebar groups them.

**Demo logins** (see `database/seeders/DatabaseSeeder.php` for the full list):

| Role | Account | Password |
|---|---|---|
| Super Admin | `owner@inventory.test` | `123456789` |
| Manager | `manager@inventory.test` | `123456789` |

**The one rule that explains everything:** every page filters to `branch_id = yours` when signed in as a **manager**, and shows **all 6 branches** when signed in as **owner/admin**. Flip between the two accounts to see the difference live.

**Sidebar behavior:** the six group headers (Planning → Compliance) expand/collapse. The open/closed state of each group — plus scroll position — is remembered per browser tab via `sessionStorage` (`sidebar_state`), so the app comes back the way you left it.

---

## Top-level (above the groups)

### 🏠 Dashboard — `/dashboard`
Landing page after login. Cards across the top: **revenue this month vs last month**, stock-leak value (negative shift variances), **active discrepancy alerts**, workforce count (staff + managers; the owner account is excluded). Charts show monthly revenue for the year. It's the health check, not a workspace.

### 🗺️ Map — `/map`
Interactive map with toggleable layers: **Branches**, **Suppliers**, **Warehouses**. Clicking a branch pin shows its stats: pending alerts, low-stock count, today's sales, staff count. Quick way to spot which branch is bleeding.

---

## 📅 Planning

### Calendar — `/calendar`
Month view with events color-coded by type; week strip and "upcoming" list below. Click a day / use the form to **create meetings, tasks, or events**, assign a branch and attendees. Managers are locked to their own branch automatically.

### Leave Requests — `/leave`
Staff-filed leave appears here; **approve/reject** with the status buttons, or file one on someone's behalf.

---

## 🏢 Business

### Branches — `/branches`
One card per branch with today's transactions and staff. Includes the **Recipes tab**: pick a product, see/edit its ingredient formula per size (regular/large) — this is what POS sales deduct against.

### Employees — `/branches/{id}?tab=workers`
The worker directory inside a branch. Per worker: **profile** (contact, schedule, skills), **attendance** (clock-in/out history), **activity feed** (transactions, shifts, discrepancies), **peer reviews**, **goals**. Admin manages any branch; manager only their own.

### Hiring — `/hiring`
Three tabs: **Openings** (post/edit job slots), **Applicants** (move candidates through stages), **Pipeline** (visual funnel: Applied → Shortlisted → Interviewed → Hired/Rejected).

### Customers — `/customers`
Loyalty registry with tier badges (bronze → platinum), **add points**, record **feedback**, search by name/email/phone, filter by branch.

---

## 💰 Finance

### Profit & Loss — `/profit-loss`
Statement builder; switch period (month/quarter/year/custom) and branch. Revenue minus payments, payroll, and purchases.

### Payments — `/payments`
Operating-cost ledger (rent, utilities, wages…). **Record** with category + method (cash/bank transfer/GCash/check) + optional receipt photo, **mark paid**, edit, delete. Managers: own branch only.

### Invoices — `/invoices`
Sales invoice list; create, **update status** (draft → sent → paid), delete.

### Salary — `/salary`
Worker list with rates, **generate payslips** for a period, view payslip, **mark paid**. Changing someone's pay rate is the **admin-only** action here.

### Receipts — `/receipts`
**Scan a receipt photo** (OCR) and reconcile it against recorded purchases; list + summary totals.

---

## 📊 Analytics

### Analytics Dashboard — `/analytics`
The biggest page. Tabs toggle between **Inventory** and **Sales** views, branch comparison charts, recent transactions, active alerts. The **Export CSV** button is admin-only (managers get 403 — by design).

### Reports — `/reports`
Discrepancy flag center: recent (7 days) vs older/resolved, filter by branch, **download a flag as PDF**.

### Custom Reports — `/reports/custom`
Pick dimensions/date ranges and generate an on-demand report.

### Forecasting — `/forecasting`
Takes the last 6 months of sales/costs/purchases and projects the next month.

### Benchmarking — `/benchmarking`
Branch-vs-branch leaderboard (revenue, leakage, costs). **Owners only — a manager who opens it gets bounced back to the dashboard with an error.**

---

## ⚙️ Operations

### Inventory — `/inventory`
"Inventory intelligence": **items expiring within 7 days**, stock movements, low-stock vs capacity. This is where you catch waste.

### Purchase Orders — `/purchase-orders`
Full PO lifecycle: **create → update status (approve/receive) → delete**, tied to suppliers/ingredients.

### Ingredient Pricing — `/pricing/ingredients`
Per-ingredient unit cost from the primary supplier; feeds the what-if margin simulator.

---

## 🛡️ Compliance

### Equipment — `/equipment`
Machines per branch with **maintenance schedules**; log service events.

### Health & Safety — `/safety`
**Create checklists** (daily opening/closing items), view completion history.

### Audit Trail — `/audit`
Chronological **who-did-what** log of every write action. Your evidence trail after an incident.

### Legal Papers — `/legal-papers`
Business permits/licenses. Both roles **view + download**; **upload/edit/delete is admin-only**.

---

## Below the divider (all roles, including staff)

| Page | URL | Notes |
|---|---|---|
| Settings | `/settings` | Profile, password, and the **payment-categories list** Finance uses |
| Mail/Messages | `/mail` | Internal notices |
| Help Center | `/help` | FAQ |

---

## Pages with no sidebar link

| Page | URL | Notes |
|---|---|---|
| Logistics | `/logistics` | Per-branch stock: estimated (system) vs on-site (counted) amounts, flags tab |
| Suppliers | `/suppliers` | Directory, link ingredients with unit costs, record purchases |
| Setup wizard | `/setup` | First-run ingredient/product/stock entry |
| API docs | `/api-docs` | Machine-readable endpoint reference |
| Staff dashboard | `/staff/dashboard` | Staff-only: clock in/out, stock & till verification, shift close |

---

## Suggested demo flow

**Dashboard → Map** (spot a low-stock branch) **→ Reports** (review its flag) **→ Payments** (log a repair cost) **→ Salary** (generate payslips).

That loop touches every subsystem and shows off the admin-vs-manager branch scoping naturally.
