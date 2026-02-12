# PuzzlingCRM Dashboard (React SPA + shadcn/ui)

React SPA for the PuzzlingCRM WordPress plugin dashboard. Built with Vite, React 19, TypeScript, Tailwind CSS, and shadcn-style components (Radix UI + Tailwind).

## Setup

```bash
cd puzzlingcrm-dashboard
npm install
npm run build
```

Build output is written to `../assets/dashboard-build/` (dashboard-main.js, dashboard-main.css). The WordPress plugin serves the SPA from `/dashboard` and loads these assets.

## Development

```bash
npm run dev
```

For local dev you may need to proxy API requests to your WordPress site or run the plugin and open `/dashboard` (then the SPA will be served by WordPress with the built files; rebuild after changes).

## Structure

- `src/api/` – API client for WordPress `admin-ajax.php` and domain helpers (e.g. leads).
- `src/components/ui/` – shadcn-style UI components (Button, Card, Dialog, Table, etc.).
- `src/components/layout/` – Layout, Sidebar, Header.
- `src/contexts/` – Theme (light/dark) context.
- `src/pages/` – Dashboard and feature pages (e.g. Leads).
- `src/types/global.d.ts` – `window.puzzlingcrm` bootstrap types.

## RTL and i18n

Bootstrap data from PHP includes `isRtl` and `locale`. The app sets `dir` and `lang` on the document. Copy/translations can be taken from `window.puzzlingcrm.i18n`.
