#coding: utf-8
import sqlite3
import subprocess
import datetime
import os
import re


traffic_db = '/mnt/hdd/RoomManager/db/traffic.db'
device_db = '/mnt/hdd/RoomManager/db/device.db'

traffic_conn = sqlite3.connect(traffic_db)
traffic_cursor = traffic_conn.cursor()
device_conn = sqlite3.connect(device_db)
device_cursor = device_conn.cursor()


def make_tabel():
	# テーブルが存在しないときは作成する
	check_table = "SELECT * FROM sqlite_master WHERE type='table'"
	traffic_cursor.execute(check_table)
	
	if len(traffic_cursor.fetchall()) == 0:
		create_table = 'CREATE TABLE log (date varchar(12), device varchar(64), upload int(256), download int(256))'
		traffic_cursor.execute(create_table)
		print('テーブルを作成しました')
		print('-------------------------------------')


def get_log():
	traffic_cursor.execute('SELECT * FROM log')

	for traffic in traffic_cursor.fetchall():
		print('{}\t{}\t{}\t{}\t{}'.format(traffic[0], traffic[1], traffic[2], traffic[3], traffic[4]))


def get_device():
	device_cursor.execute('SELECT * FROM devices')
	return device_cursor.fetchall()


# 最新のトラフィックデータから対話ログを抽出
def get_conversations():
	data_dir = '/mnt/hdd/RoomManager/trafficData/'
	latest_file = sorted(os.listdir(data_dir), reverse=True)
	#latest_file = sorted(os.listdir(data_dir+latest_dir), reverse=True)
	if len(latest_file) == 0:
		exit(0)
	elif len(latest_file) == 1:
		latest_file = latest_file[0]
	else:
		latest_file = latest_file[1]
	date = '{}'.format(latest_file).replace('.pcap', '')
	

	cmd = 'tshark -r /mnt/hdd/RoomManager/trafficData/{} -z conv,tcp -q'.format(latest_file)
	datas = subprocess.Popen(cmd, stdout=subprocess.PIPE, shell=True).communicate()[0].split(b'\n')[5:-3]
	
	shaping_dic = {}
	for data in datas:
		temp = data.decode().split()
		#print(temp)
		fromIP = re.sub(':[0-9]+$', '', temp[0])
		toIP = re.sub(':[0-9]+$', '', temp[2])
		upload = int(temp[6])
		download = int(temp[4])

		for device in devices:
			if device[2] == fromIP:
				#shaping_data.append([date, device[0], fromIP, toIP, size])
				if device[0] in shaping_dic:
					shaping_dic[device[0]][0] += upload
					shaping_dic[device[0]][1] += download
				else:
					shaping_dic[device[0]] = [upload, download]
				break

	shaping_data = []
	for device in shaping_dic:
		shaping_data.append([date, device, shaping_dic[device][0], shaping_dic[device][1]])

	return shaping_data


def reflect_data():
	for data in datas:
		traffic_cursor.execute('INSERT INTO log (date, device, upload, download) VALUES (?, ?, ?, ?)', data)
		
	traffic_conn.commit()
	traffic_cursor.execute('SELECT * FROM log')
	for data in traffic_cursor.fetchall():
		print(data)


if __name__ == '__main__':
	#get_log()
	make_tabel()
	devices = get_device()
	datas = get_conversations()
	reflect_data()

	traffic_conn.close()
	device_conn.close()