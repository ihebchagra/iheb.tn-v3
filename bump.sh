#!/bin/sh

# ----------------------
# Setup: File monitoring initialization
echo "Setting up file monitoring with inotify..."
find public routes -type f \( -name "*.html" -o -name "*.php" -o -name "*.js" -o -name "*.json" \) > /tmp/files_to_check.txt
if [ -f "./index.php" ]; then
    echo "./index.php" >> /tmp/files_to_check.txt
fi
touch /tmp/recently_processed.txt
trap "rm -f /tmp/files_to_check.txt /tmp/recently_processed.txt /tmp/version-bump.lock; echo 'Cleaned up and exiting.'; exit 0" INT TERM EXIT

# Check installation of inotify-tools
if ! command -v inotifywait >/dev/null 2>&1; then
    echo "Error: inotify-tools not installed. Please install it with your package manager."
    echo "For example: sudo apt-get install inotify-tools"
    exit 1
fi

# ----------------------
# File Monitoring Loop: Process changed files
echo "Starting file monitoring on public/ and routes/ directories. Press Ctrl+C to stop."
inotifywait -m -r -e modify --format '%w%f' public routes | while read file; do
    if [ -f "$file" ]; then
        touch /tmp/version-bump.lock
        filename=$(basename "$file")
        
        # Check: Already processed within last 2 seconds?
        if grep -q "^$file:" /tmp/recently_processed.txt; then
            last_proc=$(grep "^$file:" /tmp/recently_processed.txt | cut -d':' -f2)
            now=$(date +%s)
            if [ $(($now - $last_proc)) -lt 2 ]; then
                echo "Skipping $filename (processed recently)"
                rm -f /tmp/version-bump.lock
                continue
            fi
        fi
        
        # Mark file as recently processed
        echo "$file:$(date +%s)" >> /tmp/recently_processed.tmp
        grep -v "^$file:" /tmp/recently_processed.txt >> /tmp/recently_processed.tmp 2>/dev/null
        mv /tmp/recently_processed.tmp /tmp/recently_processed.txt
        
        # ----------------------
        # Section 1: Update references for the changed file
        echo "Processing references to $filename"
        cat /tmp/files_to_check.txt | while read ref_file; do 
            if [ "$(basename "$ref_file")" = "sw.js" ]; then 
                echo "  Skipping sw.js file"
                continue
            fi
            if grep -q "$filename" "$ref_file"; then 
                echo "  Checking $ref_file"
                if grep -q "$filename?v=[0-9]" "$ref_file"; then 
                    current_version=$(grep -o "$filename?v=[0-9]*" "$ref_file" | grep -o "[0-9]*$")
                    if [ -n "$current_version" ]; then 
                        new_version=$(expr $current_version + 1)
                        sed -i -E "s/$filename\?v=$current_version/$filename?v=$new_version/g" "$ref_file"
                        echo "    Updated version in $ref_file from $current_version to $new_version"
                    fi
                else 
                    sed -i -E "s/(href|src)=([\"'])([^\"']*$filename)([\"'])/\\1=\\2\\3?v=1\\4/g" "$ref_file"
                    echo "    Added version parameter in $ref_file"
                fi
            fi
        done
        
        # ----------------------
        # Section 2: For changes inside /assets/js/components/ (excluding components.js),
        # bump the components.js version in public/index.php
        if echo "$file" | grep -q "/assets/js/components/" && [ "$filename" != "components.js" ]; then
            echo "Updating components.js reference inside public/index.php due to change in $file"
            index_file="public/index.php"
            if [ -f "$index_file" ]; then
                if grep -qE "components\.js\?v=[0-9]+" "$index_file"; then
                    current_version=$(grep -oE "components\.js\?v=[0-9]+" "$index_file" | grep -oE "[0-9]+")
                    if [ -n "$current_version" ]; then
                        new_version=$(expr $current_version + 1)
                        sed -i -E "s,(components\\.js\\?v=)[0-9]+,\\1$new_version," "$index_file"
                        echo "  Updated components.js version in index.php from $current_version to $new_version"
                    fi
                else
                    sed -i -E "s,(src=[\"']/assets/js/components\\.js)([\"']),\\1?v=1\\2," "$index_file"
                    echo "  Added components.js version parameter in index.php"
                fi
            fi
        fi

        rm -f /tmp/version-bump.lock
    fi
done
