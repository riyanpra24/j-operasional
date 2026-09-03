param(
    [string] $NodeCommand = 'node',
    [string] $PnpmCommand = 'pnpm',
    [string] $SharpModule = 'sharp'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot

Push-Location $projectRoot
try {
    & $PnpmCommand dlx esbuild@0.25.9 public/assets/app.css --minify --outfile=public/assets/app.min.css
    & $PnpmCommand dlx esbuild@0.25.9 public/assets/app.js --minify --outfile=public/assets/app.min.js
    & $PnpmCommand dlx esbuild@0.25.9 public/assets/required-markers.js --minify --outfile=public/assets/required-markers.min.js
    & $PnpmCommand dlx esbuild@0.25.9 public/assets/url-mask.js --minify --outfile=public/assets/url-mask.min.js

    $env:SHARP_MODULE = $SharpModule
    & $NodeCommand tools/optimize-assets.cjs
} finally {
    Pop-Location
}
