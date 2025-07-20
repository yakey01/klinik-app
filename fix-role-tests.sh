#!/bin/bash

# Fix Role::create to Role::firstOrCreate in test files
echo "🔧 Fixing Role::create to Role::firstOrCreate in test files..."

# Find all test files with Role::create
find tests/ -name "*.php" -type f -exec grep -l "Role::create" {} \; | while read file; do
    echo "Processing: $file"
    
    # Create backup
    cp "$file" "$file.backup"
    
    # Replace Role::create with Role::firstOrCreate
    # Pattern 1: Role::create(['name' => 'x', 'display_name' => 'y'])
    sed -i '' "s/Role::create(\['name' => '\([^']*\)', 'display_name' => '\([^']*\)'\]/Role::firstOrCreate(['name' => '\1'], ['display_name' => '\2']/g" "$file"
    
    # Pattern 2: Role::create(['name' => 'x', 'display_name' => 'y', 'description' => 'z'])
    sed -i '' "s/Role::create(\['name' => '\([^']*\)', 'display_name' => '\([^']*\)', 'description' => '\([^']*\)'\]/Role::firstOrCreate(['name' => '\1'], ['display_name' => '\2', 'description' => '\3']/g" "$file"
    
    # Pattern 3: Role::create(['name' => 'x', 'display_name' => 'y', 'guard_name' => 'z'])
    sed -i '' "s/Role::create(\['name' => '\([^']*\)', 'display_name' => '\([^']*\)', 'guard_name' => '\([^']*\)'\]/Role::firstOrCreate(['name' => '\1'], ['display_name' => '\2', 'guard_name' => '\3']/g" "$file"
    
    # Pattern 4: Role::create(['name' => 'x', 'label' => 'y'])
    sed -i '' "s/Role::create(\['name' => '\([^']*\)', 'label' => '\([^']*\)'\]/Role::firstOrCreate(['name' => '\1'], ['label' => '\2']/g" "$file"
    
    # Pattern 5: Simple Role::create(['name' => 'x'])
    sed -i '' "s/Role::create(\['name' => '\([^']*\)'\]/Role::firstOrCreate(['name' => '\1']/g" "$file"
    
    echo "✅ Fixed: $file"
done

echo "🎉 All test files have been updated!"
echo "📝 Backups created with .backup extension"
echo ""
echo "🧪 You can now run your tests without UNIQUE constraint violations!" 