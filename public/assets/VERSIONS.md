# Vendored frontend assets

The app ships its JavaScript and CSS in the repository so it has no CDN dependency and deploys with `git clone` + `composer install`. Treat these files as read-only: re-download them at the pinned version instead of editing by hand.

## Pinned versions

| Library | Version | Files | Source |
|---|---|---|---|
| Bootstrap | 4.6.2 | `bootstrap.min.css`, `bootstrap.bundle.min.js` | `https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/` |
| jQuery | 3.7.1 | `jquery.min.js` | `https://code.jquery.com/jquery-3.7.1.min.js` |
| SweetAlert2 | 11.26.25 | `sweetalert2.min.css`, `sweetalert2.all.min.js` | `https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/` |
| DataTables + Bootstrap 4 | 1.13.11 | `jquery.dataTables.min.{js,css}`, `dataTables.bootstrap4.min.{js,css}` | `https://cdn.datatables.net/1.13.11/` |

Bootstrap stays on 4.x because the layout targets Bootstrap 4 classes. jQuery stays on 3.7.1 because Bootstrap 4 is not compatible with jQuery 4. DataTables 1.13.x is the last line with documented Bootstrap 4 integration.

## Integrity

| File | SHA-256 |
|---|---|
| `bootstrap/4.6.2/bootstrap.min.css` | `f886516f3d41e9e7bd994c7f7a39a89cafae9483f90396cb0ddeafe8d1ea5e72` |
| `bootstrap/4.6.2/bootstrap.bundle.min.js` | `19126b874a32753d42c12dfa6c17892bfd93820a5a5100ba1b34da4d07599b49` |
| `jquery/3.7.1/jquery.min.js` | `fc9a93dd241f6b045cbff0481cf4e1901becd0e12fb45166a8f17f95823f0b1a` |
| `sweetalert2/11.26.25/sweetalert2.min.css` | `a1173e56fd474be04ddbefa81bd75cc465627ef2f5db261792e7d25fb7871743` |
| `sweetalert2/11.26.25/sweetalert2.all.min.js` | `4e86f0e22e4771b5b8aac24c613c661806a678b1afa284d03cf6ad03d3e21a0a` |
| `datatables/1.13.11/jquery.dataTables.min.js` | `a33146fad8c7228dc4dc90043e3e50d51cead8b2267aeac32b0a8f3be8a52b86` |
| `datatables/1.13.11/jquery.dataTables.min.css` | `b113506cbced802d49dd3f2fdec2d9de60c9cbfe53ab13b87337e34ef10200eb` |
| `datatables/1.13.11/dataTables.bootstrap4.min.js` | `e8dd8ff4b7568aa33170d6750c480e8cac29999da8a3a3e78dea13a4dfd8c8ef` |
| `datatables/1.13.11/dataTables.bootstrap4.min.css` | `68fde7cb424333dcbf8cc1e77d3f45229af5388249f144aec469db409e416f22` |

## Updating a library

1. Download every file for the new version from the source above.
2. Update the version in this table, the checksums, and the `<link>`/`<script>` paths in `app/Views/partials/`.
3. Run `vendor/bin/phpunit`; `tests/feature/ShellTest.php` fails if the layout points at a missing file.
