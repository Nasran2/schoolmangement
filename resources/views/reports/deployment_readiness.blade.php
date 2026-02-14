<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Deployment Readiness Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 16px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #ddd; }
        h3 { font-size: 12px; margin: 12px 0 6px; }
        .meta { font-size: 11px; color: #444; margin-bottom: 12px; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 10px; }
        .ok { background: #e7f8ee; color: #0f5b2f; }
        .action { background: #fff1f1; color: #7a1f1f; }
        .recommended { background: #fff7e6; color: #6b4f00; }
        .manual { background: #eef2ff; color: #1f2d7a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e5e5; padding: 6px; vertical-align: top; }
        th { background: #f7f7f7; font-size: 11px; text-align: left; }
        .small { font-size: 10px; }
        .muted { color: #666; }
        .page-break { page-break-after: always; }
        ul { margin: 0; padding-left: 18px; }
        li { margin: 2px 0; }
        code { font-family: DejaVu Sans, monospace; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Deployment Readiness Report</h1>
    <div class="meta">
        Generated at: <strong>{{ $generated_at }}</strong><br>
        App: <strong>{{ $app['name'] }}</strong> — Laravel <strong>{{ $app['laravel_version'] }}</strong> — PHP <strong>{{ $app['php_version'] }}</strong><br>
        Environment: <strong>{{ $app['env'] }}</strong> | Debug: <strong>{{ $app['debug'] ? 'ON' : 'OFF' }}</strong> | URL: <strong>{{ $app['url'] }}</strong>
    </div>

    @php
        $customerCompany = (string) ($customer['company'] ?? '');
        $customerSchool = (string) ($customer['school'] ?? '');
        $customerHighlights = (array) ($customer['highlights'] ?? []);
        $hasCustomerBlock = trim($customerCompany) !== '' || trim($customerSchool) !== '' || !empty($customerHighlights);
    @endphp

    @if($hasCustomerBlock)
        <h2>Customer Overview</h2>
        <table>
            <tbody>
            @if(trim($customerSchool) !== '')
                <tr>
                    <td style="width: 22%"><strong>School</strong></td>
                    <td class="small">{{ $customerSchool }}</td>
                </tr>
            @endif
            @if(trim($customerCompany) !== '')
                <tr>
                    <td><strong>Developed by</strong></td>
                    <td class="small">{{ $customerCompany }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>What this system does</strong></td>
                <td class="small">
                    A web-based School Finance & Administration system to manage students and teachers, record revenue/expenses,
                    automate routine finance tasks, and generate detailed reports with strong role/permission security.
                </td>
            </tr>
            @if(!empty($customerHighlights))
                <tr>
                    <td><strong>Key highlights</strong></td>
                    <td class="small">
                        <ul>
                            @foreach($customerHighlights as $h)
                                <li>{{ $h }}</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    @endif

    <h2>Go-Live Checklist</h2>
    <table>
        <thead>
        <tr>
            <th style="width: 22%">Item</th>
            <th>Detail</th>
            <th style="width: 12%">Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach($go_live_checklist as $item)
            @php
                $status = strtoupper($item['status']);
                $cls = match ($status) {
                    'OK' => 'ok',
                    'ACTION' => 'action',
                    'RECOMMENDED' => 'recommended',
                    default => 'manual',
                };
            @endphp
            <tr>
                <td><strong>{{ $item['title'] }}</strong></td>
                <td class="small">{{ $item['detail'] }}</td>
                <td><span class="pill {{ $cls }}">{{ $status }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Runtime Summary</h2>
    <table>
        <thead>
        <tr>
            <th>Category</th>
            <th>Values</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><strong>Cache</strong></td>
            <td class="small">
                Config cached: <strong>{{ $cache['config_cached'] ? 'yes' : 'no' }}</strong> | Routes cached: <strong>{{ $cache['routes_cached'] ? 'yes' : 'no' }}</strong> |
                Events cached: <strong>{{ $cache['events_cached'] ? 'yes' : 'no' }}</strong> | Views cache dir: <strong>{{ $cache['views_cached'] ? 'present' : 'missing' }}</strong>
            </td>
        </tr>
        <tr>
            <td><strong>Drivers</strong></td>
            <td class="small">
                DB: <code>{{ $drivers['db'] }}</code> | Cache: <code>{{ $drivers['cache'] }}</code> | Queue: <code>{{ $drivers['queue'] }}</code> |
                Session: <code>{{ $drivers['session'] }}</code> | Mail: <code>{{ $drivers['mail'] }}</code> | Log: <code>{{ $drivers['log'] }}</code>
            </td>
        </tr>
        <tr>
            <td><strong>Storage</strong></td>
            <td class="small">
                public/storage exists: <strong>{{ $storage['public_storage_exists'] ? 'yes' : 'no' }}</strong> | is symlink: <strong>{{ $storage['public_storage_is_link'] ? 'yes' : 'no' }}</strong><br>
                storage/ writable: <strong>{{ $storage['storage_writable'] ? 'yes' : 'no' }}</strong> | bootstrap/cache writable: <strong>{{ $storage['bootstrap_cache_writable'] ? 'yes' : 'no' }}</strong>
            </td>
        </tr>
        <tr>
            <td><strong>Audit Logs</strong></td>
            <td class="small">
                Retention days: <strong>{{ $audit_logs['retention_days'] }}</strong> (scheduled prune)
            </td>
        </tr>
        </tbody>
    </table>

    <h2>Available Functions (Features)</h2>
    <div class="small muted">Derived from controllers and registered routes. This describes what the system can do.</div>
    @foreach($features as $module => $items)
        <h3>{{ $module }}</h3>
        <ul class="small">
            @foreach($items as $ctrl)
                <li>{{ $ctrl }}</li>
            @endforeach
        </ul>
    @endforeach

    <h2>Controllers</h2>
    <table>
        <thead>
        <tr>
            <th style="width: 20%">Area</th>
            <th style="width: 35%">Class</th>
            <th>File</th>
        </tr>
        </thead>
        <tbody>
        @foreach($controllers as $c)
            <tr>
                <td class="small">{{ $c['area'] }}</td>
                <td class="small"><code>{{ $c['class'] }}</code></td>
                <td class="small"><code>{{ $c['file'] }}</code></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Services</h2>
    <table>
        <thead>
        <tr>
            <th style="width: 40%">Class</th>
            <th>File</th>
        </tr>
        </thead>
        <tbody>
        @forelse($services as $s)
            <tr>
                <td class="small"><code>{{ $s['class'] }}</code></td>
                <td class="small"><code>{{ $s['file'] }}</code></td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="small">No services found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <h2>Artisan Commands</h2>
    <table>
        <thead>
        <tr>
            <th style="width: 38%">Signature</th>
            <th style="width: 40%">Class</th>
            <th>File</th>
        </tr>
        </thead>
        <tbody>
        @foreach($commands as $cmd)
            <tr>
                <td class="small"><code>{{ $cmd['signature'] ?? 'N/A' }}</code></td>
                <td class="small"><code>{{ $cmd['class'] }}</code></td>
                <td class="small"><code>{{ $cmd['file'] }}</code></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if(!empty($routes))
        <div class="page-break"></div>
        <h2>Routes (All Available Endpoints)</h2>
        <div class="small muted">This is the complete route list from the running application.</div>
        <table>
            <thead>
            <tr>
                <th style="width: 10%">Methods</th>
                <th style="width: 22%">URI</th>
                <th style="width: 18%">Name</th>
                <th style="width: 30%">Action</th>
                <th>Middleware</th>
            </tr>
            </thead>
            <tbody>
            @foreach($routes as $r)
                <tr>
                    <td class="small"><code>{{ $r['methods'] }}</code></td>
                    <td class="small"><code>{{ $r['uri'] }}</code></td>
                    <td class="small"><code>{{ $r['name'] }}</code></td>
                    <td class="small"><code>{{ $r['action'] }}</code></td>
                    <td class="small">
                        @if(!empty($r['middleware']))
                            <code>{{ implode(', ', $r['middleware']) }}</code>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
