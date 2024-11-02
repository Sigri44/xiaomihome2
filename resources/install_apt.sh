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
echo "Installation des dépendances apt"
sudo apt-get -y install python3-dev python3-pip python3-cryptography libffi-dev libssl-dev python3-setuptools

echo 20 > ${PROGRESS_FILE}
if [ $(pip3 list | grep construct | wc -l) -eq 0 ]; then
    echo "Installation du module construct pour python"
    sudo pip3 install construct
fi

echo 30 > ${PROGRESS_FILE}
if [ $(pip3 list | grep pyudev | wc -l) -eq 0 ]; then
    echo "Installation du module pyudev pour python"
    sudo pip3 install pyudev
fi

echo 40 > ${PROGRESS_FILE}
if [ $(pip3 list | grep requests | wc -l) -eq 0 ]; then
    echo "Installation du module requests pour python"
    sudo pip3 install requests
fi

echo 50 > ${PROGRESS_FILE}
if [ $(pip3 list | grep pyserial | wc -l) -eq 0 ]; then
    echo "Installation du module pyserial pour python"
    sudo pip3 install pyserial
fi

echo 60 > ${PROGRESS_FILE}
if [ $(pip3 list | grep "future " | wc -l) -eq 0 ]; then
    echo "Installation du module future pour python"
    sudo pip3 install future
fi

echo 70 > ${PROGRESS_FILE}
if [ $(pip3 list | grep pycrypto | wc -l) -eq 0 ]; then
    echo "Installation du module pycrypto pour python"
    sudo pip3 install pycrypto
fi

echo 75 > ${PROGRESS_FILE}
if [ $(pip3 list | grep cryptography | wc -l) -eq 0 ]; then
    echo "Installation du module cryptography pour python"
    sudo pip3 install cryptography
fi

echo 80 > ${PROGRESS_FILE}
if [ $(pip3 list | grep enum34 | wc -l) -eq 0 ]; then
    echo "Installation du module enum34 pour python"
    sudo pip3 install enum34
fi

echo 90 > ${PROGRESS_FILE}
if [ $(pip3 list | grep enum-compat | wc -l) -eq 0 ]; then
    echo "Installation du module enum-compat pour python"
    sudo pip3 install enum-compat
fi

echo 95 > ${PROGRESS_FILE}
if [ $(pip3 list | grep wheel | wc -l) -eq 0 ]; then
    echo "Installation du module wheel pour python"
    sudo pip3 install wheel
fi

echo 95 > ${PROGRESS_FILE}
if [ $(pip3 list | grep yeelight | wc -l) -eq 0 ]; then
    echo "Installation du module yeelight pour python"
    sudo pip3 install yeelight
fi

echo 95 > ${PROGRESS_FILE}
if [ $(pip3 list | grep miio | wc -l) -eq 0 ]; then
    echo "Installation du module miio pour python"
    sudo pip3 install python-miio
fi

echo 95 > ${PROGRESS_FILE}
if [ $(pip3 list | grep thread | wc -l) -eq 0 ]; then
    echo "Installation du module thread pour python"
    sudo pip3 install _thread
fi

echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
