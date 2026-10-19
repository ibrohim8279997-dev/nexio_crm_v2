<?php
namespace App\Plugins;

/**
 * Server-side manifest validation.
 *
 * A declared permission is NOT a granted permission: this class only validates the shape and
 * the platform policy (e.g. a manifest claiming to be official must actually come from the
 * official publisher). Granting happens separately in PermissionEngine, by Super Admin.
 */
class Manifest
{
    public const REQUIRED = ['id', 'name', 'version', 'type', 'author', 'category', 'api_version', 'min_nexio_version'];

    /** Keys a manifest may declare. Anything else is rejected so hidden fields cannot smuggle intent. */
    public const ALLOWED = [
        'id', 'name', 'display_name', 'version', 'type', 'author', 'category', 'description', 'tagline',
        'icon', 'homepage', 'api_version', 'min_nexio_version', 'dependencies', 'permissions', 'hooks',
        'settings_schema', 'feature_flags', 'usage_metrics', 'runtime', 'signature', 'pricing', 'entry',
        'data_tables',
    ];

    /**
     * Tables a plugin may NEVER claim as its own, whatever its manifest says.
     *
     * `data_tables` exists so that the «purge» uninstall policy can drop the plugin's own rows.
     * Without an outright denylist a malicious manifest could declare `orders` or `users` and turn
     * «uninstall my plugin» into «delete this company's CRM data». Core tables (schema 0001) are
     * listed explicitly; platform plugin tables (schema 0002) are protected by name prefix too.
     */
    public const CORE_TABLES = [
        'activities', 'ai_agents', 'ai_approvals', 'ai_calls', 'ai_campaigns', 'ai_evaluations',
        'ai_tool_calls', 'audit_logs', 'automations', 'billing_events', 'communications', 'consents',
        'contacts', 'deals', 'documents', 'feature_flags', 'integrations', 'invitations', 'invoices',
        'leads', 'nexio_service_requests', 'order_items', 'orders', 'organizations', 'payments',
        'pipeline_stages', 'pipelines', 'plan_features', 'plans', 'products', 'project_milestones',
        'projects', 'sessions', 'subscriptions', 'suppression_list', 'tasks', 'tenant_memberships',
        'tenant_settings', 'tenants', 'tickets', 'usage_records', 'users', 'webhook_events',
    ];

    /** Names a plugin-owned table may use: lowercase, underscore-separated, bounded length. */
    public const DATA_TABLE_PATTERN = '/^[a-z][a-z0-9_]{2,48}$/';

    /** True when the name is a table this plugin is allowed to own (and therefore to purge). */
    public static function isPurgableTableName(string $name): bool
    {
        if (!preg_match(self::DATA_TABLE_PATTERN, $name)) {
            return false;
        }
        if (str_starts_with($name, 'plugin_') || str_starts_with($name, 'sqlite_')) {
            return false;
        }
        return !in_array($name, self::CORE_TABLES, true);
    }

    /** Permissions a plugin may ever request. Anything outside this catalog is rejected outright. */
    public const PERMISSION_CATALOG = [
        // CRM — read, write and delete are always separate capabilities.
        'crm.clients.read' => false,
        'crm.clients.write' => false,
        'crm.clients.delete' => true,
        'crm.clients.export' => true,
        'crm.read' => false,
        'crm.contacts.read' => false,
        'crm.contacts.write' => false,
        'crm.orders.read' => false,
        'crm.orders.create' => false,
        'crm.orders.update' => false,
        'crm.products.read' => false,
        // AI assistant
        'ai.read' => false,
        'ai.write' => false,
        // Delivery (universal layer)
        'delivery.read' => false,
        'delivery.create' => false,
        'delivery.update' => false,
        'delivery.cancel' => false,
        'delivery.track' => false,
        // Telephony
        'telephony.calls.read' => false,
        'telephony.calls.create' => false,
        'telephony.calls.update' => false,
        'telephony.recordings.read' => true,
        'telephony.numbers.manage' => false,
        // Platform plumbing
        'events.subscribe' => false,
        'settings.write' => false,
        'usage.self.read' => false,
    ];

