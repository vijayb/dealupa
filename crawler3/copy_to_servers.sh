#!/bin/sh
tar zcvf code.tar.gz *.js *.sh *.pm *.pl *.dat
scp -P 33777 code.tar.gz vijay@108.166.127.22:~/crawler3;
ssh -p 33777 vijay@108.166.127.22 'cd ~/crawler3/ ; tar zxvf code.tar.gz'


scp -P 33777 code.tar.gz vijay@184.106.175.144:~/crawler3;
ssh -p 33777 vijay@184.106.175.144 'cd ~/crawler3/ ; tar zxvf code.tar.gz'


scp -P 33777 code.tar.gz vijay@184.106.174.162:~/crawler3;
ssh -p 33777 vijay@184.106.174.162 'cd ~/crawler3/ ; tar zxvf code.tar.gz'


scp -P 33777 code.tar.gz vijay@50.57.36.164:~/crawler3;
ssh -p 33777 vijay@50.57.36.164 'cd ~/crawler3/ ; tar zxvf code.tar.gz'

rm code.tar.gz