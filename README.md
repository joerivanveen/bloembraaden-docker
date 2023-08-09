# Bloembraaden-docker
This is the official docker image for [Bloembraaden].

## We like unicorns more than uniforms
[Bloembraaden] is an open source cms for digital agencies and designers with a focus on freedom.

## How to
Read up on how Bloembraaden works on [its GitHub repo][gh-repo].

This docker image creates a default Bloembraaden environment to begin creating your own websites.

## Setup Bloembraaden

1. Create a folder on your computer where you want this project, e.g. `/path/to/bloembraaden` and go into it.
2. Clone this repo to your local computer, it will create a folder `./docker` 
    ```shell
    gh repo clone joerivanveen/bloembraaden-docker .
    ```
3. Clone the Bloembraaden repo next to your docker folder:
    ```shell
    gh repo clone joerivanveen/bloembraaden ./bloembraaden
    ```

## Your websites

Still inside your Bloembraaden root folder `/path/to/bloembraaden` you will create a folder for your own websites.
This folder must contain a `htdocs` folder with at least an `index.php` file (refer to the [Bloembraaden GitHub repo][gh-repo]). This will be your own (private) repo where you keep your client websites.
To start you can download an example.

1. Create a folder for your client websites / instances:
    ```shell
    mkdir ./bloembraaden-sites
    ```
2. Create or clone your folders and files, or start with an example:
    ```shell
    gh repo clone joerivanveen/bloembraaden-boilerplate ./bloembraaden-sites/htdocs
    ```

If you want to use a different name than `bloembraaden-sites`, you need to change the references to this folder in docker-compose as well.

## Config

Copy .env.template to .env, and fill in your own values. The config.json in the docker containers will be filled with the values from your .env file.

```shell
cp .env.template .env
```

Fill in all env variables in the .env file for a complete working setup.

## Run Bloembraaden

```shell
docker compose up -d
```

The first time this will build the Bloembraaden image and install the cms.

## Visit your installation

Edit your `hosts` file to point `bloembraaden.local`, as well as the mandatory `www` and `static` subdomains to your localhost, where docker will present it for you.

You can stage client websites on subdomains to reuse the automated ssl certificate. Of course, you can point any domain to your localhost and Bloembraaden will respond, only you need to fix the ssl certificate yourself or ignore it.

```
127.0.0.1       bloembraaden.local
127.0.0.1       www.bloembraaden.local
127.0.0.1       static.bloembraaden.local
127.0.0.1       first-client.bloembraaden.local
```

Open your browser and visit `bloembraaden.local`.

## SSL

The automated ssl certificate is not trusted by default, you need to add it to your certificate store once.

1. In your browser go to the certificate, probably via the lock in the address bar.
2. Export the certificate chain (probably on the details tab there is an Export button).
3. Make sure you export the entire **chain**, not just the certificate.
4. Import the chain into your certificate store. Now your computer will trust bloembraaden.local.

## Database

You can connect to the postgres database in your ide, for instance PhpStorm
on `localhost:5432` using the `POSTGRES_USER` and `POSTGRES_PASSWORD` you set up in the `.env` file.

## Volumes

This docker project will create a `volumes` folder next to its docker folder, with all the volumes that are necessary for and used by Bloembraaden for your convenience.

You can safely browse and edit the contents of these folders, that will be managed by Bloembraaden as well.

This is so you can easily find the logs and the files you uploaded to see what is going on.

You can safely exclude this folder from your project. It will be recreated (but of course be empty, ie your images will be gone) when you remove it.

[Bloembraaden]: https://bloembraaden.io
[gh-repo]: https://github.com/joerivanveen/bloembraaden
