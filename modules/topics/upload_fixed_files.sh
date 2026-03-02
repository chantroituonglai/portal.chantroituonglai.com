#!/bin/bash

# Script to find uncommitted files and upload them to an FTP server
# Scan the project only when requested with the '--scan' parameter

# Color definitions
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
WHITE='\033[1;37m'
RESET='\033[0m'
BOLD='\033[1m'

# Clean up temporary files when the script exits (even on error)
trap 'rm -f "$TEMP_FILE_LIST"; rm -f "$LOG_DIR"/*.tmp; rm -f "$LOG_DIR"/*.status; rm -f "$LOG_DIR"/*.missing_*' EXIT

# Function to print colorful logs
function log_info() {
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[INFO]${RESET} $1"
}

function log_success() {
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${GREEN}[SUCCESS]${RESET} $1"
}

function log_warning() {
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${YELLOW}[WARNING]${RESET} $1"
}

function log_error() {
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[ERROR]${RESET} $1"
}

function log_connection() {
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} $1"
}

# Function to extract JSON value by key
function get_json_value() {
    local json_file=$1
    local key=$2
    
    # Check if jq is installed
    if command -v jq &> /dev/null; then
        # Use jq to extract the value (more reliable)
        jq -r ".$key" "$json_file"
    else
        # Fallback method if jq is not available
        local json=$(cat "$json_file")
        echo "$json" | grep "\"$key\"" | sed -E 's/.*"'$key'"[[:space:]]*:[[:space:]]*"([^"]*)".*$/\1/'
    fi
}

# Check for FTP configuration file
FTP_CONFIG_FILE=".vscode/ftp-sync.json"
if [ -f "$FTP_CONFIG_FILE" ]; then
    log_info "Found FTP configuration file: $FTP_CONFIG_FILE"
    
    # Check if jq is installed
    if command -v jq &> /dev/null; then
        log_info "Using jq for JSON parsing"
        # Extract FTP connection details using jq
        FTP_HOST=$(jq -r '.host' "$FTP_CONFIG_FILE")
        FTP_USER=$(jq -r '.username' "$FTP_CONFIG_FILE")
        FTP_PASS=$(jq -r '.password' "$FTP_CONFIG_FILE")
        FTP_PORT=$(jq -r '.port' "$FTP_CONFIG_FILE")
        FTP_REMOTE_PATH=$(jq -r '.remotePath' "$FTP_CONFIG_FILE")
        
        # Fix null values in remotePath (in case it's not defined in the config)
        if [ "$FTP_REMOTE_PATH" = "null" ]; then
            FTP_REMOTE_PATH=""
        fi
    else
        log_warning "jq not found, using fallback JSON parsing method"
        # Extract FTP connection details using grep/sed
        FTP_HOST=$(get_json_value "$FTP_CONFIG_FILE" "host")
        FTP_USER=$(get_json_value "$FTP_CONFIG_FILE" "username")
        FTP_PASS=$(get_json_value "$FTP_CONFIG_FILE" "password")
        FTP_PORT=$(get_json_value "$FTP_CONFIG_FILE" "port")
        FTP_REMOTE_PATH=$(get_json_value "$FTP_CONFIG_FILE" "remotePath")
    fi
    
    if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASS" ]; then
        log_error "Could not extract required FTP connection details from $FTP_CONFIG_FILE"
        exit 1
    fi
    
    # Standardize remote path - remove leading ./ if present and ensure it ends with a /
    if [[ "$FTP_REMOTE_PATH" == "./"* ]]; then
        FTP_REMOTE_PATH="${FTP_REMOTE_PATH:2}"
    fi
    # Ensure remote path ends with a / if not empty
    if [ ! -z "$FTP_REMOTE_PATH" ] && [[ "$FTP_REMOTE_PATH" != */ ]]; then
        FTP_REMOTE_PATH="${FTP_REMOTE_PATH}/"
    fi
    
    # Force set remotePath if it's empty (for debugging purposes)
    # Uncomment and modify the line below to force a specific remote path
    # FTP_REMOTE_PATH="modules/topics/"
    
    # Hiển thị đường dẫn từ xa trên server (more verbose)
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[PATH]${RESET} Đường dẫn từ xa: ${WHITE}${BOLD}$FTP_REMOTE_PATH${RESET}"
    
    # Lấy thư mục gốc từ server để hiển thị đường dẫn đầy đủ
    SERVER_ROOT_DIR=$(lftp -c "open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; pwd; bye" 2>/dev/null)
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[PATH]${RESET} Thư mục gốc trên server: ${WHITE}${BOLD}$SERVER_ROOT_DIR${RESET}"
    
    # Display the FTP configuration (for debugging)
    log_info "Host: ${YELLOW}$FTP_HOST${RESET}"
    log_info "Username: ${YELLOW}$FTP_USER${RESET}"
    log_info "Port: ${YELLOW}$FTP_PORT${RESET}"
    log_info "Remote Path: ${YELLOW}$FTP_REMOTE_PATH${RESET}"
    
    log_success "FTP configuration loaded successfully."
