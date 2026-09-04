                   SaaS Provider
                         │
                  ┌──────▼──────┐
                  │ Admin Panel │
                  └──────┬──────┘
                         │
              Tenant / Subscription
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ▼                ▼                ▼
    Tenant A          Tenant B          Tenant C
        │                │                │
     POS App          POS App          POS App


# Admin panel

- Admin panel has no api. Admin panel view should be in the blade view. And interaction can be done by Vanilla Javascript or if required use JQuery.

# POS panel

- Pos panel only provide api. The user interface is designed by vue js in separate repo.