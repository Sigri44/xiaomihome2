import hashlib
import datetime
import globals
import logging
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import padding
_backend = default_backend()

def hex_color_to_rgb(color):
	"Convert a hex color string to an RGB tuple."
	color = color.strip("#")
	try:
		red, green, blue = tuple(int(color[i:i + 2], 16) for i in (0, 2, 4))
	except:
		red, green, blue = (255, 0, 0)
	return red, green, blue

def md5(data):
	checksum = hashlib.md5()
	checksum.update(data)
	return checksum.digest()

def key_iv(token):
	key = md5(token)
	iv = md5(key+token)
	return (key, iv)

def encrypt(token, plaintext):
	key, iv = key_iv(token)
	padder = padding.PKCS7(128).padder()
	padded_plaintext = padder.update(plaintext) + padder.finalize()
	cipher = Cipher(algorithms.AES(key), modes.CBC(iv), backend=default_backend())
	encryptor = cipher.encryptor()
	return encryptor.update(padded_plaintext) + encryptor.finalize()

def decrypt(token, ciphertext):
	key, iv = key_iv(token)
	cipher = Cipher(algorithms.AES(key), modes.CBC(iv), backend=default_backend())
	decryptor = cipher.decryptor()
	padded_plaintext = decryptor.update(ciphertext) + decryptor.finalize()
	unpadder = padding.PKCS7(128).unpadder()
	unpadded_plaintext = unpadder.update(padded_plaintext)
	unpadded_plaintext += unpadder.finalize()
	return unpadded_plaintext

def pretty_area(x):
	return int(x) / 1000000

def pretty_seconds(x):
	return datetime.timedelta(seconds=x)

def pretty_time(x):
	return datetime.datetime.fromtimestamp(x)

def clean_result(device,result):
	if device in globals.DICT_STATE_WIFI:
		if 'get_status' in result and 'state' in result['get_status']:
			if result['get_status']['state'] in globals.DICT_STATE_WIFI[device]:
				result['get_status']['state'] = globals.DICT_STATE_WIFI[device][result['get_status']['state']]
		if 'status' in result and 'run_state' in result['status']:
			if result['status']['run_state'] in globals.DICT_STATE_WIFI[device]:
				result['status']['run_state'] = globals.DICT_STATE_WIFI[device][result['status']['run_state']]
	if device in globals.DICT_ERROR_WIFI:
		if 'get_status' in result and 'error_code' in result['get_status']:
			if result['get_status']['error_code'] in globals.DICT_ERROR_WIFI[device]:
				result['get_status']['error_code'] = globals.DICT_ERROR_WIFI[device][result['get_status']['error_code']]
	if device in ['vacuum','vacuum2']:
		if 'get_status' in result and 'clean_area' in result['get_status']:
			result['get_status']['clean_area'] = pretty_area(result['get_status']['clean_area'])
		if 'get_status' in result and 'clean_time' in result['get_status']:
			result['get_status']['clean_time'] = str(pretty_seconds(result['get_status']['clean_time']))+'s'
	if device in ['vacuumviomi']:
		if 'status' in result and 's_time' in result['status']:
			result['status']['s_time'] = str(pretty_seconds(result['status']['s_time'] * 60))+'s'
	return result
