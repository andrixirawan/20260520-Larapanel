# POS System PRD

## Ringkasan

POS ini adalah modul web-first untuk operasional kasir yang tetap general untuk berbagai bisnis: retail, F&B sederhana, jasa dengan stok opsional, dan toko multi-channel. Fokus awal adalah membuat fondasi data dan flow yang aman: shift, transaksi, invoice, pembayaran, inventory ledger, finance entry, dan audit trail.

API untuk React Native Expo dibuat setelah flow web stabil. Struktur data tahap awal sengaja memakai `product_variant_id` walaupun produk saat ini belum punya variasi, agar size/color/package variant bisa ditambahkan tanpa migrasi besar di transaksi historis.

## Role dan Permission

- `super-admin`: bypass semua permission lewat `Gate::before`.
- `administrator`: mengelola produk, inventory, melihat semua shift/transaksi/finance, dan dapat melakukan override operasional.
- `cashier`: membuka/menutup shift sendiri, membuat transaksi, melihat produk aktif, dan melihat transaksi yang relevan dengan operasional kasir.
- Role masa depan: `inventory-manager`, `finance-manager`, `store-manager`, `auditor`.

Permission MVP:

- `pos.products.view`
- `pos.products.manage`
- `pos.inventory.view`
- `pos.inventory.manage`
- `pos.shifts.view`
- `pos.shifts.open`
- `pos.shifts.close`
- `pos.shifts.manage`
- `pos.sales.view`
- `pos.sales.create`
- `pos.sales.void`
- `pos.finance.view`

## Scope MVP

Legend status:

- `[x]` selesai dan sudah ada implementasi utama di repo.
- `[~]` parsial, fondasi/data sudah ada tetapi flow/UI/aturan operasional belum lengkap.
- `[ ]` belum dikerjakan.

### Tahap 1 - Core POS Web

Tujuan: kasir bisa membuka shift, menjual produk, menerima cash/dummy non-cash, sistem membuat invoice, stok berkurang, finance entry tercatat, dan administrator bisa menyiapkan produk/stok.

Deliverable:

- [x] Master produk sederhana dengan default variant.
  Progres: CRUD dasar produk POS dan default variant sudah ada, termasuk list/search dan create dari web.
- [x] Inventory stock dan movement ledger, bukan update stok tanpa jejak.
  Progres: `pos_inventory_stocks` dan `pos_inventory_movements` sudah dipakai untuk opening stock, adjustment, dan pengurangan stok saat sale.
- [x] Shift opening dan closing dengan deklarasi cash awal/akhir.
  Progres: open/close shift, expected cash, counted cash, dan cash difference sudah berjalan.
- [x] POS terminal web responsive untuk cashier/administrator.
  Progres: halaman `/pos` aktif, mobile memakai cart sheet, desktop memakai cart sidebar, dan halaman baru dipadatkan agar POS jadi fokus utama.
- [x] Invoice number unik.
  Progres: invoice number unik sudah dipakai pada sale dan disimpan immutable di transaksi.
- [x] Sale item snapshot untuk menjaga histori walaupun produk berubah.
  Progres: `sku_snapshot`, `name_snapshot`, `unit_price`, dan `cost_price_snapshot` sudah disimpan saat sale dibuat.
- [x] Payment method `cash` aktif, dan dummy `qris_dummy`, `card_dummy`, `bank_transfer_dummy`, `ewallet_dummy`.
  Progres: pilihan payment method sudah tersedia dan flow checkout menerima cash maupun dummy non-cash.
- [x] Finance entry otomatis dari pembayaran.
  Progres: sale membuat payment dan finance entry dalam transaction yang sama.
- [x] Audit log untuk event penting: product created, stock adjusted, shift opened/closed, sale created.
  Progres: event-event inti tersebut sudah dicatat melalui `PosAuditLogger`.
- [x] Permission dan menu sesuai role.
  Progres: permission POS sudah didefinisikan, role seed sudah terpasang, pembatasan backend route/action sudah aktif, dan menu sidebar utama POS sudah mengikuti permission user.

Non-goal tahap 1:

- Integrasi Midtrans produksi.
- Refund parsial, split bill kompleks, promo engine, tax engine kompleks.
- Multi-outlet dan multi-warehouse.
- Print thermal native.
- Offline-first POS.

### Tahap 2 - Operational Hardening

- [~] Void/refund dengan approval administrator dan reason wajib.
  Progres: void sale oleh administrator sudah ada lengkap dengan reason wajib, reversal stok, reversal finance, status refunded, dan audit log. Bagian refund terpisah di luar void belum dibuat.
