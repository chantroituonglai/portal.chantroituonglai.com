#!/bin/bash

# Script to find uncommitted files and upload them to the FTP server
# Optional project scan is performed only if a scan script exists and --scan is passed

# Ensure temporary files are always cleaned up
trap 'rm -f "$TEMP_FILE_LIST"; rm -f "$LOG_DIR"/*.tmp; rm -f "$LOG_DIR"/*.status; rm -f "$LOG_DIR"/*.missing_*' EXIT

# FTP configuration
FTP_HOST="srv456.main-hosting.eu"
FTP_USER="u310178187.portal.chantroituonglai.com"
FTP_PASS="@E9mYGUGYn=3h67~"

# Working directory for this project
WORK_DIR="/Users/macbook/Documents/DEV/FutureWorld/perfex_crm"
TEMP_FILE_LIST="/tmp/files_to_upload.txt"
LOG_DIR="$WORK_DIR/upload_logs"
LAST_UPDATE_FILE="$WORK_DIR/last_update_files.txt"

# Flags
SCAN_PROJECT=false
CHECK_SYNC=false
for arg in "$@"; do
    if [ "$arg" == "--scan" ]; then
        SCAN_PROJECT=true
    elif [ "$arg" == "--check-sync" ]; then
        CHECK_SYNC=true
    fi
done

echo "Starting the file upload process..."

# Move to working directory
cd "$WORK_DIR" || { echo "Could not change directory to $WORK_DIR"; exit 1; }

# Create log directory if missing
mkdir -p "$LOG_DIR" || { echo "Could not create log directory $LOG_DIR"; exit 1; }

# Check lftp availability
if ! command -v lftp &> /dev/null; then
    echo "Error: 'lftp' command not found. Please install it (e.g., 'brew install lftp' on macOS)."
    exit 1
fi

# Optionally run a project scan if requested and a scan script exists
if [ "$SCAN_PROJECT" = true ]; then
    if [ -f "scan_project.py" ]; then
        echo "Running scan_project.py (this may take a while)..."
        python3 scan_project.py || { echo "Error running scan_project.py"; exit 1; }
        echo "Project scan (scan_project.py) completed."
    elif [ -f "scan_project.sh" ]; then
        echo "Running scan_project.sh (this may take a while)..."
        bash scan_project.sh || { echo "Error running scan_project.sh"; exit 1; }
        echo "Project scan (scan_project.sh) completed."
    else
        echo "--scan was provided, but no scan script found. Skipping scan."
    fi
else
    echo "Skipping project scan (use --scan to enable if a scan script exists)."
fi

# Get list of uncommitted files (excluding deleted)
echo "Getting list of uncommitted files (excluding deleted files)..."
# Deleted files
git ls-files --deleted > "$TEMP_FILE_LIST.deleted"
# Modified + untracked
git ls-files --modified --others --exclude-standard > "$TEMP_FILE_LIST.all"
# Remove deleted from the list
grep -v -f "$TEMP_FILE_LIST.deleted" "$TEMP_FILE_LIST.all" > "$TEMP_FILE_LIST" || cp "$TEMP_FILE_LIST.all" "$TEMP_FILE_LIST"
rm -f "$TEMP_FILE_LIST.all" "$TEMP_FILE_LIST.deleted"

# Filter out upload_logs directory from upload list
echo "Filtering out upload_logs directory from upload list..."
grep -v "^upload_logs/" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Filter out this script itself from upload list
echo "Filtering out this script (upload_fixed_files.sh) from upload list..."
grep -v "^upload_fixed_files.sh$" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Filter out last_update_files.txt from upload list
echo "Filtering out last_update_files.txt from upload list..."
grep -v "^last_update_files.txt$" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Exit if nothing to upload
if [ ! -s "$TEMP_FILE_LIST" ]; then
    echo "No uncommitted files found. Nothing to upload."
    rm -f "$TEMP_FILE_LIST"
    exit 0
fi

# --- Start building HTML log ---
TIMESTAMP=$(date +%d_%m_%Y_%H_%M_%S)
LOG_FILE="$LOG_DIR/Uploaded_${TIMESTAMP}.html"

{
    echo "<!DOCTYPE html>"
    echo "<html lang=\"en\">"
    echo "<head>"
    echo "    <meta charset=\"UTF-8\">"
    echo "    <title>Upload Log - $TIMESTAMP</title>"
    echo "</head>"
    echo "<body>"
    echo "    <h1>Upload Session Log</h1>"
    echo "    <p>Timestamp: $(date '+%d/%m/%Y %H:%M:%S')</p>"
    echo "    <h2>Files Processed for Upload:</h2>"
    echo "    <ul>"
} > "$LOG_FILE"

