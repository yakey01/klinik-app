#!/bin/bash
# World-class approach to install sshpass on macOS

echo "=== INSTALLING SSHPASS - WORLD CLASS APPROACH ==="

# Method 1: Try Homebrew
if command -v brew >/dev/null 2>&1; then
    echo "✅ Homebrew found, installing sshpass..."
    brew install hudochenkov/sshpass/sshpass
    
    if command -v sshpass >/dev/null 2>&1; then
        echo "✅ sshpass installed successfully via Homebrew"
        echo "Now you can use: sshpass -p 'password' ssh user@host"
        exit 0
    fi
fi

# Method 2: Try MacPorts  
if command -v port >/dev/null 2>&1; then
    echo "✅ MacPorts found, installing sshpass..."
    sudo port install sshpass
    
    if command -v sshpass >/dev/null 2>&1; then
        echo "✅ sshpass installed successfully via MacPorts"
        exit 0
    fi
fi

# Method 3: Compile from source
echo "📦 Compiling sshpass from source..."
cd /tmp
curl -L https://sourceforge.net/projects/sshpass/files/sshpass/1.09/sshpass-1.09.tar.gz/download -o sshpass.tar.gz
tar -xzf sshpass.tar.gz
cd sshpass-1.09
./configure
make
sudo make install

if command -v sshpass >/dev/null 2>&1; then
    echo "✅ sshpass compiled and installed successfully"
    exit 0
fi

echo "❌ Failed to install sshpass. Using expect script instead."
echo "The expect script should work as an alternative."