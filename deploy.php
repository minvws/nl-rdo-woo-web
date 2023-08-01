<?php

namespace Deployer;

require 'recipe/symfony.php';

set('application', 'woopie');
set('repository', 'git@github.com:minvws/nl-rdo-woo-web');

set('git_tty', true);
set('allow_anonymous_stats', false);
set('bin_dir', 'bin');

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', ['var/documents', 'var/thumbnails', 'var/batches']);
add('writable_dirs', ['var/documents', 'var/thumbnails', 'var/batches']);

// Hosts
host('woopie.deadcode.nl')
    ->set('labels', ['stage' => 'prod'])
    ->set('remote_user', 'woopie')
    ->set('deploy_path', '/wwwroot/{{application}}')
;

before('deploy:symlink', 'frontend:build');
before('deploy:symlink', 'db:migrate');
before('deploy:symlink', 'deploy:version');

after('deploy', 'worker:restart');
after('deploy:failed', 'deploy:unlock');

// -----------------------------------------------------------
// Tasks

task('frontend:build', function () {
    runLocally('npm install && npm run build');
    upload('public/build', '{{release_path}}/public');
});

task('db:migrate', function () {
    run('cd {{release_path}} && php bin/console d:m:m');
});

task('deploy:version', function () {
    $rev = substr(get('release_revision'), 0, 8);
    $date = date('Y-m-d H:i:s');
    $target = get('target');
    run('cd {{release_path}} && jq -n "{version: \"' . $target . '\", git_ref: \"' . $rev . '\", date: \"' . $date . '\"}" > public/version.json');
});

task('worker:restart', function () {
    run('supervisorctl reread');
    run('supervisorctl restart high:*');
    run('supervisorctl restart ingestor:*');
    run('supervisorctl restart esupdater:*');
    run('supervisorctl restart global:*');
});