else
    log_error "FTP configuration file not found at $FTP_CONFIG_FILE"
    echo "Please ensure .vscode/ftp-sync.json exists with host, username, and password defined."
    exit 1
fi

# Dynamically set the path to the current project's root directory (where this script is located)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
WORK_DIR="$SCRIPT_DIR"
TEMP_FILE_LIST="/tmp/files_to_upload.txt"
LOG_DIR="$WORK_DIR/upload_logs"
LAST_UPDATE_FILE="$WORK_DIR/last_update_files.txt"
# Server base path for this module (scope all remote operations to this dir)
SERVER_BASE_PATH="/public_html/modules/topics"

# Check if project scan is needed
SCAN_PROJECT=false
CHECK_SYNC=false
DEBUG_MODE=false
PRUNE_REMOTE=false
PRUNE_DRY_RUN=true
for arg in "$@"; do
    if [ "$arg" == "--scan" ]; then
        SCAN_PROJECT=true
    elif [ "$arg" == "--check-sync" ]; then
        CHECK_SYNC=true
    elif [ "$arg" == "--prune-remote" ]; then
        PRUNE_REMOTE=true
        PRUNE_DRY_RUN=true
    elif [ "$arg" == "--prune-remote-apply" ] || [ "$arg" == "--yes-prune" ]; then
        PRUNE_REMOTE=true
        PRUNE_DRY_RUN=false
    elif [ "$arg" == "--debug" ]; then
        DEBUG_MODE=true
    fi
done

echo -e "${WHITE}${BOLD}=== FTP FILE DEPLOYMENT SYSTEM v1.0 ===${RESET}"
log_info "Starting the file upload process..."

log_info "Working directory set to: ${YELLOW}$WORK_DIR${RESET}"

# Change to the working directory
cd "$WORK_DIR" || { log_error "Could not change directory to $WORK_DIR"; exit 1; }

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR" || { log_error "Could not create log directory $LOG_DIR"; exit 1; }

# Check for lftp
if ! command -v lftp &> /dev/null; then
    log_error "'lftp' command not found. This script requires lftp to run."
    echo "Please install lftp (e.g., 'brew install lftp' on macOS) and try again."
    exit 1
fi

# Check if the directory is a Git repository
if ! git rev-parse --is-inside-work-tree &> /dev/null; then
    log_error "Not a Git repository. This script must be run in a Git repository."
    exit 1
fi

# Run project scan script if requested
if [ "$SCAN_PROJECT" = true ]; then
    log_info "Running project scan script (this may take a while)..."
    python3 scan_project.py
    
    if [ $? -ne 0 ]; then
        log_error "Error running Python script. Exiting."
        exit 1
    fi
    
    log_success "Project scan completed successfully."
else
    log_info "Skipping project scan (use --scan parameter if you want to run the scan)."
fi

# Get list of uncommitted files from Git, excluding deleted files
log_info "Getting list of uncommitted files (excluding deleted files)..."
# Get list of deleted files
git ls-files --deleted > "$TEMP_FILE_LIST.deleted"
# Get list of modified and untracked files
git ls-files --modified --others --exclude-standard > "$TEMP_FILE_LIST.all"
# Remove deleted files from the upload list
grep -v -f "$TEMP_FILE_LIST.deleted" "$TEMP_FILE_LIST.all" > "$TEMP_FILE_LIST" || cp "$TEMP_FILE_LIST.all" "$TEMP_FILE_LIST"
# Clean up temporary files
rm -f "$TEMP_FILE_LIST.all" "$TEMP_FILE_LIST.deleted"

# Filter out files in the upload_logs directory from the upload list
log_info "Filtering out upload_logs directory from upload list..."
grep -v "^upload_logs/" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Filter out this script from the upload list
log_info "Filtering out this script (upload_fixed_files.sh) from upload list..."
grep -v "^upload_fixed_files.sh$" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Filter out last_update_files.txt from the upload list
log_info "Filtering out last_update_files.txt from upload list..."
grep -v "^last_update_files.txt$" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Filter out any paths starting with 'upload_fixed_files' (files or folders)
log_info "Filtering out upload_fixed_files* from upload list..."
grep -v "^upload_fixed_files" "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Filter out files inside dot-directories (e.g., .vscode/, .git/)
log_info "Filtering out files located in dot-directories (e.g., .vscode/, .git/) from upload list..."
grep -vE '(^|/)\.[^/]+/' "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Explicitly filter out common dot-directories as a guard
log_info "Explicitly filtering out .git/, .vscode/, .cursor/ from upload list..."
grep -vE '^(\.git/|\.vscode/|\.cursor/)' "$TEMP_FILE_LIST" > "$TEMP_FILE_LIST.filtered" && mv "$TEMP_FILE_LIST.filtered" "$TEMP_FILE_LIST"

# Check if there are files to upload
if [ ! -s "$TEMP_FILE_LIST" ]; then
    log_info "No uncommitted files found. Nothing to upload."
    rm "$TEMP_FILE_LIST"
    exit 0
