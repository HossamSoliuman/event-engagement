<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — EventBomb</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #0A0A18;
            color: #e8e8f2;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background-image: radial-gradient(ellipse at 15% 50%, rgba(255, 61, 0, .09) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 20%, rgba(255, 215, 0, .05) 0%, transparent 50%);
        }

        .wrap {
            width: 100%;
            max-width: 400px;
            padding: 0 20px
        }

        .logo {
            text-align: center;
            margin-bottom: 36px
        }

        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 34px;
            font-weight: 800;
            color: #FF3D00;
            letter-spacing: -1px
        }

        .logo p {
            color: #7878A0;
            font-size: 13px;
            margin-top: 6px
        }

        .card {
            background: #12121F;
            border: 1px solid #252540;
            border-radius: 16px;
            padding: 32px
        }

        .form-group {
            margin-bottom: 18px
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7878A0;
            margin-bottom: 7px
        }

        input {
            width: 100%;
            background: #0A0A18;
            border: 1px solid #252540;
            border-radius: 8px;
            padding: 12px 14px;
            color: #e8e8f2;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color .15s
        }

        input:focus {
            outline: none;
            border-color: #FF3D00
        }

        input::placeholder {
            color: #7878A0
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #7878A0;
            cursor: pointer
        }

        .remember input[type=checkbox] {
            width: auto;
            accent-color: #FF3D00;
            width: 15px;
            height: 15px
        }

        .btn-login {
            width: 100%;
            background: #FF3D00;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Syne', sans-serif;
            cursor: pointer;
            margin-top: 22px;
            letter-spacing: .3px;
            transition: background .15s
        }

        .btn-login:hover {
            background: #d93400
        }

        .error {
            color: #f87171;
            font-size: 12px;
            margin-top: 5px
        }

        .hint {
            text-align: center;
            color: #7878A0;
            font-size: 11px;
            margin-top: 20px
        }

        /* Demo credentials box */
        .demo-box {
            margin-bottom: 22px;
            background: #0A0A18;
            border: 1px dashed #FF3D0055;
            border-radius: 10px;
            padding: 14px 16px;
            position: relative;
        }

        .demo-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #FF3D0022;
            color: #FF3D00;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .demo-badge::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #FF3D00;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1)
            }

            50% {
                opacity: .4;
                transform: scale(.8)
            }
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
        }

        .demo-label {
            font-size: 10px;
            color: #7878A0;
            text-transform: uppercase;
            letter-spacing: .8px;
            font-weight: 700
        }

        .demo-value {
            font-size: 12.5px;
            color: #e8e8f2;
            font-family: monospace;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-btn {
            background: none;
            border: 1px solid #252540;
            border-radius: 4px;
            color: #7878A0;
            font-size: 10px;
            padding: 2px 7px;
            cursor: pointer;
            transition: all .15s;
            font-family: 'DM Sans', sans-serif;
        }

        .copy-btn:hover {
            border-color: #FF3D00;
            color: #FF3D00
        }

        .copy-btn.copied {
            border-color: #22c55e;
            color: #22c55e
        }

        .demo-fill-btn {
            width: 100%;
            background: transparent;
            border: 1px solid #252540;
            border-radius: 6px;
            color: #7878A0;
            font-size: 12px;
            font-family: 'DM Sans', sans-serif;
            padding: 8px;
            cursor: pointer;
            margin-top: 12px;
            transition: all .15s;
            letter-spacing: .3px;
        }

        .demo-fill-btn:hover {
            border-color: #FF3D0066;
            color: #FF3D00;
            background: #FF3D0008
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="logo">
            <h1> EventBomb</h1>
            <p>Admin Console · Secure Access</p>
        </div>
        <div class="card">
            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="email-input" value="{{ old('email') }}" required autofocus
                        placeholder="admin@example.com">
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password-input" required placeholder="••••••••">
                </div>
                <label class="remember">
                    <input type="checkbox" name="remember"> Keep me signed in
                </label>
                <button type="submit" class="btn-login">Sign In →</button>
            </form>
            <div class="hint">EventBomb Platform v2.0 · All rights reserved</div>
        </div>
    </div>

</body>

</html>
