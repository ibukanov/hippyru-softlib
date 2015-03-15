#!/bin/bash

user=forum.hippy.ru
forum_website=www.hippy.ru/forum

log "Preparing PHP config for $forum_website"

forum_cache="$(get_scratch_dir $user)/forum-cache/"
mkdir -p "$forum_cache"
chmod 770 "$forum_cache"

fluxbb_root=/www/soft/fluxbb

# Generate and link config

php_prepare_config_write "$user" fluxbb-config.php

php_write_config <<EOF
<?php
\$db_host     = ':$mysql_socket';
\$db_username = '$php_db_user';
\$db_password = '$php_db_password';
\$db_name     = '$php_db_name';

\$db_type = 'mysql';
\$db_prefix = 'f_';
\$p_connect = false;

\$base_url = 'https://$forum_website';

\$cookie_name = 'punbb_cookie';
\$cookie_domain = '';
\$cookie_path = '/';
\$cookie_secure = 0;

define('FORUM', 1);
define('FORUM_DISABLE_CSRF_CONFIRM', 1);

define('FORUM_CACHE_DIR', '$forum_cache');
EOF

rm -rf "$fluxbb_root/config.php"
ln -s "$php_config_path" "$fluxbb_root/config.php"

# Hack to workaround fluxbb limitation that requires the upload dir to reside
# under the tree with php sources.

rm -rf "$fluxbb_root/img/avatars"
ln -s /www/site/forum.hippy.ru/html/img/avatars "$fluxbb_root/img/avatars"
