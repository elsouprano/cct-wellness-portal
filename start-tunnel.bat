@echo off
echo Checking if assets need to be built...
if not exist public\build (
    echo Building assets...
    call npm run build
) else (
    echo Assets already built.
)

echo Starting Laravel server on port 8000 in the background...
start /b php artisan serve --host=127.0.0.1 --port=8000

echo Waiting for server to start...
timeout /t 3 /nobreak >nul

echo Starting Cloudflare tunnel...
cloudflared tunnel --url http://127.0.0.1:8000
