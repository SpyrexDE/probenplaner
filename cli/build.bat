@echo off
cd /d "%~dp0"
echo Building Probenplaner CLI...

echo.
echo [1/3] Building for Windows (probenplaner.exe)...
go build -o ../probenplaner.exe .
if errorlevel 1 goto error

echo [2/3] Building for Linux (probenplaner-linux)...
set GOOS=linux
set GOARCH=amd64
go build -o ../probenplaner-linux .
if errorlevel 1 goto error

echo [3/3] Building for Mac (probenplaner-mac)...
set GOOS=darwin
set GOARCH=amd64
go build -o ../probenplaner-mac .
if errorlevel 1 goto error

:: Reset env vars
set GOOS=
set GOARCH=

echo.
echo ------------------------------------------
echo ✅ Build successful!
echo Binaries created in project root:
echo - probenplaner.exe (Windows)
echo - probenplaner-linux (Linux server)
echo - probenplaner-mac (macOS)
echo ------------------------------------------
exit /b 0

:error
echo.
echo ❌ Build failed!
exit /b 1
