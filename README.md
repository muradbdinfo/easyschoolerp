&lt;div align="center"&gt;

# 🎓 Easy School ERP

**Modern Multi-Tenant School Management System**

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vite](https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

&lt;/div&gt;

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🏢 **Multi-Tenant Architecture** | Separate subdomains for each school branch |
| 👨‍💼 **Admin Dashboard** | Centralized management for all tenants |
| 🎨 **Modern UI/UX** | Built with Vue 3 Composition API + Tailwind CSS |
| ⚡ **Lightning Fast** | Vite HMR with instant page loads |
| 🔄 **Seamless Routing** | Inertia.js for SPA-like experience without API complexity |

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Composer
- MySQL/PostgreSQL

### Installation

```bash
# Clone repository
git clone https://github.com/muradbdinfo/easyschoolerp.git
cd easyschoolerp

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Start development servers
npm run dev
php artisan serve --host=0.0.0.0