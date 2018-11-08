set :domain, '37.187.148.59'
set :user, 'chine'
set :deploy_to, '/home/chine/site/sogedial/'
set :port, '22'
set :rsync_options, %w[--recursive --delete --delete-excluded --exclude-from=config/.rsyncignore] + ["-e", "ssh -p 22"]
