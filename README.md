# yii2-static-assets
Prevent publishing of assets at production time without changing your application.

This fork removes docker-related stuff (just like the work of the ItsReddi: https://github.com/ItsReddi/yii2-static-assets, just more recent) and adds CLI command parameters. Original project of the SAM-IT's is available here: https://github.com/SAM-IT/yii2-static-assets. 

Original project description below.

# Motivation
Nowadays docker is getting more and more attention and PHP applications are seeing different deployment scenarios.
Where a few years ago if you split your nodes up at all you'd only split up the database server and the webserver that runs PHP as module or more recently via PHP-FPM, nowadays you want to split everything up.

# Challenges
Having your webserver, for example nginx, running on a different server than PHP-FPM comes with several challenges, pros and cons:
1. PHP can't write files and create publicly accessible URLs for them
2. Cannot use local session storage
3. File uploads that need to be publicly accessible need to be published to the webserver.
4. File uploads that need to be protected have to accessible by all PHP nodes (so some kind of central storage is needed).

# Solution
This extension will provide a solution for number 1 when it comes to Yii assets.
The asset management system is nice when pushing changed assets directly to a server, it doesn't really work well in distributed environments though, so we need another approach.

This extension provides:
- A console command that will scan your source code and vendor directory and will extract all your assets.
- An AssetManager for use in production that will generate URLs for assets without checking for touching storage.

The workflow then becomes a bit different.
During development the asset manager will act like a normal asset manager, publishing assets to the asset directory. When using docker-compose for the test environment you would mount the same host directory to the asset folder in the PHP container and in the webserver container.

During deployment the assetmanager simply returns URLs for assets on the assumption that they exist. 
Before deploying a new version of your app, you will rebuild your webserver container.
This extension provides a console command that will publish all your assets to a directory of your choosing, this can then be used as part of the docker build context.

To publish your assets run the following command:
````
yii staticAssets/asset/publish build12345
````

This will create the directory `build12345` inside your runtime directory and publish all assets there.

# Asset discovery / publishing
Assets are discovered by recursively iterating over all folders and files.
Each file that ends with `.php` is then processed:
- The namespace is extracted via regular expression matching.
- The class name is extracted via regular expression matching.
- We use reflection to check if the class is an instance of `yii\web\AssetBundle` and if so it is published.

# Container building
To build an nignx container for server your application use this:
````
yii staticAssets/asset/build-container
````
You can configure the module to set some default values.

# Configuration
For simple configuration use the `ReadOnlyAssetManager` in your application during production and development.
This asset manager will use a simpler "hash" function that keeps directory structure readable.
It supports `$assetDevelopmentMode` which allows for local asset development in a dockerized environment.

# Asset development
The assumption is that you use docker-compose for local development, in which case you need to define a volume where assets are stored so that they are available in both the webserver as well as the phpfpm container:
````
volumes:
  assets:
nginx:
    image: [name of your nginx container, built by this module]
    environment:
      PHPFPM: "phpfpm:9000"
      RESOLVER: "127.0.0.11"
    ports:
      - "12346:80" # Port where the application will be available
    depends_on:
      - phpfpm
    volumes:
    # Defines the named volume as read-only for the webserver.
    # Note the dev-assets, which allows to easily identify development
    # mode while using browsers' developer tools.
      - type: volume
        source: assets
        target: /www/dev-assets
        read_only: true
        volume:
          nocopy: true
  phpfpm:
    dns: 8.8.4.4
    image: [ name of your PHPFPM docker image ]
    environment:
      DB_USER: root
      DB_NAME: test
      DB_PASS: secret
      DB_HOST: mysql
    depends_on:
      - mysql
    volumes:
      # The source code is loaded into PHPFPM for local development,
      # for production it should be baked into the image.
      - .:/project:ro
      # The asset volume, note the location which is where the ReadOnlyAssetManager
      # will publish assets when in development mode.
      - type: volume
        source: assets
        target: /tmp/assets
````


