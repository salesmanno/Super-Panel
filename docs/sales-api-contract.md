# Sales admin API contract

New interface: `modules/sales_content.php`, `assets/sales-admin.js`,
`assets/sales-admin.css`. Shared Bootstrap 5/dashboard theme. No public storefront.
Three existing dashboard subtabs will become Kasse (`sales-varesalg`),
Produkter og lager (`sales-items`), Salg (`sales-transactions`).

All operations use `/api/sales.php?action=ACTION`. Responses JSON
`{success:true,...}`; failures have `success:false,message` and appropriate HTTP
status. All POST bodies JSON require `csrf_token`. Errors preserve drafts.
Minor amounts are integer øre. Product SQL prices remain decimal strings.

GET actions:
- `bootstrap`: `{csrf_token,tax_registered,products,methods,method_error,cart,pending_order}`.
  `methods`: cash first/default, then actual company gateway configurations:
  `{id:null|gatewayUUID,method_type,method_name,available,is_demo,reason}`.
  Unavailable/unsupported methods cannot be selected. No hardcoded provider options.
- `products`: `{products,tax_registered,methods,method_error}`.
- `cart`: `{cart,pending_order}`.
- `orders`: `{orders}`: company Sales ledger groups, newest first (max 200):
  `{id,order_number,created_at,total_minor,payment_status,payment_method,status}`.
- `order&order_id=UUID`: `{order}` receipt/detail, immutable ordered items.
- `fields&product_id=UUID`: `{fields}` visible product-specific dynamic definitions,
  each with field_name,field_label,field_type,is_required,default_value,options,
  validation_rules,sort_order. Empty means no customisation needed.

Product shape: SQL fields `id,name,description,sku,barcode,category_id,cost_price,
selling_price,discount_percent,discount_starts_at,discount_ends_at,img_product,
tax_class,tax_rate,item_taxable,unit,reorder_level,low_stock_threeshold,
stock_quantity,is_active,is_backorder,visibility,currency,updated_at`.
Also `price_minor` effective current gross price; `has_fields` boolean.
No synthetic categories; category filtering may use existing IDs only when
meaningful names are available; otherwise leave it out.

Cart: `{id:null|UUID,revision:string,items:[],total_minor,subtotal_minor,tax_minor}`.
Items: `{id,product_id,name,img_product,quantity,notes,form_data,unit_price_minor,
total_minor,subtotal_minor,tax_minor,available}`. Quantity >=1, zero removes via remove.
Every cart mutation returns `{cart}`. Serialize mutations; preserve/reuse the
same mutation_id (UUID) when retrying an uncertain mutation. Refresh conflicts
without losing form drafts; 409 means no mutation was applied.

POST actions:
- `product_save`: `{id?:UUID,name,selling_price,cost_price?,stock_quantity? (creation
  only),img_product?,description?,visibility:kiosk|online|both,is_backorder:0|1,
  is_active:0|1,tax_class?,tax_rate?,item_taxable?,unit?,reorder_level?,
  low_stock_threeshold?,discount_percent?,discount_starts_at?,discount_ends_at?,
  expected_revision?}`. Returns `{product}`. Existing products require their
  latest SHA256 revision. Gross prices incl VAT. Non-tax
  registered: do NOT show VAT options; backend forces exempt/rate0/taxable0.
  Code and SKU server-generated, stable, readonly output (not credential fields).
  Online/both cannot save without usable configured online payment.
- `stock`: `{product_id,quantity:nonnegativeInteger,reason,expected_quantity}`:
  absolute available stock adjustment with required reason; returns `{product}`.
- `add`: `{product_id,quantity,notes,form_data:{name:value},revision,mutation_id}`.
  Required/select/number/date/email/etc fields validated server-side; prompt
  for configured fields before adding. Different answers/notes stay separate.
- `quantity`: `{line_id,quantity,revision,mutation_id}`.
- `remove`: `{line_id,revision,mutation_id}`.
- `checkout`: `{revision,payment_method:cash|vipps,gateway_id?:UUID,
  received_minor?:integer,phone?:string,expected_total_minor}`. Cash requires
  explicit confirmation with amount tendered >= total, including explicit
  handling of zero total. Returns `{order}` (paid for cash, pending for Vipps).
  Do not clear the cart on a network error: refresh cart/pending_order to resume.
- `payment_status`: `{order_id}` rechecks provider and captures only authorized
  payment; returns `{order}`. Poll only while shown/pending, no overlapping calls.
  Handle unknown, awaiting, authorized, captured/paid, aborted, expired, declined.
  Only `payment_status=paid` means complete. Pending/unknown must remain visible.
- `cancel_payment`: `{order_id}` requests safe cancellation and reconciles;
  returns `{order}`. Accepted request alone is NOT final cancellation.
- `next_customer`: no additional data; returns `{cart}`. Blocked if unresolved
  pending payment exists; resets cash selection client-side after success.
- `receipt_email`: `{order_id,email,mutation_id}` queues an optional receipt for a
  verified paid sale. Requires current `email_configs.automail_sales_order=1`
  and the active company template **sales_receipt**, never sale_receipt.
  Same mutation and recipient returns the existing queue ID, not a duplicate.
  Returns `{queued,queue_id,message}`. A queued message is not a delivery claim.

Receipt `order`: `{id,order_number,created_at,status,payment_method,payment_status,
provider_state,total_minor,subtotal_minor,tax_minor,received_minor,change_minor,
items:[{id,product_id,description,sku,quantity,unit_price_minor,list_price_minor,
discount_percent,tax_rate,tax_minor,subtotal_minor,total_minor,notes,form_data}]}`.
No credentials or raw provider responses in any response.
New receipts also expose `tenant_admin_id` and its verified name snapshot as
`tenant_admin_name`. The UI labels this as the sale's original registering
administrator, not the actor who later processed an automatic capture.
They include `receipt_email_enabled` and `receipt_email_reason`. Printing does
not depend on the automailer switch. Email is requested explicitly after sale.

The legacy `is_backorder` data flag is preserved for compatibility, but is not a
checkout permission. There is no “sell without stock” option: catalog, cart and
checkout consistently require available stock.

Dynamic field editor uses existing shared `EntityFormFields.mount(host,{entityType:
'sales_product',entityId:product.id,eventLayout:true,onSaved})`, and its normal
load/save APIs; built-in presets and custom fields remain inside product admin.
Read the actual shared editor API before integrating. Saving closes the editor;
allow field ordering; guard dirty drafts. Field definitions are exclusively in
dynamic_form_fields with entity_type=sales_product, entity_id=product UUID.

Code128 rendering will be served as same-origin SVG:
`/api/sales_barcode.php?product_id=UUID` (scoped authenticated Sales read).
Text copy and label print must use the exact stored code, never mutate on print.
No product/customer autocomplete in scanner/search/generated code fields; generated
codes are text/output, never password controls. All image/text values escaped.

Fullscreen uses the SAME cart DOM/state, not a second checkout implementation.
Support 320,390,768,1024,1280 widths without horizontal page overflow; tablet-first.
No emojis or decorative tags on product tiles; user wants images (img_product),
names and prices. Crucial stock/disabled states remain accessible.
