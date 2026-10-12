# CI/CD secrets and variables

## Required for GitHub/GitLab CI

### Backend
- APP_ENV
- DATABASE_URL
- JWT_PASSPHRASE
- POSTGRES_DB
- POSTGRES_USER
- POSTGRES_PASSWORD

### Frontend
- VITE_API_BASE_URL
- VITE_APP_ENV

### Mobile
- EXPO_TOKEN
- EAS_PROJECT_ID
- APP_VARIANT
- API_BASE_URL

## Remote
- origin: https://github.com/bativo/bativo-backend.git

## Deploy target examples
- backend: Render / Docker host / VPS
- frontend: Vercel / Netlify / Cloudflare Pages
- mobile: EAS Build / App Store / Play Store
