<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Mobile Shop</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #FDFDFC;
            margin: 0;
            padding: 20px;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .welcome {
            font-size: 24px;
            font-weight: 600;
            color: #1b1b18;
        }
        .logout-btn {
            padding: 10px 20px;
            background-color: #f53003;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
        }
        .logout-btn:hover {
            background-color: #d42a02;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #1b1b18;
            margin-bottom: 15px;
        }
        .api-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            border-left: 4px solid #1b1b18;
        }
        .api-endpoint {
            font-family: monospace;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 3px;
            margin: 5px 0;
        }
        .links {
            margin-top: 20px;
        }
        .links a {
            color: #1b1b18;
            text-decoration: none;
            margin-right: 20px;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div class="welcome">Welcome to Mobile Shop Dashboard</div>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>API Endpoints</h2>
                <div class="api-info">
                    <p><strong>Available API endpoints:</strong></p>
                    <div class="api-endpoint">POST /api/register</div>
                    <div class="api-endpoint">POST /api/login</div>
                    <div class="api-endpoint">POST /api/logout (requires auth)</div>
                    <div class="api-endpoint">GET /api/products (requires auth)</div>
                    <div class="api-endpoint">GET /api/products/{id} (requires auth)</div>
                    <div class="api-endpoint">POST /api/products (requires auth)</div>
                    <div class="api-endpoint">PUT /api/products/{id} (requires auth)</div>
                    <div class="api-endpoint">DELETE /api/products/{id} (requires auth)</div>
                </div>
            </div>
            
            <div class="section">
                <h2>Quick Actions</h2>
                <div class="links">
                    <a href="{{ url('/') }}">Back to Home</a>
                    <a href="{{ route('login') }}">Login Page</a>
                    <a href="{{ route('register') }}">Register Page</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function logout() {
            const token = localStorage.getItem('auth_token');
            
            if (token) {
                fetch('/api/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }).finally(() => {
                    localStorage.removeItem('auth_token');
                    window.location.href = '/login';
                });
            } else {
                window.location.href = '/login';
            }
        }
    </script>
</body>
</html>
