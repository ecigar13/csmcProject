# CSMC Website

This is a basis for the CSMC website codebase. This provides the a Docker setup necessary to get a Symfony project up and running with some of the same technologies that the actual CSMC website uses.

## Getting Started

These instructions will get you a basis for the project up and running on your local machine for development and testing purposes.

### Prerequisites

To use this basis successfully you should first have Docker with Docker Compose installed. 

To install Docker first download it [here](https://www.docker.com/community-edition#/download).

Docker Compose is included with Mac and Windows installations. For Linux, find instructions [here](https://docs.docker.com/compose/install/).

### Installing

#### Docker Setup

To get the system running and ready for development you first need to run a few commands. 

First build the Docker containers (this may take a few minutes).

```
docker-compose build
```

Then run the Docker containers in detached mode.

```
docker-compose up -d
```

To test if it is up and running navigate to localhost:8080 in a browser.

There is probably an error displayed on the page, this is because we do not have our dependencies installed.

#### Dependency Installation

To prevent us from having to commit all of the vendor files that can be large and will not change unless we update versions we utilize two package managers, Composer and Yarn. Therefore we need to install all of our dependencies with the following two commands (in this order).

```
docker-compose exec php composer install
```
```
docker-compose exec php yarn install
```

#### Database Setup

In this project we utilize an Object Relational Mapper (ORM) called Doctrine to implement our data model. Because of this all of our database tables are implemented using annotations in PHP classes, the entities. However, the database is not built automatically, so we need to build it.

We build the database with two commands, first to create the database:

```
docker-compose exec php php bin/console doctrine:database:create
```

We can then create the tables with:

```
docker-compose exec php php bin/console doctrine:schema:update --force
```

And now we have a database. Whenever changes are made to any of the entity classes we can update the database with the above command.

You can investigate the database tables and data directly by going to localhost:8081. This brings up PHPMyAdmin.

To populate the database with fake data, we can utilize fixtures. There are already several included in this repository. To load them, run the following: 

```
docker-compose exec php php bin/console doctrine:fixtures:load
```

#### Asset Building

Utilizing Webpack Encore to handle our assets for us means we need to build them before we can use them. This lets us use Sass, TypeScript, or any other compiled frontend language easily because it will handle the compiling for us.

To build the assets just run the following command:

```
docker-compose exec php yarn run encore dev
```

Make sure to run this command whenever assets change.

If changes are being made and checked rapidly we might want to automatically rebuild our assets. There are two ways we can do this.

The first way is to append `--watch` to the above command. This will recompile whenever a change is made.

The second way is to use the dev-server, for details on how to use this, go [here](https://symfony.com/doc/3.4/frontend/encore/dev-server.html).

#### Adding Yourself as a Developer

Run the following command to add yourself as a developer in the system: *(Note: This command makes an LDAP query to get your actual login data from UTD. Thus, if you are developing off-campus, you will need to VPN into the campus network for this command to work.)*

```
docker-compose exec php php bin/console app:add-user yournetid developer
```

After that, you should be able to log in to the website using your NetID and password (i.e. what you use to log in to Galaxy). *(NOTE: If you are developing off-campus you will need to VPN to log in to the website as well.)*

With the developer role, you have the ability to impersonate other users of the system. For example, you can impersonate a user with a mentor role to see how the website would look to mentors. Assuming you have already run the command to load fixtures into your database, you can add a `_switch_user=impersonatingnetid` query parameter to the website URL indicating which user you would like to impersonate.

Example:

```
localhost:8080?_switch_user=mxm000000
```

NOTE: When loading the homepage while impersonating a certain role, the website may throw exceptions about routes not existing, e.g. `href="{{ path('session_schedule') }}"`. Those routes have not been included because this repository only contains a subset of the full website. For the purposes of developing the Mentor Management System, those paths can temporarily be changed to a placeholder like `href="#"`.

### Running the System Without Docker

While this project is intended to be run using Docker, it can also be run without. Docker saves us from having to install each software package to our local machine, so without Docker we need to install all of these.

The following are required to run the system:

- PHP 7
- MySQL
- Composer
- Node
- Yarn

When we have these installed and setup, we then need to install an extra dependency to run a local PHP server:

```
composer require server --dev
```

We can then run a local PHP server with the following command (needs to be done in the symfony folder):

```
php bin/console server:start
```

The command will start the server and expose some port which it will return, and we can access it in a browser at localhost:<port>.

The rest of the setup is the same as above just remove the `docker-compose exec php` portion of the commands.

## Executing Commands In Docker

We have seen several commands already, but what do these different parts actually do? Docker separates the project into containers. This project has 4 containers: web, php, phpmyadmin, and db. These separate the webserver from the PHP instance and the database server. Because these containers are separate, if we need to run a command in one we have to tell Docker which container.

For example, say we want to run one of the Symfony console commands, specifically the list command to see what other commands we have available.

```
docker-compose exec php php bin/console list
```

There are 3 main parts of this command. The first part `docker-compose exec` tells Docker to execute a command. The second part `php` is the name of the container we want that command to execute in. Then the third part `php bin/console list` is the command to execute. Since the container is built on a Linux distribution, we can execute almost any Linux command (Docker containers typically are bare-bones and thus some typical commands may be missing).

## Useful Commands

The following are a collection of commands that can be useful while developing. All of the commands can be executed in the PHP Docker container and thus `docker-compose exec php` is being excluded below, simply prepend it for the whole command.

### Symfony Commands

All Symfony commands are run using `php bin/console` followed by the command and arguments. Append `--help` on any of the commands for more details on a command.

#### List All Commands
```
php bin/console list
```

#### Update Schema
```
php bin/console doctrine:schema:update --force
```

### Composer Commands

#### Install
```
composer install
```

#### Add Dependency
```
composer require <bundle/package>
```

Append `--dev` to only require the bundle/package in the dev environment.

### Yarn Commands

#### Build Assets

```
yarn run encore dev
```

Append `--watch` to rebuild assets when they change.

### Run tests
```
./vendor/bin/simple-phpunit
```
