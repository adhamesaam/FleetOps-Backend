# FleetOps Backend - Quick Start Guide
### 1. Build and Start
.\docker.ps1 install

# Rebuild containers with fresh build
.\docker.ps1 rebuild

# Seed database with test data
.\docker.ps1 seed

# Fresh database with migrations and seeds
.\docker.ps1 fresh

# Check API health
.\docker.ps1 health





## 🚀 Get Started in 3 Steps

### 1. Build and Start
**Windows:**
```powershell
.\docker.ps1 install
```
**Linux:**
```bash
./docker.sh install
```
Or manually:

```powershell
docker-compose up -d --build
docker-compose exec app php artisan key:generate --force
docker-compose exec app php artisan migrate --force

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
```
### for linux if you already built the containers 
./docker.sh up
./docker.sh down
### 2. Verify
```powershell
curl http://localhost:8000/api/health
```

### 3. Access
- **API Endpoint**: http://localhost:8000
- **SQL Server**: localhost:1433
  - Username: `sa`
  - Password: `Fleetops12345678!`
  - Database: `fleetops`

## 📋 Common Commands

```powershell
# View logs
.\docker.ps1 logs

Linux:
./docker.sh logs

# Run migrations
.\docker.ps1 migrate

Linux:
./docker.sh migrate

# Access container shell
.\docker.ps1 shell

Linux:
./docker.sh shell

# Stop containers
.\docker.ps1 down

Linux:
./docker.sh down

# Restart containers
.\docker.ps1 restart

Linux:
./docker.sh restart

# Clear caches
.\docker.ps1 clear

Linux:
./docker.sh clear

# Run tests
.\docker.ps1 test

Linux:
./docker.sh test

# Show all commands
.\docker.ps1 help

Linux:
./docker.sh help
```

## 🔧 Troubleshooting

### Container won't start
```powershell
.\docker.ps1 clean
.\docker.ps1 install
```

### Database connection error
```powershell
# Check SQL Server status
docker-compose logs sqlserver

Linux:
docker compose logs sqlserver

# Verify database exists
.\docker.ps1 db
SELECT name FROM sys.databases;
GO

Linux:
docker compose exec sqlserver sqlcmd -S localhost -U sa -P "Fleetops12345678!" -C -Q "SELECT name FROM sys.databases"
```

### Permission errors
```powershell
# On Windows (usually not needed)
icacls storage /grant Everyone:F /t
icacls bootstrap\cache /grant Everyone:F /t

Linux:
sudo chown -R 1000:1000 storage bootstrap/cache

```

## 📊 Performance Features

✅ PHP 8.3 with OPcache enabled
✅ Nginx FastCGI caching
✅ Gzip compression
✅ Optimized Composer autoloader
✅ Alpine Linux (minimal footprint)
✅ Multi-stage Docker builds

## 🎯 Next Steps

1. Configure your `.env` file
2. Set up your API routes
3. Run seeders: `.\docker.ps1 seed`
4. Check API documentation: `API_DOCUMENTATION.md`

## 💡 Tips

- Use `.\docker.ps1 help` to see all available commands
- Logs are in `storage/logs/laravel.log`
- SQL Server data persists in Docker volume `sqlserver-data`
- For production, change passwords in `docker-compose.yml`
- All commands use PowerShell script (no Makefile needed on Windows)
