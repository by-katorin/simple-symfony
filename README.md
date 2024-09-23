# Product CRUD

This web application is designed to manage products using a basic CRUD (Create, Read, Update, Delete) interface. It's built with **Symfony 7** framework, leverages **Docker** for containerization, and uses **Twig** for templating with **Tailwind CSS** for styling.

## Key Features

- **Symfony 7**: A modern PHP framework providing a robust foundation for web applications.
- **Docker**: A platform for building, shipping, and running applications in containers.
- **Twig**: A flexible templating engine for Symfony.
- **Tailwind CSS**: A utility-first CSS framework for rapid UI development.

## Prerequisites

- **Docker Desktop**: Ensure you have [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed in your system.

## Getting Started

### Clone the Repository
```
git clone https://github.com/by-katorin/symfony-dockerized.git
```

### Make Initial Setup
```
cp .env.example .env
```
Make sure to input necessary values to the following environment variables
```
PROJECT_NAME=simpleuser

DB_ROOT_PASSWORD=root
DB_DATABASE=simpleuser
DB_USERNAME=simpleuser
DB_PASSWORD=password

# Can be populated later
DB_CONNECTION=mysql 
DB_HOST=mysql
DB_PORT=3306
```

### Build Docker Containers
```
cd simple-user-management
docker compose build
docker compose up -d
```

### Install Dependencies

```
php artisan key:generate
php artisan storage:link
```
> Note: Though these commands are already ran in `Dockerfile` (php and node), you can re-run them if new libraries/packages are installed.
```
# Backend
composer install

# Frontend
npm install
```

### Run Database Migrations and Seeders
```
docker compose exec php sh
php artisan migrate
php artisan db:seed
```

## Usage

### Start the Development server

Dev server is already ran inside `node` container. No need to explicitly run `npm run dev`.

> Note: Please be advised that running this project on a Windows-based system may result in slower performance due to the limitations of the Windows Subsystem for Linux (WSL). If you are using Windows, I recommend to run `npm run dev` outside of the Docker container with Node.js and npm installed.

### Access the Application

Visit http://localhost in your web browser.

## Contributing

Contributions are welcome! Please follow these guidelines:

- Fork the Repository: Fork the project on GitHub.
- Create a Branch: Create a new branch for your feature or bug fix.
- Make Changes: Implement your changes and write tests.
- Submit a Pull Request: Submit a pull request to the main branch.

## Additional Notes

- For more information on Laravel Breeze, Inertia.js, and Vue.js, please refer to their official documentation.
- Customize the project to fit your specific needs by adding more features or modifying the existing ones.