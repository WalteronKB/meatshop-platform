-- Add shop_id support for suppliers and purchase orders

ALTER TABLE mrb_suppliers
  ADD COLUMN IF NOT EXISTS shop_id INT NULL AFTER status;

ALTER TABLE mrb_purchase_orders
  ADD COLUMN IF NOT EXISTS shop_id INT NULL AFTER status;

-- Helpful indexes for scoped queries
CREATE INDEX IF NOT EXISTS idx_suppliers_shop_id ON mrb_suppliers(shop_id);
CREATE INDEX IF NOT EXISTS idx_purchase_orders_shop_id ON mrb_purchase_orders(shop_id);

-- Backfill purchase order shop from linked supplier when available
UPDATE mrb_purchase_orders po
JOIN mrb_suppliers s ON po.supplier_id = s.supplier_id
SET po.shop_id = s.shop_id
WHERE po.shop_id IS NULL;
