# RIMS – Deployment & Architecture (1‑Page)

## 1. Stack Overview

* **Backend**: Laravel (UUID primary key)
* **Frontend**: Inertia.js + Vue 3
* **Realtime**: Laravel Reverb (Pusher-compatible)
* **Queue**: Database queue + Supervisor
* **Cache / Session**: Redis
* **Web Server**: Nginx + PHP 8.4 FPM
* **SSL**: Cloudflare Origin Certificate

---

## 2. User & Permission Model

* User ID: UUID (`HasUuids`, non-incrementing)
* Permission: JSON array di kolom `permissions`

Contoh:

```json
["receivings"]
```

Helper:

```php
$user->hasPermission('receivings');
```

---

## 3. Realtime (Reverb)

* Reverb listen di `127.0.0.1:8080`
* Nginx proxy WebSocket: `/app/*`
* Frontend **tetap pakai Echo + Pusher protocol**

ENV penting:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_KEY=local
REVERB_HOST=rims.r-dev.asia
REVERB_PORT=443
REVERB_SCHEME=https

VITE_PUSHER_APP_KEY=${REVERB_APP_KEY}
VITE_PUSHER_HOST=${REVERB_HOST}
VITE_PUSHER_PORT=${REVERB_PORT}
VITE_PUSHER_SCHEME=${REVERB_SCHEME}
```

---

## 4. Nginx

* HTTP/2 aktif (`http2 on;`)
* Laravel routing via `try_files`
* PHP socket: `php8.4-fpm.sock`
* Static asset cache 1 tahun

WebSocket block:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_pass http://127.0.0.1:8080;
}
```

---

## 5. Supervisor

### Queue Worker

* Command: `php artisan queue:work`
* Driver: database
* `numprocs = 8`

### Reverb

* Single process
* Autostart + autorestart

---

## 6. Redis

* Digunakan untuk:

  * session
  * cache

Flush manual:

```bash
redis-cli FLUSHDB
```

---

## 7. Common Issues & Fix

| Issue                        | Fix                      |
| ---------------------------- | ------------------------ |
| `502 Bad Gateway`            | Reverb mati / port salah |
| `inertia:error`              | WebSocket gagal          |
| `419 Page Expired`           | Redis session lama       |
| `You must pass your app key` | Mapping `VITE_PUSHER_*`  |

---

## 8. Status

* Auth: ✅
* Realtime: ✅
* WebSocket HTTPS: ✅
* Permission system: ✅
* Production-ready: ✅
