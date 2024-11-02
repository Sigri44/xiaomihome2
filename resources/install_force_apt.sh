#!/bin/bash
PROGRESS_FILE=/tmp/dependancy_xiaomihome_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
sudo apt-get update
echo 10 > ${PROGRESS_FILE}

sudo apt-get -y install python3-dev python3-pip python3-cryptography libffi-dev libssl-dev python3-setuptools

echo 20 > ${PROGRESS_FILE}
sudo pip3 install construct --upgrade --ignore-installed

echo 30 > ${PROGRESS_FILE}
sudo pip3 install pyudev --upgrade --ignore-installed

echo 40 > ${PROGRESS_FILE}
sudo pip3 install requests --upgrade --ignore-installed

echo 50 > ${PROGRESS_FILE}
sudo pip3 install pyserial --upgrade --ignore-installed

echo 60 > ${PROGRESS_FILE}
sudo pip3 install future --upgrade --ignore-installed

echo 70 > ${PROGRESS_FILE}
sudo pip3 install pycrypto --upgrade --ignore-installed

echo 75 > ${PROGRESS_FILE}
sudo pip3 install cryptography --upgrade --ignore-installed

echo 80 > ${PROGRESS_FILE}
sudo pip3 install enum34 --upgrade --ignore-installed

echo 90 > ${PROGRESS_FILE}
sudo pip3 install enum-compat --upgrade --ignore-installed

echo 95 > ${PROGRESS_FILE}
sudo pip3 install wheel --upgrade --ignore-installed

echo 95 > ${PROGRESS_FILE}
sudo pip3 install yeelight --upgrade --ignore-installed

echo 95 > ${PROGRESS_FILE}
sudo pip3 install python-miio --upgrade --ignore-installed

echo 95 > ${PROGRESS_FILE}
sudo pip3 install _thread --upgrade --ignore-installed

echo 100 > ${PROGRESS_FILE}

echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