- [ ] Cash in/out drawer movement di luar penjualan.
  Progres: belum ada model/flow drawer movement khusus di luar payment sale.
- [ ] Shift handover: kasir keluar, kasir masuk, admin approval untuk selisih cash.
  Progres: belum ada handover antar cashier atau approval threshold selisih cash.
- [ ] Receipt/invoice printable.
  Progres: ada halaman detail sale, tetapi belum ada layout print/thermal/printable action.
- [~] Stock opname dan adjustment batch.
  Progres: adjustment stok manual per variant sudah ada; batch stock opname dan workflow count belum ada.
- [ ] Low stock alert.
  Progres: stok tersedia di data model dan UI, tetapi alert/rule threshold belum ada.
- [~] Sales report per shift, cashier, product, payment method.
  Progres: list shifts, sales, finance, dan filter dasar sudah ada; report agregat khusus per dimensi belum lengkap.
- [ ] Export CSV/PDF.
  Progres: belum ada export.

### Tahap 3 - Payment Provider

- [ ] Abstraction `PaymentGateway` dengan driver `cash`, `dummy`, `midtrans`.
  Progres: method payment masih berupa enum/options dan belum masuk abstraction gateway.
- [ ] QRIS Midtrans charge, callback/webhook verification, idempotency key.
  Progres: belum ada integrasi Midtrans.
- [ ] Payment pending/expired/paid lifecycle.
  Progres: status payment dasar ada di schema, tetapi lifecycle provider belum diimplementasikan.
- [ ] Reconcile provider settlement vs finance entry.
  Progres: belum ada.

### Tahap 4 - Scale dan Mobile API

- [~] API token/mobile auth untuk POS Expo app.
  Progres: mobile auth/token generik sudah ada di backend, tetapi API domain khusus POS untuk Expo app belum dibuat.
- [ ] Offline queue dengan idempotency key dan conflict handling.
  Progres: belum ada.
- [ ] Multi-store, register/terminal, warehouse/location.
  Progres: belum ada model/store/register/warehouse; saat ini shift masih scoped per cashier.
- [~] Product variants UI.
  Progres: data model variant sudah dipakai sebagai fondasi transaksi, tetapi UI masih fokus ke default variant tunggal.
- [ ] Role khusus inventory/finance/store manager.
  Progres: role masa depan baru tercatat di dokumen, belum diimplementasikan.

## Flow Utama

### Open Shift

1. Cashier login.
2. Sistem cek apakah user punya shift `open`.
3. Jika belum ada, sistem menampilkan referensi saldo kas terakhir yang sudah direkonsiliasi sebagai baseline anti-fraud.
4. Cashier wajib menghitung fisik uang di laci lalu input `opening_cash`.
5. Sistem membuat shift dengan `cashier_id`, `opened_by`, `opened_at`, `opening_cash`.
6. Semua transaksi cash harus terikat ke shift open.

Kontrol anti-fraud:

- Satu cashier hanya boleh punya satu open shift.
- Opening cash tidak bisa diedit langsung setelah shift dibuat.
- Sistem menampilkan saldo referensi dari shift tertutup terakhir agar kasir tahu nominal yang seharusnya tersedia sebelum input manual.
- Koreksi shift harus lewat flow adjustment/approval di tahap berikutnya.
- Event dicatat di audit log.

### Sale

1. Cashier memilih produk aktif.
2. Cart menghitung subtotal, discount, tax, total.
3. Payment dipilih.
4. Untuk `cash`, cashier input uang diterima dan sistem validasi cukup.
5. Untuk dummy non-cash, sistem membuat reference dummy dan menandai paid untuk simulasi flow.
6. Sistem membuat sale, sale items, payments, inventory movements, finance entries dalam DB transaction.
7. Invoice number dibuat unik dan immutable.

Kontrol anti-fraud:

- Harga dan nama produk disnapshot ke sale item.
- Stok dikunci saat transaksi (`lockForUpdate`) sebelum dikurangi.
- Transaksi harus atomic: sale gagal berarti stok/finance tidak berubah.
- Void/refund tidak menghapus transaksi, hanya membuat reversal di tahap berikutnya.

### Close Shift

1. Sistem menampilkan snapshot kas berjalan: `opening_cash + total cash sale = expected cash`.
2. Cashier hitung fisik uang di laci lalu input `counted_cash`.
3. Sistem simpan counted cash dan difference.
4. Shift menjadi `closed`.

Kontrol anti-fraud:

