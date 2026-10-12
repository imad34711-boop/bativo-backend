# Docker backend

Ce dossier permet de lancer le backend Symfony avec ses services d'infrastructure :

- PostgreSQL 16
- PHP-FPM
- Nginx

## Démarrage

```bash
cd bativo-backend/docker
cp .env.example .env
docker compose up -d --build
```

## Services

- API Symfony : http://localhost:8000
- PostgreSQL : localhost:5432

## Commandes utiles

```bash
docker compose logs -f php
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:fixtures:load
```
