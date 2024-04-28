# EcommitDeployRsyncBundle

The EcommitDeployRsyncBundle bundle (for Symfony) deploys your Symfony project with RSYNC.


![Tests](https://github.com/e-commit/deploy-rsync-bundle/workflows/Tests/badge.svg)


## Installation ##

Install the bundle with Composer : In your project directory, execute the following command :

```bash
$ composer require ecommit/deploy-rsync-bundle
```

Enable the bundle in the `config/bundles.php` file for your project :

```php
return [
    //...
    Ecommit\DeployRsyncBundle\EcommitDeployRsyncBundle::class => ['all' => true],
    //...
];
```

In your project, add the configuration file `config/packages/ecommit_deploy_rsync.yaml` :

```yaml
ecommit_deploy_rsync:
    #Environments configuration
    environments:
        my_server1: #Environment name
            #Target - Required
            #The target can be either an SSH target or a local target
            #SSH target format: ssh://<username>@<hostname>:<path> or ssh://<username>@<hostname>:<port>:<path>
            #Local target format: file://<path>
            target: ssh://myuser@myserver.com:/home/remote_dir
            #rsync_options: [] #Rsync command options - Not required - Default values: [] - If not defined, the global rsync_options is used
            #ignore_file: #Rsync ignore file - Not required - Default value: null - If not defined, the global ignore_file option is used

        #You can define others environments :
        #my_server2:
            #target: ssh://myuser@myserver2.com:/home/remote_dir
    
    #Rsync global configuration
    #rsync:
        #rsync_path: rsync #Rsync bin path - Not required - Default value: "rsync"
        #rsync_options #Rsync command options (global) - Default values:
            # - '-azC'
            # - '--force'
            # - '--delete'
            # - '--progress'
        #ignore_file: #Rsync ignore file - Not required - Default value: null
```


## Usage ##

```bash
#Perform a trial run with no changes made
php bin/console ecommit:deploy-rsync my_server1
#Execute the changes
php bin/console ecommit:deploy-rsync my_server1 --go
```


## License ##

This bundle is available under the MIT license. See the complete license in the *LICENSE* file.
