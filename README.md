# Kahuna Project

## Overview

Kahuna Inc. is a manufacturer of smart home appliances aiming to implement a customer portal for appliance registration, product management, and customer support ticketing.

## Features

- **User Authentication:**
  - Register and login functionality for customers.
  - Admin panel for agents.
- **Products:**
  - Customers can register a product and view its details.
  - Admins can manage products.
- **Ticket System:**
  - Customers can submit tickets regarding product issues.
  - Agents can view and reply to tickets.
  - Agents can change ticket status (open/closed).
- **User Management:**
  - Admins can create, edit, and manage users (customers and agents).
  - Admins can assign roles to users.

## Technologies Used

- **Backend:**
  - PHP with AltoRouter for routing
  - MariaDB for database management
- **Frontend:**
  - HTML
  - CSS
  - JavaScript
- **Testing:**
  - Postman for API testing
- **Deployment:**
  - Docker for containerization
  - Docker Compose for managing multi-container Docker applications

## Database Schema

The database schema includes tables for users, products, tickets, and more:

- **AccessToken Table:** Stores user tokens for authentication.
- **Users Table:** Stores user info and roles.
- **Products Table:** Contains registered appliances and their details.
- **Tickets Table:** Tracks customer support tickets.
- **Ticket Replies Table:** Stores replies related to tickets.

## License

This project is licensed under the MIT License - see the LICENSE.md file for details.

# PHP & MariaDB Development Environment

## Purpose

This app helps set up a working environment for your final project, including checking PHP functionality and MariaDB connectivity.

## Installation and Setup

1. Clone this repository.
2. Ensure Docker Desktop is running.
3. Open a terminal and navigate to the cloned repository folder.
4. Create a .env file (if not there already) in the `api` folder and in it write the following:
`DB_USER = "root"`
`DB_PASS = "root"`
4. Run the `run.cmd` script:
   - On Windows: `.\run.cmd`
   - On macOS or Linux: `./run.cmd`
5. Open [http://localhost:8001](http://localhost:8001) in your browser.

Notes:
If `nginx: [emerg] host not found in upstream "php" in /etc/nginx/conf.d/api.conf:14` error in the `api-1` container is encountered when running the script navigate to `docker/scripts/run.sh` and set End of Line Sequence to `LF`.

If `/bin/sh: 1: /opt/run.sh: not found` error in the `php-1` container is encountered try running `docker system prune -a -f --volumes` in cmd and try again.

If any other errors encountered please send an e-mail to `lee.h.degiorgio@gmail.com` for further assistance.
    
`Creating an agent account:` 
Postman collection and enviornment jsons in `support` folder need to be used to create an agent account. When imported to postman navigate to kahuna/Admin/Admin Authentication/Register.

## Details

PHP and MariaDB have been configured:
- **Host**: mariadb
- **Database Name**: kahuna
- **User**: root
- **Password**: root
- **Port**: 3306

Services started include:
- API Server: [http://localhost:8000](http://localhost:8000)
- Client: [http://localhost:8001](http://localhost:8001)