while IFS= read -r file; do
    echo "        <li>$file</li>" >> "$LOG_FILE"
done < "$TEMP_FILE_LIST"

{
    echo "    </ul>"
    echo "    <h2 style='color: red;'>Files Missing During Upload Attempt:</h2>"
    echo "    <ul id=\"missing-files-list\">"
    echo "        <!-- Missing files will be listed here -->"
    echo "    </ul>"
    echo "    <h2 style='color: orange;'>Unsynced Files (Remote Only):</h2>"
    echo "    <ul id=\"unsynced-files-list\">"
    echo "        <!-- Unsynced files will be listed here if --check-sync was used -->"
    echo "    </ul>"
    echo "    <h2>Upload Status:</h2>"
    echo "    <ul id=\"upload-status-list\">"
    echo "        <!-- Upload status for each file will be listed here -->"
    echo "    </ul>"
    echo "</body>"
    echo "</html>"
} >> "$LOG_FILE"
# --- End building HTML log ---

# --- Optional sync check ---
if [ "$CHECK_SYNC" = true ]; then
    echo "Checking for unsynced files (Remote Only)..."
    REMOTE_FILES_TMP="/tmp/remote_files_$$.txt"
    LOCAL_FILES_TMP="/tmp/local_files_$$.txt"
    UNSYNCED_FILES_TMP="/tmp/unsynced_files_$$.txt"

    echo "Getting remote file list via lftp..."
    lftp -c "open -u $FTP_USER,$FTP_PASS $FTP_HOST; find . ; bye" | grep -v '/$' | sed 's|^\./||' > "$REMOTE_FILES_TMP"
    if [ $? -ne 0 ]; then
        echo "Error getting remote file list with lftp."
        UNSYNCED_CONTENT="        <li>Error getting remote file list with lftp.</li>"
    else
        echo "Getting local file list (excluding upload_logs)..."
        find . -path ./upload_logs -prune -o -type f -print | sed 's|^\./||' | sort > "$LOCAL_FILES_TMP"

        echo "Comparing file lists..."
        comm -13 "$LOCAL_FILES_TMP" <(sort "$REMOTE_FILES_TMP") > "$UNSYNCED_FILES_TMP"

        if [ ! -s "$UNSYNCED_FILES_TMP" ]; then
            echo "No unsynced (remote only) files found."
            UNSYNCED_CONTENT="        <li>No unsynced (remote only) files found.</li>"
        else
            echo "Found unsynced (remote only) files:"
            cat "$UNSYNCED_FILES_TMP"
            UNSYNCED_CONTENT=$(awk '{print "        <li>" $0 "</li>"}' "$UNSYNCED_FILES_TMP")
        fi
    fi

    awk -v unsynced="$UNSYNCED_CONTENT" '
    /<ul id="unsynced-files-list">/ { print; print unsynced; state="in_unsynced_list"; next }
    /<!-- Unsynced files will be listed here if --check-sync was used -->/ { next }
    /<\/ul>/ && state=="in_unsynced_list" { print; state=""; next }
    { print }
    ' "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    echo "Updated HTML log with sync check results."

    rm -f "$REMOTE_FILES_TMP" "$LOCAL_FILES_TMP" "$UNSYNCED_FILES_TMP"
fi
# --- End optional sync check ---

# Show list of files to be uploaded
echo "The following files will be uploaded:"
cat "$TEMP_FILE_LIST"

# Ensure tracking DB exists
if [ ! -f "$LAST_UPDATE_FILE" ]; then
    echo "Creating new file tracking database..."
    touch "$LAST_UPDATE_FILE"
fi

# --- Upload via lftp with MD5 tracking ---
echo "Checking files for changes and uploading if needed..."
UPLOAD_STATUS_TMP="$LOG_FILE.status"
> "$UPLOAD_STATUS_TMP"

