#!/bin/bash
#
# Generate .pot translation template file
#
# This script generates a .pot file containing all translatable strings
# from the plugin source code using WP-CLI.
#
# Usage:
#   ./bin/generate-pot.sh
#   OR
#   bash bin/generate-pot.sh
#

# Get the plugin directory (parent of bin/)
PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo "Error: WP-CLI is not installed or not in PATH."
    echo ""
    echo "Please install WP-CLI first:"
    echo "  https://wp-cli.org/#installing"
    echo ""
    echo "Alternatively, you can generate the .pot file manually using:"
    echo "  wp i18n make-pot . languages/ics-calendar-enhanced.pot --domain=ics-calendar-enhanced --exclude=node_modules,vendor,aidocs"
    exit 1
fi

# Change to plugin directory
cd "$PLUGIN_DIR" || exit 1

# Generate .pot file
echo "Generating .pot file..."
wp i18n make-pot . languages/ics-calendar-enhanced.pot \
    --domain=ics-calendar-enhanced \
    --exclude=node_modules,vendor,aidocs,bin

if [ $? -eq 0 ]; then
    echo "✓ Successfully generated: languages/ics-calendar-enhanced.pot"
    echo ""
    echo "Next steps:"
    echo "  1. Copy the .pot file to create a .po file for your language:"
    echo "     cp languages/ics-calendar-enhanced.pot languages/ics-calendar-enhanced-de_DE.po"
    echo "  2. Translate the strings in the .po file"
    echo "  3. Compile to .mo file using: msgfmt -o languages/ics-calendar-enhanced-de_DE.mo languages/ics-calendar-enhanced-de_DE.po"
    echo "  4. Or use Poedit to translate and compile automatically"
else
    echo "✗ Error generating .pot file"
    exit 1
fi
