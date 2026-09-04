Database architecture

I'd separate the tables conceptually into:

                 DATABASE
                    │
        ┌───────────┴───────────┐
        │                       │
 PROVIDER TABLES         TENANT POS TABLES
        │                       │
        │                       │
 tenants                   products
 plans                     categories
 features                  variants
 plan_features             inventory
 subscriptions             purchases
 invoices                  sales
 payments                  customers
 coupons                   suppliers