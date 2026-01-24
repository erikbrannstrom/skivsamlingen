<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Auth Test - Skivsamlingen</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .status {
            padding: 12px 16px;
            border-radius: 6px;
            margin: 16px 0;
        }
        .status.authenticated {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.guest {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .info {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 16px;
        }
        .info h3 {
            margin: 0 0 8px 0;
            color: #084298;
        }
        .info p {
            margin: 4px 0;
            color: #0a58ca;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Laravel Auth Test</h1>
        <p>This page tests the SharedAuth middleware that reads CodeIgniter session cookies.</p>

        @auth
            <div class="status authenticated">
                <strong>Authenticated!</strong> You are logged in as: <code>{{ Auth::user()->username }}</code>
            </div>

            <div class="info">
                <h3>User Details</h3>
                <p><strong>User ID:</strong> {{ Auth::user()->id }}</p>
                <p><strong>Username:</strong> {{ Auth::user()->username }}</p>
                <p><strong>Email:</strong> {{ Auth::user()->email ?: '(not set)' }}</p>
                <p><strong>Registered:</strong> {{ Auth::user()->registered?->format('Y-m-d H:i') ?: '(unknown)' }}</p>
            </div>
        @else
            <div class="status guest">
                <strong>Not authenticated.</strong> You are browsing as a guest.
            </div>

            <div class="info">
                <h3>How to test</h3>
                <p>1. Go to <a href="http://localhost:8080/">the CodeIgniter site</a></p>
                <p>2. Log in with your account</p>
                <p>3. Return to this page and refresh</p>
                <p>The SharedAuth middleware should read your CI session cookie.</p>
            </div>
        @endauth

        <div class="footer">
            <p>Laravel {{ app()->version() }} | PHP {{ phpversion() }}</p>
            <p>SharedAuth middleware is {{ class_exists(\App\Http\Middleware\SharedAuth::class) ? 'installed' : 'NOT installed' }}</p>
        </div>
    </div>
</body>
</html>
