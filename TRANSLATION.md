# Translation Guide

This guide explains how to translate the ICS Calendar Enhanced plugin into other languages.

## Overview

The plugin uses WordPress's internationalization (i18n) system with the text domain `ics-calendar-enhanced`. All user-facing strings are already wrapped in translation functions, including the word "Legende" (Legend) that appears in the color legend display.

## Quick Start

1. **Generate the .pot template file** (if not already present)
2. **Create a .po file** for your language
3. **Translate the strings**
4. **Compile to .mo file**
5. **Place in the languages directory**

## Step-by-Step Instructions

### 1. Generate the .pot Template File

The `.pot` (Portable Object Template) file contains all translatable strings from the plugin source code.

#### Option A: Using the provided script (recommended)

```bash
./bin/generate-pot.sh
```

This script requires WP-CLI to be installed. If you don't have WP-CLI, see Option B.

#### Option B: Using WP-CLI directly

From the plugin root directory:

```bash
wp i18n make-pot . languages/ics-calendar-enhanced.pot \
    --domain=ics-calendar-enhanced \
    --exclude=node_modules,vendor,aidocs,bin
```

#### Option C: Using Poedit

1. Open Poedit
2. File → New from POT/PO file
3. Select `languages/ics-calendar-enhanced.pot` (or create it first using WP-CLI)
4. Poedit can also extract strings directly from source code

### 2. Create a .po File for Your Language

Copy the `.pot` file and rename it with your locale code:

```bash
cp languages/ics-calendar-enhanced.pot languages/ics-calendar-enhanced-de_DE.po
```

Replace `de_DE` with your locale code. Common examples:
- `de_DE` - German (Germany)
- `fr_FR` - French (France)
- `es_ES` - Spanish (Spain)
- `it_IT` - Italian (Italy)
- `nl_NL` - Dutch (Netherlands)
- `pt_BR` - Portuguese (Brazil)

### 3. Translate the Strings

Open the `.po` file in a translation editor:

#### Using Poedit (recommended for beginners)

1. Download and install [Poedit](https://poedit.net/)
2. Open `languages/ics-calendar-enhanced-{locale}.po`
3. Translate each string in the interface
4. Save the file (Poedit will automatically compile to .mo)

#### Using a text editor

1. Open the `.po` file in any text editor
2. Find lines starting with `msgstr ""`
3. Add your translation between the quotes:
   ```
   msgid "Legende"
   msgstr "Legend"
   ```
4. Save the file

### 4. Compile to .mo File

WordPress uses compiled `.mo` (Machine Object) files for translations.

#### Using Poedit

Poedit automatically compiles `.mo` files when you save the `.po` file.

#### Using command line (msgfmt)

```bash
msgfmt -o languages/ics-calendar-enhanced-de_DE.mo languages/ics-calendar-enhanced-de_DE.po
```

This requires `gettext` tools to be installed:
- **macOS**: `brew install gettext`
- **Linux**: Usually pre-installed, or `sudo apt-get install gettext`
- **Windows**: Install from [GNU gettext for Windows](https://mlocati.github.io/articles/gettext-iconv-windows.html)

### 5. Verify Translation

1. Set your WordPress site's language to match your locale (Settings → General → Site Language)
2. Clear any caching plugins
3. View the frontend where the calendar is displayed
4. The word "Legende" and other strings should now appear in your translated language

## File Structure

After translation, your `languages/` directory should contain:

```
languages/
├── .gitkeep
├── ics-calendar-enhanced.pot          # Template (tracked in git)
├── ics-calendar-enhanced-de_DE.po     # German translations (optional to track)
├── ics-calendar-enhanced-de_DE.mo     # Compiled German (ignored by git)
├── ics-calendar-enhanced-fr_FR.po     # French translations
└── ics-calendar-enhanced-fr_FR.mo     # Compiled French
```

## Updating Translations

When new translatable strings are added to the plugin:

1. **Regenerate the .pot file**:
   ```bash
   ./bin/generate-pot.sh
   ```

2. **Update your .po file**:
   - Using Poedit: Catalog → Update from POT file
   - Or merge manually using `msgmerge`:
     ```bash
     msgmerge -U languages/ics-calendar-enhanced-de_DE.po languages/ics-calendar-enhanced.pot
     ```

3. **Translate new strings** and recompile to `.mo`

## Contributing Translations

If you've created a translation and would like to contribute it:

1. Ensure your `.po` file is complete and accurate
2. Test the translation on a WordPress site
3. Submit the `.po` file (and optionally the compiled `.mo`) to the plugin maintainer

## Technical Details

### Text Domain

The plugin uses the text domain: `ics-calendar-enhanced`

This is defined in:
- Plugin header: `ics-calendar-enhanced.php`
- All translation function calls throughout the codebase

### Translation Functions Used

The plugin uses these WordPress translation functions:
- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `esc_html__()` - Returns translated and escaped string
- `esc_html_e()` - Echoes translated and escaped string
- `esc_attr__()` - Returns translated and attribute-escaped string
- `esc_attr_e()` - Echoes translated and attribute-escaped string

### Key Translatable Strings

Some important strings to translate:
- "Legende" - The legend title above the color legend
- "ICS Calendar Enhanced" - Plugin name
- "Category Image Mappings" - Settings section title
- "Select Image" - Button text
- "Save Settings" - Button text
- And 30+ other user-facing strings

## Troubleshooting

### Translations not appearing?

1. **Check file naming**: The `.mo` file must be named exactly:
   `ics-calendar-enhanced-{locale}.mo` (e.g., `ics-calendar-enhanced-de_DE.mo`)

2. **Check WordPress locale**: Go to Settings → General and verify the Site Language matches your locale

3. **Clear cache**: Clear any caching plugins or server-side cache

4. **Check file location**: `.mo` files must be in the `/languages/` directory

5. **Verify text domain**: Ensure all strings use `ics-calendar-enhanced` as the text domain

### WP-CLI not found?

Install WP-CLI:
- **macOS**: `brew install wp-cli`
- **Linux**: Follow [WP-CLI installation guide](https://wp-cli.org/#installing)
- **Windows**: Download from [wp-cli.org](https://wp-cli.org/#installing)

Or use Poedit to extract strings directly from source code.

## Resources

- [WordPress i18n Documentation](https://developer.wordpress.org/plugins/internationalization/)
- [Poedit Translation Editor](https://poedit.net/)
- [WP-CLI i18n Commands](https://developer.wordpress.org/cli/commands/i18n/)
- [GNU gettext Manual](https://www.gnu.org/software/gettext/manual/)
