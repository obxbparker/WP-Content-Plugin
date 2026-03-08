#!/bin/bash
#
# Bump the plugin version, build, and optionally create a zip for deployment.
#
# Usage:
#   ./scripts/version-bump.sh 1.0.1
#   ./scripts/version-bump.sh 1.0.1 --zip
#

set -e

NEW_VERSION="$1"
CREATE_ZIP="$2"
PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_FILE="$PLUGIN_DIR/contenthub-wp.php"

if [ -z "$NEW_VERSION" ]; then
    # Show current version if no argument given.
    CURRENT=$(grep -m1 "define( 'CONTENTHUB_WP_VERSION'" "$PLUGIN_FILE" | sed "s/.*'\\(.*\\)'.*/\\1/")
    echo "Current version: $CURRENT"
    echo ""
    echo "Usage: $0 <new-version> [--zip]"
    echo "  e.g. $0 1.0.1"
    echo "  e.g. $0 1.1.0 --zip"
    exit 0
fi

echo "Bumping version to $NEW_VERSION..."

# 1. Update plugin header version.
sed -i '' "s/^ \* Version:.*/ * Version:     $NEW_VERSION/" "$PLUGIN_FILE"

# 2. Update PHP constant.
sed -i '' "s/define( 'CONTENTHUB_WP_VERSION', '.*'/define( 'CONTENTHUB_WP_VERSION', '$NEW_VERSION'/" "$PLUGIN_FILE"

echo "Updated contenthub-wp.php"

# 3. Build the admin UI.
echo "Building admin UI..."
cd "$PLUGIN_DIR/admin-ui"
npm run build --silent
echo "Admin UI build complete."

# 4. Build the portal UI.
echo "Building portal UI..."
cd "$PLUGIN_DIR/portal-ui"
npm run build --silent
echo "Portal UI build complete."

# 5. Optionally create a deployable zip.
if [ "$CREATE_ZIP" = "--zip" ]; then
    echo "Creating zip..."
    cd "$PLUGIN_DIR/.."
    ZIP_NAME="contenthub-wp-${NEW_VERSION}.zip"
    zip -r "$ZIP_NAME" contenthub-wp/ \
        -x "contenthub-wp/admin-ui/node_modules/*" \
        -x "contenthub-wp/admin-ui/src/*" \
        -x "contenthub-wp/portal-ui/node_modules/*" \
        -x "contenthub-wp/portal-ui/src/*" \
        -x "contenthub-wp/.git/*" \
        -x "contenthub-wp/scripts/*" \
        -x "contenthub-wp/.claude/*" \
        -x "contenthub-wp/CLAUDE.md" \
        > /dev/null
    echo "Created: $ZIP_NAME"
fi

echo ""
echo "Version bumped to $NEW_VERSION"