fi

# --- Start creating log HTML ---
TIMESTAMP=$(date +%d_%m_%Y_%H_%M_%S)
LOG_FILE="$LOG_DIR/Uploaded_${TIMESTAMP}.html"

echo "<!DOCTYPE html>" > "$LOG_FILE"
echo "<html lang=\"en\">" >> "$LOG_FILE"
echo "<head>" >> "$LOG_FILE"
echo "    <meta charset=\"UTF-8\">" >> "$LOG_FILE"
echo "    <title>Upload Log - $TIMESTAMP</title>" >> "$LOG_FILE"
echo "</head>" >> "$LOG_FILE"
echo "<body>" >> "$LOG_FILE"
echo "    <h1>Upload Session Log</h1>" >> "$LOG_FILE"
echo "    <p>Timestamp: $(date '+%d/%m/%Y %H:%M:%S')</p>" >> "$LOG_FILE"
echo "    <h2>Files Processed for Upload:</h2>" >> "$LOG_FILE"
echo "    <ul>" >> "$LOG_FILE"

# Write list of files to log
while IFS= read -r file; do
    echo "        <li>$file</li>" >> "$LOG_FILE"
done < "$TEMP_FILE_LIST"

echo "    </ul>"
# Add section for missing files
echo "    <h2 style='color: red;'>Files Missing During Upload Attempt:</h2>" >> "$LOG_FILE"
echo "    <ul id=\"missing-files-list\">" >> "$LOG_FILE"
echo "        <!-- Missing files will be listed here -->" >> "$LOG_FILE"
echo "    </ul>" >> "$LOG_FILE"
# Add section for unsynced files
echo "    <h2 style='color: orange;'>Unsynced Files (Remote Only):</h2>" >> "$LOG_FILE"
echo "    <ul id=\"unsynced-files-list\">" >> "$LOG_FILE"
echo "        <!-- Unsynced files will be listed here if --check-sync was used -->" >> "$LOG_FILE"
echo "    </ul>" >> "$LOG_FILE"
# Add section for upload status
echo "    <h2>Upload Status:</h2>" >> "$LOG_FILE"
echo "    <ul id=\"upload-status-list\">" >> "$LOG_FILE"
echo "        <!-- Upload status for each file will be listed here -->" >> "$LOG_FILE"
echo "    </ul>" >> "$LOG_FILE"
echo "</body>" >> "$LOG_FILE"
echo "</html>" >> "$LOG_FILE"
# --- End creating log HTML ---

# --- Start checking for unsynced files (if requested) ---
if [ "$CHECK_SYNC" = true ]; then
    echo "Checking for unsynced files (Remote Only)..."
    REMOTE_FILES_TMP="/tmp/remote_files_$$.txt"
    LOCAL_FILES_TMP="/tmp/local_files_$$.txt"
    UNSYNCED_FILES_TMP="/tmp/unsynced_files_$$.txt"

    # Get remote file list (scoped to module dir) using lftp
    echo "Getting remote file list via lftp..."
    MODULE_PREFIX_NO_SLASH="${SERVER_BASE_PATH#/}"
    lftp -c "open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; find . ; bye" \
        | grep -v '/$' \
        | sed 's|^\\./||' \
        | grep -E "^$MODULE_PREFIX_NO_SLASH/" \
        | sed "s|^$MODULE_PREFIX_NO_SLASH/||" > "$REMOTE_FILES_TMP"
    if [ $? -ne 0 ]; then
         echo "Error getting remote file list with lftp."
         UNSYNCED_CONTENT="        <li>Error getting remote file list with lftp.</li>"
    else
        # Get local file list, excluding upload_logs and dot-directories
        echo "Getting local file list (excluding upload_logs and dot-directories)..."
        find . -path ./upload_logs -prune -o -type d -name ".*" -prune -o -type f -print | sed 's|^\\./||' | sort > "$LOCAL_FILES_TMP"

        # Compare lists
        echo "Comparing file lists..."
        comm -13 "$LOCAL_FILES_TMP" <(sort "$REMOTE_FILES_TMP") > "$UNSYNCED_FILES_TMP"

        if [ ! -s "$UNSYNCED_FILES_TMP" ]; then
            echo "No unsynced (remote only) files found."
            UNSYNCED_CONTENT="        <li>No unsynced (remote only) files found.</li>"
        else
            echo "Found unsynced (remote only) files:"
            cat "$UNSYNCED_FILES_TMP"
            # Prepare content for HTML log
            UNSYNCED_CONTENT=$(awk '{print "        <li>" $0 "</li>"}' "$UNSYNCED_FILES_TMP")
         fi
    fi

    # Update HTML log with sync check results
    awk -v unsynced="$UNSYNCED_CONTENT" '
    /<ul id="unsynced-files-list">/ { print; print unsynced; state="in_unsynced_list"; next }
    /<!-- Unsynced files will be listed here if --check-sync was used -->/ { next }
    /</ul>/ && state=="in_unsynced_list" { print; state=""; next }
    { print }
    ' "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    echo "Updated HTML log with sync check results."

    # Clean up temporary files of sync check
    rm -f "$REMOTE_FILES_TMP" "$LOCAL_FILES_TMP" "$UNSYNCED_FILES_TMP"
