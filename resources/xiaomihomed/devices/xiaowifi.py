from past.builtins import basestring
import socket
import binascii
import struct
import json
import logging
import globals
import utils
import threading
import socket
import time
from random import randint
from .xiaomi.xiaomipacket import *

def discover(message):
	device = message['model']
	result={}
	default={}
	i = 0
	while i<3:
		try:
			Packet = XiaomiPacket()
			Packet = GetSessionInfo(message['dest'],message['token'])
			k = utils.key_iv(Packet.token)
		except:
			default['ip']=message['dest'];
			default['notfound'] =1;
			globals.JEEDOM_COM.send_change_immediate({'devices':{'wifi':default}})
			logging.debug('Did not find the device try again')
		if Packet.devicetype != 'ffff':
			logging.debug('Found the device : ' + message['dest'])
			default['model']=device
			default['ip']=message['dest']
			default['serial']=Packet.serial.hex()
			default['devtype']=Packet.devicetype.hex()
			default['token']=Packet.token.hex()
			default['found']=1
			result[message['dest']]=default
			globals.JEEDOM_COM.send_change_immediate({'devices':{'wifi':result[message['dest']]}})
			break
		else:
			default['ip']=message['dest'];
			default['notfound'] =1;
			globals.JEEDOM_COM.send_change_immediate({'devices':{'wifi':default}})
			logging.debug('Did not find the device try again')
		i = i+1
	return

def execute_action(message):
	logging.debug("executing " + str(message))
	try:
		randid = randint(1, 65000)
		device = message['model']
		Packet  = XiaomiPacket()
		Packet = GetSessionInfo(message['dest'],message['token'])
		if message['param'] == '':
			if device == 'vacuum' and str(message['method']) == 'app_charge':
				logging.debug('{"id":'+str(randid)+',"method":"app_stop"}')
				Packet.setPlainData('{"id":'+str(randid)+',"method":"app_stop"}')
				SendRcv(Packet,message['dest'])
			logging.debug('{"id":'+str(randid+1)+',"method":"'+str(message['method'])+'"}')
			Packet.setPlainData('{"id":'+str(randid+1)+',"method":"'+str(message['method'])+'"}')
		else:
			logging.debug('{"id":'+str(randid)+',"method":"'+str(message['method'])+'","params":'+str(message['param'])+'}')
			Packet.setPlainData('{"id":'+str(randid)+',"method":"'+str(message['method'])+'","params":'+str(message['param'])+'}')
		SendRcv(Packet,message['dest'])
		#t = threading.Timer(5)
		#t.start()
	except Exception as e:
		logging.debug(str(e))
	return

def refresh(message):
	logging.debug("refreshing " + str(message))
	device = message['model']
	result={}
	status ={}
	result['model'] = device
	result['ip'] = message['dest']
	if device in globals.DICT_REFRESH_WIFI:
		Packet  = XiaomiPacket()
		Packet = GetSessionInfo(message['dest'],message['token'])
		for info in globals.DICT_REFRESH_WIFI[device]:
			jsoninfo = json.loads(info)
			randid = randint(1, 65000)
			jsoninfo['id'] = randid
			info = json.dumps(jsoninfo)
			Packet.setPlainData(info)
			SendRcv(Packet,message['dest'])
			dict_params = json.loads(info)
			if 'params' in info and device != 'airmonitorb1':
				plaindata = Packet.getPlainData().decode('utf-8')
				logging.debug("params " + plaindata)
				if plaindata[-1:] != "}":
					plaindata = plaindata[0:-1]
				dict_result = json.loads(plaindata)
				results = dict_result['result']
				j=0
				for param in dict_params['params']:
					if param == 'temp_dec':
						status[param] = results[j]/10.0
					elif param == 'current':
						status[param] = results[j]
						status['powercalc'] = results[j]*220.0
					else:
						try:
							status[param] = results[j]
						except IndexError:
							status[param] = 'null'
					j = j+1
					result['status'] = status
			if device in ['vacuum','vacuum2','airmonitorb1']:
				real_result= Packet.getPlainData().decode('utf-8').split('{',2)[2].split('}',1)[0]
				logging.debug("vacuum " + real_result)
				logging.debug(json.loads('{'+real_result+'}'))
				result[dict_params['method']] =json.loads('{'+real_result+'}')
	result = utils.clean_result(device,result)
	globals.JEEDOM_COM.add_changes('devices::wifi_'+message['dest'],result)
	return

def GetSessionInfo(ip,token=None):
	try:
		sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	except socket.error:
		logging.debug('Failed to create socket')
		return
	try:
		PACKET  = XiaomiPacket()
		PACKET.setHelo()
		sock.sendto(PACKET.getRaw(), (ip, 54321))
		sock.settimeout(5.0)
		try:
			d = sock.recvfrom(1024)
		except socket.timeout:
			logging.debug("Timeout")
			return
		PACKET.setRaw(d[0])
		if token and token != '':
			PACKET.token=codecs.decode(token, 'hex')
		return PACKET
	except socket.error as msg:
		logging.debug('Error Code: '+str(msg[0])+' Message: '+msg[1])

def SendRcv(PACKET,ip):
	try:
		sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	except socket.error:
		logging.debug('Failed to create socket')
		return
	try:
		sock.settimeout(5)
		sock.sendto(PACKET.getRaw(), (ip, 54321))
		d = sock.recvfrom(1024)
		#logging.debug('Receive : '+str(d[0]))
		PACKET.setRaw(d[0])
		return
	except socket.error as msg:
		logging.debug('Error Code: '+str(msg[0])+' Messge: '+msg[1])
		return
	return
