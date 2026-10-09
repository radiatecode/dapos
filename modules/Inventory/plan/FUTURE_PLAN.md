# Add supplier credit/refund tables?

I recommend eventually having:

supplier_transactions

rather than putting everything into purchase_returns.

For example:

supplier_transactions

id
tenant_id
supplier_id
transaction_type
reference_type
reference_id
debit
credit
balance
transaction_date

Possible transaction types:

PURCHASE
PURCHASE_RETURN
PAYMENT
REFUND
OPENING_BALANCE
ADJUSTMENT

Then:

Purchase
    → Supplier payable increases

Purchase Return
    → Supplier payable decreases

Payment
    → Supplier payable decreases

Refund
    → Supplier balance/financial transaction recorded

This gives you a proper supplier statement.

What happens financially when purchase return happened?

A purchase return isn't just inventory.

You also need to track what the supplier owes you.

For example:

Purchase
100 × ৳100
= ৳10,000

Return:

10 × ৳100
= ৳1,000

Supplier should now owe you:

৳1,000

Depending on your accounting model, that can be handled as:

Supplier credit
Supplier balance
-৳1,000

Then later:

New purchase = ৳5,000
Supplier credit = ৳1,000

Payable = ৳4,000

Or:

Supplier refund

Supplier sends:

৳1,000

and you record the refund payment.

For your current system, I'd start with supplier credit + refund tracking, rather than trying to build a complete accounting system immediately.


# Preset Reason for purchase return use it vue: 
Damaged, Wrong product, Wrong quantity supplied, Expired/near-expiry goods, Quality issue, Supplier sent a different variant etc.