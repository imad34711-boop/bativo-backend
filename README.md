# Bativo Backend

Standalone repository for the Symfony API.

## Purpose
- Expose the API under `/api`
- Manage the PostgreSQL 16 database
- Handle authentication, business logic, and data access

## Local development

```bash
cp docker/.env.example docker/.env
cd docker && docker compose up -d
```

## Deployment on Render

This repository is prepared for a Docker-based Render web service.

### Required Render configuration

1. Create a new Web Service on Render.
2. Connect this repository.
3. Use the Dockerfile at the repository root.
4. Set the health check path to `/api/health`.
5. Add the following environment variables:

```bash
APP_ENV=production
APP_SECRET=replace-me
DATABASE_URL=postgresql://user:password@host:5432/db?serverVersion=16&charset=utf8
CORS_ALLOW_ORIGIN=https://your-frontend-domain.example.com
JWT_SECRET_KEY=/var/www/html/config/jwt/private.pem
JWT_PUBLIC_KEY=/var/www/html/config/jwt/public.pem
JWT_PASSPHRASE=
```

> For this Symfony project, `JWT_SECRET_KEY` and `JWT_PUBLIC_KEY` must be file paths inside the container, not random generated strings. The key pair in the repository is now unencrypted, so `JWT_PASSPHRASE` must stay empty.

### Render database

Create a PostgreSQL database in Render and copy its connection string to `DATABASE_URL`.

### Deploy

Render will run the Docker image from [Dockerfile](Dockerfile) and execute the application through Nginx + PHP-FPM.

The app is expected to respond on `/api/health` and to run database migrations via the `render.yaml` deploy hook.

