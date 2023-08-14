[![SymfonyInsight](https://insight.symfony.com/projects/23cc1bb0-55ef-49fe-b5a7-05c54abd2d94/mini.svg)](https://insight.symfony.com/projects/23cc1bb0-55ef-49fe-b5a7-05c54abd2d94) [![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)

# P7 OC DAPS - BILEMO

For this 7th project, the objective was to develop a web service exposing an API while adhering to the 3rd level of the Richardson Maturity Model.

In this project, we act as developers within the company "BileMo." The company offers a comprehensive selection of high-end mobile phones. Their business model does not involve directly selling products on the website.

This web service allows users to:

 - View the list of products;
 - View the details of a product;
 - Add a user to their collection (Only for Customers);
 - Remove a user from their collection (Only for Customers);
 - View the list of users in their collection (Only for Customers);
 - View the details of a user in their collection (Only for Customers).

#### All UML diagrams of the project are available in the [diagrams](https://github.com/MH-DevApp/OC_Projet_7/tree/develop/diagrams) folder.

## Specs

* PHP >= 8.1
* Symfony 5.4
* Bundles installed via Composer :
  * Symfony - Maker ;
  * Symfony - Serializer ;
  * Symfony - Validator ;
  * Symfony - Security ;
  * Symfony - Uid ;
  * Symfony - Twig ;
  * Symfony - Asset ;
  * Doctrine ORM ;
  * Doctrine Fixtures ;
  * Lexik JWT ;
  * Nelmio - API Doc ;
  * Will Durand - HATEOAS

### Success criteria
The api must be secured. Code quality assessments done via [SymfonyInsight](https://insight.symfony.com/projects/23cc1bb0-55ef-49fe-b5a7-05c54abd2d94).

## Install, build and run

First clone or download the source code and extract it.

### Local webserver
___
#### Requirements
- You need to have composer on your computer
- Your server needs PHP >= 8.1
- MySQL or MariaDB
- Apache or Nginx

The following PHP extensions need to be installed and enabled :
- pdo_mysql
- mysqli
- intl

#### Install

1. Create public and private keys for the authentication:

    ##### Note: For the first command line, you will be prompted for a passphrase. Keep this securely as you will need it for your .env file and for generating the public key.

    ##### Before entering the command lines, please ensure that the `jwt` folder within the `config` directory is present. Please create it if it's not already there.

    ```bash
    > openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
    ```
    
    ```bash
    > openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
    ```

2. To install dependencies with Composer:

    ```bash
    > composer install
    ```

3. Creation of a `.env.dev.local` file with the following information:

    ##### Note: `*user*`, `*password*` and `*Your passphrase*` should be replaced with your own credentials for your database and passphrase JWT.

    ##### example :

    ```dotenv
    DATABASE_URL="mysql://*user*:*password*@127.0.0.1:3306/oc_p7?serverVersion=8&charset=utf8mb4"
    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
    JWT_PASSPHRASE=**Your passphrase**
    ```

4. To run the script for load all fixtures:

    ```bash
    > composer run load
    ```

5. To launch a PHP development server:

   **Note: Please free up port 3000 or modify it in the following command.**

    ```bash
    > php -S localhost:3000 -t public/
    ```

   or

   ```bash
   > symfony serve -port=3000
   ```

6. Clear the cache:

   ##### A caching system has been implemented in the application. If you want to reset your application's cache, execute the following command:

    ```bash
    > php bin/console c:c --env=dev
    ```

The website is available at the url: https://localhost:3000

The documentation is available at : https://localhost:3000/api/doc

### With Docker
___
#### Requirements
To install this project, you will need to have [Docker](https://www.docker.com/) installed on your Computer.

#### Install

Once your Docker configuration is up and ready, you can follow the instructions below:

1. To create a volume for the database:

    ```bash
    > docker volume create oc_dev
    ```

2. Create public and private keys for the authentication:

   ##### Note: For the first command line, you will be prompted for a passphrase. Keep this securely as you will need it for your .env file and for generating the public key.

   ##### Before entering the command lines, please ensure that the `jwt` folder within the `config` directory is present. Please create it if it's not already there.

    ```bash
    > openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
    ```

    ```bash
    > openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
    ```

3. Creation of a `.env.dev.local` file with the following information:

   ##### example :

    ```dotenv
    DATABASE_URL="mysql://root:password@db/oc_p7?serverVersion=8&charset=utf8mb4"
    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
    JWT_PASSPHRASE=**Your passphrase**
    ```

4. To build a Docker image:

   ##### Note: Please free up port 3000.

    ```bash
    > docker-compose -f ../docker-compose.dev.yml up -d --build --remove-orphans
    ```

5. To run the script for load all fixtures:

    ```bash
    > docker exec -it php composer run load
    ```

6. Clear the cache:

   ##### A caching system has been implemented in the application. If you want to reset your application's cache, execute the following command:

    ```bash
    > docker exec -it php symfony console c:c --env=dev
    ```

7. To destroy/remove a Docker image, you can use the following command:

    ```bash
    > docker-compose -f ../docker-compose.dev.yml down -v --remove-orphans
    ```
    ##### The generated Docker container uses PHP8.2, MySQL 8.0 and phpMyAdmin.

The website is available at the url: https://localhost:3000

The documentation is available at : https://localhost:3000/api/doc

#### DBMS

You can access the DBMS (phpMyAdmin) to view and configure your database. Please go to the url: http://localhost:8080.

- Username: `root` ;
- Password: `password`.

This assumes that you have set up a Docker container running phpMyAdmin and configured it to run on port 8080. Make sure that the Docker container is running and accessible before attempting to access phpMyAdmin.

### USERS CREDENTIALS

- [List of Customers](https://github.com/MH-DevApp/OC_Projet_7/blob/develop/src/DataFixtures/data/customers.json)
- [List of Users](https://github.com/MH-DevApp/OC_Projet_7/blob/develop/src/DataFixtures/data/users.json)

Default accounts:
- Username: `user@oc-p7.fr` (User) or `customer@oc-p7.fr` (Customer)
- Password for all users: `123456`


### ONLINE

If you want, you can try this application online at : https://p7.mehdi-haddou.fr/api/doc
