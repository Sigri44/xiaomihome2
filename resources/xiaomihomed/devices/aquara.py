from past.builtins import basestring
import socket
import binascii
import struct
import json
import logging
import globals
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend

class XiaomiConnector:
	"""Connector for the Xiaomi Mi Hub and devices on multicast."""

	MULTICAST_PORT = 9898
	SERVER_PORT = 4321

	MULTICAST_ADDRESS = '224.0.0.50'
	SOCKET_BUFSIZE = 1024

	def __init__(self, data_callback=None, auto_discover=True):
		"""Initialize the connector."""
		self.data_callback = data_callback
		self.last_tokens = dict()
		self.socket = self._prepare_socket()

		self.nodes = dict()

	def _prepare_socket(self):
		sock = socket.socket(socket.AF_INET,  # Internet
							 socket.SOCK_DGRAM)  # UDP

		sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
		sock.bind(("0.0.0.0", self.MULTICAST_PORT))

		mreq = struct.pack("=4sl", socket.inet_aton(self.MULTICAST_ADDRESS),
							 socket.INADDR_ANY)
		sock.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_TTL, 32)
		sock.setsockopt(socket.IPPROTO_IP, socket.IP_MULTICAST_LOOP, 1)
		sock.setsockopt(socket.SOL_SOCKET, socket.SO_RCVBUF,
						self.SOCKET_BUFSIZE)
		sock.setsockopt(socket.IPPROTO_IP, socket.IP_ADD_MEMBERSHIP, mreq)

		return sock

	def check_incoming(self):
		"""Check incoming data."""
		data, addr = self.socket.recvfrom(self.SOCKET_BUFSIZE)
		try:
			payload = json.loads(data.decode("utf-8"))
			logging.debug(payload)
			self.handle_incoming_data(payload, addr)

		except Exception as e:
			raise
			logging.debug("Can't handle message %r (%r)" % (data, e))

	def handle_incoming_data(self, payload, addr):
		"""Handle an incoming payload, save related data if needed,
		and use the callback if there is one.
		"""
		if isinstance(payload.get('data', None), basestring):
			cmd = payload["cmd"]
			token =''
			if "token" in payload:
				token = payload["token"]
			if cmd in ["heartbeat", "report", "read_ack"]:
				if self.data_callback is not None and 'model' in payload:
					self.data_callback(payload["model"],
										 payload["sid"],
										 payload["cmd"],
										 payload["short_id"],
										 token,
										 json.loads(payload["data"]),'aquara', addr[0])

			if cmd == "read_ack" and payload["sid"] not in self.nodes:
				if 'model' in payload:
					self.nodes[payload["sid"]] = dict(model=payload["model"])

			if cmd == "heartbeat" and payload["sid"] not in self.nodes:
				self.request_sids(payload["sid"])
				self.nodes[payload["sid"]] = json.loads(payload["data"])
				self.nodes[payload["sid"]]["model"] = payload["model"]
				self.nodes[payload["sid"]]["sensors"] = []

			if cmd == "get_id_list_ack":
				device_sids = json.loads(payload["data"])
				self.nodes[payload["sid"]]["nodes"] = device_sids

				for sid in device_sids:
					self.request_current_status(sid)

		if "token" in payload:
			self.last_tokens[payload["sid"]] = payload['token']

	def request_sids(self, sid):
		"""Request System Ids from the hub."""
		self.send_command({"cmd": "get_id_list", sid: sid})

	def request_current_status(self, device_sid):
		"""Request (read) the current status of the given device sid."""
		self.send_command({"cmd": "read", "sid": device_sid})

	def send_command(self, data, addr = MULTICAST_ADDRESS, port=MULTICAST_PORT):
		"""Send a command to the UDP subject (all related will answer)."""
		if type(data) is dict:
			self.socket.sendto(json.dumps(data).encode("utf-8"), (addr, port))
		else:
			self.socket.sendto(data.encode("utf-8"), (addr, port))

	def get_nodes(self):
		"""Return the current discovered node configuration."""
		return self.nodes

def get_key(data):
				init_vector = bytes(bytearray.fromhex('17996d093d28ddb3ba695a2e6f58562e'))
				encryptor = Cipher(algorithms.AES(data['password'].encode()), modes.CBC(init_vector), backend=default_backend()).encryptor()
				ciphertext = encryptor.update(globals.CONNECTOR.last_tokens[data["sidG"]].encode()) + encryptor.finalize()
				return ''.join('{:02x}'.format(x) for x in ciphertext)

def execute_action(data):
	logging.debug("executing " + str(data))
	write_key = get_key(data)
	if str(data['switch']) == 'mid' :
		command = {str(data['switch']):data['request'],"vol" : int(data['vol']), "key":write_key}
	else:
		if isinstance(data['request'],int) :
			command = {str(data['switch']):int(data['request']),"key":write_key}
		else :
			if data['request'].isnumeric() :
				command = {str(data['switch']):int(data['request']),"key":write_key}
			else :
				command = {str(data['switch']):data['request'],"key":write_key}
	write_command = {"cmd":"write",\
					 "model":str(data['model']),\
					 "sid":str(data['sid']),\
					 "short_id":str(data['short_id']),\
					 "data":command}
	logging.debug("executing data " + str(write_command))
	globals.CONNECTOR.send_command(write_command, str(data['dest']), 9898)

def refresh(data):
	logging.debug("executing " + str(data))
	write_key = get_key(data)
	command = {"key":write_key}
	write_command = {"cmd":"read",\
					 "sid":str(data['sid']),}
	globals.CONNECTOR.send_command(write_command, str(data['dest']), 9898)