    public const OFFICIAL_AUTHOR = 'Nexio Lab';

    /**
     * @return array{ok:bool,errors:string[],manifest:array,sensitive_permissions:string[]}
     */
    public static function validate(array $m): array
    {
        $errors = [];

        foreach (self::REQUIRED as $key) {
            if (!isset($m[$key]) || $m[$key] === '' || $m[$key] === []) {
                $errors[] = "Manifest must declare «{$key}»";
            }
        }

        foreach (array_keys($m) as $key) {
            if (!in_array($key, self::ALLOWED, true)) {
                $errors[] = "Unknown manifest field «{$key}»";
            }
        }

        $id = (string) ($m['id'] ?? '');
        if ($id !== '' && !preg_match('/^[a-z0-9][a-z0-9\-]*(\.[a-z0-9][a-z0-9\-]*)+$/', $id)) {
            $errors[] = 'Manifest id must be a dotted lowercase identifier, e.g. nexio.delivery';
        }
        if (strlen($id) > 64) {
            $errors[] = 'Manifest id is longer than 64 characters';
        }

        $version = (string) ($m['version'] ?? '');
        if ($version !== '' && !Semver::isValid($version)) {
            $errors[] = 'Manifest version must be semantic (e.g. 1.2.0)';
        }

        $type = (string) ($m['type'] ?? '');
        if ($type !== '' && !in_array($type, ['official', 'third_party'], true)) {
            $errors[] = 'Manifest type must be official or third_party';
        }

        // Spoofing guard: only the official publisher may declare type=official.
        if ($type === 'official' && strcasecmp((string) ($m['author'] ?? ''), self::OFFICIAL_AUTHOR) !== 0) {
            $errors[] = 'A manifest declaring type=official must be authored by ' . self::OFFICIAL_AUTHOR;
        }

        $minNexio = (string) ($m['min_nexio_version'] ?? '');
        if ($minNexio !== '' && !Semver::isValid($minNexio)) {
            $errors[] = 'min_nexio_version must be semantic';
        }

        if (isset($m['permissions'])) {
            if (!is_array($m['permissions']) || array_is_list($m['permissions']) === false) {
                $errors[] = 'permissions must be a list';
            } else {
                foreach ($m['permissions'] as $p) {
                    if (!is_string($p) || !array_key_exists($p, self::PERMISSION_CATALOG)) {
                        $errors[] = 'Unknown permission «' . (is_scalar($p) ? (string) $p : gettype($p)) . '»';
                    }
                }
                if (count($m['permissions']) !== count(array_unique($m['permissions']))) {
                    $errors[] = 'permissions contains duplicates';
                }
            }
        }

        if (isset($m['dependencies'])) {
            if (!is_array($m['dependencies'])) {
                $errors[] = 'dependencies must be a list';
            } else {
                foreach ($m['dependencies'] as $d) {
                    if (is_string($d)) {
                        continue; // shorthand: "nexio.delivery" or "nexio.delivery@^1.0.0"
                    }
                    if (!is_array($d) || empty($d['plugin_id'])) {
                        $errors[] = 'Each dependency must be a plugin id string or an object with plugin_id';
                        continue;
                    }
                    $c = (string) ($d['constraint'] ?? '*');
                    if ($c !== '*' && trim($c) === '') {
                        $errors[] = "Dependency «{$d['plugin_id']}» has an empty version constraint";
                    }
                }
            }
        }

        // A runtime entry point is only meaningful for code that ships with the platform.
        if (isset($m['runtime']['entry']) && $type !== 'official') {
            $errors[] = 'runtime.entry may only be declared by official plugins';
        }

        if (isset($m['data_tables'])) {
            if (!is_array($m['data_tables']) || array_is_list($m['data_tables']) === false) {
                $errors[] = 'data_tables must be a list of table names';
            } else {
                foreach ($m['data_tables'] as $t) {
                    if (!is_string($t)) {
                        $errors[] = 'Each data_tables entry must be a table name string';
                        continue;
                    }
                    if (!self::isPurgableTableName($t)) {
                        $errors[] = "data_tables may not claim «{$t}»: reserved or invalid table name";
                    }
                }
                if (count($m['data_tables']) !== count(array_unique($m['data_tables']))) {
                    $errors[] = 'data_tables contains duplicates';
                }
            }
        }

        // Only official plugins may declare owned tables. A third-party manifest is unreviewed code
        // and must not be able to point the purge policy at arbitrary tables.
        if ($type !== 'official' && !empty($m['data_tables'])) {
            $errors[] = 'Only official plugins may declare data_tables';
        }

        $declared = self::permissionsOf($m);
        $sensitive = array_values(array_filter($declared, fn ($p) => self::PERMISSION_CATALOG[$p] ?? false));

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'manifest' => self::normalise($m),
            'sensitive_permissions' => $sensitive,
        ];
    }

    /** Declared permissions, normalised to a string list. */
    public static function permissionsOf(array $m): array
    {
        $out = [];
        foreach ((array) ($m['permissions'] ?? []) as $p) {
            if (is_string($p)) $out[] = $p;
        }
        return array_values(array_unique($out));
    }

    /** Declared dependencies, normalised to [{plugin_id, constraint, optional}]. */
    public static function dependenciesOf(array $m): array
    {
        $out = [];
        foreach ((array) ($m['dependencies'] ?? []) as $d) {
            if (is_string($d)) {
                [$pid, $constraint] = array_pad(explode('@', $d, 2), 2, '*');
                $out[] = ['plugin_id' => trim($pid), 'constraint' => trim($constraint) ?: '*', 'optional' => false];
                continue;
            }
            if (is_array($d) && !empty($d['plugin_id'])) {
                $out[] = [
                    'plugin_id' => (string) $d['plugin_id'],
                    'constraint' => (string) ($d['constraint'] ?? '*') ?: '*',
                    'optional' => (bool) ($d['optional'] ?? false),
                ];
            }
        }
        return $out;
    }

    /** Plugin-owned data tables, normalised to a unique, validated string list. */
    public static function dataTablesOf(array $m): array
    {
        $out = [];
        foreach ((array) ($m['data_tables'] ?? []) as $t) {
            if (is_string($t) && self::isPurgableTableName($t)) {
                $out[] = $t;
            }
        }
        return array_values(array_unique($out));
    }

    /** Declared usage metrics, normalised to a unique string list. */
    public static function metricsOf(array $m): array
    {
        $out = [];
        foreach ((array) ($m['usage_metrics'] ?? []) as $x) {
            if (is_string($x)) {
                $out[] = $x;
            } elseif (is_array($x) && isset($x['metric'])) {
                $out[] = (string) $x['metric'];
            }
        }
        return array_values(array_unique($out));
    }

    /** @return array{plugin_id:string,permissions:string[],sensitive:string[]} */
    public static function normalise(array $m): array
    {
        return [
            'plugin_id' => (string) ($m['id'] ?? ''),
            'name' => (string) ($m['name'] ?? ''),
            'version' => Semver::normalise((string) ($m['version'] ?? '0.0.0')),
            'type' => (string) ($m['type'] ?? 'third_party'),
            'author' => (string) ($m['author'] ?? ''),
            'category' => strtolower((string) ($m['category'] ?? 'other')),
            'description' => (string) ($m['description'] ?? ''),
            'api_version' => (string) ($m['api_version'] ?? '1.0'),
            'min_nexio_version' => Semver::normalise((string) ($m['min_nexio_version'] ?? '1.0.0')),
            'permissions' => self::permissionsOf($m),
            'dependencies' => self::dependenciesOf($m),
            'usage_metrics' => self::metricsOf($m),
            'data_tables' => self::dataTablesOf($m),
            'hooks' => array_values(array_filter((array) ($m['hooks'] ?? []), 'is_string')),
            'settings_schema' => is_array($m['settings_schema'] ?? null) ? $m['settings_schema'] : [],
            'feature_flags' => array_values(array_filter((array) ($m['feature_flags'] ?? []), 'is_string')),
            'runtime' => is_array($m['runtime'] ?? null) ? $m['runtime'] : [],
            'entry' => isset($m['runtime']['entry']) ? (string) $m['runtime']['entry'] : null,
        ];
    }
}
