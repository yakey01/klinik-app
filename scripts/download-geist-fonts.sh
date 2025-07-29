#!/bin/bash

# Geist Font Downloader for Medical Dashboard
# Downloads Geist Sans and Geist Mono fonts for self-hosting

set -e

# Configuration
FONT_DIR="public/fonts/geist"
TEMP_DIR="/tmp/geist-fonts"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔤 Geist Font Downloader${NC}"
echo -e "${BLUE}Downloading Geist fonts for self-hosting...${NC}\n"

# Create directories
echo -e "${YELLOW}📁 Creating font directories...${NC}"
mkdir -p "$FONT_DIR"
mkdir -p "$TEMP_DIR"

# Geist font files URLs (official repository)
echo -e "${YELLOW}⬇️  Downloading Geist font files...${NC}"

GEIST_SANS_FILES=(
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-sans/Geist-Light.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-sans/Geist-Regular.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-sans/Geist-Medium.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-sans/Geist-SemiBold.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-sans/Geist-Bold.woff2"
)

GEIST_MONO_FILES=(
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-mono/GeistMono-Light.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-mono/GeistMono-Regular.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-mono/GeistMono-Medium.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-mono/GeistMono-SemiBold.woff2"
    "https://github.com/vercel/geist-font/raw/main/packages/next/dist/fonts/geist-mono/GeistMono-Bold.woff2"
)

# Download Geist Sans
echo -e "${YELLOW}📥 Downloading Geist Sans...${NC}"
for url in "${GEIST_SANS_FILES[@]}"; do
    filename=$(basename "$url")
    echo -e "  ⬇️  Downloading ${filename}..."
    
    if command -v curl >/dev/null 2>&1; then
        curl -L -o "$TEMP_DIR/$filename" "$url" --silent --show-error || {
            echo -e "${RED}  ❌ Failed to download ${filename} - continuing...${NC}"
            continue
        }
    elif command -v wget >/dev/null 2>&1; then
        wget -q -O "$TEMP_DIR/$filename" "$url" || {
            echo -e "${RED}  ❌ Failed to download ${filename} - continuing...${NC}"
            continue
        }
    else
        echo -e "${RED}❌ Error: Neither curl nor wget found.${NC}"
        exit 1
    fi
    
    if [ -f "$TEMP_DIR/$filename" ] && [ -s "$TEMP_DIR/$filename" ]; then
        echo -e "${GREEN}  ✅ Downloaded ${filename}${NC}"
    fi
done

# Download Geist Mono
echo -e "${YELLOW}📥 Downloading Geist Mono...${NC}"
for url in "${GEIST_MONO_FILES[@]}"; do
    filename=$(basename "$url")
    echo -e "  ⬇️  Downloading ${filename}..."
    
    if command -v curl >/dev/null 2>&1; then
        curl -L -o "$TEMP_DIR/$filename" "$url" --silent --show-error || {
            echo -e "${RED}  ❌ Failed to download ${filename} - continuing...${NC}"
            continue
        }
    elif command -v wget >/dev/null 2>&1; then
        wget -q -O "$TEMP_DIR/$filename" "$url" || {
            echo -e "${RED}  ❌ Failed to download ${filename} - continuing...${NC}"
            continue
        }
    fi
    
    if [ -f "$TEMP_DIR/$filename" ] && [ -s "$TEMP_DIR/$filename" ]; then
        echo -e "${GREEN}  ✅ Downloaded ${filename}${NC}"
    fi
done

# Move files to destination
echo -e "\n${YELLOW}📦 Installing font files...${NC}"
if [ "$(ls -A $TEMP_DIR)" ]; then
    cp "$TEMP_DIR"/* "$FONT_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠️  Some files may not have been copied${NC}"
else
    echo -e "${RED}❌ No font files downloaded successfully${NC}"
    exit 1
fi

# Verify installation
echo -e "\n${YELLOW}🔍 Verifying installation...${NC}"
for file in "$FONT_DIR"/*.woff2; do
    if [ -f "$file" ]; then
        size=$(du -h "$file" | cut -f1)
        echo -e "${GREEN}  ✅ $(basename "$file") (${size})${NC}"
    fi
done

# Generate CSS file
echo -e "\n${YELLOW}📝 Generating Geist CSS...${NC}"
cat > "$FONT_DIR/geist-self-hosted.css" << 'EOF'
/**
 * Self-hosted Geist Font Family
 * Modern typography for medical dashboard
 */

/* Geist Sans Font Face Declarations */
@font-face {
  font-family: 'Geist Sans';
  src: url('./Geist-Light.woff2') format('woff2');
  font-weight: 300;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Sans';
  src: url('./Geist-Regular.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Sans';
  src: url('./Geist-Medium.woff2') format('woff2');
  font-weight: 500;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Sans';
  src: url('./Geist-SemiBold.woff2') format('woff2');
  font-weight: 600;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Sans';
  src: url('./Geist-Bold.woff2') format('woff2');
  font-weight: 700;
  font-style: normal;
  font-display: swap;
}

/* Geist Mono Font Face Declarations */
@font-face {
  font-family: 'Geist Mono';
  src: url('./GeistMono-Light.woff2') format('woff2');
  font-weight: 300;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Mono';
  src: url('./GeistMono-Regular.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Mono';
  src: url('./GeistMono-Medium.woff2') format('woff2');
  font-weight: 500;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Mono';
  src: url('./GeistMono-SemiBold.woff2') format('woff2');
  font-weight: 600;
  font-style: normal;
  font-display: swap;
}

@font-face {
  font-family: 'Geist Mono';
  src: url('./GeistMono-Bold.woff2') format('woff2');
  font-weight: 700;
  font-style: normal;
  font-display: swap;
}

/* Font Stack Definitions */
:root {
  --font-family-geist: 'Geist Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-family-geist-mono: 'Geist Mono', 'SF Mono', Monaco, Inconsolata, 'Roboto Mono', monospace;
}

/* Medical Dashboard Geist Classes */
.font-geist {
  font-family: var(--font-family-geist);
  font-feature-settings: 'cv02', 'cv03', 'cv04';
  font-variant-numeric: tabular-nums;
}

.font-geist-mono {
  font-family: var(--font-family-geist-mono);
  font-variant-numeric: tabular-nums;
}

.font-geist-display {
  font-family: var(--font-family-geist);
  letter-spacing: -0.02em;
  font-feature-settings: 'cv02', 'cv03', 'cv04', 'ss01';
}
EOF

echo -e "${GREEN}  ✅ Generated geist-self-hosted.css${NC}"

# Cleanup
echo -e "\n${YELLOW}🧹 Cleaning up...${NC}"
rm -rf "$TEMP_DIR"

# Calculate total size
total_size=$(du -sh "$FONT_DIR" | cut -f1)

echo -e "\n${GREEN}🎉 Geist font installation complete!${NC}"
echo -e "${GREEN}📊 Total size: ${total_size}${NC}"
echo -e "${GREEN}📁 Location: ${FONT_DIR}${NC}"
echo -e "\n${BLUE}Next steps:${NC}"
echo -e "1. Update font system to include Geist fonts"
echo -e "2. Add Geist to medical font component"  
echo -e "3. Test Geist font loading"
echo -e "4. Consider using Geist for modern UI elements"