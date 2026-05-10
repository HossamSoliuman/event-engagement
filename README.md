# EventBomb Platform — Version 2 (Full Local)

Live Application URL: https://event-engagement.my-board.org/

Demo Video: [Demo Placeholder - Insert Video/Link Here]

> Full-featured event engagement platform — all modules working locally. No cloud dependencies.

---

## What's New in V2 vs V1

| Feature | V1 | V2 |
|---|---|---|
| Foto Bomb upload & moderation | Yes | Yes Enhanced |
| Lottery draw | Basic | Yes Animated draw + confetti |
| Voting results | Basic | Yes Live bar charts + podium |
| Membership list | Basic | Yes Search + export CSV |
| Admin dashboard | Stats | Yes Activity feed + charts |
| Event create/edit | Basic | Yes Candidate editor + vidiwall settings |
| Vidiwall | Single photo | Yes + Slideshow mode |
| Admin Users CRUD | Yes | Yes With roles |
| Event Moderators | No | Yes Assign moderators to specific events |
| Settings (profile/password) | Yes | Yes |
| Mobile Admin API (Sanctum) | Yes | Yes |
| Activity logging | Yes | Yes Full |
| CSV export (fotos/lottery/votes/members) | Yes | Yes |
| Duplicate event | Yes | Yes |
| Auto-refresh foto queue | Yes | Yes 10s countdown |
| Overlay text on vidiwall | Yes | Yes |
| SSO / Cognito | No | V3 |
| S3 / CloudFront | No | V3 |
| Real-time websockets | No | V3 |

---

## Setup (5 minutes)

### 1 — Install into fresh Laravel 10

```bash
composer create-project laravel/laravel eventbomb "^10.0"
cd eventbomb
```

### 2 — Install packages

```bash
composer require livewire/livewire:^3 \
    simplesoftwareio/simple-qrcode:^4 \
    intervention/image:^2 \
    laravel/sanctum:^3
```

### 3 — Environment

```bash
cp .env.example .env && php artisan key:generate
```

Edit `.env` with your DB credentials.

### 4 — Database + Seed

```bash
php artisan migrate:fresh --seed
```

### 5 — Storage link

```bash
php artisan storage:link
```

### 6 — Run

```bash
php artisan serve
```

---

## Login Credentials (seeded)

| Role | Email | Password |
|---|---|---|
| Super Admin | admin@eventbomb.com | secret123 |
| Moderator | mod@eventbomb.com | secret123 |

---

## Mobile Admin API

Use for building a native mobile app. All endpoints require Bearer token.

### Authenticate
```
POST /api/v1/login
Body: { "email": "...", "password": "..." }
Returns: { "token": "...", "user": {...} }
```

### Endpoints
```
GET  /api/v1/dashboard
GET  /api/v1/events/{id}/fotos/pending
POST /api/v1/fotos/{id}/approve
POST /api/v1/fotos/{id}/reject
POST /api/v1/fotos/{id}/push-to-screen
GET  /api/v1/events/{id}/stats
POST /api/v1/events/{id}/toggle-module
POST /api/v1/logout
```

---

## Vidiwall Setup

Open `/screen/{event-slug}` in a browser on the vidiwall computer / OBS browser source.

**Single mode** (default): Shows one photo at a time. Admin pushes from moderation queue.

**Slideshow mode**: Auto-rotates all approved on-screen photos. Set interval in event settings.

---

## Admin Navigation

| Page | URL | Purpose |
|---|---|---|
| Dashboard | /admin | Overview + activity |
| Events | /admin/events | Manage all events |
| Foto Queue | /admin/events/{id}/fotos | Approve/push photos |
| Lottery | /admin/events/{id}/lottery | Draw winner |
| Voting | /admin/events/{id}/voting | Live results |
| Members | /admin/events/{id}/membership | Member list |
| Users | /admin/users | Admin user management |
| Settings | /admin/settings | Profile/password |

---

## V3 Upgrade Path

V3 adds:
- **AWS S3** — swap `FILESYSTEM_DISK=public` → `s3`
- **CloudFront CDN** — set `CLOUDFRONT_URL` in `.env`
- **AWS Cognito SSO** — replace password auth with OAuth
- **Laravel Reverb / Pusher** — true real-time websockets (no polling)
- **ECS Fargate / Beanstalk** — containerised deployment
- **SQS + Queue workers** — background image processing

All these are drop-in upgrades — the codebase is structured to support them with minimal changes.
