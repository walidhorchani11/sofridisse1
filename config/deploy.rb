require 'mina/bundler'
require 'mina/rails'
require 'mina/git'
require "mina/rsync"


set :term_mode, nil
set :repository, 'git://'
set :branch, 'master'
set :rsync_options, %w[--recursive --delete --delete-excluded --exclude-from=config/.rsyncignore]
set :rsync_stage, '.'

set :stage_to, ENV['to']
file = "config/stages/#{stage_to}.rb"
load file


set :shared_paths, ['app/logs', 'web/cache', 'web/images/flags', 'web/images/product']
#set :shared_paths, ['app/logs', 'app/data', 'web/cache', 'web/images/flags', 'web/images/product']


# This task is the environment that is loaded for most commands, such as
# `mina deploy` or `mina rake`.
task :environment do
  # If you're using rbenv, use this to load the rbenv environment.
  # Be sure to commit your .rbenv-version to your repository.
  # invoke :'rbenv:load'

  # For those using RVM, use this to load an RVM version@gemset.
  # invoke :'rvm:use[ruby-1.9.3-p125@default]'
end

# Put any custom mkdir's in here for when `mina setup` is ran.
# For Rails apps, we'll make some of the shared paths that are shared between
# all releases.
task :setup => :environment do
  queue! %[mkdir -p "#{deploy_to}/shared/app"]

  #queue! %[mkdir -p "#{deploy_to}/shared/app/cache"]

  queue! %[mkdir -p "#{deploy_to}/shared/app/logs"]

  queue! %[mkdir -p "#{deploy_to}/shared/app/data"]

  queue! %[mkdir -p "#{deploy_to}/shared/app/data/uploads"]

  queue! %[mkdir -p "#{deploy_to}/shared/web"]

  queue! %[mkdir -p "#{deploy_to}/shared/web/cache"]

  queue! %[mkdir -p "#{deploy_to}/shared/web/images/product"]

  queue! %[mkdir -p "#{deploy_to}/shared/web/images/flags"]

  queue! "cd #{deploy_to}/shared"

  queue! %[setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX app/cache app/logs app/data web/cache]
  queue! %[setfacl -dR -m u:www-data:rwx -m u:`whoami`:rwx app/cache app/logs app/data web/cache]
end

desc "Deploys the current version to the server."
task :deploy => :environment do
  deploy do
    invoke "rsync:deploy"
    invoke :'deploy:link_shared_paths'

    to :launch do
      queue "cd #{deploy_to}/current"
      queue %[php app/console cache:clear --env=#{stage_to} --no-debug]
      queue %[php app/console doctrine:schema:update --force --env=#{stage_to}]
      queue %[php app/console doctrine:migrations:migrate --env=#{stage_to}]
      queue %[php app/console assetic:dump --env=#{stage_to} --no-debug]
      queue %[php app/console assets:install --env=#{stage_to} --no-debug]
      queue %[php app/console cache:clear --env=#{stage_to} --no-debug]
      queue "chmod -R 777 app/cache"
    end
    invoke :'deploy:cleanup'
  end
end
