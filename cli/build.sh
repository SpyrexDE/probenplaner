#!/bin/bash
set -e
cd "$(dirname "$0")"

echo "Building Probenplaner CLI..."

echo ""
echo "[1/4] Building for Windows (probenplaner.exe)..."
GOOS=windows GOARCH=amd64 go build -o ../probenplaner.exe .

echo "[2/4] Building for Linux x86_64 (probenplaner-linux)..."
GOOS=linux GOARCH=amd64 go build -o ../probenplaner-linux .

echo "[3/4] Building for Linux ARM64 (probenplaner-linux-arm)..."
GOOS=linux GOARCH=arm64 go build -o ../probenplaner-linux-arm .

echo "[4/4] Building for Mac (probenplaner-mac)..."
GOOS=darwin GOARCH=amd64 go build -o ../probenplaner-mac .

echo ""
echo "------------------------------------------"
echo "✅ Build successful!"
echo "Binaries created in project root:"
echo "- probenplaner.exe (Windows)"
echo "- probenplaner-linux (Linux x86_64)"
echo "- probenplaner-linux-arm (Linux ARM64)"
echo "- probenplaner-mac (macOS)"
echo "------------------------------------------"
