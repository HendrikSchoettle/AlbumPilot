#!/usr/bin/env bash

# ---------------------------------------------------------------------------------------------------------
# File: images_checker/find_lost_images.sh – AlbumPilot Plugin for Piwigo - Helper to find missing files
# Author: Hendrik Schöttle
# SPDX-License-Identifier: MIT OR LGPL-2.1-or-later OR GPL-2.0-or-later
#
# This script checks if files listed in a CSV export exist
# on disk under your Piwigo installation.
#
# Tip: If you have filenames with commas, export only the 'path' column in your CSV.
# ---------------------------------------------------------------------------------------------------------

# === CONFIG ===
# Full path to your Piwigo root directory (no trailing slash)
ROOT_PATH="/var/www/html/piwigo"

# Path to your exported CSV file
CSV_FILE="./piwigo_images.csv"

# === Counters ===
total=0
found=0
missing=0

echo "Starting file existence check..."

# Then we only read the first column:
while IFS=',' read -r path; do

  # Remove any double quotes
  path="${path%\"}"
  path="${path#\"}"

  # Remove leading './' if present
  relpath="${path#./}"

  # Combine to full absolute path
  fullpath="$ROOT_PATH/$relpath"

  ((total++))

  if [[ -f "$fullpath" ]]; then
    # Include the following line for full report:
    # echo "[FOUND] $fullpath"
    ((found++))
  else
    echo "[MISSING] $fullpath"
    ((missing++))
  fi

done < <(tail -n +2 "$CSV_FILE")

echo
echo "File check completed."
echo "Total files checked: $total"
echo "Files found: $found"
echo "Files missing: $missing"
