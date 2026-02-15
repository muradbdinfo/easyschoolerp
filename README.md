EASY SCHOOL ERP QUICK START

Tech: Laravel + Vue 3 + Inertia.js + Vite + Tailwind CSS
Subdomains: admin.easyschool.local, junior.easyschool.local, middle.easyschool.local, senior.easyschool.local, highcare.easyschool.local

Clone & Setup

git clone https://github.com/muradbdinfo/easyschoolerp.git

cd easyschoolerp
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate

Hosts File (Local)

127.0.0.1 easyschool.local
127.0.0.1 www.easyschool.local

127.0.0.1 admin.easyschool.local
127.0.0.1 junior.easyschool.local
127.0.0.1 middle.easyschool.local
127.0.0.1 senior.easyschool.local
127.0.0.1 highcare.easyschool.local

Routes Overview

Marketing: / → Marketing/Home

Admin: /admin/dashboard → Admin/Dashboard

Tenant: /dashboard → Tenant/Dashboard

Vite Dev Server

npm run dev

Laravel server:

php artisan serve --host=0.0.0.0  


HMR works with all subdomains. Access e.g.:

http://easyschool.local:5173  
http://admin.easyschool.local:5173  


Production Build

npm run build

Assets are served from public/build.

Notes

Vue 3 uses Composition API

Inertia handles frontend routing

Tailwind CSS for styling

Subdomain wildcard supported in dev