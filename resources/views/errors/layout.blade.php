<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($code ?? 500) }} | {{ config('app.name', 'School System') }}</title>
    <style>
        :root {
            --bg-1: #f4f6f8;
            --bg-2: #dde5ea;
            --ink: #1f2a37;
            --muted: #5b6773;
            --card: #ffffff;
            --accent: #0f766e;
            --accent-hover: #115e59;
            --ring: rgba(15, 118, 110, 0.2);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 10%, #ffffff 0%, transparent 45%),
                linear-gradient(140deg, var(--bg-1), var(--bg-2));
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .card {
            width: min(680px, 100%);
            background: var(--card);
            border: 1px solid #d6dee6;
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(17, 24, 39, 0.1);
            padding: 32px 28px;
        }

        .code {
            display: inline-block;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #c8d2dc;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(26px, 5vw, 40px);
            line-height: 1.1;
        }

        p {
            margin: 0;
            line-height: 1.65;
            color: var(--muted);
            font-size: 16px;
        }

        .actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .button {
            appearance: none;
            border: none;
            border-radius: 10px;
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            padding: 11px 16px;
            transition: background 120ms ease, transform 120ms ease, box-shadow 120ms ease;
        }

        .button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .button.secondary {
            background: #e8eef2;
            color: #223041;
            font-weight: 600;
        }

        .button.secondary:hover {
            background: #dde5eb;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="code">Error {{ $code ?? 500 }}</span>
        <h1>{{ $title ?? 'Unexpected error' }}</h1>
        <p>{{ $message ?? 'Something went wrong. Please try again shortly.' }}</p>

        <div class="actions">
            <a class="button" href="{{ url('/') }}">Go to dashboard</a>
            <a class="button secondary" href="{{ url()->previous() }}">Go back</a>
        </div>
    </main>
</body>
</html>
