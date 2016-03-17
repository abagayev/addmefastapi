# AddMeFast API
Simple PHP wrapper for the AddMeFast

Use this class to create, start, pause and delete sites/posts using your AddMeFast account.

### How to run

To test this code you need to install dependencies with Composer, open demo.php file and fill your login and password.

```shell
composer install
php5 demo.php
```

### Public methods

- addSite() to create site
- touchSite() to start, pause and delete sites
- getSites() to get list of existing sites