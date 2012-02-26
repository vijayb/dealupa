sudo apt-get update;
sudo apt-get install git-core;
sudo aptitude install build-essential;
sudo apt-get install libqt4-dev libqtwebkit-dev qt4-qmake;
git clone git://github.com/ariya/phantomjs.git && cd phantomjs;
git checkout 1.3;
qmake-qt4 && make;
sudo apt-get install xvfb xfonts-100dpi xfonts-75dpi xfonts-scalable xfonts-cyrillic;