#!/bin/bash

user=bergenrabbit.net

log "Preparing PHP config for wordpress at $user"

wp_keysalt_path="$(get_scratch_dir $user)/wp-keysalt"
ensure_file_with_randoms 640 $((64*8)) "$wp_keysalt_path"

keysalt=()
for ((i=0; i<8; i+=1)); do
    dd_args=(status=none if="$wp_keysalt_path" bs=64 count=1 skip=$i)
    keysalt+=("$(dd "${dd_args[@]}" | print_binary_as_php_string_literal)")
done

# Generate and link config

php_prepare_config_write "$user" wp-config.php

php_write_config <<EOF
<?php

define('DB_HOST',     'localhost:$mysql_socket');
define('DB_USER',     '$php_db_user');
define('DB_PASSWORD', '$php_db_password');
define('DB_NAME',     '$php_db_name');

define('DB_CHARSET',  'utf8');
define('DB_COLLATE',  '');

define('AUTH_KEY',         "${keysalt[0]}");
define('SECURE_AUTH_KEY',  "${keysalt[1]}");
define('LOGGED_IN_KEY',    "${keysalt[2]}");
define('NONCE_KEY',        "${keysalt[3]}");
define('AUTH_SALT',        "${keysalt[4]}");
define('SECURE_AUTH_SALT', "${keysalt[5]}");
define('LOGGED_IN_SALT',   "${keysalt[6]}");
define('NONCE_SALT',       "${keysalt[7]}");

define('WP_DEBUG', false);
define('WP_CORE_UPDATE', false);
define('WP_ALLOW_MULTISITE', true);

\$table_prefix  = 'wp_';

define('WPLANG', 'ru_RU');

define('FORCE_SSL_LOGIN', true);

require_once(ABSPATH . 'wp-settings.php');
EOF
