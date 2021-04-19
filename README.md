# DEHIA Collect Service
The Collect service for [DEHIA](http://sedici.unlp.edu.ar/handle/10915/116617), a platform for managing and executing data collection activities that require human intervention.

## Contents
- [DEHIA](#dehia)
- [Installation](#installation)
  - [Docker](#docker-recommended)
  - [Run locally (Linux)](#run-locally-linux)
- [Environment Variables](#environment-variables)
  - [Docker variables](#docker-variables)
  - [PHP variables](#php-variables)
- [Endpoints](#endpoints)
- [See Also](#see-also)

## DEHIA
DEHIA is a platform for Defining and Executing Human Intervention Activities. Its goal is to allow users without programming knowledge to create activities (sets of tasks, mainly for data collection) through a web authoring tool. The activities are exported to a configuration file and then "executed" (solved) from a mobile app. This kind of activities requires human intervention and cannot be solved automatically. 

There is also an API that manages the activities lifecycle, collects the data from the mobile app and returns the results. It also manages the security of the application. The API includes a Gateway and four services: Define, Auth, Collect and Results.

## Installation
You can install the service either in containerized version using Docker or locally (on Linux) using PHP7.4 and Apache or NGINX. The database can be the one included in the docker-compose file or an external one.
### Docker (recommended)
 1. Create an `app/.env.local` file based in `app/.env` (See [Environment Variables](#Environment-Variables))
 2. Start the mongodb container to initialize it. This can take up to 5 minutes (only needed once).
 ```
 docker-compose up collect.mongo
 ```
 3. In another terminal, start the rest of the containers.
 ```
 docker-compose up
 ```
 4. Now you can add the URL to the gateway and the other services.
## Run locally (Linux)
# Environment Variables
Docker variablas go in the `.env` file. PHP variables go in the `app/.env.local` file.
## Docker varaibles
- **MONGO_INITDB_ROOT_USERNAME**: root user for the mongodb container
- **MONGO_INITDB_ROOT_PASSWORD**: root password for the mongodb container. 
- **MONGO_DB**: database to be created for the application. It must match the URL in `app/.env.local`
- **MONGO_USER**: database user to be created for the application. It must match the URL in `app/.env.local`
- **MONGO_PASSWORD**: password for the aforementioned database user. It must match the URL in `app/.env.local`
- **MONGO_EXPRESS_PORT**: port to be exposed for the Mongo Express user (DB client).
- **LOCAL_USER**: user in the container system. The same id of the host user is preferred (because of the volume sharing the files)
## PHP variables
- **DATABASE_URL**: template for the MongoDB URL. The placeholders must be filled with the information in the docker `.env` file.

## Endpoints
- 


*Secured endpoint: it needs an `Authorization: Bearer <JWT-token>` header, where `JWT-token` is obtained from the gateway


## See also
- [DEHIA Frontend](https://github.com/mokocchi/autores-demo-client)
- [DEHIA Gateway](https://github.com/mokocchi/dehia_gateway)
- [DEHIA Mobile App](https://github.com/mokocchi/prototipo-app-actividades)
- [DEHIA Auth Service](https://github.com/mokocchi/dehia_auth)
- [DEHIA Collect Service](https://github.com/mokocchi/dehia_collect)
- [DEHIA Results Service](https://github.com/mokocchi/dehia_results)
