# Multi Tenant SaaS POS

Actually have two applications logically.

## SaaS Provider Admin

- It has it's own separate module. It code and logic will be in the module/Admin.

Provider Admin
│
├── Dashboard
├── Tenants
├── Users
├── Plans
├── Features
├── Add-ons
├── Subscriptions
├── Invoices
├── Payments
├── Coupons
├── Usage
├── Subscription Events
└── System Settings

## Tenant POS - Use by client's users

- It will be access by api. It UI in separate repo. It's logic and code will be in the default laravel structure.

Dashboard
│
├── POS
├── Products
├── Inventory
├── Purchases
├── Sales
├── Customers
├── Suppliers
├── Reports
├── Users
└── Settings


# Layer 1 — SaaS/Tenant

Tenant
User
Subscription
Plan
Feature
Usage
Invoice
Payment

# Layer 2 — Business

Location
Warehouse
Cash Register
Employees
Customers
Suppliers

# Layer 3 — POS/Inventory

Product
Variant
Purchase
Stock Layer
Stock Movement
Sale
Sale Item
Payment
Return