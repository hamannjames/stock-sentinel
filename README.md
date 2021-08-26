# Installation

## Requirements

-   Composer CLI (I am using version 2.0.8)
-   NPM CLI (I am using 7.5.2)
-   PHP version 8
-   MySQL

## How to Install

1. Clone repo
2. In command line at root, run “composer install”
3. In command line at root, run “npm install”
4. In command line at root, run “npm run dev”
5. Create a database in mysql for the app
6. Create .env file in root directory from .env.example file
7. Edit .env file database parameters with your database name, port, and credentials.
8. Add pro publica api key to .env file with correct name and value (attached in note with zip upload)
9. In command line, run “php artisan key:generate”
10. In command line, run "php artisan seed:transactors"
11. In command line, run "php artisan seed:ptrs --startDate='{start date of your choice}'" (the further back you start the longer it will take to seed)
12. In command line, run "php artisan slug:tickers"
13. In command line, run "php artisan slug:transactors"
14. If necessary, run "php artisan serve" in command line at root directory to start a web server
15. Have fun!
16. Feel free to login as seeded user or create your own user
