#!/usr/bin/env drush

<?php

/**
 * @file
 * Import the SQL DB from the Dropbox file.
 *
 * This script requires Drush 6.0.0 or later.
 */

$self_record = drush_sitealias_get_record('@warmshowers.dev');
if (!$self_record) {
  return drush_set_error('NO_ALIAS_FOUND', dt('Failed to load your @warmshowers.dev alias.'));
}

// Get the Dropbox path from the Drush alias, if set. The default is
// "~/Dropbox".
$dropbox_root = sprintf('/%s/Dropbox', $_SERVER['HOME']);
if (isset($self_record['#rebuild']['dropbox_root'])) {
  if (!file_exists($self_record['#rebuild']['dropbox_root'])) {
    return drush_set_error('DROPBOX_ROOT_NOT_FOUND', dt('Could not find the Dropbox root specified in the alias.'));
  }
  else {
    $dropbox_root = $self_record['#rebuild']['dropbox_root'];
  }
}

// Get file name for sanitized dump.
$files = scandir(sprintf('/%s/ws_private', $dropbox_root));
$sql_dump = '';
foreach ($files as $file) {
  if (strpos($file, 'warmshowers_sanitized_') === 0) {
    $sql_dump = sprintf('%s/ws_private/%s', $dropbox_root, $file);
    break;
  }
}
// Fail early if dump isn't found.
if (!$sql_dump || !file_exists($sql_dump)) {
  return drush_set_error('NO_SQL_DUMP_FOUND', dt('Failed to find a SQL dump in Dropbox.'));
}
drush_log('Starting import of DB. This might take a while!', 'ok');
$ret = drush_invoke_process('@warmshowers.dev', 'sql-connect');
$mysql_connect = $ret['output'];
// Use gzip -dc to import the DB.
$cmd = sprintf('gzip -dc %s | %s', $sql_dump, $mysql_connect);
drush_log(dt('Importing database with command: "!command"', array('!command' => $cmd)), 'ok');
$ret = drush_shell_exec($cmd);
if (!$ret) {
  drush_set_error(dt('Failed to import database.'));
  drush_die();
}
