# 📥 Import / Export CSV (for Sym8)

Author: Twisted Interactive

Maintainer (Fork for Sym8): Tilo Schröder

Version: 1.2+ (compatible with Symphony CMS ≥ 2.84.0 & PHP 8.x)

This extension lets you:

- Export entries of a section as a `.csv` file
- Import entries from a `.csv` file — as new entries, updates, or ignored rows

In the revamped Sym8 version, the import process has been significantly improved:
All fields are now fully validated (`required`, `min`, `max`, `pattern`, `validator`, etc.). Invalid rows are skipped, and details are logged in the Symphony Log.

## 🛠 Import drivers

Import and export functionality is handled by import drivers, located in the `/drivers` folder.

By default, the extension looks for a driver named `ImportDriver_(fieldname)` to handle each field. If no specific driver is found, it falls back to `ImportDriver_default`.

Example:

- `select` field → `ImportDriver_select`
- `upload` field → `ImportDriver_upload`

## 📦 Included drivers

This fork includes drivers for:

- Frontend Member Password (avoids double-MD5 hashing)
- Select (handles multiple values via comma separation)
- Selectbox Link and Reference Link (exports readable values)
- Subsection Manager
- Status fields
- Upload (handles file paths and import)

## ✏️ Writing your own driver

If you're using a custom field type and the default driver doesn't fit, you can write your own driver. It's quite simple — just take a look at `ImportDriver_default.php` or the included drivers.

🔁 `import($value, $entry_id)`

This function prepares a value from the CSV for storage in an entry.It should return an array `$data`, which will be passed to `$entry->setData($field_id, $data)`.

🔁 `export($data, $entry_id)`

This function prepares the entry data for the CSV output. Return a readable string, e.g. comma-separated for multi-value fields.

🔍 `scanDatabase($value)`

Used when updating or ignoring entries based on a unique value. This function searches the database and returns the `entry_id` of a matching record, or `null` if no match was found.

## 💡 Features & Notes

The import process performs full field validation (as defined by your section schema)

- Invalid rows are not saved
- Errors are logged in the Symphony Log (`/manifest/logs/main`)
- Large CSV files are processed in batches
