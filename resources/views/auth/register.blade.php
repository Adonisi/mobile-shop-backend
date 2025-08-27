<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Mobile Shop</title>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #FDFDFC;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #1b1b18;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e3e3e0;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #1b1b18;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
        }
        button:hover {
            background-color: #000;
        }
        .error {
            color: #f53003;
            margin-top: 10px;
            font-size: 14px;
        }
        .success {
            color: #22c55e;
            margin-top: 10px;
            font-size: 14px;
        }
        .links {
            margin-top: 20px;
            text-align: center;
        }
        .links a {
            color: #1b1b18;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1 style="text-align: center; margin-bottom: 30px; color: #1b1b18;">Register</h1>
        
        <form id="registerForm">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div id="message"></div>
        
        <div class="links">
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ url('/') }}">Back to Home</a>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('message');
            
            // Check if passwords match
            if (formData.get('password') !== formData.get('password_confirmation')) {
                messageDiv.innerHTML = '<div class="error">Passwords do not match</div>';
                return;
            }
            
            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        name: formData.get('name'),
                        email: formData.get('email'),
                        password: formData.get('password'),
                        password_confirmation: formData.get('password_confirmation')
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    messageDiv.innerHTML = '<div class="success">Registration successful! Redirecting to login...</div>';
                    // Redirect to login page
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    let errorMessage = 'Registration failed';
                    if (data.errors) {
                        errorMessage = Object.values(data.errors).flat().join(', ');
                    } else if (data.message) {
                        errorMessage = data.message;
                    }
                    messageDiv.innerHTML = `<div class="error">${errorMessage}</div>`;
                }
            } catch (error) {
                messageDiv.innerHTML = '<div class="error">An error occurred. Please try again.</div>';
            }
        });
    </script>
</body>
</html>
