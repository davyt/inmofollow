param(
    [string]$Message = ""
)

$SSH_KEY = "$env:USERPROFILE\.ssh\inmofollow_hostinger"
$SSH_HOST = "u528839040@147.79.85.220"
$SSH_PORT = "65002"
$APP_PATH = "~/domains/lightblue-sparrow-828519.hostingersite.com/public_html"

function Run-Remote($cmd) {
    ssh -p $SSH_PORT -i $SSH_KEY $SSH_HOST $cmd
}

Write-Host "`n==> Verificando cambios locales..." -ForegroundColor Cyan
$status = git -C $PSScriptRoot status --porcelain
if ($status) {
    if ($Message -eq "") {
        $Message = Read-Host "Mensaje de commit"
    }
    git -C $PSScriptRoot add .
    git -C $PSScriptRoot commit -m $Message
    if ($LASTEXITCODE -ne 0) { Write-Host "Error en commit" -ForegroundColor Red; exit 1 }
}

Write-Host "`n==> Pushing a GitHub..." -ForegroundColor Cyan
git -C $PSScriptRoot push
if ($LASTEXITCODE -ne 0) { Write-Host "Error en push" -ForegroundColor Red; exit 1 }

Write-Host "`n==> Desplegando en servidor..." -ForegroundColor Cyan
Run-Remote "cd $APP_PATH && git pull"
if ($LASTEXITCODE -ne 0) { Write-Host "Error en git pull" -ForegroundColor Red; exit 1 }

Write-Host "`n==> Ejecutando post-deploy..." -ForegroundColor Cyan
Run-Remote @"
cd $APP_PATH
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
"@

Write-Host "`n==> Deploy completado!" -ForegroundColor Green
