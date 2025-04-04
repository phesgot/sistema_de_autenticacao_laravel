# 🚀 Sistema de autenticação 

![Laravel](https://img.shields.io/badge/Laravel-12.x-red?style=flat&logo=laravel)  
Este projeto é uma aplicação web desenvolvida em Laravel que implementa um sistema completo de autenticação de usuários. Ele inclui:

- ✅ Registro de novos usuários
- ✅ Autenticação (login)
- ✅ Logout seguro
- ✅ Alteração de senha
- ✅ Recuperação e redefinição de senha
- ✅ Validações robustas para garantir a segurança dos dados

## 🎥 Demonstração


## 📂 Tecnologias Utilizadas
- ✅ Laravel 12.3.0
- ✅ Blade 
- ✅ MySQL  
- ✅ Bootstrap  

## 📦 Instalação e Execução  
```bash
# Clone o repositório
git clone https://github.com/phesgot/notas.git

# Entre na pasta do projeto

# Abra o projeto na IDE e abra o terminal

# Instale as dependências do Laravel
composer update

# Configure o banco de dados no .env e rode as migrations
php artisan migrate

# Alimente a base de dados rode o sedder
php artisan db:seed --class=UsersTableSeeder

php artisan db:seed --class=DatabaseSeeder

# Inicie o servidor local
php artisan serve
