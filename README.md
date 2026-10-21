# Directory Tree Scanner 🗂️

A powerful, highly practical, and single-file PHP script that scans your project directory, calculates statistics, and visualizes the file structure.

## Advanced Features
- **Plug & Play:** Just one file. Drop it into any directory and run it.
- **File Extension Filter:** Target specific file types (e.g., `php, js, css`).
- **Max Depth Limit:** Restrict how deep the scanner goes to optimize performance.
- **Hidden Files Toggle:** Choose whether to reveal or hide dotfiles (like `.env`).
- **Control Panel UI:** Tweak settings directly from the sleek dark-mode web interface.
- **Developer API:** Supports full JSON output, inheriting all advanced filters.

## Usage

Upload `directoryscanner.php` to the root folder of the project you want to analyze.

### 1. Web Interface
Open the file in your browser. You can use the top control bar to apply filters instantly:
`http://your-domain.com/directoryscanner.php`

### 2. JSON API
You can fetch the filtered tree programmatically by adding the `api=true` parameter alongside any other options.

**Request (Example: Only PHP files, max depth 2, show hidden):**
`GET /directoryscanner.php?api=true&ext=php&depth=2&hidden=1`

**Response:**
```json
{
  "path": "/var/www/html/my-project",
  "options": {
    "depth": 2,
    "extensions": ["php"],
    "hidden": true
  },
  "statistics": {
    "files": 45,
    "dirs": 4,
    "size": 1024000
  },
  "tree": []
}
