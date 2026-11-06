# Nexio Inventory

## Scope

Nexio Inventory is an official, tenant-scoped plugin. It uses the existing Nexio session, tenant context, RBAC, Plugin Runtime, usage meter and `audit_logs`. It does not create a second authentication or permission system.

## Implemented workflows

- products, warehouses and stock balances;
- receive, issue and inter-warehouse transfer;
- reservations and release;
- suppliers and purchase orders;
- purchase approval;
- partial receipts and receipt-to-stock posting;
- supplier returns;
- inventory counts, variance capture and approval adjustments;
- inventory value, received value, issued value and COGS summary;
- idempotent runtime calls and tenant-scoped audit records.

## API surface

All operations use the existing guarded endpoint:

`POST /api/v1/plugins/nexio.inventory/operations/{operation}`

Supported operations are declared by the manifest and exposed through `PluginRuntime`; a caller cannot invoke an undeclared operation. The tenant id is read from the authenticated session, never from request input.

## Data safety

Stock changes happen in transactions. Foreign product, warehouse, supplier, purchase and count identifiers resolve only inside the current tenant. Issue, return, reservation and receipt operations reject insufficient or inconsistent quantities. Every successful or failed plugin operation is recorded through the existing plugin audit trail.

## Deployment

1. Set `PLUGIN_SECRETS_KEY` as required by the shared plugin platform.
2. Run `php artisan migrate --force`.
3. Run the existing plugin seeder so the manifest is registered and plan limits are created.
4. Install and enable `nexio.inventory` from Marketplace or Plugin Manager.
5. Create a warehouse and products before posting movements.

## Known environment limitation

The repository environment used for this implementation does not contain the PHP executable, so Laravel migrations and PHPUnit must be executed in the project runtime/CI environment. The frontend production build is verified with `npm.cmd run build`.
