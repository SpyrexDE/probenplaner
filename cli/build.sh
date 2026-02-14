#!/bin/bash
set -e
cd "$(dirname "$0")"

echo "Building Probenplaner CLI..."

echo ""
echo "[1/3] Building for Windows (probenplaner.exe)..."
GOOS=windows GOARCH=amd64 go build -o ../probenplaner.exe .

echo "[2/3] Building for Linux (probenplaner-linux)..."
GOOS=linux GOARCH=amd64 go build -o ../probenplaner-linux .

echo "[3/3] Building for Mac (probenplaner-mac)..."
GOOS=darwin GOARCH=amd64 go build -o ../probenplaner-mac .

echo ""
echo "------------------------------------------"
echo "✅ Build successful!"
echo "Binaries created in project root:"
echo "- probenplaner.exe (Windows)"
echo "- probenplaner-linux (Linux server)"
echo "- probenplaner-mac (macOS)"
echo "------------------------------------------"
