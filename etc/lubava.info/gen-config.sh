#!/bin/bash

user=lubava.info

log "Preparing textdb PHP config for $user"

login_seed_path="$(get_scratch_dir $user)/textdb-login-seed"
ensure_file_with_randoms 640 64 "$login_seed_path"
login_seed="$(print_binary_as_php_string_literal < "$login_seed_path")"

# Generate and link config

php_prepare_config_write "$user" textdb-config.php

php_write_config <<EOF
<?php
define('DEF_DB_PATH',     '$mysql_socket');
define('DEF_DB_USER',     '$php_db_user');
define('DEF_DB_PASSWORD', '$php_db_password');
define('DEF_DB_NAME',     '$php_db_name');

define('DEF_LOG_PATH',    '$log_fifo');

define('DEF_LOGIN_SEED',  "$login_seed");
EOF

rm -rf /www/soft/textdb/config.php
ln -s "$php_config_path" /www/soft/textdb/config.php