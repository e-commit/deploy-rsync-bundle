# UPGRADE FROM 1.x to 2.0

The following options have been removed and replaced by the option `target` :
* `hostname`
* `port`
* `username`
* `dir`

Before :

```yaml
ecommit_deploy_rsync:
    environments:
        my_server1:
            hostname: myserver.com
            username: myuser
            dir: /home/remote_dir

        my_server2:
            hostname: myserver2.com
            port: 23
            username: myuser
            dir: /home/remote_dir
```

After :

```yaml
ecommit_deploy_rsync:
    environments:
        my_server1:
            target: ssh://myuser@myserver.com:/home/remote_dir

        my_server2:
            target: ssh://myuser@myserver2.com:23:/home/remote_dir
```
