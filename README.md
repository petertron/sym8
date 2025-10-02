![Stand with Ukraine](https://sym8.io/app/public/ua-badge.png)

# ![Sym8](https://sym8.io/app/public/logo-sym8-2025.svg)


## 🌀 What is Sym8?

Sym8 is a modern fork and continuation of the open-source CMS Symphony, originally created by Alistair Kearney and the Symphony community. It preserves the power of XSLT and the elegance of modular extensibility – brought up to date for modern PHP and developer workflows.

## 🔢 Versioning

Sym8 intentionally starts at version `2.84.0`, following a clear logic:

- `2`: reflects the underlying Symphony codebase,
- `84`: indicates the maximum supported PHP version (8.4),
- `0`: denotes the patch level.

This versioning scheme is designed to restore trust among developers by making it immediately clear which PHP version is fully supported — without needing to read through changelogs or compatibility notes.

## 🔥 What's new?

### HTML5 inside

All standard HTML5 input types – including `color`, `email`, `month`, `number`, `range`, `tel`, `time`, `url` and `week` – are now natively supported in the Symphony Core. And even the good old radio button.

This enables native browser validation and improves accessibility and usability out of the box.

### Features

Sym8 ships with essential improvements and must-have extensions out of the box:

- 🌐 Database connection changed from __utf8__ to __utf8mb4__ (multibyte)
- ✅ __Dashboard__-Extension (required) — shows system environment (PHP version, available pages, data sources, sections) and includes a feed for upcoming Sym8 versions
- 🗜 __HTML5__-Extension: the trailing slash for self-closing elements is removed according to the recommendation of the W3C validator. Addional the HTML source code can be minified.
- ✨ Minimalist frontend with [Pico CSS](https://picocss.com/) included for a clean start

## 🖥️ Server requirements

- A webserver (known to be used with Apache, Litespeed, Nginx and Hiawatha)
- Apache’s `mod_rewrite` module or equivalent
- PHP 8.0 - 8.4
- PHP’s LibXML module, with the XSLT extension enabled (`--with-xsl`)
- PHP’s built in json functions, which are enabled by default in PHP 5.2 and above; if they are missing, ensure PHP wasn’t compiled with `--disable-json`
- `gd` or `imagick` library

Sym8 also requires a MySQL-compatible database.

✅ Recommended:

- MariaDB 10.3 – 11.x
- MySQL 5.5 – 8.0

⚠️ Note: MySQL 8.1+ and MySQL 11 have introduced changes that may affect installation or operation. If you're using these versions, please make sure to disable `ONLY_FULL_GROUP_BY` and check compatibility. Full support is planned for a future release.

## 🧑‍💻 Installing

1. Upload the entire `sym8` folder to your webspace and let your domain (e.g. example.com) point to that directory.

   For example:
   If your server path is `/home/username/web/example.com`, upload to `/home/username/web/example.com/sym8` and point the domain to that directory.
2. Open the `install` subdirectory in your web browser (e.g., https://example.com/install/) and enter the following details:
    - Website name and email address for outgoing messages
    - Time zone and date format
    - Details for the database (user, password, server, port)
    - Author details
3. Please note that Symphony creates three pages during installation:
    - Home (simple `index` page with Pico CSS)
    - Error page `403` (for directories without an index file)
    - Error page `404`
4. After successful installation, you can log in to the backend at `/symphony` using the credentials you just set.


## Update

### Update Sym8 2.84.0+

✅ __Safe to use the built-in update function.__

Updates from Sym8 2.84.0 and later (e.g. future releases like 2.85.0) can be performed via the built-in update mechanism.

All database changes and bundled extensions are fully handled by the updater.

### Update from SymphonyCMS <= 2.7.10

🚫 __Do not use the built-in update function__! ⚡

⛔ Older Symphony installations must not be updated via the built-in update mechanism. Doing so will break your installation irreversibly. There are two main reasons for this:

1. __Database changes__: Sym8 uses `utf8mb4` and introduces new columns in several tables. The old updater does not apply these changes, which will lead to severe errors.
2. __Extensions__: Legacy extensions that are not part of the Sym8 package can cause errors and malfunctions if carried over without review.

#### How to upgrade manually

If you want to migrate an existing Symphony installation to Sym8, please proceed as follows:

1. __Back up__ your database and `/workspace` directory.
2. __Install__ Sym8 fresh in a new location (new database).
3. __Migrate content__:
    - Recreate your sections, data sources, and events in the new installation.
    - Use the opportunity to modernize your fields:
      Replace text fields with regex validation (for email or number formats) by the new native HTML5 fields (`email`, `number`) to improve UX and mobile input handling.
    - Export your entries from the old instance via the extension Import/Export CSV.
    - Import the entries into the new instance: The extension Import/Export CSV validates required fields and constraints (`required`, `min`, `max`, etc.) during import. This helps detect and skip broken records from your old instance.
4. __Migrate workspace assets__: Move templates, stylesheets, scripts, and other assets from your old `/workspace` into the new one.
5. __Review extensions__: Only use extensions included in the Sym8 package. If you rely on custom or third-party extensions, check compatibility carefully before enabling them.
6. Test thoroughly before going live.

