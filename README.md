# Dylan HRM

Lean Laravel HRM dashboard running with Docker. The default Laravel welcome UI, npm, Vite, Tailwind and unused starter scripts have been removed.

## Stack

- Laravel 13
- PHP 8.3 FPM
- Nginx Alpine
- MySQL 8.4
- Blade with plain CSS
- Cursor MCP config for Google Stitch in `.cursor/mcp.json`

## Run With Docker

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
```

Open the app at `http://localhost:8090`.

## Useful Commands

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint
docker compose down
```

## Project Notes

- App entry route: `/`
- Main view: `resources/views/hrm/dashboard.blade.php`
- Selected HRM screens:
  - `/nhan-su`
  - `/cham-cong`
  - `/luong-thuong`
  - `/tuyen-dung`
  - `/bao-cao`
- Database config uses the `mysql` service from `docker-compose.yml`
