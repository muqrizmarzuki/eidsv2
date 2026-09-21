<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deploy</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 640px; margin: 60px auto; padding: 0 20px; color: #1a1a1a; }
        h1 { font-size: 18px; }
        input[type=password] { width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        button { margin-top: 10px; padding: 10px 18px; background: #00342b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        pre { background: #111; color: #0f0; padding: 16px; border-radius: 6px; overflow-x: auto; white-space: pre-wrap; font-size: 12px; }
        .error { color: #b91c1c; font-weight: bold; }
        .warn { color: #b45309; font-size: 13px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Migrate &amp; Seed</h1>

    @if($error)
        <p class="error">{{ $error }}</p>
    @endif

    @if($output)
        <pre>{{ $output }}</pre>
        <p class="warn">Done. Now delete app/Http/Controllers/DeployController.php, this view, and its route from routes/web.php, then redeploy.</p>
    @else
        <form method="POST">
            @csrf
            <input type="password" name="password" placeholder="Password" autofocus>
            <button type="submit">Run migrate + db:seed</button>
        </form>
    @endif
</body>
</html>
