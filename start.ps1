$root = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "Starting FastAPI analytics-service on http://127.0.0.1:8000 ..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "Set-Location '$root\analytics-service'; .\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8000"
)

Start-Sleep -Seconds 2

Write-Host "Starting PHP web app on http://127.0.0.1:8080 ..." -ForegroundColor Green
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "Set-Location '$root'; php -S 127.0.0.1:8080 -t php-app/public"
)

Start-Sleep -Seconds 1
Start-Process "http://127.0.0.1:8080"
Write-Host "Both servers launched. Close the two server windows to stop them." -ForegroundColor Yellow