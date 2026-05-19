# Tok-Stock — Multi-Company Stock Management System

> Operating Systems Course Project · Universidad ·Team of 3

Tok-Stock is a web-based stock management platform that lets multiple companies manage their products, orders, stock movements, users, and categories from a single dashboard. Each company's data is fully isolated; a System Admin account oversees the whole system.

---

## Architecture

Six services run together via Docker Compose and are exposed through an NGINX reverse proxy:

| Service | Description | Address |
|---|---|---|
| **Custom Dashboard** | PHP application (this repo) | `localhost:8080` |
| **MariaDB** | Shared relational database | internal only |
| **NGINX** | Reverse proxy & static files | port 8080 / 443 |
| **OwnCloud** | Private cloud storage | `cloud.firma.lan` |
| **osTicket** | Customer support tickets | `support.firma.lan` |
| **Zen Cart** | E-commerce / shop | `shop.firma.lan` |

MariaDB is isolated behind a private `backend_network` and is never reachable from the internet.

---

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Docker Compose)
- Git

---

## Getting Started

**1. Clone the repository**
```bash
git clone <repo-url>
cd stockManagementProject
```

**2. Make sure Docker Desktop is running**

Open Docker Desktop and wait for it to fully start (whale icon in the system tray stops animating).

**3. Start all services**
```bash
docker compose up --build
```

The first run will take a few minutes while Docker downloads images and builds the custom containers. Once ready, open your browser at:

```
http://localhost:8080
```

**4. First-time setup**

On the first visit, the app will redirect you to a setup page where you create the initial System Admin account.

---

## Useful Commands

```bash
# Run in the background
docker compose up --build -d

# Follow logs
docker compose logs -f

# Stop all services
docker compose down

# Stop and delete all data (wipes the database)
docker compose down -v
```

---

## User Roles

| Role | Permissions |
|---|---|
| **System Admin** | Full access to all companies, users, orders, and settings |
| **Company Admin** | Manages their own company's users, products, and orders |
| **Staff** | Creates and views orders for their own company |

---

## Features

- Multi-company product catalog with SKU, categories, stock levels, and pricing
- Order management with automatic stock deduction on order creation
- Stock movement log (IN / OUT / ADJUSTMENT)
- User management with role-based access control
- CSV import/export for products
- Integrated cloud storage (OwnCloud), support tickets (osTicket), and shop (Zen Cart)

---

## Team

| Name | Role |
|---|---|
| Juan Diego Ron Molina | Full-stack Developer — dashboard, authentication, orders |
| M. Murad Chamaa | System Administrator — Docker, NGINX, network architecture |
| *(third member)* | *(role)* |

---

## Project Structure

```
stockManagementProject/
├── dashboard/          # PHP application
│   ├── public/         # Entry points (orders.php, products.php, …)
│   └── src/            # Services, layout templates
├── docker/
│   ├── nginx/          # NGINX virtual host configs
│   └── php/            # Custom Dockerfiles (FPM + Apache)
├── sql/
│   └── init.sql        # Database schema & seed data
├── zencart/            # Zen Cart files
├── docker-compose.yml
└── .env                # Environment variables (DB credentials)
```