while IFS= read -r file; do
    if [ -f "$file" ]; then
        remote_dir=$(dirname "$file")
        remote_base=$(basename "$file")
        local_full_path="$WORK_DIR/$file"

        # Compute MD5 (macOS md5, fallback to md5sum)
        current_md5=$(md5 -q "$file" 2>/dev/null || md5sum "$file" | awk '{print $1}')

        # Lookup old MD5
        old_md5=$(grep "^$file|" "$LAST_UPDATE_FILE" | cut -d'|' -f3)

        if [ -z "$old_md5" ] || [ "$current_md5" != "$old_md5" ]; then
            echo "Processing: $file (changed or new)"

            LFTP_OUTPUT_TMP="/tmp/lftp_output_$$.txt"

            lftp_cmd="open -u $FTP_USER,$FTP_PASS $FTP_HOST; \
                      set ssl:verify-certificate no; \
                      mkdir -p \"$remote_dir\"; \
                      cd \"$remote_dir\"; \
                      put \"$local_full_path\" -o \"./$remote_base\"; \
                      bye"

            lftp -c "$lftp_cmd" 2>&1 > "$LFTP_OUTPUT_TMP"
            LFTP_STATUS=$?

            if [ $LFTP_STATUS -eq 0 ] && ! grep -qi "failed\|error" "$LFTP_OUTPUT_TMP"; then
                echo " -> Uploaded."
                echo "        <li>$file: Uploaded (changed)</li>" >> "$UPLOAD_STATUS_TMP"

                if [ -n "$old_md5" ]; then
                    sed -i.bak "s@^$file|.*@$file|$remote_dir|$current_md5@" "$LAST_UPDATE_FILE"
                    rm -f "$LAST_UPDATE_FILE.bak"
                else
                    echo "$file|$remote_dir|$current_md5" >> "$LAST_UPDATE_FILE"
                fi
            else
                cat "$LFTP_OUTPUT_TMP"
                echo " -> Upload failed."
                echo "        <li style='color: red;'>$file: Upload failed - $(tr -d '\n' < "$LFTP_OUTPUT_TMP" | cut -c 1-100)...</li>" >> "$UPLOAD_STATUS_TMP"
            fi

            rm -f "$LFTP_OUTPUT_TMP"
        else
            echo "Processing: $file (unchanged, skipping upload)"
            echo "        <li style='color: green;'>$file: Unchanged (skipped)</li>" >> "$UPLOAD_STATUS_TMP"
        fi
    else
        echo "Warning: File $file not found locally before lftp attempt. Skipping."
        echo "        <li>$file (Missing locally before upload attempt)</li>" >> "$LOG_FILE.missing_local"

        sed -i.bak "s@^$file|.*@@" "$LAST_UPDATE_FILE"
        sed -i.bak '/^$/d' "$LAST_UPDATE_FILE"
        rm -f "$LAST_UPDATE_FILE.bak"
    fi

done < "$TEMP_FILE_LIST"

echo "Upload process finished."
# --- End upload ---

# --- Update HTML log with missing local files ---
MISSING_FILES_CONTENT=""
if [ -f "$LOG_FILE.missing_local" ]; then
    MISSING_FILES_CONTENT=$(cat "$LOG_FILE.missing_local")
    rm -f "$LOG_FILE.missing_local"
fi
if [ -n "$MISSING_FILES_CONTENT" ]; then
    awk -v missing="$MISSING_FILES_CONTENT" '
    /<ul id="missing-files-list">/ { print; print missing; state="in_missing_list"; next }
    /<!-- Missing files will be listed here -->/ { next }
    /<\/ul>/ && state=="in_missing_list" { print; state=""; next}
    { print }
    ' "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    echo "Updated HTML log with missing local files information."
fi
# --- End update HTML for missing ---

# --- Update HTML log with upload status ---
if [ -f "$UPLOAD_STATUS_TMP" ]; then
    HTML_TMP="$LOG_FILE.html.tmp"

    if sed -e "/<ul id=\"upload-status-list\">/,/<\/ul>/ {
        /<ul id=\"upload-status-list\">/ {
            p
            r $UPLOAD_STATUS_TMP
            d
        }
        /<!-- Upload status for each file will be listed here -->/ d
    }" "$LOG_FILE" > "$HTML_TMP"; then
        mv "$HTML_TMP" "$LOG_FILE"
        echo "Updated HTML log with upload status."
    else
        echo "Error updating HTML log with upload status."
        rm -f "$HTML_TMP"
    fi
    rm -f "$UPLOAD_STATUS_TMP"
fi
# --- End update HTML for status ---

# Cleanup temp list
rm -f "$TEMP_FILE_LIST"

# Final cleanup of temp artifacts
rm -f "$LOG_DIR"/*.tmp "$LOG_DIR"/*.status "$LOG_DIR"/*.missing_*

echo "Process completed!"
echo "File change tracking database updated at: $LAST_UPDATE_FILE"
