# QuestUno test suite

The suite uses the official WordPress PHPUnit test library and must run only against a dedicated database.

Do not point `WP_TESTS_DIR` at a configuration that uses the Local site database.

1. Create an empty database such as `questuno_test` and configure the WordPress test library's `wp-tests-config.php` to use it.
2. In the Local Site Shell, set the following environment variables:

```powershell
$env:WP_TESTS_DIR = 'D:\path\to\wordpress-tests-lib'
$env:QUESTUNO_TEST_DB = 'questuno_test'
$env:QUESTUNO_TESTS_ALLOW_DB_RESET = '1'
```

3. Run the PHPUnit executable supplied by the test environment from the plugin directory:

```powershell
phpunit -c phpunit.xml.dist
```

The bootstrap refuses to load unless the explicit database name and reset confirmation are both present. The WordPress test framework resets only the database specified by `wp-tests-config.php`.