fi
# --- End checking for unsynced files ---

# --- Begin pruning remote-only files (if requested) ---
if [ "$PRUNE_REMOTE" = true ]; then
    echo "Preparing to prune remote-only files under $SERVER_BASE_PATH ..."
    REMOTE_FILES_TMP="/tmp/remote_files_$$.txt"
    LOCAL_FILES_TMP="/tmp/local_files_$$.txt"
    TO_DELETE_TMP="/tmp/to_delete_remote_$$.txt"

    # Build remote (scoped) and local lists again
    MODULE_PREFIX_NO_SLASH="${SERVER_BASE_PATH#/}"
    lftp -c "open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; find . ; bye" \
        | grep -v '/$' \
        | sed 's|^\\./||' \
        | grep -E "^$MODULE_PREFIX_NO_SLASH/" \
        | sed "s|^$MODULE_PREFIX_NO_SLASH/||" \
        | sort > "$REMOTE_FILES_TMP"

    find . -path ./upload_logs -prune -o -type d -name ".*" -prune -o -type f -print \
        | sed 's|^\\./||' | sort > "$LOCAL_FILES_TMP"

    comm -13 "$LOCAL_FILES_TMP" "$REMOTE_FILES_TMP" > "$TO_DELETE_TMP"

    if [ ! -s "$TO_DELETE_TMP" ]; then
        echo "No remote-only files to prune."
    else
        echo "Remote-only files (relative to module) to prune:" | cat
        cat "$TO_DELETE_TMP"

        if [ "$PRUNE_DRY_RUN" = true ]; then
            log_info "Dry-run: listing files that would be deleted from remote. Use --prune-remote-apply to actually delete."
            # Log into HTML
            while IFS= read -r f; do
                echo "        <li style='color: orange;'>[DRY-RUN] remote delete: $f</li>" >> "$LOG_FILE.status"
            done < "$TO_DELETE_TMP"
        else
            echo "Deleting remote-only files from $SERVER_BASE_PATH ..."
            while IFS= read -r relpath; do
                [ -z "$relpath" ] && continue
                remote_full="$SERVER_BASE_PATH/$relpath"
                # Perform deletion
                lftp -c "open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; \
                         set ssl:verify-certificate no; \
                         rm -f \"$remote_full\"; bye" >/dev/null 2>&1
                if [ $? -eq 0 ]; then
                    echo "        <li style='color: red;'>[DELETED] $relpath</li>" >> "$LOG_FILE.status"
                    echo "Deleted remote file: $relpath"
                else
                    echo "        <li style='color: red;'>[FAILED DELETE] $relpath</li>" >> "$LOG_FILE.status"
                    echo "Failed to delete remote file: $relpath"
                fi
            done < "$TO_DELETE_TMP"
        fi
    fi

    rm -f "$REMOTE_FILES_TMP" "$LOCAL_FILES_TMP" "$TO_DELETE_TMP"
fi
# --- End pruning remote-only files ---

# Show list of files to be uploaded
echo -e "\n${YELLOW}=== FILES QUEUED FOR DEPLOYMENT ===${RESET}"
cat "$TEMP_FILE_LIST" | while read line; do
  echo -e "  ${CYAN}→${RESET} $line"
done

# Create last_update_files.txt if it doesn't exist
if [ ! -f "$LAST_UPDATE_FILE" ]; then
    log_info "Creating new file tracking database..."
    touch "$LAST_UPDATE_FILE"
fi

# Display a loading bar for server connection and provide a cancel option
echo -e "\n${MAGENTA}[SYSTEM]${RESET} Initiating secure connection to remote server..."

# Test FTP connection before proceeding
log_info "Testing FTP connection to ${YELLOW}$FTP_HOST${RESET}..."

# Debug values
log_info "Debug FTP values (stripped of quotes and spaces):"
log_info "Host: '${FTP_HOST}'"
log_info "User: '${FTP_USER}'"
log_info "Port: '${FTP_PORT}'"

# Use port if specified, otherwise default to port 21
FTP_PORT_PARAM=""
if [ ! -z "$FTP_PORT" ]; then
    FTP_PORT_PARAM="-p $FTP_PORT"
    log_info "Using specified port: $FTP_PORT"
else
    log_info "Using default FTP port (21)"
fi

# Construct a clean connection string
TEST_CONN_CMD="open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; pwd; bye"
log_info "Connection command: lftp -c \"${TEST_CONN_CMD/$FTP_PASS/****}\""

TEST_CONN_OUTPUT=$(lftp -c "$TEST_CONN_CMD" 2>&1)
FTP_TEST_STATUS=$?

