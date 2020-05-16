#coding: utf-8
import sqlite3
import sys
import os


traffic_db = '/mnt/hdd/RoomManager/db/traffic.db'
device_db = '/mnt/hdd/RoomManager/db/device.db'

traffic_conn = sqlite3.connect(traffic_db)
traffic_cursor = traffic_conn.cursor()
device_conn = sqlite3.connect(device_db)
device_cursor = device_conn.cursor()


def get_log():
	# sql = 'SELECT date, SUM(upload) as upload,SUM(download) as download FROM log WHERE device=? AND date LIKE ?'
	# traffic_cursor.execute(sql, ["RasberryPi4", "{}%".format(20200514)])

	# sql = 'SELECT * FROM log'
	sql = 'SELECT * FROM log WHERE device=?'
	traffic_cursor.execute(sql, [args])

	for traffic in traffic_cursor.fetchall():
		print('{}\t{}\t{}'.format(traffic[0], traffic[1], traffic[2]))
		# print('{}\t{}'.format(traffic[0], traffic[1]))


if __name__ == '__main__':
	args = sys.argv[1]
	print(args)
	get_log()

	traffic_conn.close()
	device_conn.close()