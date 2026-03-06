<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Locked</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .lock-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 350px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            width: 100%;
            padding: 10px;
            border: none;
            background: #0d6efd;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="lock-box">
        <h2>System Locked</h2>
        <p>Welcome back, {{ auth()->user()->name ?? 'User' }}</p>

        <form method="POST" action="{{ route('unlock') }}">
            @csrf
            <input type="password" name="password" placeholder="Enter your password" required>

            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit">Unlock</button>
        </form>
    </div>
</body>
</html>