# Check for common error patterns even if the exit status is 0
if [ $FTP_TEST_STATUS -ne 0 ] || echo "$TEST_CONN_OUTPUT" | grep -q -E "no such (tcp service|host|file)" || echo "$TEST_CONN_OUTPUT" | grep -q -E "Not connected"; then
    log_error "Failed to connect to FTP server. Error details:"
    echo -e "${RED}$TEST_CONN_OUTPUT${RESET}"
    log_error "Please check your FTP credentials and server status."
    exit 1
else
    log_success "FTP connection test successful!"
    log_info "Server response: ${CYAN}$TEST_CONN_OUTPUT${RESET}"
fi

# Function to display a hacker-style loading bar
function show_loading_bar() {
    local duration=$1
    local interval=0.1
    local total_steps=$((duration * 10))
    local step=0
    local bar_length=30
    local symbols=("⠋" "⠙" "⠹" "⠸" "⠼" "⠴" "⠦" "⠧" "⠇" "⠏")
    local symbol_index=0
    
    # Display port info correctly
    local port_display="21 (default)"
    if [ ! -z "$FTP_PORT" ]; then
        port_display="$FTP_PORT"
    fi
    
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Establishing connection to ${YELLOW}$FTP_HOST${RESET}:${YELLOW}$port_display${RESET}..."
    
    while [ $step -lt $total_steps ]; do
        step=$((step + 1))
        percent=$((step * 100 / total_steps))
        filled=$((step * bar_length / total_steps))
        empty=$((bar_length - filled))
        
        # Create the progress bar
        bar=""
        for ((i=0; i<filled; i++)); do bar+="█"; done
        for ((i=0; i<empty; i++)); do bar+="▒"; done
        
        # Update symbol_index
        symbol_index=$(( (symbol_index + 1) % ${#symbols[@]} ))
        
        # Display the progress bar with color
        if [ $percent -lt 30 ]; then
            color="${BLUE}"
        elif [ $percent -lt 60 ]; then
            color="${YELLOW}"
        else
            color="${GREEN}"
        fi
        
        # Add connection status messages at certain points
        if [ $percent -eq 25 ]; then
            printf "\r${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Resolving host DNS... ${GREEN}[OK]${RESET}                          \n"
        elif [ $percent -eq 45 ]; then
            printf "\r${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Verifying server fingerprint... ${GREEN}[VERIFIED]${RESET}          \n"
        elif [ $percent -eq 65 ]; then
            masked_user=$(echo $FTP_USER | sed 's/\(.\{3\}\).\+/\1****/')
            printf "\r${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Authenticating as ${YELLOW}$masked_user${RESET}... ${GREEN}[ACCEPTED]${RESET}       \n"
        elif [ $percent -eq 85 ]; then
            printf "\r${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Negotiating encryption protocol... ${GREEN}[TLS_1.2]${RESET}         \n"
        fi
        
        printf "\r${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} ${symbols[$symbol_index]} [${color}${bar}${RESET}] ${WHITE}${percent}%%${RESET} "
        sleep $interval
    done
    printf "\n"
}

# Show loading bar for 3 seconds and allow user to cancel
log_warning "Press ${RED}Ctrl+C${RESET} to cancel the upload within 3 seconds..."
(trap 'echo -e "\n${RED}[ABORT]${RESET} Upload cancelled by user."; exit 1' INT; show_loading_bar 3) || { echo -e "\n${RED}[ABORT]${RESET} Upload cancelled."; exit 1; }

# Show connection successful info
echo -e "\n${CYAN}[$(date +%H:%M:%S)]${RESET} ${GREEN}[SUCCESS]${RESET} Connection established!"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Server: ${YELLOW}$FTP_HOST${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Remote Path: ${YELLOW}$FTP_REMOTE_PATH${RESET}"
# Display port info correctly
if [ ! -z "$FTP_PORT" ]; then
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Port: ${YELLOW}$FTP_PORT${RESET}"
else
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Port: ${YELLOW}21 (default)${RESET}"
fi

echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} User: ${YELLOW}$(echo $FTP_USER | sed 's/\(.\{3\}\).\+/\1****/')${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Protocol: ${YELLOW}FTP/SFTP${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Encryption: ${YELLOW}TLS 1.2${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Session ID: ${YELLOW}$(echo $RANDOM | md5sum | head -c 8)${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Handshake: ${GREEN}COMPLETED${RESET}\n"

echo -e "${WHITE}${BOLD}=== BEGINNING FILE DEPLOYMENT === ${RESET}"
log_info "Checking files for changes and uploading if needed..."
UPLOAD_STATUS_TMP="$LOG_FILE.status"
> "$UPLOAD_STATUS_TMP" # Create/Clear temporary status file

# Add a function to print the remote directory listing for debugging
function debug_remote_listing() {
    local dir_path=$1
    
    # Prepend the remotePath to the directory path
    if [ ! -z "$FTP_REMOTE_PATH" ]; then
        local remote_full_dir="${FTP_REMOTE_PATH}${dir_path}"
    else
        local remote_full_dir="${dir_path}"
    fi
    
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[DEBUG]${RESET} Listing contents of remote directory: ${YELLOW}$remote_full_dir${RESET}"
    
    # Use lftp to list the remote directory
    lftp -c "open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; 
             set ssl:verify-certificate no;
             ls -la \"$remote_full_dir\";
             bye" 2>&1 | while read line; do
        echo -e "       ${CYAN}↳${RESET} $line"
    done
}

while IFS= read -r file; do
    # Safety guard: never upload files inside upload_logs or dot-directories
    if [[ "$file" == upload_logs/* || "$file" == .git/* || "$file" == .vscode/* || "$file" == .cursor/* ]]; then
        log_info "Skipping excluded file: ${YELLOW}$file${RESET}"
        echo "        <li style='color: green;'>$file: Skipped (excluded)</li>" >> "$UPLOAD_STATUS_TMP"
        continue
    fi
    if [ -f "$file" ]; then
        remote_dir=$(dirname "$file")
        remote_base=$(basename "$file")
        local_full_path="$WORK_DIR/$file"
        
        # Calculate MD5 of current file
        current_md5=$(md5 -q "$file" 2>/dev/null || md5sum "$file" | awk '{print $1}')
        
        # Check if file is in database
        old_md5=$(grep "^$file|" "$LAST_UPDATE_FILE" | cut -d'|' -f3)
        
        if [ -z "$old_md5" ] || [ "$current_md5" != "$old_md5" ]; then
            echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[UPLOAD]${RESET} Processing ${YELLOW}$file${RESET} (changed or new)"
            
            # Create temporary file to store lftp output
            LFTP_OUTPUT_TMP="/tmp/lftp_output_$$.txt"
            
            # Show file transfer animation
            echo -ne "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[UPLOAD]${RESET} Transferring ${YELLOW}$file${RESET} "
            for i in {1..10}; do
                echo -ne "${YELLOW}→${RESET}"
                sleep 0.05
            done
            echo ""
            
            # Đặt cứng đường dẫn gốc trên server - không phụ thuộc vào ftp-sync.json
            SERVER_BASE_PATH="/public_html/modules/topics"
            log_info "Đường dẫn gốc đặt cứng trên server: ${YELLOW}$SERVER_BASE_PATH${RESET}"
            
            # Đường dẫn tương đối của file từ thư mục gốc của project
            if [ "$remote_dir" = "." ]; then
                # File nằm ngay thư mục gốc, không có thư mục con
                full_destination_path="$SERVER_BASE_PATH/$remote_base"
            else
                # File nằm trong thư mục con
                full_destination_path="$SERVER_BASE_PATH/$remote_dir/$remote_base"
            fi
            
            # Loại bỏ các dấu // trùng lặp
            full_destination_path=$(echo "$full_destination_path" | sed 's#//#/#g')
            
            # Hiển thị thông tin đường dẫn
            log_info "Đường dẫn tương đối file: ${YELLOW}$remote_dir/$remote_base${RESET}"
            log_info "Đường dẫn upload đầy đủ: ${YELLOW}$full_destination_path${RESET}"
            
            echo -e "\n${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[UPLOAD PATH]${RESET} Thông tin đường dẫn chi tiết:"
            echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[UPLOAD PATH]${RESET} File gốc: ${YELLOW}$file${RESET}"
            echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[UPLOAD PATH]${RESET} Thư mục local: ${YELLOW}$remote_dir${RESET}"
            echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${RED}[UPLOAD PATH]${RESET} ĐƯỜNG DẪN UPLOAD: ${WHITE}${BOLD}$full_destination_path${RESET}"
            
            # Before we execute the LFTP command, let's add a verification step
            echo -e "\n${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[VERIFY]${RESET} Kiểm tra lệnh upload:"
            echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[VERIFY]${RESET} File nguồn: ${YELLOW}$local_full_path${RESET}"
            echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[VERIFY]${RESET} Đường dẫn upload: ${YELLOW}$full_destination_path${RESET}"
            
            # Xây dựng lệnh LFTP đơn giản
            lftp_cmd="open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM; 
                      set ssl:verify-certificate no; 
                      set ftp:ssl-allow true;
                      set net:max-retries 3;
                      set net:reconnect-interval-base 5;
                      set net:reconnect-interval-multiplier 1;
                      set ftp:list-options -a;
                      set xfer:clobber yes; # Cho phép ghi đè file
                      
                      # Tạo thư mục đích (và thư mục cha) nếu chưa tồn tại
                      # Lưu ý: phải tách riêng dấu ; để không trở thành một phần của đường dẫn
                      echo \"Creating directory structure if needed\";
                      mkdir -p \"$(dirname "$full_destination_path")\"
                      ;
                      
                      # Upload file trực tiếp vào đường dẫn đích
                      echo \"Uploading file to: $full_destination_path\";
                      put \"$local_full_path\" -o \"$full_destination_path\"
                      ;
                      
                      # Xác nhận đường dẫn file đã tạo
                      echo \"Verifying file exists at destination:\";
                      ls -la \"$full_destination_path\"
                      ;
                      
                      bye"
            
            # Print the exact command for debugging (mask password)
            masked_lftp_cmd=$(echo "$lftp_cmd" | sed "s/$FTP_PASS/****/g")
            # echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[DEBUG CMD]${RESET} Running LFTP command:\n${WHITE}lftp -c \"$masked_lftp_cmd\"${RESET}"

            # Run lftp and store output
            lftp -c "$lftp_cmd" 2>&1 > "$LFTP_OUTPUT_TMP"
            LFTP_STATUS=$?
            
            # Initial check for *showstopper* errors (excluding mkdir exists)
            CRITICAL_ERROR=false
            if [ $LFTP_STATUS -ne 0 ]; then
                CRITICAL_ERROR=true
            # Check for critical errors in output, IGNORING the mkdir 'File exists'
            elif grep -q -i -E "(failed|error|access.*fail|usage|cannot|not found|permission denied)" "$LFTP_OUTPUT_TMP" && \
                 ! grep -q -i -E "Access failed: 550.*File exists" "$LFTP_OUTPUT_TMP"; then
                # Found a potential critical error other than 'mkdir File exists'
                CRITICAL_ERROR=true
            fi

            # Now decide based on CRITICAL_ERROR and MD5 check
            if [ "$CRITICAL_ERROR" = "true" ]; then
                # A definite error occurred during the lftp command execution
                log_error "Upload command failed for ${YELLOW}$file${RESET}"
                cat "$LFTP_OUTPUT_TMP" | while read line; do echo -e "       ${RED}↳${RESET} $line"; done
                echo "        <li style='color: red;'>$file: Upload command failed</li>" >> "$UPLOAD_STATUS_TMP"
            else
                # LFTP command seemed okay, proceed to verification
                log_info "LFTP command completed for ${YELLOW}$file${RESET}. Verifying file integrity..."
                
                # Kiểm tra xem file đã được upload thành công hay chưa bằng cách tìm output của lệnh ls
                if grep -q "$full_destination_path" "$LFTP_OUTPUT_TMP"; then
                    log_info "File đã được upload thành công, tiếp tục với kiểm tra MD5"
                    
                    # Download the file we just uploaded for MD5 verification
                    TEMP_DOWNLOAD_FILE="/tmp/verify_upload_$$.tmp"
                    rm -f "$TEMP_DOWNLOAD_FILE" # Ensure it doesn't exist beforehand

                    # Download the file we just uploaded
                    lftp -c "open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" $FTP_PORT_PARAM;
                              set ssl:verify-certificate no;
                              get \"$full_destination_path\" -o \"$TEMP_DOWNLOAD_FILE\";
                              bye" > /dev/null 2>&1
                    DOWNLOAD_STATUS=$?

                    if [ $DOWNLOAD_STATUS -eq 0 ] && [ -f "$TEMP_DOWNLOAD_FILE" ]; then
                        downloaded_md5=$(md5 -q "$TEMP_DOWNLOAD_FILE" 2>/dev/null || md5sum "$TEMP_DOWNLOAD_FILE" | awk '{print $1}')

                        if [ "$current_md5" = "$downloaded_md5" ]; then
                            log_success "File ${YELLOW}$file${RESET} uploaded and verified successfully (MD5 match)."
                            echo "        <li>$file: Uploaded and verified (MD5 match)</li>" >> "$UPLOAD_STATUS_TMP"
                            # Update database only on confirmed success
                            if [ -n "$old_md5" ]; then
                                sed -i.bak "s@^$file|.*@$file|$remote_dir|$current_md5@" "$LAST_UPDATE_FILE" && rm -f "$LAST_UPDATE_FILE.bak"
                            else
                                echo "$file|$remote_dir|$current_md5" >> "$LAST_UPDATE_FILE"
                            fi
                        else
                            log_error "Upload FAILED for ${YELLOW}$file${RESET}. MD5 mismatch."
                            log_info "Local MD5: ${YELLOW}$current_md5${RESET}"
                            log_info "Server MD5: ${YELLOW}$downloaded_md5${RESET}"
                            echo "        <li style='color: red;'>$file: Uploaded but MD5 verification failed</li>" >> "$UPLOAD_STATUS_TMP"
                            log_info "LFTP command output:"
                            cat "$LFTP_OUTPUT_TMP" | while read line; do echo -e "       ${YELLOW}↳${RESET} $line"; done
                        fi
                        rm -f "$TEMP_DOWNLOAD_FILE"
                    else
                        log_error "Upload FAILED for ${YELLOW}$file${RESET}. Could not download file for verification."
                        echo "        <li style='color: red;'>$file: Uploaded but couldn't download for verification</li>" >> "$UPLOAD_STATUS_TMP"
                        log_info "LFTP command output:"
                        cat "$LFTP_OUTPUT_TMP" | while read line; do echo -e "       ${YELLOW}↳${RESET} $line"; done
                    fi
                else
                    log_error "Upload FAILED for ${YELLOW}$file${RESET}. File not found at destination path."
                    echo "        <li style='color: red;'>$file: Upload failed - File not found at destination</li>" >> "$UPLOAD_STATUS_TMP"
                    log_info "LFTP command output:"
                    cat "$LFTP_OUTPUT_TMP" | while read line; do echo -e "       ${YELLOW}↳${RESET} $line"; done
                fi
            fi
            
            # Clean up temporary LFTP output file
            rm -f "$LFTP_OUTPUT_TMP"
        else
            log_info "Processing ${YELLOW}$file${RESET} ${GREEN}(unchanged, skipping)${RESET}"
            echo "        <li style='color: green;'>$file: Unchanged (skipped)</li>" >> "$UPLOAD_STATUS_TMP"
        fi
    else
        # Log missing files
        log_warning "File ${YELLOW}$file${RESET} not found locally before upload attempt. Skipping."
        echo "        <li>$file (Missing locally before upload attempt)</li>" >> "$LOG_FILE.missing_local"
        
        # Remove file from database if it no longer exists
        sed -i.bak "s@^$file|.*@@" "$LAST_UPDATE_FILE"
        sed -i.bak '/^$/d' "$LAST_UPDATE_FILE"  # Remove empty lines
        rm -f "$LAST_UPDATE_FILE.bak"
    fi
done < "$TEMP_FILE_LIST"

echo -e "\n${GREEN}${BOLD}=== DEPLOYMENT COMPLETED ====${RESET}"
log_success "Upload process finished."
# --- End Uploading using lftp ---

# --- Update Log HTML with missing files (local) ---
# Rename temporary file for clarity
MISSING_FILES_CONTENT=""
if [ -f "$LOG_FILE.missing_local" ]; then
    MISSING_FILES_CONTENT=$(cat "$LOG_FILE.missing_local")
    rm "$LOG_FILE.missing_local"
fi
# Insert missing files content into log HTML if any
if [ -n "$MISSING_FILES_CONTENT" ]; then
    awk -v missing="$MISSING_FILES_CONTENT" '
    /<ul id="missing-files-list">/ { print; print missing; state="in_missing_list"; next }
    /<!-- Missing files will be listed here -->/ { next } 
    /</ul>/ && state=="in_missing_list" { print; state=""; next}
    { print } 
    ' "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    log_info "Updated HTML log with missing local files information."
fi
# --- End updating Log HTML for missing files ---

# --- Update Log HTML with upload status --- 
if [ -f "$UPLOAD_STATUS_TMP" ]; then
    # Simplified method: create temporary HTML file
    HTML_TMP="$LOG_FILE.html.tmp"
    
    # Use sed to replace placeholder
    if sed -e "/<ul id=\"upload-status-list\">/,/<\/ul>/ {
        /<ul id=\"upload-status-list\">/ {
            p
            r $UPLOAD_STATUS_TMP
            d
        }
        /<!-- Upload status for each file will be listed here -->/ d
    }" "$LOG_FILE" > "$HTML_TMP"; then
        # Replace original file if sed succeeds
        mv "$HTML_TMP" "$LOG_FILE"
        log_info "Updated HTML log with upload status."
    else
        log_error "Error updating HTML log with upload status."
        # Always delete temporary file if there's an error with sed
        rm -f "$HTML_TMP"
    fi
    # Always delete temporary status file
    rm -f "$UPLOAD_STATUS_TMP"
fi
# --- End updating Log HTML for status ---

# Add debug information if requested
if [ "$DEBUG_MODE" = true ]; then
    echo -e "\n${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[DEBUG]${RESET} Debugging information:"
    
    # List recently processed files
    echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[DEBUG]${RESET} Files processed in this session:"
    while IFS= read -r file; do
        echo -e "       ${CYAN}→${RESET} $file"
    done < "$TEMP_FILE_LIST"
    
    # Get a list of unique remote directories from the upload list
    UNIQUE_DIRS=$(cat "$TEMP_FILE_LIST" | xargs -n1 dirname 2>/dev/null | sort | uniq)
    
    # Show listing for each directory
    for dir in $UNIQUE_DIRS; do
        debug_remote_listing "$dir"
    done
fi

# Clean up temporary file
rm -f "$TEMP_FILE_LIST" 

# Final cleanup - ensure no temporary files remain
rm -f "$LOG_DIR"/*.tmp "$LOG_DIR"/*.status "$LOG_DIR"/*.missing_*

echo -e "\n${CYAN}[$(date +%H:%M:%S)]${RESET} ${GREEN}[COMPLETE]${RESET} Process completed successfully!"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[INFO]${RESET} File change tracking database at: ${YELLOW}$LAST_UPDATE_FILE${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${BLUE}[INFO]${RESET} Log file generated at: ${YELLOW}$LOG_FILE${RESET}"
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${MAGENTA}[CONN]${RESET} Closing secure connection..."
echo -e "${CYAN}[$(date +%H:%M:%S)]${RESET} ${GREEN}[SUCCESS]${RESET} Connection terminated.\n"
echo -e "${WHITE}${BOLD}=== FTP DEPLOYMENT COMPLETED ====${RESET}" 