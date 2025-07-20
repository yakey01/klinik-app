#!/bin/bash

echo "🧹 Cleaning up test database files..."

# Remove all test database files
if [ -d "database" ]; then
    echo "📁 Found database directory, cleaning test files..."
    
    # Count files before cleanup
    file_count=$(find database -name "testing_*.sqlite" | wc -l)
    echo "📊 Found $file_count test database files"
    
    # Remove test database files
    find database -name "testing*.sqlite" -delete
    
    echo "✅ Test database cleanup completed"
else
    echo "📁 Database directory not found"
fi

# Also clean up any temporary SQLite files
echo "🧹 Cleaning up temporary SQLite files..."
find . -name "*.sqlite" -path "*/tests/*" -delete 2>/dev/null || true

echo "🎉 Cleanup completed!" 