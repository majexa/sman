#tmux bind k kill-session -t asd
tmux kill-session -t asd

tmux -2 new-session -d -s asd
tmux new-window -t asd:1 -n 'dnsMaster'

tmux split-window -h -p 50

tmux select-pane -t 0
tmux send-keys "ssh 82.196.14.175 -t 'watch -n 5 cat /etc/bind/zones/db.majexa.ru'" C-m

tmux select-pane -t 1
tmux send-keys "ssh 82.196.14.175" C-m

tmux split-window -v -p 30 -t 0
tmux select-pane -t 2
tmux send-keys "watch -n 30 \"php ~/run/run.php '(new ErrorsCollector)->run()' NGN_ENV_PATH/manager/init.php\"" C-m

tmux split-window -v -p 60 -t 1
tmux select-pane -t 3
tmux send-keys "ssh 37.139.26.212" C-m
tmux send-keys "su user" C-m
tmux send-keys "cd ~" C-m

# current server shell
tmux split-window -v -p 20 -t 3
tmux select-pane -t 4

tmux split-window -h -p 45 -t 0
tmux select-pane -t 5
tmux send-keys "ssh 82.196.14.175 -t 'watch -n 20 cat /etc/bind/named.conf.local'" C-m

tmux select-window -t asd:1
tmux select-pane -t 3

tmux -2 attach-session -t asd