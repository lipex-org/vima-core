# Vima Core v1

Vima Core is a high-performance, contract-first access control library (RBAC & ABAC) designed for modern PHP applications.

## 🏗️ Architecture
Vima Core v1 utilizes a **Domain-Driven Design (DDD)** approach, organizing components into vertical slices (Audit, Cache, Config, Deployment, Events, Permission, Policy, Role, Support, User) to reduce cognitive load and eliminate feature scatter.

## 🚀 Key Features
- **Native Fluent API**: Highly readable, symmetrical DSL (e.g., `Vima::user($user)->grant()->role('admin')`).
- **Domain-Driven Design**: Logic is segmented by business domain, not technical layer.
- **Configurable Storage**: Dynamic table prefixing support to work seamlessly with any database schema.
- **Contract-First**: Everything is driven by interfaces, allowing for easy swapping of repositories.

## 📚 Documentation
Please see the [docs/](docs/) directory for detailed architecture, integration guides, and testing strategies.

## 🛠️ Getting Started
```bash
composer install
```

## 📜 License
MIT
