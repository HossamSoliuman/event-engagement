<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — EventBomb</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #0D0D1A;
            color: #e8e8f0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background-image: radial-gradient(ellipse at 20% 50%, rgba(255, 61, 0, .08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(255, 215, 0, .05) 0%, transparent 50%);
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 0 20px;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: #FF3D00;
            letter-spacing: -1px;
        }

        .logo p {
            color: #8888aa;
            font-size: 14px;
            margin-top: 6px;
        }

        .card {
            background: #161628;
            border: 1px solid #2a2a45;
            border-radius: 16px;
            padding: 36px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8888aa;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            background: #0D0D1A;
            border: 1px solid #2a2a45;
            border-radius: 8px;
            padding: 12px 16px;
            color: #e8e8f0;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color .15s;
        }

        input:focus {
            outline: none;
            border-color: #FF3D00;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #8888aa;
        }

        .remember input[type=checkbox] {
            width: auto;
            accent-color: #FF3D00;
        }

        .btn-login {
            width: 100%;
            background: #FF3D00;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Syne', sans-serif;
            cursor: pointer;
            margin-top: 24px;
            letter-spacing: .5px;
            transition: background .15s;
        }

        .btn-login:hover {
            background: #e03500;
        }

        .error {
            color: #f87171;
            font-size: 13px;
            margin-top: 6px;
        }

        .divider {
            text-align: center;
            color: #8888aa;
            font-size: 12px;
            margin-top: 24px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="logo">
            <h1>⚡ EventBomb</h1>
            <p>Admin Console — Secure Access</p>
        </div>
        <div class="card">
            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <label class="remember">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <button type="submit" class="btn-login">Sign In →</button>
            </form>
            <div class="divider">EventBomb Platform v1.0</div>
        </div>
    </div>
</body>

</html>
