# generate new keypair or atleat test getting new jwt token
`php bin/console lexik:jwt:generate-keypair --overwrite`

# migrate db
`php bin/console doctrine:migrations:migrate`
sonatauser roles are no longer arrays, JSON instead

## Doctrine move to Native Lazy Object

https://www.doctrine-project.org/2025/06/28/orm-3.4.0-released.html