- Counted cash wajib.
- Difference tersimpan permanen.
- Expected cash terlihat sebelum submit agar kasir bisa recount jika angka fisik tidak cocok.
- Admin dapat melihat semua shift dan selisih.
- Tahap berikutnya menambahkan approval untuk selisih melewati threshold.

## Data Model MVP

### `pos_products`

- `id`
- `name`
- `sku` nullable unique
- `status`: active/inactive
- `description` nullable
- `metadata` json nullable
- `created_by`, `updated_by`
- timestamps, soft deletes

### `pos_product_variants`

- `id`
- `product_id`
- `name` nullable, default `Default`
- `sku` nullable unique
- `barcode` nullable unique
- `price`
- `cost_price` nullable
- `track_inventory` boolean
- `allow_backorder` boolean
- `is_default` boolean
- `metadata` json nullable
- timestamps, soft deletes

### `pos_inventory_stocks`

- `id`
- `product_variant_id` unique
- `quantity_on_hand`
- `quantity_reserved`
- timestamps

### `pos_inventory_movements`

- `id`
- `product_variant_id`
- `actor_id`
- `type`: opening, adjustment, sale, sale_void, refund
- `quantity_before`
- `quantity_delta`
- `quantity_after`
- `reference_type`, `reference_id`
- `notes` nullable
- `metadata` json nullable
- timestamps

### `pos_shifts`

- `id`
- `cashier_id`
- `opened_by`, `closed_by`
- `status`: open/closed
- `opening_cash`
- `expected_cash` nullable
- `counted_cash` nullable
- `cash_difference` nullable
- `opened_at`, `closed_at`
- `notes` nullable
- `metadata` json nullable
- timestamps

### `pos_sales`

- `id`
- `shift_id`
- `cashier_id`
- `invoice_number` unique
- `status`: completed/voided/refunded
- `payment_status`: paid/pending/failed/refunded
- `subtotal`
- `discount_total`
- `tax_total`
- `total`
- `paid_total`
- `change_total`
- `customer_name` nullable
- `notes` nullable
- `completed_at`
- `voided_by`, `voided_at`, `void_reason`
- `metadata` json nullable
- timestamps

### `pos_sale_items`

- `id`
- `sale_id`
- `product_id`
- `product_variant_id`
- `sku_snapshot`
- `name_snapshot`
- `quantity`
- `unit_price`
- `discount_total`
- `tax_total`
- `line_total`
- `cost_price_snapshot`
- `metadata` json nullable
- timestamps

### `pos_payments`

- `id`
- `sale_id`
- `method`
- `status`
- `amount`
- `received_amount`
- `change_amount`
- `provider`
- `provider_reference`
- `metadata` json nullable
- timestamps

### `pos_finance_entries`

- `id`
- `entry_date`
- `shift_id`
- `source_type`, `source_id`
- `type`: sale_income, cash_difference, adjustment
- `direction`: debit/credit
- `payment_method`
- `amount`
- `created_by`
- `notes` nullable
- `metadata` json nullable
- timestamps

### `pos_audit_logs`

- `id`
- `actor_id`
- `event`
- `subject_type`, `subject_id`
- `before` json nullable
- `after` json nullable
- `ip_address` nullable
- `user_agent` nullable
- `metadata` json nullable
- timestamps

## UI/UX Web

- Screen cashier utama: product search/grid, cart sticky, payment panel, shift status.
- Responsive: desktop memakai 2-3 kolom, mobile menjadi stacked flow.
- Role-based screens:
  - Cashier: POS terminal, shift status, invoice terakhir.
  - Administrator: product/inventory management, all shift/sales/finance views.
- Empty state jelas: belum ada shift, belum ada produk, stok kosong.
- Aksi kritikal memakai confirmation dan reason pada tahap lanjutan.

## Prinsip Extensibility

- Semua flow transaksi lewat service layer, bukan langsung dari controller.
- Payment method memakai field provider/reference untuk Midtrans nanti.
- Sale item selalu snapshot agar reporting historis stabil.
- Inventory memakai ledger agar bisa audit dan replay.
- Audit log dibuat generic agar event baru dapat di-inject.
- Controller web saat ini dapat diparalelkan menjadi API mobile tanpa mengganti domain service.

## Risiko dan Mitigasi

- Race condition stok: gunakan DB transaction dan row lock.
- Cash fraud: opening/closing cash immutable, difference tercatat.
- Void/refund disalahgunakan: tahap 2 wajib approval dan reversal ledger.
- Payment callback palsu: tahap 3 wajib signature verification dan idempotency.
- Produk berubah setelah transaksi: sale item snapshot.
