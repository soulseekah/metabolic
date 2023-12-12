# metabolic

A WordPress plugin and developer API to optimize post, user, taxonomy, site, term and comment meta by combining sequential operations into a handful of SQL queries.

## Usage

- Install as a WordPress plugin (mu-plugin) or add it to your project as a composer dependency (`composer require soulseekah/metabolic`).
- Call `metabolic/queue_meta_updates()`.
- Perform a massive amount of meta update operations.
- Call `metabolic/commit_meta_updates()`.
- Enjoy reduced database load.

No time to find the hotspots? `metabolic/metabolic()` will automatically queue and commit sequential adds, updates and deletes as needed. Boost this up.

## API

### `metabolic/queue_meta_updates( array $args )`

### `metabolic/commit_meta_updates( array $args )`

### `metabolic/flush_meta_updates( array $args )`

### `metabolic/metabolic( bool $activate )`

## Development

- `composer install --dev`
- `git clone --depth=1 git@github.com:WordPress/wordpress-develop.git`
- `cd wordpress-develop && npm i && npm run build; cd ..`
- `vendor/bin/phpunit`

## TODO

- [ ] Concept
- [ ] Benchmarks
- [ ] Tests
- [ ] Auto-queue and auto-commit
